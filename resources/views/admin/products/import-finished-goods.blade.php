@extends('layouts.admin.base')

@section('title', 'Import Finished Goods CSV')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Import finished goods</h1>
            <p class="text-muted mb-0">Bulk create or update carton specifications and measured weights in grams.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.products.index') }}">Back to catalog</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <p><strong>Step 1:</strong> Download the CSV template. The mandatory columns are
                <code>name</code>, <code>category</code>, and <code>weight_g</code>.
                Provide <code>sku</code> for new cartons or <code>product_id</code> for existing goods.
            </p>
            <a href="{{ route('admin.products.import.template') }}" class="btn btn-outline-primary mb-4">
                <i class="bi bi-download me-1"></i> Download CSV template
            </a>
            <a href="{{ route('admin.products.import.export') }}" class="btn btn-outline-secondary mb-4 ms-2">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export existing finished goods with IDs
            </a>
            <p><strong>Step 2:</strong> Keep categories identical to those already created in the catalog.
                Dimensions use millimetres; carton weights use grams. The importer accepts 5,000 rows per file
                and rejects invalid files without saving partial changes.</p>
            <form action="{{ route('admin.products.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label for="finished_goods_csv" class="form-label fw-semibold">CSV file</label>
                <input id="finished_goods_csv" class="form-control mb-3" type="file" name="csv"
                       accept=".csv,text/csv,text/plain" required>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import finished goods</button>
            </form>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <strong>Stock safety:</strong> Importing product weights does not retroactively deduct material,
        change FIFO purchase batches, overwrite historical customer specifications, or activate/reprice BOMs.
        Existing production still uses its approved BOM, including material-specific kilograms and wastage.
        A finished carton weight alone cannot determine the split between kraft, fluting, glue and ink.
    </div>
</div>
@endsection
