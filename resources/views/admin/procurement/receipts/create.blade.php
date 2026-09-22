@extends('layouts.admin.base')
@section('title','Goods Receipt')
@section('content')
<div class="container-fluid px-3 px-md-4">
 <div class="mb-4"><h2 class="fw-bold mb-1">Goods Receipt · {{ $purchase->purchase_no }}</h2><div class="text-muted">{{ $purchase->supplier?->name }} — record accepted and rejected quantities.</div></div>
 <form method="POST" action="{{ route('admin.procurement.receipts.store',$purchase) }}">@csrf
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Material</th><th>Ordered</th><th>Received</th><th>Rejected</th></tr></thead><tbody>
   @foreach($purchase->items as $i=>$item)<tr><td>{{ $item->product?->name }}<input type="hidden" name="items[{{ $i }}][purchase_item_id]" value="{{ $item->id }}"><input type="hidden" name="items[{{ $i }}][unit]" value="{{ $item->inventoryCostBasisUnit() }}"></td><td>{{ $item->inventoryCostBasisUnit()==='kg'?number_format((float)$item->totalKg(),6):number_format((float)$item->qty,6) }} {{ $item->inventoryCostBasisUnit() }}</td><td><input type="number" name="items[{{ $i }}][quantity_received]" value="{{ $item->inventoryCostBasisUnit()==='kg'?(float)$item->totalKg():(float)$item->qty }}" min="0" step="0.000001" class="form-control" required></td><td><input type="number" name="items[{{ $i }}][quantity_rejected]" value="0" min="0" step="0.000001" class="form-control" required></td></tr>@endforeach
  </tbody></table></div><div class="card-body border-top"><label class="form-label">Receipt Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div><div class="card-footer bg-white text-end"><button class="btn btn-primary">Save Draft GRN</button></div></div>
 </form>
</div>
@endsection
