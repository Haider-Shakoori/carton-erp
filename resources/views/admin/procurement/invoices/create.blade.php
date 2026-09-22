@extends('layouts.admin.base')
@section('title','Supplier Invoice')
@section('content')
<div class="container-fluid px-3 px-md-4">
 <div class="mb-4"><h2 class="fw-bold mb-1">Supplier Invoice · {{ $purchase->purchase_no }}</h2><div class="text-muted">{{ $purchase->supplier?->name }} — saving immediately runs three-way matching.</div></div>
 <form method="POST" action="{{ route('admin.procurement.invoices.store',$purchase) }}">@csrf
  <div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="row g-3">
   <div class="col-md-4"><label class="form-label fw-semibold">Supplier Invoice #</label><input name="invoice_no" class="form-control" required maxlength="100"></div>
   <div class="col-md-4"><label class="form-label fw-semibold">Invoice Date</label><input type="date" name="invoice_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
   <div class="col-md-4"><label class="form-label fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control"></div>
   <div class="col-md-6"><label class="form-label fw-semibold">Invoice Total ({{ $purchase->currency?->code }})</label><input type="number" name="total" class="form-control" min="0" step="0.01" value="{{ $purchase->grand_total }}" required></div>
   <div class="col-md-6"><label class="form-label fw-semibold">Invoice Total USD</label><input type="number" name="usd_total" class="form-control" min="0" step="0.01" value="{{ $purchase->usd_grand_total }}" required></div>
   <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
  </div></div><div class="card-footer bg-white text-end"><button class="btn btn-primary">Save & Three-Way Match</button></div></div>
 </form>
</div>
@endsection
