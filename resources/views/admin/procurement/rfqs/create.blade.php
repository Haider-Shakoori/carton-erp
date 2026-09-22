@extends('layouts.admin.base')
@section('title','Create RFQ')
@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="mb-4"><h2 class="fw-bold mb-1">Create RFQ for {{ $purchaseRequest->request_no }}</h2><div class="text-muted">Approved internal requirement becomes a supplier quotation request.</div></div>
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">
        @foreach($purchaseRequest->items as $item)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $item->product?->name }}</span><strong>{{ number_format((float)$item->quantity,6) }} {{ $item->unit }}</strong></div>@endforeach
    </div></div>
    <form action="{{ route('admin.procurement.rfqs.store',$purchaseRequest) }}" method="POST">@csrf
      <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
        <div class="row g-3"><div class="col-md-4"><label class="form-label fw-semibold">Quotation Due Date</label><input type="date" name="due_date" class="form-control"></div><div class="col-md-8"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div></div>
      </div><div class="card-footer bg-white text-end"><button class="btn btn-primary">Create Draft RFQ</button></div></div>
    </form>
</div>
@endsection
