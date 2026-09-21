<?php
// app/Models/Account.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'account_type',
        'contact',
        'address',
        'company',
        'email',
        'whatsapp',
        'tax_number',
        'currency_code',
        'is_active',
        'bi_icon',
        'bi_icon_color',
        'profile_bg',
        'is_safe',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_safe' => 'boolean',
    ];

    // Account type constants
    const TYPE_CUSTOMER = 'customer';
    const TYPE_SUPPLIER = 'supplier';
    const TYPE_AGENT = 'agent';
    const TYPE_OWNER = 'owner';
    const TYPE_EXPENSE = 'expense';
    const TYPE_SARAF = 'saraf';
    const TYPE_CASH = 'cash';
    const TYPE_BANK = 'bank';
    const TYPE_REVENUE = 'revenue';

    const TYPES = [
        self::TYPE_CUSTOMER => 'Customer',
        self::TYPE_SUPPLIER => 'Supplier',
        self::TYPE_AGENT => 'Agent',
        self::TYPE_OWNER => 'Owner',
        self::TYPE_EXPENSE => 'Expense',
        self::TYPE_SARAF => 'Saraf',
        self::TYPE_CASH => 'Cash',
        self::TYPE_BANK => 'Bank',
        self::TYPE_REVENUE => 'Revenue',
    ];

    // Icons for each account type
    const TYPE_ICONS = [
        'customer' => ['icon' => 'bi-people', 'color' => 'primary'],
        'supplier' => ['icon' => 'bi-truck', 'color' => 'success'],
        'agent' => ['icon' => 'bi-person-badge', 'color' => 'warning'],
        'owner' => ['icon' => 'bi-building', 'color' => 'danger'],
        'expense' => ['icon' => 'bi-receipt', 'color' => 'danger'],
        'saraf' => ['icon' => 'bi-currency-exchange', 'color' => 'info'],
        'cash' => ['icon' => 'bi-cash', 'color' => 'success'],
        'bank' => ['icon' => 'bi-bank', 'color' => 'primary'],
        'revenue' => ['icon' => 'bi-graph-up-arrow', 'color' => 'success'],
    ];

    // ─── RELATIONSHIPS ───

    /**
     * Get all transactions for this account.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    /**
     * Get all sales for this account (when account is customer).
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    /**
     * Get all sale returns for this account (when account is customer).
     */
    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class, 'customer_id');
    }

    /**
     * Customer-specific carton master specifications imported from client data.
     */
    public function cartonSpecifications()
    {
        return $this->hasMany(FinishedGoodSpecification::class, 'customer_id');
    }

    /**
     * Get all exchanges for this account.
     */
    public function exchanges()
    {
        return $this->hasMany(Exchange::class, 'account_id');
    }

    /**
     * Get all remittances for this account.
     */
    public function remittances()
    {
        return $this->hasMany(Remittance::class, 'account_id');
    }

    /**
     * Get all purchase orders for this account (when account is supplier).
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    /**
     * Get all expenses for this account.
     */
    public function expenses()
    {
        return $this->hasMany(Expenses::class, 'account_id');
    }

    /**
     * Get the user who created this account.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── SCOPES ───

    /**
     * Scope a query to only include accounts of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope a query to only include customers.
     */
    public function scopeCustomers($query)
    {
        return $query->where('account_type', self::TYPE_CUSTOMER);
    }

    /**
     * Scope a query to only include suppliers.
     */
    public function scopeSuppliers($query)
    {
        return $query->where('account_type', self::TYPE_SUPPLIER);
    }

    /**
     * Scope a query to only include agents.
     */
    public function scopeAgents($query)
    {
        return $query->where('account_type', self::TYPE_AGENT);
    }

    /**
     * Scope a query to only include sarafs.
     */
    public function scopeSarafs($query)
    {
        return $query->where('account_type', self::TYPE_SARAF);
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── ACCESSORS ───

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute()
    {
        return self::TYPES[$this->account_type] ?? $this->account_type;
    }

    /**
     * Get the icon for the account type.
     */
    public function getIconAttribute()
    {
        return self::TYPE_ICONS[$this->account_type]['icon'] ?? 'bi-person';
    }

    /**
     * Get the icon color for the account type.
     */
    public function getIconColorAttribute()
    {
        return self::TYPE_ICONS[$this->account_type]['color'] ?? 'secondary';
    }

    /**
     * Get the display name with code.
     */
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->code . ')';
    }

    /**
     * Get the avatar initials.
     */
    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get total credit amount for this account.
     */
    public function getTotalCreditAttribute()
    {
        return (float) $this->transactions()
            ->where('status', 'active')
            ->where('transaction_type', 'credit')
            ->sum('amount') ?? 0;
    }

    /**
     * Get total debit amount for this account.
     */
    public function getTotalDebitAttribute()
    {
        return (float) $this->transactions()
            ->where('status', 'active')
            ->where('transaction_type', 'debit')
            ->sum('amount') ?? 0;
    }

    /**
     * Get net balance for this account.
     */
    public function getNetBalanceAttribute()
    {
        return $this->total_credit - $this->total_debit;
    }

    // ─── HELPER METHODS ───

    /**
     * Get the account balance for a specific currency.
     */
    public function getBalanceForCurrency($currencyId)
    {
        return $this->transactions()
            ->where('currency_id', $currencyId)
            ->where('status', 'active')
            ->selectRaw("
                COALESCE(
                    SUM(CASE 
                        WHEN transaction_type = 'credit' THEN amount 
                        ELSE -amount 
                    END),
                    0
                ) as balance
            ")
            ->value('balance') ?? 0;
    }

    /**
     * Get all balances grouped by currency.
     */
    public function getBalancesByCurrency()
    {
        return $this->transactions()
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->where('transactions.status', 'active')
            ->select(
                'currencies.id as currency_id',
                'currencies.code as currency_code',
                'currencies.symbol as currency_symbol',
                'currencies.name as currency_name',
                \DB::raw("
                    COALESCE(
                        SUM(CASE 
                            WHEN transactions.transaction_type = 'credit' THEN transactions.amount 
                            ELSE -transactions.amount 
                        END),
                        0
                    ) as balance
                ")
            )
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol', 'currencies.name')
            ->orderBy('currencies.code')
            ->get()
            ->map(function ($item) {
                $item->balance = (float) $item->balance;
                return $item;
            });
    }

    /**
     * Check if account has transactions.
     */
    public function hasTransactions()
    {
        return $this->transactions()->exists();
    }

    /**
     * Check if account can be deleted.
     */
    public function canBeDeleted()
    {
        return !$this->hasTransactions();
    }

    /**
     * Generate account code.
     */
    public static function generateCode($type)
    {
        $prefixes = [
            'customer' => 'CUS',
            'supplier' => 'SUP',
            'agent' => 'AGT',
            'owner' => 'OWN',
            'expense' => 'EXP',
            'saraf' => 'SAR',
            'cash' => 'CSH',
            'bank' => 'BNK',
            'revenue' => 'REV',
        ];

        $prefix = $prefixes[$type] ?? 'ACC';
        $last = self::where('account_type', $type)->orderBy('id', 'desc')->first();
        $number = $last ? intval(substr($last->code, -4)) + 1 : 1;
        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
