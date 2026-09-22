@extends('layouts.admin.base')
@section('title','RFQ '.$rfq->rfq_no)
@section('content')
<div class="container-fluid px-3 px-md-4">
 <div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="fw-bold mb-1">{{ $rfq->rfq_no }}</h2><div class="text-muted">Supplier comparison for {{ $rfq->request?->request_no }}</div></div>
 @if($rfq->status==='draft')@can('update rfq')<form method="POST" action="{{ route('admin.procurement.rfqs.open',$rfq) }}">@csrf<button class="btn btn-primary">Open RFQ</button></form>@endcan@endif</div>
 <div class="row g-4">
  <div class="col-xl-7"><div class="card border-0 shadow-sm rounded-4"><div class="card-header bg-white"><h5 class="mb-0 fw-bold">Supplier Quotations</h5></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Supplier</th><th>Quote</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
   @forelse($rfq->quotes as $quote)<tr><td>{{ $quote->supplier?->name }}</td><td>{{ $quote->quote_no ?: '—' }}</td><td>{{ $quote->currency?->code }} {{ number_format((float)$quote->total,2) }}</td><td><span class="badge bg-{{ $quote->status==='selected'?'success':($quote->status==='rejected'?'secondary':'primary') }}">{{ ucfirst($quote->status) }}</span></td><td class="text-end">@if($quote->status==='submitted')@can('award rfq')<form method="POST" action="{{ route('admin.procurement.quotes.select',$quote) }}">@csrf<button class="btn btn-sm btn-success" onclick="return confirm('Award this quote and create a draft purchase order?')">Award</button></form>@endcan@endif</td></tr>@empty<tr><td colspan="5" class="text-center py-4 text-muted">No quotations recorded.</td></tr>@endforelse
  </tbody></table></div></div></div>
  <div class="col-xl-5">
   @can('update rfq')
   <div class="card border-0 shadow-sm rounded-4"><div class="card-header bg-white"><h5 class="mb-0 fw-bold">Record Supplier Quote</h5></div><div class="card-body">
    <form method="POST" action="{{ route('admin.procurement.rfqs.quotes.store',$rfq) }}">@csrf
     <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select" required>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div><div class="col-6"><label class="form-label">Currency</label><select name="currency_id" class="form-select" required>@foreach($currencies as $currency)<option value="{{ $currency->id }}">{{ $currency->code }}</option>@endforeach</select></div><div class="col-6"><label class="form-label">Quote #</label><input name="quote_no" class="form-control"></div><div class="col-6"><label class="form-label">FX Rate</label><input name="exchange_rate" class="form-control" type="number" step="0.000001" min="0.000001" value="1" required></div></div>
     <div class="small fw-bold text-muted mb-2">Line Prices</div>
     @foreach($rfq->request->items as $i=>$line)
       <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $line->product_id }}"><input type="hidden" name="items[{{ $i }}][quantity]" value="{{ $line->quantity }}">
       <div class="input-group mb-2"><span class="input-group-text flex-grow-1 justify-content-start">{{ $line->product?->name }} · {{ number_format((float)$line->quantity,4) }} {{ $line->unit }}</span><input type="number" name="items[{{ $i }}][unit_price]" min="0" step="0.000001" class="form-control" style="max-width:160px" placeholder="Unit price" required></div>
     @endforeach
     <div class="mb-3"><label class="form-label">Terms</label><textarea name="terms" class="form-control" rows="2"></textarea></div><button class="btn btn-primary w-100">Save Supplier Quote</button>
    </form>
   </div></div>@endcan
  </div>
 </div>
</div>
@endsection
