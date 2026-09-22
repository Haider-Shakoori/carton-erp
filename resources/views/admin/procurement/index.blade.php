@extends('layouts.admin.base')

@section('title', 'Procure-to-Pay Control Center')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-diagram-3 me-2 text-primary"></i>Procure-to-Pay Control Center</h2>
            <div class="text-muted">Purchase request → approval → RFQ → supplier quote → purchase order → goods receipt → supplier invoice → payment.</div>
        </div>
        @can('create purchase requests')
            <a href="{{ route('admin.procurement.requests.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Purchase Request</a>
        @endcan
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Purchase Requests</h5></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Request</th><th>Needed</th><th>Items</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        @forelse($requests as $row)
                            <tr>
                                <td><strong>{{ $row->request_no }}</strong><div class="small text-muted">{{ $row->requestedBy?->name }}</div></td>
                                <td>{{ $row->needed_by?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $row->items->count() }}</td>
                                <td><span class="badge bg-{{ $row->status === 'approved' || $row->status === 'converted' ? 'success' : ($row->status === 'submitted' ? 'warning text-dark' : 'secondary') }}">{{ ucfirst($row->status) }}</span></td>
                                <td class="text-end">
                                    @if($row->status === 'draft')
                                        @can('submit purchase requests')
                                            <form method="POST" action="{{ route('admin.procurement.requests.submit',$row) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary">Submit</button></form>
                                        @endcan
                                    @elseif($row->status === 'submitted')
                                        @can('approve purchase requests')
                                            <form method="POST" action="{{ route('admin.procurement.requests.approve',$row) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                                        @endcan
                                    @elseif($row->status === 'approved')
                                        @can('create rfq')
                                            <a href="{{ route('admin.procurement.rfqs.create',$row) }}" class="btn btn-sm btn-outline-primary">Create RFQ</a>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No purchase requests.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold">RFQs / Supplier Comparison</h5></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>RFQ</th><th>Request</th><th>Quotes</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse($rfqs as $rfq)
                            <tr>
                                <td><strong>{{ $rfq->rfq_no }}</strong></td>
                                <td>{{ $rfq->request?->request_no }}</td>
                                <td>{{ $rfq->quotes->count() }}</td>
                                <td><span class="badge bg-{{ $rfq->status === 'awarded' ? 'success' : ($rfq->status === 'open' ? 'primary' : 'secondary') }}">{{ ucfirst($rfq->status) }}</span></td>
                                <td class="text-end"><a href="{{ route('admin.procurement.rfqs.show',$rfq) }}" class="btn btn-sm btn-light border">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No RFQs.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Pending Purchase Approvals</h5></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>PO</th><th>Supplier</th><th>Total USD</th><th></th></tr></thead>
                        <tbody>
                        @forelse($pendingPurchases as $purchase)
                            <tr>
                                <td><a href="{{ route('admin.purchase-orders.show',$purchase->id) }}" class="fw-bold">{{ $purchase->purchase_no }}</a></td>
                                <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                                <td>USD {{ number_format((float)$purchase->usd_grand_total,2) }}</td>
                                <td class="text-end">
                                    @can('approve purchase orders')
                                        <form action="{{ route('admin.purchase-orders.approve',$purchase->id) }}" method="POST">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No purchase orders awaiting approval.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Supplier Invoices / Three-Way Match</h5></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Invoice</th><th>PO / Supplier</th><th>Match</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_no }}</strong><div class="small text-muted">{{ $invoice->invoice_date?->format('Y-m-d') }}</div></td>
                                <td>{{ $invoice->purchase?->purchase_no }}<div class="small text-muted">{{ $invoice->purchase?->supplier?->name }}</div></td>
                                <td><span class="badge bg-{{ $invoice->three_way_match_status === 'matched' ? 'success' : ($invoice->three_way_match_status === 'exception' ? 'danger' : 'secondary') }}">{{ ucfirst(str_replace('_',' ',$invoice->three_way_match_status)) }}</span></td>
                                <td>{{ ucfirst($invoice->status) }}</td>
                                <td class="text-end">
                                    @if($invoice->three_way_match_status !== 'matched')
                                        @can('match supplier invoices')
                                            <form action="{{ route('admin.procurement.invoices.match',$invoice) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary">Rematch</button></form>
                                        @endcan
                                    @elseif($invoice->status === 'matched')
                                        @can('approve supplier invoices')
                                            <form action="{{ route('admin.procurement.invoices.approve',$invoice) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                                        @endcan
                                    @elseif($invoice->status === 'approved')
                                        @can('pay supplier invoices')
                                            <form action="{{ route('admin.procurement.invoices.pay',$invoice) }}" method="POST" class="d-flex gap-1 justify-content-end">
                                                @csrf
                                                <input name="payment_reference" class="form-control form-control-sm" style="max-width:150px" placeholder="Payment ref" required>
                                                <button class="btn btn-sm btn-primary">Mark Paid</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No supplier invoices.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Goods Receipts</h5></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>GRN</th><th>PO</th><th>Received</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        @forelse($receipts as $receipt)
                            <tr>
                                <td><strong>{{ $receipt->receipt_no }}</strong></td>
                                <td>{{ $receipt->purchase?->purchase_no }}</td>
                                <td>{{ $receipt->received_at?->format('Y-m-d H:i') ?? '—' }}<div class="small text-muted">{{ $receipt->receivedBy?->name }}</div></td>
                                <td><span class="badge bg-{{ $receipt->status === 'posted' ? 'success' : 'secondary' }}">{{ ucfirst($receipt->status) }}</span></td>
                                <td class="text-end">
                                    @if($receipt->status === 'draft')
                                        @can('post goods receipts')
                                            <form action="{{ route('admin.procurement.receipts.post',$receipt) }}" method="POST">@csrf<button class="btn btn-sm btn-success">Post</button></form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No goods receipts.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
