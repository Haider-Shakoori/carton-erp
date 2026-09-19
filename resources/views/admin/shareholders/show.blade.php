@extends('layouts.admin.base')

@section('title', 'Shareholder Profile')

@section('css')
    <style>
        .profile-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--gray-200);
        }
        .profile-avatar.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto;
        }
        .stat-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 1rem;
            text-align: center;
        }
        .stat-card .number {
            font-size: 1.5rem;
            font-weight: 800;
        }
        .stat-card .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .badge-status {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-status.active { background: var(--success-bg); color: var(--success); }
        .badge-status.inactive { background: var(--danger-bg); color: var(--danger); }
        .distribution-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .distribution-item:last-child {
            border-bottom: none;
        }
        .withdrawal-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .withdrawal-item:last-child {
            border-bottom: none;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-person-badge me-2"></i> {{ __('ui.shareholder') }} <span class="accent">{{ __('ui.profile') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> {{ $shareholder->code }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.shareholders.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('admin.shareholders.edit', $shareholder) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                    </a>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                        <i class="bi bi-cash me-1"></i> Withdraw
                    </button>
                </div>
            </div>
        </div>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    @if ($shareholder->profile_image)
                        <img src="{{ Storage::url($shareholder->profile_image) }}" class="profile-avatar">
                    @else
                        <div class="profile-avatar placeholder">
                            {{ strtoupper(substr($shareholder->name, 0, 2)) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <h2 class="fw-bold">{{ $shareholder->name }}</h2>
                    <p class="text-muted">{{ $shareholder->code }}</p>
                    <div class="d-flex gap-2 flex-wrap">
                    <span class="badge-status {{ $shareholder->is_active ? 'active' : 'inactive' }}">
                        {{ $shareholder->is_active ? 'Active' : 'Inactive' }}
                    </span>
                        <span class="badge bg-primary">{{ $shareholder->share_percentage }}% Share</span>
                        @if($shareholder->capital_contribution)
                            <span class="badge bg-info">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($shareholder->capital_contribution, 2) }} Capital</span>
                        @endif
                    </div>
                    <div class="mt-2">
                        <i class="bi bi-envelope me-1"></i> {{ $shareholder->email ?? 'No email' }}
                        @if($shareholder->phone)
                            <span class="ms-3"><i class="bi bi-phone me-1"></i> {{ $shareholder->phone }}</span>
                        @endif
                    </div>
                    @if($shareholder->address)
                        <div class="mt-1 text-muted small">
                            <i class="bi bi-geo-alt me-1"></i> {{ $shareholder->address }}
                        </div>
                    @endif
                    @if($shareholder->joining_date)
                        <div class="mt-1 text-muted small">
                            <i class="bi bi-calendar me-1"></i> Joined: {{ $shareholder->joining_date->format('M d, Y') }}
                        </div>
                    @endif
                </div>
                <div class="col-md-3 text-center">
                    <div class="stat-card">
                        <div class="number {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($balance, 2) }}
                        </div>
                        <div class="label">{{ __('ui.current_balance') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="number text-primary">{{ $distributionCount }}</div>
                    <div class="label">Total Distributions</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="number text-success">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($totalDistributed, 2) }}</div>
                    <div class="label">{{ __('ui.total_distributed') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="number text-warning">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($totalWithdrawn, 2) }}</div>
                    <div class="label">{{ __('ui.total_withdrawn') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="number text-info">{{ $withdrawals->where('status', 'pending')->count() }}</div>
                    <div class="label">Pending Withdrawals</div>
                </div>
            </div>
        </div>

        <!-- Distribution History -->
        <div class="card-modern mb-4">
            <div class="card-header">
                <i class="bi bi-clock-history me-1"></i> Distribution History
                <a href="#" class="btn btn-sm btn-outline-primary">{{ __('ui.view_all') }}</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                        <tr>
                            <th>{{ __('ui.distribution_number') }}</th>
                            <th>{{ __('ui.period') }}</th>
                            <th>{{ __('ui.share_percent') }}</th>
                            <th class="text-end">{{ __('ui.amount') }}</th>
                            <th>{{ __('ui.date') }}</th>
                            <th>{{ __('ui.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($distributions as $item)
                            <tr>
                                <td>{{ $item->distribution->distribution_number }}</td>
                                <td>{{ $item->distribution->period_label }}</td>
                                <td>{{ $item->share_percentage }}%</td>
                                <td class="text-end">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($item->amount, 2) }}</td>
                                <td>{{ $item->payment_date ? $item->payment_date->format('M d, Y') : '-' }}</td>
                                <td>
                                <span class="badge bg-{{ $item->status_badge }}">
                                    {{ $item->status_label }}
                                </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No distributions found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Withdrawal History -->
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-cash me-1"></i> Withdrawal History
                <a href="#" class="btn btn-sm btn-outline-primary">{{ __('ui.view_all') }}</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                        <tr>
                            <th>Withdrawal #</th>
                            <th class="text-end">{{ __('ui.amount') }}</th>
                            <th>{{ __('ui.date') }}</th>
                            <th>{{ __('ui.status') }}</th>
                            <th>{{ __('ui.reason') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($withdrawals as $withdrawal)
                            <tr>
                                <td>{{ $withdrawal->withdrawal_number }}</td>
                                <td class="text-end">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($withdrawal->amount, 2) }}</td>
                                <td>{{ $withdrawal->withdrawal_date->format('M d, Y') }}</td>
                                <td>
                                <span class="badge bg-{{ $withdrawal->status_badge }}">
                                    {{ $withdrawal->status_label }}
                                </span>
                                </td>
                                <td>{{ $withdrawal->reason ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No withdrawals found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Modal -->
    <div class="modal fade" id="withdrawalModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cash me-2"></i> Request Withdrawal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.shareholders.withdraw', $shareholder) }}" method="POST" id="withdrawalForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                <input type="number" class="form-control" name="amount" id="withdrawalAmount"
                                       step="0.01" min="1" max="{{ $balance }}" required>
                            </div>
                            <small class="text-muted">Available balance: {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($balance, 2) }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.reason') }}</label>
                            <textarea class="form-control" name="reason" rows="2" placeholder="{{ __('ui.withdrawal_reason') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="withdrawalSubmitBtn">
                            <i class="bi bi-check2 me-1"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Withdrawal form submit
            $('#withdrawalForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#withdrawalSubmitBtn');
                const amount = $('#withdrawalAmount').val();
                const maxBalance = {{ $balance }};

                if (parseFloat(amount) > maxBalance) {
                    Swal.fire('Error', 'Amount exceeds available balance.', 'error');
                    return;
                }

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Submit Request'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to submit withdrawal.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Submit Request'
                        );
                    }
                });
            });
        });
    </script>
@endsection
