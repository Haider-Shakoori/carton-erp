@extends('layouts.admin.base')
@section('title','New Purchase Request')
@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="mb-4"><h2 class="fw-bold mb-1">New Purchase Request</h2><div class="text-muted">Internal demand request. Submission and approval are separate actions.</div></div>
    <form method="POST" action="{{ route('admin.procurement.requests.store') }}">@csrf
        <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label fw-semibold">Needed By</label><input type="date" name="needed_by" class="form-control" value="{{ old('needed_by') }}"></div>
                <div class="col-md-9"><label class="form-label fw-semibold">Business Justification</label><input name="justification" class="form-control" value="{{ old('justification') }}" required minlength="5" maxlength="2000"></div>
            </div>
        </div></div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white d-flex justify-content-between"><h5 class="mb-0 fw-bold">Requested Materials</h5><button type="button" class="btn btn-sm btn-outline-primary" id="addPrLine">Add Line</button></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Material</th><th style="width:170px">Quantity</th><th style="width:140px">Unit</th><th>Notes</th><th></th></tr></thead><tbody id="prLines"></tbody></table></div>
            <div class="card-footer bg-white text-end"><a href="{{ route('admin.procurement.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary">Save Draft Request</button></div>
        </div>
    </form>
</div>
<template id="prLineTemplate"><tr>
    <td><select class="form-select pr-product" required><option value="">Select material</option>@foreach($products as $product)<option value="{{ $product->id }}" data-unit="{{ $product->unit ?: 'unit' }}">{{ $product->name }}</option>@endforeach</select></td>
    <td><input type="number" class="form-control pr-qty" min="0.000001" step="0.000001" required></td>
    <td><input class="form-control pr-unit" required maxlength="30"></td>
    <td><input class="form-control pr-note" maxlength="500"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger pr-remove">×</button></td>
</tr></template>
@endsection
@section('js')
<script>
(function () {
    let n = 0;
    const body = document.getElementById('prLines');
    const tpl = document.getElementById('prLineTemplate');
    function addLine() {
        const row = tpl.content.firstElementChild.cloneNode(true);
        const i = n++;
        row.querySelector('.pr-product').name = 'items[' + i + '][product_id]';
        row.querySelector('.pr-qty').name = 'items[' + i + '][quantity]';
        row.querySelector('.pr-unit').name = 'items[' + i + '][unit]';
        row.querySelector('.pr-note').name = 'items[' + i + '][notes]';
        row.querySelector('.pr-product').addEventListener('change', function (event) {
            row.querySelector('.pr-unit').value = event.target.selectedOptions[0]?.dataset.unit || 'unit';
        });
        row.querySelector('.pr-remove').addEventListener('click', function () { row.remove(); });
        body.appendChild(row);
    }
    document.getElementById('addPrLine').addEventListener('click', addLine);
    addLine();
})();
</script>
@endsection
