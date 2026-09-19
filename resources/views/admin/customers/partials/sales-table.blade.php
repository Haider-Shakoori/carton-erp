{{-- resources/views/admin/customers/partials/sales-table.blade.php --}}
@php
    $customer = $customer ?? null;
    $filters = $filters ?? [];
@endphp

<div class="filter-bar">
    <form id="salesFilterForm" action="{{ route('admin.customers.sales-data', $customer->id ?? 0) }}" data-tab="sales"
        method="GET" class="d-flex flex-wrap gap-2 w-100">
        <div class="filter-group">
            <i class="bi bi-filter-circle filter-icon"></i>
            <select name="status" class="form-select form-select-sm">
                <option value="">{{ __('ui.all_status') }}</option>
                <option value="draft" {{ ($filters['status'] ?? '') == 'draft' ? 'selected' : '' }}>{{ __('ui.draft') }}</option>
                <option value="confirmed" {{ ($filters['status'] ?? '') == 'confirmed' ? 'selected' : '' }}>{{ __('ui.confirmed') }}
                </option>
                <option value="shipped" {{ ($filters['status'] ?? '') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="delivered" {{ ($filters['status'] ?? '') == 'delivered' ? 'selected' : '' }}>Delivered
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
        <button type="button" class="btn-reset btn-sm btn-reset-filters" data-tab="sales"
            data-url="{{ route('admin.customers.sales-data', $customer->id ?? 0) }}">
            <i class="bi bi-arrow-counterclockwise"></i> {{ __('ui.reset') }}
        </button>
    </form>
</div>

<div class="table-responsive-custom">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Sale No</th>
                <th>{{ __('ui.date') }}</th>
                <th>{{ __('ui.status') }}</th>
                <th>{{ __('ui.currency') }}</th>
                <th class="text-end">{{ __('ui.total') }}</th>
                <th class="text-end">{{ __('ui.items') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>
                        <a href="{{ route('admin.sales.show', $sale->id) }}" class="text-decoration-none">
                            {{ $sale->sale_no }}
                        </a>
                    </td>
                    <td>{{ $sale->sale_date ? date('Y-m-d', strtotime($sale->sale_date)) : $sale->created_at->format('Y-m-d') }}
                    </td>
                    <td>
                        <span class="status-badge {{ $sale->status }}">
                            {{ ucfirst($sale->status) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">
                            {{ $sale->currency->code ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="text-end fw-bold">
                        {{ number_format($sale->grand_total ?? 0, 2) }}
                    </td>
                    <td class="text-end">{{ $sale->items->count() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-cart-x fs-3 d-block mb-2"></i>
                        No sales found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($sales->hasPages())
    <div class="pagination-wrap">
        <span class="info-text">
            Showing {{ $sales->firstItem() }} – {{ $sales->lastItem() }}
            of {{ $sales->total() }} sales
        </span>
        {{ $sales->links('pagination::bootstrap-5') }}
    </div>
@endif
