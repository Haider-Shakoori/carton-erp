<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\WhatsAppHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Remittance;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;


class RemittanceController extends Controller
{
    public function index()
    {
        $accounts = Account::where('account_sub_category_id', 1)->get();
        $currencies = Currency::all();
        return view('admin.remittances.index', compact('accounts', 'currencies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',
            'bank_address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $userId = auth()->id();

            if ($request->filled('remittance_id')) {
                return $this->update($request);
            }

            // Create remittance
            $remittance = Remittance::create([
                'account_id' => $validated['account_id'],
                'currency_id' => $validated['currency_id'],
                'amount' => $validated['amount'],
                'bank_name' => $validated['bank_name'],
                'account_holder' => $validated['account_holder'],
                'bank_account_number' => $validated['bank_account_number'],
                'bank_address' => $validated['bank_address'],
                'phone_number' => $validated['phone_number'],
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
                'created_by' => $userId,
            ]);

            // Create transaction (debit)
            Transaction::create([
                'table_name' => 'remittances',
                'table_row_id' => $remittance->id,
                'type' => 'remittance',
                'account_id' => $validated['account_id'],
                'currency_id' => $validated['currency_id'],
                'amount' => $validated['amount'],
                'transaction_type' => 'debit',
                'is_cash' => false,
                'note' => 'Remittance to ' . $validated['bank_name'] . ' - ' . $validated['account_holder'],
                'status' => 'pending',
                'created_by' => $userId,
                'account_type' => 'current',
                'remittance_account_id' => null, // if tracking actual bank, use a dynamic ID
                'remittance_status' => 'pending',
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Remittance submitted and transaction recorded.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $account = Account::with(['balances.currency'])->findOrFail($id);

        return response()->json([
            'name' => $account->name,
            'balances' => $account->balances->map(fn($b) => [
                'currency_id' => $b->currency_id,
                'currency' => $b->currency->code,
                'amount' => number_format($b->amount, 2)
            ])
        ]);
    }

    public function edit($id)
    {
        $remittance = Remittance::findOrFail($id);
        return response()->json(['remittance' => $remittance]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'remittance_id' => 'required|exists:remittances,id',
            'account_id' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',
            'bank_address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $remittance = Remittance::findOrFail($validated['remittance_id']);
            $remittance->update($validated);

            // Optional: update related transaction
            Transaction::where('table_name', 'remittances')
                ->where('table_row_id', $remittance->id)
                ->update([
                    'account_id' => $validated['account_id'],
                    'currency_id' => $validated['currency_id'],
                    'amount' => $validated['amount'],
                    'note' => 'Remittance to ' . $validated['bank_name'] . ' - ' . $validated['account_holder'],
                ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Remittance updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function approve(Request $request, $id)
{
    // optional file validation (image ≤ 5MB)
    $request->validate([
        'receipt_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
    ]);

    DB::beginTransaction();

    try {
        $remittance = Remittance::findOrFail($id);

        // If a new receipt is uploaded, compress + save to public/receipts
        if ($request->hasFile('receipt_file')) {
            $file = $request->file('receipt_file');

            // delete old receipt if exists
            if (!empty($remittance->receipt_file) && file_exists(public_path($remittance->receipt_file))) {
                @unlink(public_path($remittance->receipt_file));
            }

            $relativePath = $this->compressAndSaveReceipt($file); // e.g. 'receipts/receipt_...jpg'
            if ($relativePath) {
                $remittance->receipt_file = $relativePath;
            }
        }

        // approve
        $remittance->status = 'processed';
        $remittance->save();

        Transaction::where('table_name', 'remittances')
            ->where('table_row_id', $remittance->id)
            ->update([
                'remittance_status' => 'completed',
                'status' => 'active',
            ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Remittance approved successfully.'
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Approval failed: ' . $e->getMessage()
        ], 500);
    }
}

    public function print($id)
    {
        $remittance = Remittance::with(['account', 'currency', 'creator'])->findOrFail($id);
        return view('admin.remittances.print', compact('remittance'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $remittance = Remittance::findOrFail($id);

            // Check if related transactions exist
            $hasTransactions = Transaction::where('table_name', 'remittances')
                ->where('table_row_id', $remittance->id)
                ->exists();

            if ($hasTransactions) {
                $transactions = Transaction::where('table_name', 'remittances')
                    ->where('table_row_id', $remittance->id)
                    ->get();

                foreach ($transactions as $transaction) {
                    $transaction->delete(); // Triggers the observer if you have one
                }
            }

            $remittance->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $hasTransactions
                    ? 'Remittance and related transactions deleted successfully.'
                    : 'Remittance deleted successfully. No related transactions found.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Remittance Delete Failed', [
                'remittance_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete remittance and its transactions.'
            ], 500);
        }
    }

    public function data(Request $request)
    {
        $query = Remittance::with(['account', 'currency'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END") // pending first
            ->orderByDesc('created_at'); // then latest

        if ($request->filled('customer_id')) {
            $query->where('account_id', $request->customer_id);
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('account', fn($r) => $r->account->name . ' (' . $r->account->code . ')' ?? '-')
            ->addColumn('currency', fn($r) => $r->currency->code ?? '-')
            ->addColumn('currency_symbol', fn($r) => $r->currency->symbol ?? '-')
            ->addColumn('amount', fn($r) => $r->amount)
            ->addColumn('actions', function ($r) {
                return view('admin.remittances.actions', compact('r'))->render();
            })
            ->rawColumns(['actions', 'account'])
            ->make(true);
    }

    public function summary()
    {
        $currencyId = Currency::query()->where('code', 'AFN')->value('id')
            ?? Currency::query()->where('is_default', true)->value('id')
            ?? Currency::query()->orderBy('id')->value('id');

        if (! $currencyId) {
            return response()->json([
                'safe_balance' => '0',
                'customers_balance' => '0',
                'available_balance' => '0',
                'pending' => '0',
                'currency' => '',
            ]);
        }

        $customers_credit = DB::table('accounts')
            ->join('transactions', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.account_sub_category_id', 1)
            ->where('transactions.currency_id', $currencyId)
            // ->where('transactions.status', 'active')
            ->whereRaw("LOWER(transactions.transaction_type) = 'credit'")
            ->sum('transactions.amount');

        $customers_debit = DB::table('accounts')
            ->join('transactions', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.account_sub_category_id', 1)
            ->where('transactions.currency_id', $currencyId)
            // ->where('transactions.status', 'active')
            ->whereRaw("LOWER(transactions.transaction_type) = 'debit'")
            ->sum('transactions.amount');

        $customers_balance = $customers_credit - $customers_debit;

        $safe_credit = DB::table('accounts')
            ->join('transactions', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.account_sub_category_id', 4)
            ->where('transactions.currency_id', $currencyId)
            // ->where('transactions.status', 'active')
            ->whereRaw("LOWER(transactions.transaction_type) = 'credit'")
            ->sum('transactions.amount');

        $safe_debit = DB::table('accounts')
            ->join('transactions', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.account_sub_category_id', 4)
            ->where('transactions.currency_id', $currencyId)
            // ->where('transactions.status', 'active')
            ->whereRaw("LOWER(transactions.transaction_type) = 'debit'")
            ->sum('transactions.amount');

        $safe_balance = $safe_credit - $safe_debit;
        $available_balance = $safe_balance + $customers_balance;
        $pending = DB::table('remittances')
            ->where('status', 'pending')
            ->where('currency_id', $currencyId)
            ->sum('amount');

        $currencyCode = DB::table('currencies')->where('id', $currencyId)->value('code');

        return response()->json([
            'safe_balance' => number_format($safe_balance, 0),
            'customers_balance' => number_format($customers_balance, 0),
            'available_balance' => number_format($available_balance, 0),
            'pending' => number_format($pending, 0),
            'currency' => $currencyCode ?? '',
        ]);
    }

    public function sendWhatsApp($id)
    {
        $remittance = Remittance::with('currency')->findOrFail($id);
        $account = Account::findOrFail($remittance->account_id);
        $setting = Setting::first();

        $currency = $remittance->currency;
        $isCredit = false; // Since remittance is payment from customer, it's DEBIT
        $transactionTypeEn = $isCredit ? '🟢 Credit (Received)' : '🔴 Debit (Paid)';

        // Get account balance for this currency
        $balanceRow = AccountBalance::where('account_id', $account->id)
            ->where('currency_id', $currency->id)
            ->first();

        $balance = $balanceRow ? number_format($balanceRow->balance, 2) : '0.00';
        $amount = number_format($remittance->amount, 2);

        // Message formatting
        $whatsapp_message = "*" . $setting->company_name . "*\n\n" .
            "🔖 *Code:* {$account->code}\n" .
            "👤 *Name:* {$account->name}\n" .
            "📄 *Transaction Type:* {$transactionTypeEn}\n" .
            "💰 *Amount:* -" . "*{$amount} {$currency->symbol}*\n" .
            "📅 *Date:* " . now()->format('Y-m-d H:i') . "\n" .
            "📊 *Balance:* *{$balance} {$currency->symbol}*\n\n" .
            "🙏 Thank you for choosing us!\n" .
            "For more information, feel free to contact us:\n" .
            "📧 {$setting->email}\n" .
            "📞 {$setting->contact}\n\n"
            . "📌 مشتری گرامی، لطفاً از صحت بودن بیلانس حساب خود اطمینان حاصل نموده تایید نمایید.";

        $owner2 = '+93700209514';
        // Send only if number is valid international format
        if ($account->contact && (Str::startsWith($account->contact, '+') || Str::startsWith($account->contact, '00'))) {
            SendWhatsAppMessage::dispatch((string) $account->contact, (string) $whatsapp_message)
                ->delay(6);
        }

        SendWhatsAppMessage::dispatch((string) $owner2, (string) $whatsapp_message)
            ->delay(15);

        return redirect()->back()->with('success', 'WhatsApp message sent successfully.');
    }

    /**
 * Compress an uploaded image to JPG and save in public/receipts.
 * Returns relative path like 'receipts/receipt_XXXXXXXX.jpg' or null on failure.
 */
private function compressAndSaveReceipt(\Illuminate\Http\UploadedFile $file, int $maxW = 1600, int $maxH = 1600, int $quality = 75): ?string
{
    try {
        // Ensure target dir exists
        $targetDir = public_path('receipts');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        // Load image resource via GD
        $mime = $file->getMimeType();
        $src = null;

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $src = imagecreatefromjpeg($file->getRealPath());
                break;
            case 'image/png':
                $src = imagecreatefrompng($file->getRealPath());
                break;
            case 'image/webp':
                // Requires GD with WebP support; if not available, fallback by copying
                if (function_exists('imagecreatefromwebp')) {
                    $src = imagecreatefromwebp($file->getRealPath());
                }
                break;
        }

        // If we failed to create resource, fallback to a plain move without compression
        if (!$src) {
            $name = 'receipt_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
            $file->move($targetDir, $name);
            return 'receipts/' . $name;
        }

        // Handle EXIF orientation for JPEGs (avoid warnings on non-jpeg)
        if (in_array($mime, ['image/jpeg', 'image/jpg']) && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
            if (!empty($exif['Orientation'])) {
                $orientation = (int) $exif['Orientation'];
                if ($orientation === 3) {
                    $src = imagerotate($src, 180, 0);
                } elseif ($orientation === 6) {
                    $src = imagerotate($src, -90, 0);
                } elseif ($orientation === 8) {
                    $src = imagerotate($src, 90, 0);
                }
            }
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Calculate new size within bounds
        $scale = min($maxW / $origW, $maxH / $origH, 1);
        $newW = (int) floor($origW * $scale);
        $newH = (int) floor($origH * $scale);

        $dst = imagecreatetruecolor($newW, $newH);

        // For PNG/WebP with transparency -> white background (since we export JPG)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        // Resize
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        // Save as JPG (compressed)
        $name = 'receipt_' . time() . '_' . mt_rand(1000, 9999) . '.jpg';
        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $name;

        imagejpeg($dst, $fullPath, $quality);

        imagedestroy($src);
        imagedestroy($dst);

        return 'receipts/' . $name;
    } catch (\Throwable $e) {
        Log::warning('Receipt compression failed', ['err' => $e->getMessage()]);
        return null;
    }
}

}
