@forelse($transactions as $index => $transaction)
    <tr>
        <td>{{ $transactions->firstItem() + $index }}</td>
        <td>
            <div style="font-size: 11px; line-height: 1.2;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <i class="bi bi-calendar3"></i> {{ $transaction->created_at->format('Y-m-d') }}
                </div>
                <div style="display: flex; align-items: center; gap: 4px; color: #6c757d;">
                    <i class="bi bi-clock"></i> {{ $transaction->created_at->format('h:i A') }}
                </div>
            </div>
        </td>
        <td>
            <div style="display: flex; align-items: center; gap: 8px; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div
                        style="width: 28px; height: 28px; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span
                        style="font-weight: 600; color: var(--gray-800);">{{ $transaction->account->name ?? '-' }}</span>
                </div>
                <span
                    style="font-size: 11px; color: var(--gray-400); font-weight: 600;">[{{ $transaction->account->code ?? '-' }}]</span>
            </div>
        </td>
        <td>{{ $transaction->description ?? 'N/A' }}</td>
        <td>
            <div style="display: flex; align-items: center; gap: 6px;">
                @php
                    $isCredit = strtolower($transaction->transaction_type) === 'credit';
                    $badgeColor = $isCredit ? 'bg-primary' : 'bg-danger';
                    $sign = $isCredit ? '+' : '-';
                    $amountColor = $isCredit ? 'text-primary' : 'text-danger';
                @endphp
                <span class="badge {{ $badgeColor }} text-white"
                    style="font-size: 10px; padding: 2px 6px;">{{ $sign }}</span>
                <span class="{{ $amountColor }}"
                    style="font-weight: 700;">{{ number_format($transaction->amount, 2) }}</span>
                <span
                    style="font-size: 11px; color: var(--gray-400);">{{ $transaction->currency->code ?? 'USD' }}</span>
            </div>
        </td>
        <td class="text-center">
            <div style="display: flex; gap: 4px; justify-content: center;">
                @can('update transactions')
                    <button class="btn btn-sm btn-outline-secondary btn-edit"
                        style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"
                        data-id="{{ $transaction->id }}" data-account-id="{{ $transaction->account_id }}"
                        data-currency-id="{{ $transaction->currency_id }}"
                        data-transaction-type="{{ $transaction->transaction_type }}"
                        data-amount="{{ $transaction->amount }}" data-description="{{ e($transaction->description) }}">
                        <i class="bi bi-pencil-square" style="color: black;"></i>
                    </button>
                @endcan
                @can('delete transactions')
                    <button class="btn btn-sm btn-outline-secondary btn-delete"
                        style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;"
                        data-delete-id="{{ $transaction->id }}">
                        <i class="bi bi-trash" style="color: black;"></i>
                    </button>
                @endcan
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center py-4">
            <div class="empty-state">
                <i class="bi bi-inboxes"
                    style="font-size: 2rem; color: var(--gray-300); display: block; margin-bottom: 0.5rem;"></i>
                <p style="font-weight: 600; color: var(--gray-700); margin: 0;">{{ __('ui.no_transactions_found') }}</p>
                <p class="sub-text" style="color: var(--gray-400); font-size: 0.8125rem; margin-top: 0.25rem;">
                    Try adjusting your filters or create a new transaction.
                </p>
            </div>
        </td>
    </tr>
@endforelse
