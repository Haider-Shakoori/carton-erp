<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\WhatsAppHelper;
use App\Models\ExchangeRate;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Yajra\DataTables\Facades\DataTables;
use Morilog\Jalali\Jalalian;
use App\Jobs\SendWhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class ExchangeRateController extends Controller
{
    public function index()
    {
        $currencies = Currency::all();
        $customers = Account::where('is_active', true)->where('account_sub_category_id', 1)->get();
        return view('admin.exchange_rates.index', compact('currencies', 'customers'));
    }

    public function data(Request $request)
    {
        $query = ExchangeRate::with(['baseCurrency', 'targetCurrency'])
            ->orderByDesc('created_at');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('base_currency', fn($r) => $r->baseCurrency->code ?? '-')
            ->addColumn('target_currency', fn($r) => $r->targetCurrency->code ?? '-')
            ->addColumn('actions', fn($r) => '<div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-primary rounded-pill btn-edit" title="Edit" data-id="' . $r->id . '">
                    <i class="bi bi-pencil-square"></i> Edit
                </button>
                <button class="btn btn-outline-danger rounded-pill btn-delete" title="Delete" data-id="' . $r->id . '">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function getLatestRate()
    {
        // Option 1: Fetch from currency table
        $afn = Currency::where('code', 'AFN')->first();
        $usd = Currency::where('code', 'USD')->first();

        if ($afn && $usd && $usd->exchange_rate > 0) {
            $rate = $afn->exchange_rate / $usd->exchange_rate;
        } else {
            $rate = 85; // Default
        }

        // Option 2: Fetch from external API (if configured)
        // $rate = $this->fetchFromAPI();

        return response()->json([
            'success' => true,
            'rate' => $rate,
            'timestamp' => now()->toDateTimeString(),
            'source' => 'Currency Table'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'base_currency_id' => 'required|exists:currencies,id',
            'target_currency_id' => 'required|exists:currencies,id',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gt:min_amount',
            'rate' => 'required|numeric|min:0',
            'cost_rate' => 'required|numeric|min:0',
        ]);

        ExchangeRate::create($data);

        return response()->json(['message' => 'Exchange rate added successfully.']);
    }

    public function show($id)
    {
        $exchangeRate = ExchangeRate::with(['baseCurrency', 'targetCurrency'])->findOrFail($id);
        return response()->json($exchangeRate);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'base_currency_id'   => 'required|exists:currencies,id|different:target_currency_id',
            'target_currency_id' => 'required|exists:currencies,id',
            'min_amount'         => 'required|numeric|min:0',
            'max_amount'         => 'required|numeric|gte:min_amount',
            'rate'               => 'required|numeric|min:0',
            'cost_rate'          => 'required|numeric|min:0',
        ]);

        $exchangeRate = ExchangeRate::findOrFail($id);
        $exchangeRate->update($request->only([
            'base_currency_id',
            'target_currency_id',
            'min_amount',
            'max_amount',
            'rate',
            'cost_rate',
        ]));

        return response()->json(['message' => 'Exchange rate updated successfully.']);
    }

    public function fetch(Request $request)
    {
        $fromId = $request->from_currency_id;
        $toId = $request->to_currency_id;
        $amount = $request->amount;

        $rate = ExchangeRate::where('base_currency_id', $fromId)
            ->where('target_currency_id', $toId)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if (!$rate) {
            return response()->json(['error' => 'Rate not found'], 404);
        }

        return response()->json([
            'rate' => $rate->rate,
            'exchanged_amount' => number_format($amount * $rate->rate, 2)
        ]);
    }

    public function destroy($id)
    {
        $exchangeRate = ExchangeRate::findOrFail($id);
        $exchangeRate->delete();

        return response()->json(['message' => 'Exchange rate deleted successfully.']);
    }


    public function sendRateMessage(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:accounts,id',
            'rates' => 'required|array|size:4',
            'rates.*.rate' => 'required|numeric',
            'rates.*.amount' => 'required|numeric',
        ]);

        $customer = Account::findOrFail($request->customer_id);
        $rates = $request->rates;

        // Generate message
        $today = new \DateTime();
        $dayName = $today->format('l');
        $date = $today->format('Y/m/d');

        $lines = "{$dayName}       *بسم‌تعالی*    {$date}\n\n";
        $lines .= "❇️ با عرض سلام و احترام خدمت شما مشتری عزیز‌، امید که صحت‌ و سلامت باشید\n\n";
        $lines .= "‌💱نرخ هایی حواله یوان *﴿ضمانتی﴾*🎯\n ┄─┅─═✾★💎 ★✾═─┅─\n";

        foreach ($rates as $index => $r) {
            $currencyLabel = ($index === 4) ? "AliPay-WeChatPay" : "یوان";
            $lines .= "\n♻️ {$currencyLabel} *﴿ضمانتی﴾*\n🔮﴿ " . number_format($r['rate'], 4) . " ﴾ 🔜 مبالغ بالای (" . number_format($r['amount']) . ")\n";
        }

        // ✅ Step 1: Get last available timestamp of any WhatsApp job
        $now = now()->timestamp;

        $lastTimestamp = DB::table('jobs')
            ->where('payload', 'like', '%SendWhatsAppMessage%')
            ->orderByDesc('available_at')
            ->value('available_at');

        $startTime = $lastTimestamp && $lastTimestamp > $now ? $lastTimestamp : $now;

        // ✅ Step 2: Queue the new job after last scheduled
        $delayGap = 10; // Delay gap in seconds (adjust as needed)
        $sendAt = $startTime + $delayGap;

        SendWhatsAppMessage::dispatch($customer->contact, $lines)
            ->delay(Carbon::createFromTimestamp($sendAt));

        return response()->json(['message' => 'WhatsApp message queued successfully.']);
    }

}

