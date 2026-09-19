{{-- resources/views/admin/customers/partials/returns-table.blade.php --}}
<div class="table-responsive-custom">
    <table class="table-custom">
        <thead>
            <tr>
                <th>Return No</th>
                <th>{{ __('ui.date') }}</th>
                <th>{{ __('ui.status') }}</th>
                <th>{{ __('ui.currency') }}</th>
                <th class="text-end">{{ __('ui.total') }}</th>
                <th>{{ __('ui.reason') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($returns as $return)
                <tr>
                    <td>
                        <a href="{{ route('admin.sale-returns.show', $return->id) }}" class="text-decoration-none">
                            {{ $return->return_no ?? '#' . $return->id }}
                        </a>
                    </td>
                    <td>{{ $return->return_date ? date('Y-m-d', strtotime($return->return_date)) : $return->created_at->format('Y-m-d') }}
                    </td>
                    <td>
                        <span class="status-badge {{ $return->status ?? 'draft' }}">
                            {{ ucfirst($return->status ?? 'Draft') }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">
                            {{ $return->currency->code ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="text-end fw-bold">
                        {{ number_format($return->grand_total ?? 0, 2) }}
                    </td>
                    <td>{{ Str::limit($return->reason ?? 'N/A', 30) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-arrow-return-left fs-3 d-block mb-2"></i>
                        No returns found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
