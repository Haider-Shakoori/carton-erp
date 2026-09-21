@extends('layouts.admin.base')

@section('title', 'Warehouse Control')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Warehouse & Inventory Control</h1>
            <div class="text-muted small">Location balances and controlled internal stock transfers. Global FIFO batch quantities remain authoritative.</div>
        </div>
        <span class="badge bg-light text-dark border">Negative location stock is blocked</span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3 mb-4">
        @foreach($warehouses as $warehouse)
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <div class="fw-bold">{{ $warehouse->name }}</div>
                                <div class="text-muted small">{{ $warehouse->code }}</div>
                            </div>
                            @if($warehouse->is_default)<span class="badge bg-primary">Default</span>@endif
                        </div>
                        <div class="small mt-3">{{ $warehouse->balances_count }} batch/location balances</div>
                        <div class="mt-2">
                            @forelse($warehouse->locations as $location)
                                <span class="badge bg-light text-dark border me-1">{{ $location->code }}</span>
                            @empty
                                <span class="text-muted small">No sub-locations yet.</span>
                            @endforelse
                        </div>
                        @can('manage warehouses')
                            <form method="POST" action="{{ route('admin.enterprise.warehouses.locations.store', $warehouse) }}" class="row g-2 mt-2">
                                @csrf
                                <div class="col-4"><input name="code" class="form-control form-control-sm" placeholder="BIN-A1" required></div>
                                <div class="col-5"><input name="name" class="form-control form-control-sm" placeholder="Location name" required></div>
                                <div class="col-3"><button class="btn btn-sm btn-outline-primary w-100">Add</button></div>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        @endforeach

        @can('manage warehouses')
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Add Warehouse</div>
                        <form method="POST" action="{{ route('admin.enterprise.warehouses.store') }}" class="row g-2">
                            @csrf
                            <div class="col-4"><input name="code" class="form-control" placeholder="WH-02" required></div>
                            <div class="col-8"><input name="name" class="form-control" placeholder="Warehouse name" required></div>
                            <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea></div>
                            <div class="col-12"><button class="btn btn-primary">Create Warehouse</button></div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <strong>Allocated Inventory by Warehouse / Location</strong>
            <div class="text-muted small">Balances are tied to the original purchase batch so FIFO landed-cost traceability is preserved.</div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Warehouse</th><th>Location</th><th>Material</th><th>Purchase Batch</th><th class="text-end">Quantity</th><th class="text-end">Kg</th><th>Unit</th></tr>
                </thead>
                <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td>{{ $balance->warehouse->name ?? '—' }}</td>
                        <td>{{ $balance->location->code ?? 'Unassigned' }}</td>
                        <td>{{ $balance->product->name ?? $balance->purchaseItem->product->name ?? 'Material #'.$balance->product_id }}</td>
                        <td>#{{ $balance->purchase_item_id }} {{ $balance->purchaseItem->batch_no ? '· '.$balance->purchaseItem->batch_no : '' }}</td>
                        <td class="text-end">{{ number_format((float)$balance->quantity, 4) }}</td>
                        <td class="text-end">{{ number_format((float)$balance->quantity_kg, 4) }}</td>
                        <td>{{ $balance->unit ?: 'unit' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No allocated warehouse balances yet. Existing inventory is allocated to Main Warehouse when first received, transferred, or consumed.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($balances, 'links'))<div class="card-footer bg-white">{{ $balances->links() }}</div>@endif
    </div>

    @can('transfer stock')
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <strong>Post Stock Transfer</strong>
            <div class="text-muted small">Transfers only change warehouse ownership of an existing purchase batch; total company inventory and FIFO cost do not change.</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.enterprise.stock-transfers.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <select name="from_warehouse_id" class="form-select" required>
                            @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <select name="to_warehouse_id" class="form-select" required>
                            @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reason</label>
                        <input name="reason" class="form-control" required placeholder="Reason for transfer">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Batch</label>
                        <select name="items[0][purchase_item_id]" class="form-select" required>
                            @foreach($balances->getCollection()->unique('purchase_item_id') as $balance)
                                <option value="{{ $balance->purchase_item_id }}">
                                    #{{ $balance->purchase_item_id }} — {{ $balance->purchaseItem->product->name ?? 'Material' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Quantity</label><input name="items[0][quantity]" type="number" step="0.000001" min="0" class="form-control" value="0"></div>
                    <div class="col-md-2"><label class="form-label">Kg</label><input name="items[0][quantity_kg]" type="number" step="0.000001" min="0" class="form-control" value="0"></div>
                    <div class="col-md-2"><label class="form-label">From Location ID</label><input name="items[0][from_location_id]" type="number" min="1" class="form-control" placeholder="Optional"></div>
                    <div class="col-md-2"><label class="form-label">To Location ID</label><input name="items[0][to_location_id]" type="number" min="1" class="form-control" placeholder="Optional"></div>
                </div>
                <button class="btn btn-primary mt-3">Post Transfer</button>
            </form>
        </div>
    </div>
    @endcan

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white"><strong>Recent Transfers</strong></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Transfer</th><th>Status</th><th>Reason</th><th>Items</th><th>Posted</th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->transfer_no }}</td>
                            <td><span class="badge {{ $transfer->status === 'posted' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($transfer->status) }}</span></td>
                            <td>{{ $transfer->reason }}</td>
                            <td>{{ $transfer->items->count() }}</td>
                            <td>{{ $transfer->posted_at ? $transfer->posted_at->format('Y-m-d H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No transfers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
