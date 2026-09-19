{{-- resources/views/admin/customers/partials/transactions-table.blade.php --}}
@php
    $customer = $customer ?? null;
    $filters = $filters ?? [];
    $currencies = $currencies ?? [];
@endphp

<div class="filter-bar">
    <form id="transactionFilterForm" action="{{ route('admin.customers.transaction-data', $customer->id ?? 0) }}"
        data-tab="transactions" method="GET" class="d-flex flex-wrap gap-2 w-100">
        <div class="filter-group">
            <i class="bi bi-currency-dollar filter-icon"></i>
            <select name="currency_id" class="form-select form-select-sm">
                <option value="">{{ __('ui.all_currencies') }}</option>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                        {{ ($filters['currency_id'] ?? '') == $currency->id ? 'selected' : '' }}>
                        {{ $currency->code }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <i class="bi bi-arrow-left-right filter-icon"></i>
            <select name="transaction_type" class="form-select form-select-sm">
                <option value="">{{ __('ui.all_types') }}</option>
                <option value="credit" {{ ($filters['transaction_type'] ?? '') == 'credit' ? 'selected' : '' }}>{{ __('ui.credit') }}
                </option>
                <option value="debit" {{ ($filters['transaction_type'] ?? '') == 'debit' ? 'selected' : '' }}>Debit
                </option>
            </select>
        </div>

        <div class="filter-group">
            <i class="bi bi-calendar3 filter-icon"></i>
            <input type="text" name="date_range" class="form-control form-control-sm date-range-picker"
                placeholder="{{ __('ui.select_date_range') }}" value="{{ $filters['date_range'] ?? '' }}">
        </div>

        <button type="submit" class="btn-apply btn-sm">
            <i class="bi bi-search"></i> Apply
        </button>
        <button type="button" class="btn-reset btn-sm btn-reset-filters" data-tab="transactions"
            data-url="{{ route('admin.customers.transaction-data', $customer->id ?? 0) }}">
            <i class="bi bi-arrow-counterclockwise"></i> {{ __('ui.reset') }}
        </button>
    </form>
</div>

<div class="table-responsive-custom">
    <table class="table-custom">
        <thead>
            <tr>
                <th>{{ __('ui.date') }}</th>
                <th>{{ __('ui.type') }}</th>
                <th>{{ __('ui.description') }}</th>
                <th>{{ __('ui.currency') }}</th>
                <th class="text-end">{{ __('ui.amount') }}</th>
                <th class="text-end">{{ __('ui.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                @php
                    $isCredit = $transaction->transaction_type === 'credit';
                    $amountClass = $isCredit ? 'amount-positive' : 'amount-negative';
                    $sign = $isCredit ? '+' : '-';
                @endphp
                <tr>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="type-badge {{ $transaction->transaction_type }}">
                            {{ ucfirst($transaction->transaction_type) }}
                        </span>
                    </td>
                    <td>{{ $transaction->description ?? 'N/A' }}</td>
                    <td>
                        <span class="badge bg-light text-dark">
                            {{ $transaction->currency->code ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="text-end {{ $amountClass }}">
                        {{ $sign }} {{ number_format($transaction->amount, 2) }}
                    </td>
                    <td class="text-end">
                        {{ number_format($transaction->running_balance ?? 0, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        {{ __('ui.no_transactions_found') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($transactions->hasPages())
    <div class="pagination-wrap">
        <span class="info-text">
            Showing {{ $transactions->firstItem() }} – {{ $transactions->lastItem() }}
            of {{ $transactions->total() }} transactions
        </span>
        {{ $transactions->links('pagination::bootstrap-5') }}
    </div>
@endif
