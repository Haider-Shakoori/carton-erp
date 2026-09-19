{{-- resources/views/admin/shareholders.blade.php --}}
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
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
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
                        {{ $shareholder->status_label }}
                    </span>
                        <span class="badge bg-primary">{{ $shareholder->share_percentage }}% Share</span>
                    </div>
                    <div class="mt-2">
                        <i class="bi bi-envelope me-1"></i> {{ $shareholder->email }}
                        @if($shareholder->phone)
                            <span class="ms-3"><i class="bi bi-phone me-1"></i> {{ $shareholder->phone }}</span>
                        @endif
                    </div>
                    @if($shareholder->address)
                        <div class="mt-1 text-muted small">
                            <i class="bi bi-geo-alt me-1"></i> {{ $shareholder->address }}
                        </div>
                    @endif
                </div>
                <div class="col-md-3">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                            <i class="bi bi-cash me-1"></i> Request Withdrawal
                        </button>
                        <a href="{{ route('admin.shareholders.edit', $shareholder) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="number text-primary">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($balance, 2) }}</div>
                    <div class="label">{{ __('ui.current_balance') }}</div>
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
                    <div class="number text-info">{{ $distributionCount }}</div>
                    <div class="label">{{ __('ui.distributions') }}</div>
                </div>
            </div>
        </div>

        <!-- Distribution History -->
        <div class="card-modern">
            <div class="card-header">
                <i class="bi bi-clock-history me-1"></i> Distribution History
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                        <tr>
                            <th>{{ __('ui.distribution_number') }}</th>
                            <th>{{ __('ui.period') }}</th>
                            <th>{{ __('ui.share_percent') }}</th>
                            <th>{{ __('ui.amount') }}</th>
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
                                <td>{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($item->amount, 2) }}</td>
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
                    <form action="{{ route('admin.shareholders.withdraw', $shareholder) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('ui.amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="amount"
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
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2 me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
