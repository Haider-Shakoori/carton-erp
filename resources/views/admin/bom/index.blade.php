@extends('layouts.admin.base')

@section('title', __('ui.bill_of_materials'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-index.css') }}">
    <style>
        .bom-index {
            --bom-purple: #4f46e5;
            --bom-green: #059669;
            --bom-amber: #d97706;
            --bom-slate: #64748b;
            --bom-border: #e8edf4;
        }

        .bom-index .bom-stat-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .bom-index .bom-stat-card .stat-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px !important;
        }

        .bom-index .bom-stat-card.total .stat-card::before { background: var(--bom-purple); }
        .bom-index .bom-stat-card.active .stat-card::before { background: var(--bom-green); }
        .bom-index .bom-stat-card.draft .stat-card::before { background: var(--bom-amber); }
        .bom-index .bom-stat-card.archived .stat-card::before { background: #94a3b8; }

        .bom-index .bom-stat-card.is-selected .stat-card {
            border-color: rgba(79, 70, 229, .28) !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .055), var(--index-shadow-sm) !important;
        }

        .bom-index .stat-icon.purple { background:#eef2ff; color:#4f46e5; }
        .bom-index .stat-icon.green { background:#ecfdf5; color:#059669; }
        .bom-index .stat-icon.amber { background:#fffbeb; color:#d97706; }
        .bom-index .stat-icon.slate { background:#f1f5f9; color:#64748b; }

        .bom-index .filter-bar {
            justify-content: space-between;
        }

        .bom-index .bom-filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
            margin-left: auto;
        }

        .bom-index .bom-search {
            width: min(310px, 100%);
        }

        .bom-index .bom-code {
            font-size: .75rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -.01em;
        }

        .bom-index .bom-name {
            margin-top: .12rem;
            font-size: .66rem;
            color: #64748b;
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bom-index .bom-version {
            display: inline-flex;
            align-items: center;
            margin-top: .28rem;
            padding: .12rem .38rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: .56rem;
            font-weight: 750;
        }

        .bom-index .product-name {
            color:#0f172a;
            font-size:.74rem;
            font-weight:750;
        }

        .bom-index .product-meta,
        .bom-index .spec-meta,
        .bom-index .money-meta,
        .bom-index .date-meta {
            margin-top:.12rem;
            color:#94a3b8;
            font-size:.61rem;
            line-height:1.4;
        }

        .bom-index .spec-primary {
            color:#334155;
            font-size:.71rem;
            font-weight:700;
            white-space:nowrap;
        }

        .bom-index .material-count {
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            min-height:27px;
            padding:.2rem .5rem;
            border-radius:8px;
            background:#f8fafc;
            border:1px solid #e8edf4;
            color:#475569;
            font-size:.66rem;
            font-weight:750;
        }

        .bom-index .money-main {
            color:#0f172a;
            font-size:.75rem;
            font-weight:800;
            white-space:nowrap;
        }

        .bom-index .money-main.rate {
            color:#047857;
        }

        .bom-index .work-chip {
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            margin-top:.24rem;
            padding:.12rem .4rem;
            border-radius:999px;
            background:#ecfdf5;
            color:#047857;
            font-size:.56rem;
            font-weight:750;
            white-space:nowrap;
        }

        .bom-index .status-badge {
            border:1px solid transparent;
        }

        .bom-index .status-badge.active {
            background:#ecfdf5;
            border-color:#a7f3d0;
            color:#047857;
        }

        .bom-index .status-badge.draft {
            background:#fffbeb;
            border-color:#fde68a;
            color:#b45309;
        }

        .bom-index .status-badge.archived {
            background:#f1f5f9;
            border-color:#e2e8f0;
            color:#64748b;
        }

        .bom-index .governance-chip {
            display:inline-flex;
            align-items:center;
            gap:.25rem;
            margin-top:.3rem;
            padding:.14rem .4rem;
            border-radius:999px;
            font-size:.55rem;
            font-weight:750;
        }

        .bom-index .governance-chip.locked {
            background:#eef2ff;
            color:#4f46e5;
        }

        .bom-index .governance-chip.effective {
            background:#ecfdf5;
            color:#047857;
        }

        .bom-index .governance-chip.inactive {
            background:#fef2f2;
            color:#b91c1c;
        }

        .bom-index .table-responsive-custom {
            overflow-x:auto;
        }

        .bom-index .table-ledger {
            min-width: 1120px;
        }

        .bom-index .action-buttons form {
            margin:0;
        }

        .bom-index .action-btn.text-success:hover {
            border-color:#a7f3d0 !important;
            background:#ecfdf5 !important;
            color:#047857 !important;
        }

        .bom-index .action-btn.text-warning:hover {
            border-color:#fde68a !important;
            background:#fffbeb !important;
            color:#b45309 !important;
        }

        .bom-index .filter-note {
            display:flex;
            align-items:center;
            gap:.4rem;
            color:#94a3b8;
            font-size:.62rem;
            font-weight:600;
        }

        .bom-index .empty-state .btn {
            margin-top:.85rem;
        }

        @media (max-width: 768px) {
            .bom-index .filter-bar {
                align-items:stretch;
            }

            .bom-index .bom-filter-form,
            .bom-index .bom-search {
                width:100%;
                margin-left:0;
            }

            .bom-index .bom-filter-form .form-select,
            .bom-index .bom-filter-form .btn {
                flex:1 1 135px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4 erp-index-ui bom-index">

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        {{-- PAGE HEADER --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-diagram-3 me-2"></i>
                        Bill of <span class="accent">Materials</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        Manage carton specifications, production recipes and standard customer rates
                        @if(($stats['locked'] ?? 0) > 0)
                            <span class="ms-2"><i class="bi bi-lock me-1"></i>{{ $stats['locked'] }} approved/locked</span>
                        @endif
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    @can('view bom')
                        <a href="{{ route('bom.calculator') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-calculator me-1"></i> Cost Calculator
                        </a>
                    @endcan
                    @can('create bom')
                        <a href="{{ route('bom.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Create BOM
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- GLOBAL STATS --}}
        <div class="stats-grid">
            <a class="bom-stat-card total {{ request('status', 'all') === 'all' ? 'is-selected' : '' }}"
               href="{{ route('bom.index', request()->except('page', 'status')) }}">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon purple"><i class="bi bi-diagram-3"></i></div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                    <div class="stat-label">Total BOMs</div>
                </div>
            </a>

            <a class="bom-stat-card active {{ request('status') === 'active' ? 'is-selected' : '' }}"
               href="{{ route('bom.index', array_merge(request()->except('page', 'status'), ['status' => 'active'])) }}">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
                        <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                    <div class="stat-label">Active BOMs</div>
                </div>
            </a>

            <a class="bom-stat-card draft {{ request('status') === 'draft' ? 'is-selected' : '' }}"
               href="{{ route('bom.index', array_merge(request()->except('page', 'status'), ['status' => 'draft'])) }}">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon amber"><i class="bi bi-pencil-square"></i></div>
                        <div class="stat-value">{{ $stats['draft'] ?? 0 }}</div>
                    </div>
                    <div class="stat-label">Draft BOMs</div>
                </div>
            </a>

            <a class="bom-stat-card archived {{ request('status') === 'archived' ? 'is-selected' : '' }}"
               href="{{ route('bom.index', array_merge(request()->except('page', 'status'), ['status' => 'archived'])) }}">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon slate"><i class="bi bi-archive"></i></div>
                        <div class="stat-value">{{ $stats['archived'] ?? 0 }}</div>
                    </div>
                    <div class="stat-label">Archived BOMs</div>
                </div>
            </a>
        </div>

        {{-- FILTERS --}}
        <div class="filter-bar">
            <div class="status-filter-group">
                <a href="{{ route('bom.index', request()->except('page', 'status')) }}"
                   class="status-filter-btn {{ request('status', 'all') === 'all' ? 'active-all' : '' }}">
                    <i class="bi bi-circle-fill"></i> All
                </a>
                <a href="{{ route('bom.index', array_merge(request()->except('page', 'status'), ['status' => 'active'])) }}"
                   class="status-filter-btn {{ request('status') === 'active' ? 'active-active' : '' }}">
                    <i class="bi bi-circle-fill"></i> Active
                </a>
                <a href="{{ route('bom.index', array_merge(request()->except('page', 'status'), ['status' => 'draft'])) }}"
                   class="status-filter-btn {{ request('status') === 'draft' ? 'active-draft' : '' }}">
                    <i class="bi bi-circle-fill"></i> Draft
                </a>
                <a href="{{ route('bom.index', array_merge(request()->except('page', 'status'), ['status' => 'archived'])) }}"
                   class="status-filter-btn {{ request('status') === 'archived' ? 'active-archived' : '' }}">
                    <i class="bi bi-circle-fill"></i> Archived
                </a>
            </div>

            <form action="{{ route('bom.index') }}" method="GET" class="bom-filter-form">
                @if(request('status') && request('status') !== 'all')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="input-group bom-search">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search"
                           name="search"
                           class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Search code, BOM, or product...">
                </div>

                <select name="sort" class="form-select" style="width:auto;min-width:145px;">
                    <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest updated</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
                    <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                    <option value="rate_high" @selected(request('sort') === 'rate_high')>Rate high–low</option>
                    <option value="rate_low" @selected(request('sort') === 'rate_low')>Rate low–high</option>
                </select>

                <select name="per_page" class="form-select" style="width:auto;min-width:105px;">
                    @foreach([15, 30, 50] as $pageSize)
                        <option value="{{ $pageSize }}" @selected((int) request('per_page', 15) === $pageSize)>
                            {{ $pageSize }} / page
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn-filter btn-primary">
                    <i class="bi bi-search me-1"></i> Apply
                </button>

                @if(request()->filled('search') || request()->filled('sort') || request()->filled('per_page') || (request()->filled('status') && request('status') !== 'all'))
                    <a href="{{ route('bom.index') }}" class="btn-filter btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- BOM LIST --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-table me-1"></i>
                    BOM Library
                    <span class="header-badge ms-2">
                        <i class="bi bi-database"></i>
                        {{ $boms->total() }} {{ $boms->total() === 1 ? 'result' : 'results' }}
                    </span>
                </h5>
                <div class="filter-note">
                    <i class="bi bi-info-circle"></i>
                    Physical cost and standard rate are shown per finished unit.
                </div>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th style="min-width:190px;">BOM</th>
                        <th style="min-width:180px;">Product</th>
                        <th style="min-width:170px;">Specification</th>
                        <th class="text-center" style="min-width:95px;">Materials</th>
                        <th class="text-end" style="min-width:135px;">Physical Cost</th>
                        <th class="text-end" style="min-width:145px;">Standard Rate</th>
                        <th class="text-center" style="min-width:120px;">Status</th>
                        <th style="min-width:125px;">Updated</th>
                        <th class="text-end" style="width:1%;min-width:145px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($boms as $bom)
                        @php
                            $dimensionItem = $bom->items->first(function ($item) {
                                return (float) ($item->length_inch ?? 0) > 0
                                    && (float) ($item->width_inch ?? 0) > 0
                                    && (float) ($item->height_inch ?? 0) > 0;
                            });

                            $paperRows = $bom->items->filter(function ($item) {
                                return ($item->component_type ?? null) === 'paper'
                                    || in_array(($item->formula_type ?? null), ['carton_3d', 'cut_roll'], true);
                            });

                            $gsmValues = $paperRows
                                ->pluck('paper_gsm')
                                ->filter(fn ($gsm) => (float) $gsm > 0)
                                ->map(fn ($gsm) => (int) $gsm)
                                ->unique()
                                ->values();

                            $materialCostAfn = (float) ($bom->total_material_cost_afn ?? 0);
                            if ($materialCostAfn <= 0) {
                                $materialCostAfn = (float) $bom->items->sum('total_cost_afn');
                            }

                            $standardRateAfn = (float) ($bom->selling_price_afn ?? 0);
                            $expectedProfitAfn = (float) ($bom->profit_afn ?? 0);
                            $workPercentage = (float) ($bom->work_percentage ?? 40);

                            $statusConfig = [
                                'active' => ['icon' => 'bi-check2-circle', 'label' => 'Active'],
                                'draft' => ['icon' => 'bi-pencil-square', 'label' => 'Draft'],
                                'archived' => ['icon' => 'bi-archive', 'label' => 'Archived'],
                            ];
                            $status = $statusConfig[$bom->status] ?? [
                                'icon' => 'bi-circle',
                                'label' => ucfirst((string) $bom->status),
                            ];
                        @endphp

                        <tr>
                            <td class="text-muted fw-semibold">
                                {{ ($boms->firstItem() ?? 1) + $loop->index }}
                            </td>

                            <td>
                                <a href="{{ route('bom.show', $bom) }}" class="bom-code">
                                    {{ $bom->code }}
                                </a>
                                <div class="bom-name" title="{{ $bom->name }}">{{ $bom->name }}</div>
                                <span class="bom-version">
                                    v{{ $bom->version ?: '1.0' }}
                                    @if(($bom->revision_sequence ?? 0) > 0)
                                        · R{{ $bom->revision_sequence }}
                                    @endif
                                </span>
                            </td>

                            <td>
                                <div class="product-name">{{ $bom->product->name ?? 'Product unavailable' }}</div>
                                <div class="product-meta">
                                    {{ $bom->product->unit ?? 'finished unit' }}
                                    @if($bom->createdBy)
                                        · by {{ $bom->createdBy->name }}
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if($dimensionItem)
                                    <div class="spec-primary">
                                        {{ number_format((float) $dimensionItem->length_inch, 2) }}
                                        × {{ number_format((float) $dimensionItem->width_inch, 2) }}
                                        × {{ number_format((float) $dimensionItem->height_inch, 2) }} in
                                    </div>
                                @else
                                    <div class="spec-primary text-muted">Standard / fixed formula</div>
                                @endif

                                <div class="spec-meta">
                                    @if($gsmValues->isNotEmpty())
                                        GSM {{ $gsmValues->implode(' / ') }}
                                    @else
                                        Technical recipe
                                    @endif
                                    @if($paperRows->count() > 0)
                                        · {{ $paperRows->count() }} paper {{ $paperRows->count() === 1 ? 'row' : 'rows' }}
                                    @endif
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="material-count">
                                    <i class="bi bi-boxes"></i>
                                    {{ $bom->items->count() }}
                                </span>
                            </td>

                            <td class="text-end">
                                <div class="money-main">
                                    {{ $materialCostAfn > 0 ? '؋'.number_format($materialCostAfn, 2) : '—' }}
                                </div>
                                <div class="money-meta">material / unit</div>
                            </td>

                            <td class="text-end">
                                <div class="money-main rate">
                                    {{ $standardRateAfn > 0 ? '؋'.number_format($standardRateAfn, 2) : '—' }}
                                </div>
                                <span class="work-chip">
                                    <i class="bi bi-graph-up-arrow"></i>
                                    {{ number_format($workPercentage, 0) }}% work/profit
                                </span>
                                @if($expectedProfitAfn != 0)
                                    <div class="money-meta">
                                        Expected {{ $expectedProfitAfn > 0 ? '+' : '-' }}؋{{ number_format(abs($expectedProfitAfn), 2) }}
                                    </div>
                                @endif
                            </td>

                            <td class="text-center">
                                <span class="status-badge {{ $bom->status }}">
                                    <i class="bi {{ $status['icon'] }}"></i>
                                    {{ $status['label'] }}
                                </span>

                                <div>
                                    @if($bom->locked_at)
                                        <span class="governance-chip locked">
                                            <i class="bi bi-lock-fill"></i> Locked
                                        </span>
                                    @elseif($bom->is_effective)
                                        <span class="governance-chip effective">
                                            <i class="bi bi-lightning-charge-fill"></i> Effective
                                        </span>
                                    @elseif(!$bom->is_active)
                                        <span class="governance-chip inactive">
                                            <i class="bi bi-pause-circle"></i> Disabled
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold" style="color:#475569;font-size:.68rem;">
                                    {{ $bom->updated_at?->format('d M Y') ?? '—' }}
                                </div>
                                <div class="date-meta">
                                    {{ $bom->updated_at?->format('H:i') ?? '' }}
                                </div>
                            </td>

                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('bom.show', $bom) }}"
                                       class="action-btn"
                                       title="View BOM"
                                       aria-label="View BOM">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @can('update bom')
                                        @if(!$bom->locked_at)
                                            <a href="{{ route('bom.edit', $bom) }}"
                                               class="action-btn"
                                               title="Edit BOM"
                                               aria-label="Edit BOM">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif

                                        <form action="{{ route('bom.toggle-status', $bom) }}" method="POST"
                                              onsubmit="return confirm('{{ $bom->status === 'active' ? 'Archive' : 'Activate' }} this BOM?');">
                                            @csrf
                                            <button type="submit"
                                                    class="action-btn text-warning"
                                                    title="{{ $bom->status === 'active' ? 'Archive BOM' : 'Activate BOM' }}"
                                                    aria-label="{{ $bom->status === 'active' ? 'Archive BOM' : 'Activate BOM' }}">
                                                <i class="bi bi-{{ $bom->status === 'active' ? 'archive' : 'check2-circle' }}"></i>
                                            </button>
                                        </form>
                                    @endcan

                                    @can('create bom')
                                        @if($bom->status !== 'active')
                                            <form action="{{ route('bom.clone', $bom) }}" method="POST"
                                                  onsubmit="return confirm('Clone this BOM?');">
                                                @csrf
                                                <button type="submit"
                                                        class="action-btn text-success"
                                                        title="Clone BOM"
                                                        aria-label="Clone BOM">
                                                    <i class="bi bi-copy"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                    @can('delete bom')
                                        @if($bom->status !== 'active' && !$bom->locked_at)
                                            <form action="{{ route('bom.destroy', $bom) }}" method="POST"
                                                  onsubmit="return confirm('Delete this BOM? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="action-btn text-danger"
                                                        title="Delete BOM"
                                                        aria-label="Delete BOM">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-diagram-3"></i>
                                    @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all'))
                                        <p>No BOMs match the current filters.</p>
                                        <div class="sub-text">Try another search or reset the filters.</div>
                                        <a href="{{ route('bom.index') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                                        </a>
                                    @else
                                        <p>No BOMs have been created yet.</p>
                                        <div class="sub-text">Use the Quick BOM Builder to create the first carton specification.</div>
                                        @can('create bom')
                                            <a href="{{ route('bom.create') }}" class="btn btn-primary btn-sm">
                                                <i class="bi bi-plus-lg me-1"></i> Create First BOM
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($boms->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing {{ $boms->firstItem() ?? 0 }}–{{ $boms->lastItem() ?? 0 }}
                        of {{ $boms->total() }} BOMs
                    </span>
                    {{ $boms->links('pagination::bootstrap-5') }}
                </div>
            @elseif($boms->total() > 0)
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing all {{ $boms->total() }} {{ $boms->total() === 1 ? 'BOM' : 'BOMs' }}
                    </span>
                </div>
            @endif
        </div>
    </div>
@endsection
