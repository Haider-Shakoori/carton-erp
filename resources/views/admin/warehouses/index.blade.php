@extends('layouts.admin.base')

@section('title', 'Warehouses & Inventory Control')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-boxes me-2 text-primary"></i>Warehouses & Inventory Control</h2>
            <div class="text-muted">Physical location, blocked/damaged stock and transfer controls. Company inventory totals remain batch/FIFO authoritative.</div>
        </div>
        <a href="{{ route('admin.warehouses.transfers') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left-right me-1"></i> Stock Transfers
        </a>
    </div>

    <div class="row g-3 mb-4">
        @foreach($warehouses as $warehouse)
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">{{ $warehouse->code }}</div>
                                <h5 class="fw-bold mb-1">{{ $warehouse->name }}</h5>
                                @if($warehouse->businessUnit)
                                    <span class="badge bg-light text-dark border">{{ $warehouse->businessUnit->name }}</span>
                                @else
                                    <span class="badge bg-light text-dark border">Shared / Unified</span>
                                @endif
                            </div>
                            <i class="bi bi-building fs-3 text-primary"></i>
                        </div>

                        <div class="mt-3">
                            @foreach($warehouse->locations as $location)
                                <div class="border rounded-3 p-3 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $location->name }}</strong>
                                        <span class="small text-muted">{{ $location->code }}</span>
                                    </div>
                                    <div class="row g-2 mt-1 small">
                                        <div class="col-4"><span class="text-success">Available</span><br><strong>{{ number_format((float)($location->available_quantity ?? 0), 4) }}</strong></div>
                                        <div class="col-4"><span class="text-warning">Blocked</span><br><strong>{{ number_format((float)($location->blocked_quantity ?? 0), 4) }}</strong></div>
                                        <div class="col-4"><span class="text-danger">Damaged</span><br><strong>{{ number_format((float)($location->damaged_quantity ?? 0), 4) }}</strong></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-bold">Location Stock</h5>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material / Batch</th>
                        <th>Warehouse Location</th>
                        <th>Condition</th>
                        <th class="text-end">Quantity</th>
                        <th>Unit</th>
                        @can('condition stock')
                            <th style="min-width:300px;">Reclassify</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $balance->purchaseItem?->product?->name ?? 'Unknown' }}</div>
                            <div class="small text-muted">Batch #{{ $balance->purchase_item_id }} · {{ $balance->purchaseItem?->purchase?->purchase_no }}</div>
                        </td>
                        <td>{{ $balance->location?->warehouse?->name }} / {{ $balance->location?->name }}</td>
                        <td>
                            @php
                                $conditionClass = match($balance->condition_status) {
                                    'available' => 'success',
                                    'blocked' => 'warning',
                                    'damaged' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $conditionClass }}">{{ ucfirst($balance->condition_status) }}</span>
                        </td>
                        <td class="text-end fw-bold">{{ number_format((float)$balance->quantity, 6) }}</td>
                        <td>{{ $balance->unit }}</td>
                        @can('condition stock')
                            <td>
                                <form action="{{ route('admin.warehouses.balances.condition', $balance) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" class="form-control form-control-sm" min="0.000001" step="0.000001" max="{{ $balance->quantity }}" value="{{ $balance->quantity }}" required>
                                    <select name="condition_status" class="form-select form-select-sm" required>
                                        <option value="available">Available</option>
                                        <option value="blocked">Blocked</option>
                                        <option value="damaged">Damaged</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Apply</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No warehouse balances yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($balances, 'links'))
            <div class="card-footer bg-white">{{ $balances->links() }}</div>
        @endif
    </div>
</div>
@endsection
