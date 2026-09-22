@extends('layouts.admin.base')

@section('title', 'Stock Transfers')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Stock Transfers</h2>
            <div class="text-muted">Maker-checker transfers between warehouse locations. Transfers never change total company inventory.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.warehouses.index') }}" class="btn btn-light">Warehouses</a>
            @can('transfer stock')
                <a href="{{ route('admin.warehouses.transfers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Transfer</a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transfer</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Approved By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($transfers as $transfer)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $transfer->transfer_no }}</div>
                            <div class="small text-muted">{{ $transfer->created_at?->format('Y-m-d H:i') }}</div>
                        </td>
                        <td>{{ $transfer->fromLocation?->warehouse?->name }} / {{ $transfer->fromLocation?->name }}</td>
                        <td>{{ $transfer->toLocation?->warehouse?->name }} / {{ $transfer->toLocation?->name }}</td>
                        <td>{{ $transfer->items->count() }}</td>
                        <td><span class="badge bg-{{ $transfer->status === 'completed' ? 'success' : ($transfer->status === 'approved' ? 'primary' : 'secondary') }}">{{ ucfirst($transfer->status) }}</span></td>
                        <td>{{ $transfer->requestedBy?->name ?? '—' }}</td>
                        <td>{{ $transfer->approvedBy?->name ?? '—' }}</td>
                        <td class="text-end">
                            @if($transfer->status === 'draft')
                                @can('approve stock transfers')
                                    <form action="{{ route('admin.warehouses.transfers.approve', $transfer) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary">Approve</button>
                                    </form>
                                @endcan
                            @elseif($transfer->status === 'approved')
                                @can('transfer stock')
                                    <form action="{{ route('admin.warehouses.transfers.complete', $transfer) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success" onclick="return confirm('Post this transfer?')">Complete</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted">No stock transfers recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $transfers->links() }}</div>
    </div>
</div>
@endsection
