{{-- resources/views/admin/shareholders/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.shareholders'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .shareholder-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }
        .shareholder-avatar.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }
        .badge-status {
            padding: 0.2rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-status.active {
            background: var(--success-bg);
            color: var(--success);
        }
        .badge-status.inactive {
            background: var(--danger-bg);
            color: var(--danger);
        }
        .share-percentage {
            font-weight: 700;
            color: var(--primary);
        }
        .balance-positive {
            color: var(--success);
        }
        .balance-negative {
            color: var(--danger);
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
                        <i class="bi bi-people-fill me-2"></i> <span class="accent">{{ __('ui.shareholders') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-person-badge me-1"></i> Manage company shareholders and their profit shares
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.shareholders.create') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> {{ __('ui.add_shareholder') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_shareholders') }}</div>
                <div class="stat-value">{{ $shareholders->total() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-person-check"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.active') }}</div>
                <div class="stat-value">{{ $shareholders->where('is_active', true)->count() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-percent"></i>
                    </div>
                </div>
                <div class="stat-label">Total Share</div>
                <div class="stat-value">{{ $shareholders->sum('share_percentage') }}%</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-modern mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.search') }}</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('ui.search_name_code') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('ui.all') }}</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('ui.active') }}</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('ui.inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.shareholders.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Shareholder Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> Shareholder List
            </span>
                <span class="header-badge">
                <i class="bi bi-database me-1"></i> Total: {{ $shareholders->total() }}
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="shareholdersTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 50px;">{{ __('ui.avatar') }}</th>
                            <th>{{ __('ui.code') }}</th>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.email') }}</th>
                            <th>{{ __('ui.share_percent') }}</th>
                            <th>Capital</th>
                            <th>Profit/Loss</th>
                            <th>{{ __('ui.balance') }}</th>
                            <th style="width: 110px;">{{ __('ui.status') }}</th>
                            <th style="width: 130px;" class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>

                        <!-- Add Profit/Loss data in the table body -->
                        <tbody>
                        @foreach ($shareholders as $shareholder)
                            @php
                                $netProfitLoss = $shareholder->net_profit_loss ?? 0;
                                $profitLossClass = $netProfitLoss >= 0 ? 'text-success' : 'text-danger';
                                $profitLossSign = $netProfitLoss >= 0 ? '+' : '';
                            @endphp
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    @if ($shareholder->profile_image)
                                        <img src="{{ Storage::url($shareholder->profile_image) }}" class="shareholder-avatar">
                                    @else
                                        <div class="shareholder-avatar placeholder">
                                            {{ strtoupper(substr($shareholder->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $shareholder->code }}</td>
                                <td class="fw-semibold text-dark">{{ $shareholder->name }}</td>
                                <td>{{ $shareholder->email ?? '-' }}</td>
                                <td class="share-percentage text-center">{{ $shareholder->share_percentage }}%</td>
                                <td class="num-cell">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($shareholder->capital_contribution ?? 0, 2) }}</td>
                                <td class="num-cell {{ $profitLossClass }}">
                                    {{ $profitLossSign }}{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(abs($netProfitLoss), 2) }}
                                </td>
                                <td class="num-cell {{ ($shareholder->balance ?? 0) >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                    {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($shareholder->balance ?? 0, 2) }}
                                </td>
                                <td>
            <span class="badge-status {{ $shareholder->is_active ? 'active' : 'inactive' }}">
                {{ $shareholder->is_active ? 'Active' : 'Inactive' }}
            </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">

                                        {{-- View --}}
                                        @can('view shareholder profiles')
                                            <a href="{{ route('admin.shareholders.show', $shareholder) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="{{ __('ui.view') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endcan

                                        {{-- Edit --}}
                                        @can('edit shareholders')
                                            <a href="{{ route('admin.shareholders.edit', $shareholder) }}"
                                               class="btn btn-sm btn-outline-warning"
                                               title="{{ __('ui.edit') }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            {{-- Activate / Deactivate --}}
                                            <button type="button"
                                                    class="btn btn-sm {{ $shareholder->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} toggle-status"
                                                    data-id="{{ $shareholder->id }}"
                                                    data-status="{{ $shareholder->is_active ? 1 : 0 }}"
                                                    title="{{ $shareholder->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="bi {{ $shareholder->is_active ? 'bi-person-dash' : 'bi-person-check' }}"></i>
                                            </button>
                                        @endcan

                                        {{-- Delete --}}
                                        @can('delete shareholders')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger delete-shareholder"
                                                    data-id="{{ $shareholder->id }}"
                                                    data-name="{{ $shareholder->name }}"
                                                    title="{{ __('ui.delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endcan

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $shareholders->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#shareholdersTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: false,
                order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: [1, 8] }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                }
            });

            // Toggle Status
            $(document).on('click', '.toggle-status', function() {
                const id = $(this).data('id');
                const currentStatus = $(this).data('status');
                const action = currentStatus ? 'deactivate' : 'activate';

                Swal.fire({
                    title: `${action.charAt(0).toUpperCase() + action.slice(1)} Shareholder?`,
                    text: `Are you sure you want to ${action} this shareholder?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4F46E5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: `Yes, ${action}!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/shareholders/${id}/toggle-status`,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Updated!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to update status.', 'error');
                            }
                        });
                    }
                });
            });

            // Delete Shareholder
            $(document).on('click', '.delete-shareholder', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Shareholder?',
                    text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/shareholders/${id}`,
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete shareholder.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
