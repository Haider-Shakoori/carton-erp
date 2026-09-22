@extends('layouts.admin.base')

@section('title', 'Create Stock Transfer')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1">Create Stock Transfer</h2>
        <div class="text-muted">Create a draft request. Another authorized user must approve it before posting.</div>
    </div>

    <form action="{{ route('admin.warehouses.transfers.store') }}" method="POST">
        @csrf
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">From Location</label>
                        <select name="from_location_id" class="form-select" required>
                            <option value="">Select source</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected(old('from_location_id') == $location->id)>
                                    {{ $location->warehouse?->name }} / {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">To Location</label>
                        <select name="to_location_id" class="form-select" required>
                            <option value="">Select destination</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected(old('to_location_id') == $location->id)>
                                    {{ $location->warehouse?->name }} / {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Reason</label>
                        <input name="reason" value="{{ old('reason') }}" class="form-control" required maxlength="1000">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Transfer Batches</h5></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th style="width:48px;"></th><th>Material / Batch</th><th>Current Location(s)</th><th>Available</th><th style="width:180px;">Transfer Qty</th></tr></thead>
                    <tbody>
                    @foreach($batches as $index => $batch)
                        @php($available = app(AppServicesInventoryLocationService::class)->availableForBatch($batch))
                        <tr>
                            <td><input class="form-check-input transfer-select" type="checkbox" data-index="{{ $index }}"></td>
                            <td>
                                <div class="fw-semibold">{{ $batch->product?->name }}</div>
                                <div class="small text-muted">{{ $batch->purchase?->purchase_no }} · Batch #{{ $batch->id }}</div>
                            </td>
                            <td class="small">
                                @foreach($batch->locationBalances->where('condition_status','available') as $balance)
                                    <div>{{ $balance->location?->name }}: {{ number_format((float)$balance->quantity, 4) }} {{ $balance->unit }}</div>
                                @endforeach
                            </td>
                            <td>{{ number_format($available, 6) }} {{ $batch->inventoryCostBasisUnit() }}</td>
                            <td>
                                <input type="hidden" class="batch-id" data-index="{{ $index }}" value="{{ $batch->id }}" disabled>
                                <input type="number" class="form-control qty-input" data-index="{{ $index }}" min="0.000001" max="{{ $available }}" step="0.000001" disabled>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('admin.warehouses.transfers') }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary">Create Draft Transfer</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
document.querySelectorAll('.transfer-select').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        const i = checkbox.dataset.index;
        const batch = document.querySelector('.batch-id[data-index="' + i + '"]');
        const qty = document.querySelector('.qty-input[data-index="' + i + '"]');

        if (checkbox.checked) {
            batch.disabled = false;
            qty.disabled = false;
            batch.name = 'items[' + i + '][purchase_item_id]';
            qty.name = 'items[' + i + '][quantity]';
            qty.required = true;
        } else {
            batch.disabled = true;
            qty.disabled = true;
            batch.removeAttribute('name');
            qty.removeAttribute('name');
            qty.required = false;
            qty.value = '';
        }
    });
});
</script>
@endsection
