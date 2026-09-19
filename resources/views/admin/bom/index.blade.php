@extends('layouts.admin.base')

@section('title', __('ui.bill_of_materials'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <style>
        /* ─── Status Badges ─── */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .status-badge.draft {
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.archived {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* ─── Stat Cards ─── */
        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .stat-icon.purple {
            background: #e0e7ff;
            color: #4f46e5;
        }
        .stat-icon.green {
            background: #d1fae5;
            color: #065f46;
        }
        .stat-icon.yellow {
            background: #fef3c7;
            color: #92400e;
        }
        .stat-icon.gray {
            background: #f3f4f6;
            color: #6b7280;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.2;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* ─── Action Buttons ─── */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            justify-content: flex-end;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: transparent;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .action-btn:hover {
            background: #f1f5f9;
            color: #4f46e5;
        }
        .action-btn.text-danger:hover {
            background: #fecaca;
            color: #dc2626;
        }
        .action-btn.text-success:hover {
            background: #d1fae5;
            color: #059669;
        }
        .action-btn.text-warning:hover {
            background: #fef3c7;
            color: #d97706;
        }
        .action-btn form {
            display: inline-block;
            margin: 0;
        }

        /* ─── Badge Category ─── */
        .badge-cat {
            background: #eef2ff;
            color: #4f46e5;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }

        /* ─── Table ─── */
        .table-ledger tbody tr {
            transition: background 0.2s ease;
        }
        .table-ledger tbody tr:hover {
            background: #f8fafc;
        }
        .num-cell {
            font-family: 'Inter', monospace;
            font-weight: 600;
            color: #1e293b;
        }
        .num-cell .unit-label {
            font-weight: 400;
            color: #94a3b8;
            font-size: 0.65rem;
            margin-left: 0.15rem;
        }
        .header-badge {
            background: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        /* ─── Pagination ─── */
        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }
        .pagination-wrap .info-text {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .pagination-wrap {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .stat-card {
                padding: 1rem;
            }
            .stat-value {
                font-size: 1.2rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ─── PAGE HEADER ─── --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-list-ul me-2"></i>
                        Bill of <span class="accent">Materials</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        Manage production formulas and material requirements
                    </p>
                </div>
                <div>
                    <a href="{{ route('bom.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('ui.create_bom') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- ─── STATISTICS CARDS ─── --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $boms->total() }}</div>
                        <div class="stat-label">Total BOMs</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $boms->where('status', 'active')->count() }}</div>
                        <div class="stat-label">Active BOMs</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $boms->where('status', 'draft')->count() }}</div>
                        <div class="stat-label">Draft BOMs</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon gray">
                        <i class="bi bi-archive"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $boms->where('status', 'archived')->count() }}</div>
                        <div class="stat-label">Archived BOMs</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── BOM TABLE ─── --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-table me-2"></i> {{ __('ui.bom_list') }}
                </h6>
                <span class="header-badge">
                    <i class="bi bi-database me-1"></i>
                    Showing {{ $boms->firstItem() ?? 0 }} - {{ $boms->lastItem() ?? 0 }} of {{ $boms->total() }}
                </span>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-ledger mb-0" id="bom-table">
                        <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>{{ __('ui.bom_code') }}</th>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.product') }}</th>
                            <th style="text-align: center;">{{ __('ui.items') }}</th>
                            <th style="text-align: right; min-width: 120px;">{{ __('ui.total_cost_usd') }}</th>
                            <th style="text-align: right; min-width: 120px;">Total Cost (AFN)</th>
                            <th style="text-align: center;">{{ __('ui.status') }}</th>
                            <th>{{ __('ui.created_by') }}</th>
                            <th style="text-align: right; min-width: 180px;">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($boms as $bom)
                            @php
                                // Calculate totals from items
                                $materialCostUsd = 0;
                                $materialCostAfn = 0;
                                foreach ($bom->items as $item) {
                                    $materialCostUsd += floatval($item->total_cost_usd ?? 0);
                                    $materialCostAfn += floatval($item->total_cost_afn ?? 0);
                                }

                                // Calculate work cost
                                $workPercentage = floatval($bom->work_percentage ?? 40) / 100;
                                $workCostUsd = $materialCostUsd * $workPercentage;
                                $workCostAfn = $materialCostAfn * $workPercentage;

                                // Total cost
                                $totalCostUsd = $materialCostUsd + $workCostUsd;
                                $totalCostAfn = $materialCostAfn + $workCostAfn;

                                // Exchange rate
                                $exchangeRate = floatval($bom->exchange_rate ?? 85);
                            @endphp
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $bom->code }}</span>
                                    <br>
                                    <small class="text-muted">v{{ $bom->version }}</small>
                                </td>
                                <td>{{ $bom->name }}</td>
                                <td>
                                    <span class="badge-cat">
                                        {{ $bom->product->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="num-cell">
                                        <i class="bi bi-box-seam me-1"></i>
                                        {{ $bom->items->count() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="num-cell fw-semibold text-dark">
                                        ${{ number_format($totalCostUsd, 2) }}
                                    </span>
                                    <br>
                                    <small class="text-muted" style="font-size: 0.65rem;">
                                        Material: ${{ number_format($materialCostUsd, 2) }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <span class="num-cell fw-semibold text-dark">
                                        ؋{{ number_format($totalCostAfn, 2) }}
                                    </span>
                                    <br>
                                    <small class="text-muted" style="font-size: 0.65rem;">
                                        Material: ؋{{ number_format($materialCostAfn, 2) }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge {{ $bom->status }}">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                        {{ ucfirst($bom->status) }}
                                    </span>
                                    @if($bom->is_active)
                                        <span class="badge bg-success ms-1" style="font-size: 0.6rem;">{{ __('ui.active') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small>
                                        {{ $bom->createdBy->name ?? 'Unknown' }}
                                        <br>
                                        <span class="text-muted" style="font-size: 0.65rem;">
                                            {{ $bom->created_at->format('d M, Y') }}
                                        </span>
                                    </small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        {{-- View --}}
                                        <a href="{{ route('bom.show', $bom) }}" class="action-btn" title="{{ __('ui.view_details') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        {{-- Edit --}}
                                        <a href="{{ route('bom.edit', $bom) }}" class="action-btn" title="Edit BOM">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        {{-- Clone --}}
                                        @if($bom->status !== 'active')
                                            <form action="{{ route('bom.clone', $bom) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="action-btn text-success" title="{{ __('ui.clone_bom') }}"
                                                        onclick="return confirm('Clone this BOM?')">
                                                    <i class="bi bi-files"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Toggle Status --}}
                                        <form action="{{ route('bom.toggle-status', $bom) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="action-btn text-warning"
                                                    title="{{ $bom->status === 'active' ? 'Archive' : 'Activate' }}"
                                                    onclick="return confirm('{{ $bom->status === 'active' ? 'Archive' : 'Activate' }} this BOM?')">
                                                <i class="bi bi-{{ $bom->status === 'active' ? 'archive' : 'check-circle' }}"></i>
                                            </button>
                                        </form>

                                        {{-- Delete --}}
                                        @if($bom->status !== 'active')
                                            <form action="{{ route('bom.destroy', $bom) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn text-danger" title="{{ __('ui.delete_bom') }}"
                                                        onclick="return confirm('Delete this BOM? This action cannot be undone.')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="text-center py-5">
                                        <i class="bi bi-inboxes" style="font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: 0.5rem;"></i>
                                        <p class="text-muted">No BOMs found</p>
                                        <a href="{{ route('bom.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle me-1"></i> Create First BOM
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ─── PAGINATION ─── --}}
                @if ($boms->hasPages())
                    <div class="p-3 border-top">
                        <div class="pagination-wrap">
                            <span class="info-text">
                                Showing {{ $boms->firstItem() ?? 0 }} to {{ $boms->lastItem() ?? 0 }} of {{ $boms->total() }} entries
                            </span>
                            {{ $boms->appends(request()->query())->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#bom-table').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[0, 'desc']],
                language: {
                    search: '',
                    searchPlaceholder: 'Search BOMs...',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No BOMs found',
                    infoFiltered: '(filtered from _MAX_ total)',
                },
                columnDefs: [
                    { orderable: false, targets: [4, 5, 6, 7, 8, 9] }
                ],
                // Custom styling for search
                dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2"lf>tip',
                drawCallback: function() {
                    // Re-apply any custom styling after draw
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    $('.dataTables_filter input').attr('placeholder', 'Search BOMs...');
                }
            });

            // Move the search input to match our design
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_filter input').attr('placeholder', 'Search BOMs...');

            // Handle window resize for responsive table
            $(window).resize(function() {
                table.columns.adjust();
            });
        });

        // ─── SWEET ALERT FOR DELETE CONFIRMATION ───
        document.addEventListener('DOMContentLoaded', function() {
            // Attach to all delete buttons with class 'action-btn text-danger'
            document.querySelectorAll('.action-btn.text-danger').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    // Check if this is a delete button (has trash icon)
                    const icon = this.querySelector('i');
                    if (icon && icon.classList.contains('bi-trash')) {
                        e.preventDefault();
                        const form = this.closest('form');
                        if (form) {
                            Swal.fire({
                                title: 'Are you sure?',
                                text: "This action cannot be undone!",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#dc2626',
                                cancelButtonColor: '#6b7280',
                                confirmButtonText: 'Yes, delete it!',
                                cancelButtonText: 'Cancel'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form.submit();
                                }
                            });
                        }
                    }
                });
            });
        });
    </script>
@endsection
