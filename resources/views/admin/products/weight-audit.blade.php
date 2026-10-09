@extends('layouts.admin.base')

@section('title', 'Carton Weight / BOM Audit')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h3 mb-1">Carton weight / BOM audit</h1>
            <p class="text-muted mb-0">Measured gross carton weight vs theoretical BOM paper grams per finished carton.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-warning" href="{{ route('admin.products.bom-review-sheet') }}">
                Download review-required BOM worksheet
            </a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.products.index') }}">Back to catalog</a>
        </div>
    </div>
    <div class="alert alert-info">
        Indicative only: glue, ink, moisture and rejects may explain a difference.
        Weight checks never change FIFO inventory, stock consumption or approved BOM material recipes.
        BOMs are evaluated in the current business-unit context.
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                <tr>
                    <th>Finished good</th><th>Gross weight (g)</th><th>BOM</th>
                    <th>Paper only (g)</th><th>Gross minus paper (g)</th><th>Review result</th>
                </tr>
                </thead>
                <tbody>
                @forelse($finishedProducts as $product)
                    @php
                        $bom = $product->boms->first();
                        $audit = $auditor->assess($product, $bom);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            <small class="d-block text-muted">{{ $product->sku ?: '#'.$product->id }}</small>
                        </td>
                        <td>{{ $audit['measured_g'] !== null ? number_format($audit['measured_g'], 2) : '—' }}</td>
                        <td>
                            {{ $bom?->code ?: '—' }}
                            @if($bom)<small class="d-block text-muted">{{ $bom->status }}</small>@endif
                        </td>
                        <td>
                            {{ $audit['paper_g'] !== null ? number_format($audit['paper_g'], 2) : '—' }}
                            @foreach($audit['materials'] as $part)
                                <small class="d-block text-muted">{{ $part['name'] }}: {{ number_format($part['weight_g'], 2) }} g</small>
                            @endforeach
                        </td>
                        <td>{{ $audit['difference_g'] !== null ? number_format($audit['difference_g'], 2) : '—' }}</td>
                        <td>{{ $audit['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted p-4">No finished goods found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $finishedProducts->links() }}</div>
</div>
@endsection
