@extends('layouts.admin.base')

@section('title', 'Inventory Stock & Batches')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        .pnl-positive {
            color: var(--success);
            font-weight: 700;
        }

        .pnl-negative {
            color: var(--danger);
            font-weight: 700;
        }

        .pnl-neutral {
            color: var(--gray-500);
            font-weight: 600;
        }

        /* ============================================================
                                   IMPROVED ACTION BUTTONS
                                   ============================================================ */
        .action-buttons {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }

        .btn-stock-in,
        .btn-stock-out {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 14px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            letter-spacing: 0.3px;
            line-height: 1.4;
        }

        .btn-stock-in i,
        .btn-stock-out i {
            font-size: 0.9rem;
        }

        /* ── IN Button ── */
        .btn-stock-in {
            background: var(--success-bg, #e8f5e9);
            color: var(--success, #2e7d32);
            border-color: var(--success-border, #a5d6a7);
        }

        .btn-stock-in:hover {
            background: var(--success, #2e7d32);
            color: #fff;
            border-color: var(--success, #2e7d32);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.25);
        }

        .btn-stock-in:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* ── OUT Button ── */
        .btn-stock-out {
            background: var(--danger-bg, #ffebee);
            color: var(--danger, #c62828);
            border-color: var(--danger-border, #ef9a9a);
        }

        .btn-stock-out:hover {
            background: var(--danger, #c62828);
            color: #fff;
            border-color: var(--danger, #c62828);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(198, 40, 40, 0.25);
        }

        .btn-stock-out:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* ── View Details (optional third button) ── */
        .btn-stock-view {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            background: var(--gray-100, #f5f5f5);
            color: var(--gray-600, #757575);
            border: 1px solid var(--gray-300, #e0e0e0);
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .btn-stock-view:hover {
            background: var(--gray-200, #eeeeee);
            color: var(--gray-800, #424242);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* ── Responsive adjustments ── */
        @media (max-width: 768px) {
            .action-buttons {
                flex-wrap: wrap;
                justify-content: center;
                gap: 4px;
            }

            .btn-stock-in,
            .btn-stock-out {
                font-size: 0.7rem;
                padding: 4px 10px;
            }

            .btn-stock-in i,
            .btn-stock-out i {
                font-size: 0.8rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-box-seam me-2"></i>
                        Inventory <span class="accent">{{ __('ui.ledger') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-shield-check me-1"></i>
                        Multi-batch life cycles, real-time availability, and structural valuations
                    </p>
                </div>

                {{-- Search - Using Bootstrap default styles --}}
                <div class="search-wrapper">
                    <form action="{{ route('admin.stock.index') }}" method="GET" class="d-flex">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0"
                                placeholder="{{ __('ui.search_products') }}" value="{{ request('search') }}"
                                style="border-left: none; box-shadow: none;">
                            @if (request('search'))
                                <a href="{{ route('admin.stock.index') }}" class="btn btn-outline-secondary border-start-0"
                                    style="border-left: none;">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> {{ __('ui.search') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================================
STATS CARDS - Premium Glassmorphism (Icon + Value on top row)
============================================================ --}}
        <div class="stats-grid">
            <div class="stat-card purple-accent">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="stat-value">
                        <span
                            class="currency">$</span>{{ number_format($globalStats['total_purchase_value_with_expenses'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.purchase_value') }}</div>
            </div>

            <div class="stat-card blue-accent">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-value">
                        <span
                            class="currency">$</span>{{ number_format($globalStats['total_stock_value_with_expenses'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.stock_value') }}</div>
            </div>

            <div class="stat-card red-accent">
                <div class="stat-top">
                    <div class="stat-icon red">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div class="stat-value">
                        <span
                            class="currency">$</span>{{ number_format($globalStats['total_wastage_value_with_expenses'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.wastage_value') }}</div>
            </div>

            <div class="stat-card green-accent">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-value">
                        <span class="currency">$</span>{{ number_format($globalStats['total_sale_value'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="stat-label">Sale Value</div>
            </div>
        </div>

        {{-- ============================================================
        MAIN TABLE
        ============================================================ --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-layers"></i> Product Inventory Profiles
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $stocks->total() }} products
                    </span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--success-bg); color: var(--success);">
                        <i class="bi bi-check-circle me-1"></i> In Stock
                    </span>
                    <span class="header-badge" style="background: var(--danger-bg); color: var(--danger);">
                        <i class="bi bi-exclamation-circle me-1"></i> Out of Stock
                    </span>
                </div>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                    <tr>
                        <th style="min-width: 200px;">{{ __('ui.product') }}</th>
                        <th class="text-center" style="min-width: 80px;">Stock In</th>
                        <th class="text-center" style="min-width: 80px;">{{ __('ui.used') }}</th>
                        <th class="text-center" style="min-width: 80px;">{{ __('ui.wasted') }}</th>
                        <th class="text-center" style="min-width: 100px;">{{ __('ui.available') }}</th>
                        <th class="text-end" style="min-width: 100px;">{{ __('ui.purchase_value') }}</th>
                        <th class="text-end" style="min-width: 100px;">{{ __('ui.stock_value') }}</th>
                        <th class="text-end" style="min-width: 120px;">{{ __('ui.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($stocks as $product)
                        @php
                            $unit = $product->unit ?? 'unit';
                            $unitIcon = match ($unit) {
                                'kg', 'g', 'mg', 'lb', 'oz' => 'bi bi-weight-scale',
                                'L', 'ml', 'gal' => 'bi bi-droplet',
                                'piece', 'box', 'pack', 'set', 'dozen', 'pair' => 'bi bi-box',
                                'm', 'cm', 'km', 'in', 'ft' => 'bi bi-rulers',
                                'roll' => 'bi bi-minecart-loaded',
                                default => 'bi bi-box-seam',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <div class="avatar">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}">
                                        @else
                                            <i class="bi bi-box-seam"></i>
                                        @endif
                                    </div>
                                    <div class="info">
                                        <div class="name">{{ $product->name }}</div>
                                        <div class="meta">
                                            <span class="badge-cat">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                            <span>· <i class="{{ $unitIcon }}"></i> {{ $unit }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            {{-- Stock In --}}
                            <td class="text-center">
                                <span class="fw-semibold">{{ number_format($product->total_purchased ?? 0, 2) }}</span>
                                <br><small class="text-muted">{{ $unit }}</small>
                            </td>
                            {{-- Used --}}
                            <td class="text-center">
                                <span class="fw-semibold text-warning">{{ number_format($product->total_used ?? 0, 2) }}</span>
                                <br><small class="text-muted">{{ $unit }}</small>
                            </td>
                            {{-- Wasted --}}
                            <td class="text-center">
                                <span class="fw-semibold text-danger">{{ number_format($product->total_wasted ?? 0, 2) }}</span>
                                <br><small class="text-muted">{{ $unit }}</small>
                            </td>
                            {{-- Available --}}
                            <td class="text-center">
                                @if (($product->total_available ?? 0) > 0)
                                    <span class="stock-badge in-stock">
                    <i class="bi bi-circle-fill"></i>
                    {{ number_format($product->total_available, 2) }}
                    <span class="unit-label">{{ $unit }}</span>
                </span>
                                    @if(strtolower($unit) === 'roll' && $product->current_stock_kg > 0)
                                        <div style="font-size: 0.65rem; color: var(--text-secondary); margin-top: 2px;">
                                            {{ number_format($product->current_stock_kg, 4) }} kg
                                        </div>
                                    @endif
                                @else
                                    <span class="stock-badge out-of-stock">
                    <i class="bi bi-circle-fill"></i> Out of Stock
                </span>
                                @endif
                            </td>
                            {{-- Purchase Value --}}
                            <td class="text-end">
                                <span class="fw-semibold">${{ number_format($product->total_usd_cost ?? 0, 2) }}</span>
                            </td>
                            {{-- Stock Value --}}
                            <td class="text-end">
                                <span class="fw-semibold">${{ number_format($product->total_stock_value ?? 0, 2) }}</span>
                            </td>
                            {{-- Actions --}}
                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('admin.stock.stock-in', $product->id) }}" class="btn-stock-in" title="{{ __('ui.stock_in') }}">
                                        <i class="bi bi-arrow-down-circle"></i> IN
                                    </a>
                                    <a href="{{ route('admin.stock.stock-out', $product->id) }}" class="btn-stock-out" title="{{ __('ui.stock_out') }}">
                                        <i class="bi bi-arrow-up-circle"></i> OUT
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state text-center py-4">
                                    <i class="bi bi-inboxes" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                    <p class="mt-2 text-muted">No inventory records found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>

            {{-- ─── Pagination ─── --}}
            @if ($stocks->hasPages())
                <div class="pagination-wrap">
                    <span class="info-text">
                        Showing {{ $stocks->firstItem() }} – {{ $stocks->lastItem() }}
                        of {{ $stocks->total() }} entries
                    </span>
                    {{ $stocks->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
@endsection
