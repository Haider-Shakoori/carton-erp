{{-- resources/views/admin/products/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Product Details - ' . $product->name)

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link href="{{ asset('vendor/fonts/inter/inter.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
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
                        {{ __('ui.product') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-layers me-1"></i>
                        {{ $product->name }} · Batch tracking & inventory analytics
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.stock.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================================
        PRODUCT INFO CARD
        ============================================================ --}}
        <div class="table-card" style="margin-bottom: 30px;">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-info-circle"></i> Product Information
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--primary-bg); color: var(--primary);">
                        <i class="bi bi-tag me-1"></i> {{ $product->category->name ?? 'Uncategorized' }}
                    </span>
                    <span class="header-badge" style="background: var(--success-bg); color: var(--success);">
                        <i class="bi bi-box me-1"></i> {{ $product->unit ?? 'unit' }}
                    </span>
                </div>
            </div>
            <div style="padding: 20px;">
                <div class="row align-items-center">
                    <div class="col-auto">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" 
                                 alt="{{ $product->name }}" 
                                 style="width: 100px; height: 100px; object-fit: cover; border-radius: 12px; border: 2px solid var(--border-color);">
                        @else
                            <div style="width: 100px; height: 100px; border-radius: 12px; background: var(--bg-secondary); display: flex; align-items: center; justify-content: center; font-size: 40px; color: var(--text-muted); border: 2px solid var(--border-color);">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col">
                        <h3 style="font-weight: 700; color: var(--text-primary); margin: 0;">{{ $product->name }}</h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 8px;">
                            <span style="display: flex; align-items: center; gap: 4px; color: var(--text-secondary); font-size: 14px;">
                                <i class="bi bi-hash" style="color: var(--primary);"></i> SKU: {{ $product->sku ?? 'N/A' }}
                            </span>
                            <span style="display: flex; align-items: center; gap: 4px; color: var(--text-secondary); font-size: 14px;">
                                <i class="bi bi-calendar3" style="color: var(--primary);"></i> Added: {{ $product->created_at->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
        STATS CARDS - Matching your existing style
        ============================================================ --}}
        <div class="stats-grid">
            <div class="stat-card blue-accent">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="stat-value">
                        {{ number_format($stats['total_purchased'], 2) }}
                    </div>
                </div>
                <div class="stat-label">Total Purchased ({{ $product->unit ?? 'unit' }})</div>
            </div>

            <div class="stat-card green-accent">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <div class="stat-value">
                        {{ number_format($stats['total_sold'], 2) }}
                    </div>
                </div>
                <div class="stat-label">Total Sold ({{ $product->unit ?? 'unit' }})</div>
            </div>

            <div class="stat-card red-accent">
                <div class="stat-top">
                    <div class="stat-icon red">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div class="stat-value">
                        {{ number_format($stats['total_wasted'], 2) }}
                    </div>
                </div>
                <div class="stat-label">Total Wasted ({{ $product->unit ?? 'unit' }})</div>
            </div>

            <div class="stat-card purple-accent">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-value">
                        <span class="currency">$</span>{{ number_format($stats['total_value_usd'], 2) }}
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_value_usd') }}</div>
            </div>
        </div>

        {{-- ============================================================
        BATCH DETAILS TABLE - Matching your existing table style
        ============================================================ --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-layers"></i> Batch Inventory Profiles
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> {{ $purchaseItems->count() }} batches
                    </span>
                </h5>
                <div class="d-flex gap-2">
                    <span class="header-badge" style="background: var(--success-bg); color: var(--success);">
                        <i class="bi bi-check-circle me-1"></i> In Stock
                    </span>
                    <span class="header-badge" style="background: var(--danger-bg); color: var(--danger);">
                        <i class="bi bi-exclamation-circle me-1"></i> Depleted
                    </span>
                </div>
            </div>

            <div class="table-responsive-custom">
                <table class="table-ledger">
                    <thead>
                        <tr>
                            <th style="min-width: 120px;">{{ __('ui.batch_number') }}</th>
                            <th>{{ __('ui.purchase_date') }}</th>
                            <th class="text-center">{{ __('ui.purchased') }}</th>
                            <th class="text-center">{{ __('ui.sold') }}</th>
                            <th class="text-center">{{ __('ui.wasted') }}</th>
                            <th class="text-center">{{ __('ui.available') }}</th>
                            <th class="text-end">{{ __('ui.cost_per_unit_short') }}</th>
                            <th class="text-end">{{ __('ui.value_usd') }}</th>
                            <th class="text-center">{{ __('ui.utilization') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batchMetrics as $batch)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-primary);">
                                        {{ $batch['batch_no'] }}
                                    </div>
                                </td>
                                <td>
                                    <div style="color: var(--text-secondary); font-size: 14px;">
                                        {{ $batch['purchase_date'] instanceof \Carbon\Carbon ? $batch['purchase_date']->format('M d, Y') : date('M d, Y', strtotime($batch['purchase_date'])) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell text-center">
                                        <i class="bi bi-arrow-down-circle num-icon"></i>
                                        {{ number_format($batch['qty'], 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell green text-center">
                                        <i class="bi bi-arrow-up-circle num-icon"></i>
                                        {{ number_format($batch['qty_sold'], 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell red text-center">
                                        <i class="bi bi-trash num-icon"></i>
                                        {{ number_format($batch['qty_wasted'], 2) }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($batch['qty_available'] > 0)
                                        <span class="stock-badge in-stock">
                                            <i class="bi bi-circle-fill"></i>
                                            {{ number_format($batch['qty_available'], 2) }}
                                        </span>
                                    @else
                                        <span class="stock-badge out-of-stock">
                                            <i class="bi bi-circle-fill"></i>
                                            Depleted
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="num-cell text-end" style="justify-content: flex-end;">
                                        <span class="currency-sm">$</span>{{ number_format($batch['cost_per_unit'], 4) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="num-cell text-end" style="justify-content: flex-end;">
                                        <span class="currency-sm">$</span>{{ number_format($batch['current_value'], 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex: 1; height: 6px; background: var(--bg-secondary); border-radius: 10px; overflow: hidden; min-width: 60px;">
                                            <div style="height: 100%; width: {{ min($batch['utilization_rate'], 100) }}%; background: {{ $batch['utilization_rate'] > 80 ? 'var(--success)' : ($batch['utilization_rate'] > 50 ? 'var(--warning)' : 'var(--danger)') }}; border-radius: 10px; transition: width 0.3s ease;">
                                            </div>
                                        </div>
                                        <span style="font-size: 12px; color: var(--text-secondary); min-width: 40px;">
                                            {{ number_format($batch['utilization_rate'], 1) }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-inboxes"></i>
                                        <p>{{ __('ui.no_batches_product') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================================================
        ADDITIONAL INSIGHTS - Matching your card style
        ============================================================ --}}
        <div class="row mt-4">
            {{-- Recent Activity --}}
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-clock-history"></i> {{ __('ui.recent_activity') }}
                            <span class="header-badge">Last 30 days</span>
                        </h5>
                    </div>
                    <div style="padding: 16px;">
                        @forelse($recentActivity as $activity)
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-color);">
                                <div>
                                    @if($activity->qty_sold > 0)
                                        <span style="color: var(--success); font-weight: 600;">
                                            <i class="bi bi-arrow-up-circle"></i> {{ __('ui.sold') }}
                                        </span>
                                        <span style="color: var(--text-primary); font-weight: 600;">{{ number_format($activity->qty_sold, 2) }}</span>
                                    @elseif($activity->qty_wasted > 0)
                                        <span style="color: var(--danger); font-weight: 600;">
                                            <i class="bi bi-trash"></i> {{ __('ui.wasted') }}
                                        </span>
                                        <span style="color: var(--text-primary); font-weight: 600;">{{ number_format($activity->qty_wasted, 2) }}</span>
                                    @else
                                        <span style="color: var(--primary); font-weight: 600;">
                                            <i class="bi bi-arrow-down-circle"></i> {{ __('ui.purchased') }}
                                        </span>
                                        <span style="color: var(--text-primary); font-weight: 600;">{{ number_format($activity->qty, 2) }}</span>
                                    @endif
                                    <span style="color: var(--text-muted); font-size: 13px; margin-left: 8px;">
                                        Batch: {{ $activity->batch_no ?? 'N/A' }}
                                    </span>
                                </div>
                                <div style="color: var(--text-muted); font-size: 12px;">
                                    <i class="bi bi-calendar-event"></i>
                                    {{ $activity->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        @empty
                            <div class="empty-state" style="padding: 20px 0;">
                                <i class="bi bi-inboxes"></i>
                                <p style="font-size: 14px;">{{ __('ui.no_recent_activity') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Batch Summary --}}
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-graph-up-arrow"></i> Batch Summary
                        </h5>
                    </div>
                    <div style="padding: 16px;">
                        @php
                            $totalBatches = $batchMetrics->count();
                            $activeBatches = $batchMetrics->filter(function($b) { return $b['qty_available'] > 0; })->count();
                            $totalUtilized = $batchMetrics->sum('qty_sold') + $batchMetrics->sum('qty_wasted');
                            $totalPurchased = $batchMetrics->sum('qty');
                            $overallUtilization = $totalPurchased > 0 ? ($totalUtilized / $totalPurchased) * 100 : 0;
                            $totalWastageValue = $batchMetrics->sum('wastage_value');
                            $totalProfitLoss = $batchMetrics->sum('profit_loss');
                            $avgCost = $batchMetrics->avg('cost_per_unit');
                        @endphp
                        
                        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
                            <div style="background: var(--bg-secondary); padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600;">Total Batches</div>
                                <div style="font-size: 20px; font-weight: 700; color: var(--text-primary);">{{ $totalBatches }}</div>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600;">Active Batches</div>
                                <div style="font-size: 20px; font-weight: 700; color: var(--primary);">{{ $activeBatches }}</div>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600;">Avg Cost/Unit</div>
                                <div style="font-size: 20px; font-weight: 700; color: var(--text-primary);">
                                    <span class="currency-sm">$</span>{{ number_format($avgCost, 4) }}
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div style="background: var(--bg-secondary); padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600;">{{ __('ui.utilization') }}</div>
                                <div style="font-size: 20px; font-weight: 700; color: var(--success);">{{ number_format($overallUtilization, 1) }}%</div>
                            </div>
                            <div style="background: var(--bg-secondary); padding: 12px 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600;">{{ __('ui.wastage_value') }}</div>
                                <div style="font-size: 20px; font-weight: 700; color: var(--danger);">
                                    <span class="currency-sm">$</span>{{ number_format($totalWastageValue, 2) }}
                                </div>
                            </div>
                        </div>
                        
                        @if($totalProfitLoss != 0)
                            <div style="margin-top: 16px; padding: 12px 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 500; color: var(--text-secondary);">
                                    <i class="bi bi-pie-chart"></i> Total Profit/Loss
                                </span>
                                <span style="font-size: 18px; font-weight: 700; color: {{ $totalProfitLoss > 0 ? 'var(--success)' : 'var(--danger)' }};">
                                    <span class="currency-sm">$</span>{{ number_format(abs($totalProfitLoss), 2) }}
                                    <span style="font-size: 13px; font-weight: 400; color: var(--text-muted); margin-left: 4px;">
                                        {{ $totalProfitLoss > 0 ? 'Profit' : 'Loss' }}
                                    </span>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        // Any JavaScript for the product view page
        document.addEventListener('DOMContentLoaded', function() {
            // Add any interactive elements here
        });
    </script>
@endsection