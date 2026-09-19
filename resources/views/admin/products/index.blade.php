@extends('layouts.admin.base')

@section('title', 'Products & Categories Management')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        /* DataTables customization to match premium UI */
        .dataTables_filter {
            text-align: right;
            margin-bottom: 1rem;
        }

        .dataTables_filter input {
            border-radius: var(--radius-xs);
            border: 1.5px solid var(--gray-200);
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            transition: var(--transition);
            width: 220px;
        }

        .dataTables_filter input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .dataTables_length {
            display: none;
        }

        .dataTables_info {
            font-size: 0.75rem;
            color: var(--gray-400);
            padding: 0.5rem 0;
        }

        .dataTables_paginate {
            font-size: 0.8rem;
        }

        .dataTables_paginate .paginate_button {
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-xs);
            border: 1px solid var(--gray-200);
            margin: 0 0.1rem;
            background: white;
            color: var(--gray-600);
            font-weight: 500;
            transition: var(--transition);
        }

        .dataTables_paginate .paginate_button:hover {
            background: var(--primary-bg);
            border-color: var(--primary);
            color: var(--primary);
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .dataTables_paginate .paginate_button.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        /* Select2 customization */
        .select2-container--default .select2-selection--single {
            border-radius: var(--radius-xs) !important;
            border: 1.5px solid var(--gray-200) !important;
            height: 42px !important;
            padding: 0.3rem 0.75rem !important;
        }

        .select2-container--default .select2-selection--single:focus {
            border-color: var(--primary) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }

        .select2-dropdown {
            border-radius: var(--radius-xs) !important;
            border-color: var(--gray-200) !important;
        }

        /* Image upload wrapper */
        .image-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--gray-50);
            padding: 0.5rem;
            border-radius: var(--radius-xs);
            border: 1.5px dashed var(--gray-300);
        }

        .image-upload-wrapper .form-control {
            border: none !important;
            background: transparent !important;
            padding: 0.25rem 0 !important;
        }

        .current-image-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-xs);
            border: 1.5px solid var(--gray-200);
            padding: 2px;
            background: white;
        }

        /* Product image in table */
        .product-avatar {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            border: 2px solid var(--gray-200);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        tr:hover .product-avatar {
            border-color: var(--primary-light);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.15);
        }

        .product-avatar.placeholder {
            background: linear-gradient(135deg, var(--gray-100), var(--gray-50));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--gray-400);
        }

        /* Tabs styling */
        .nav-tabs-custom {
            display: flex;
            gap: 0.5rem;
            border-bottom: none;
            margin-bottom: 0;
        }

        .nav-tabs-custom .nav-link {
            color: var(--gray-500);
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.6rem 1.25rem;
            border: none;
            border-radius: var(--radius-xs);
            transition: var(--transition);
            position: relative;
        }

        .nav-tabs-custom .nav-link:hover {
            background: var(--gray-50);
            color: var(--gray-700);
        }

        .nav-tabs-custom .nav-link.active {
            background: var(--primary-bg);
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
        }

        .nav-tabs-custom .nav-link i {
            margin-right: 0.4rem;
        }

        .nav-tabs-custom .nav-link .badge {
            margin-left: 0.4rem;
            font-size: 0.6rem;
            padding: 0.15rem 0.5rem;
            background: var(--primary-bg);
            color: var(--primary);
        }

        .nav-tabs-custom .nav-link.active .badge {
            background: white;
        }

        /* Product type badge */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .type-badge.raw {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-badge.finished {
            background: #d1fae5;
            color: #065f46;
        }

        /* Low stock notification styles */
        .low-stock-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .low-stock-notification .alert {
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border: none;
        }

        .low-stock-notification .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-left: 4px solid #f59e0b;
        }

        .low-stock-notification .alert-danger {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            border-left: 4px solid #ef4444;
        }

        .low-stock-item {
            padding: 8px 12px;
            margin: 4px 0;
            background: rgba(255,255,255,0.7);
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }

        .low-stock-item .stock-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .low-stock-item .stock-badge.danger {
            background: #ef4444;
            color: white;
        }

        .low-stock-item .stock-badge.warning {
            background: #f59e0b;
            color: white;
        }

        /* Stock indicator in table */
        .stock-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .stock-indicator.good {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-indicator.low {
            background: #fef3c7;
            color: #92400e;
        }

        .stock-indicator.critical {
            background: #fecaca;
            color: #991b1b;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* Finished goods special styling */
        .finished-goods-icon {
            color: #10b981;
        }

        /* Modal header for finished goods */
        .modal-header.finished-goods {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .modal-header.raw-materials {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-box me-2"></i>
                        Catalog <span class="accent">{{ __('ui.control') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-grid-3x3-gap me-1"></i>
                        Configure production catalog items and organizational node trees
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal"
                            onclick="resetCategoryForm()">
                        <i class="bi bi-tag me-1"></i> {{ __('ui.add_category') }}
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal"
                            onclick="resetProductForm()">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('ui.add_raw_material') }}
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#finishedGoodsModal"
                            onclick="resetFinishedGoodsForm()">
                        <i class="bi bi-check-circle me-1"></i> {{ __('ui.add_finished_good') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
        LOW STOCK NOTIFICATION
        ============================================================ --}}
        <div id="lowStockNotification" class="low-stock-notification" style="display: none;">
            <div class="alert alert-warning" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">
                            <i class="bi bi-bell me-1"></i> Low Stock Alert
                        </h6>
                        <div id="lowStockProductsList">
                            <!-- Dynamic content -->
                        </div>
                    </div>
                    <button type="button" class="btn-close" onclick="closeLowStockNotification()"></button>
                </div>
            </div>
        </div>

        {{-- ============================================================
        MAIN TABLE CARD
        ============================================================ --}}
        <div class="table-card">
            <div class="card-header-custom">
                <ul class="nav-tabs-custom" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#products-tab" type="button">
                            <i class="bi bi-boxes"></i> Raw Materials
                            <span class="badge">{{ $rawProducts->total() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#finished-goods-tab" type="button">
                            <i class="bi bi-check-circle-fill finished-goods-icon"></i> Finished Goods
                            <span class="badge">{{ $finishedProducts->total() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#categories-tab" type="button">
                            <i class="bi bi-tags"></i> {{ __('ui.categories') }}
                            <span class="badge">{{ $categories->total() }}</span>
                        </button>
                    </li>
                </ul>
                <div>
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> Total: {{ $allProducts->total() + $categories->total() }} entries
                    </span>
                </div>
            </div>

            <div class="p-0">
                <div class="tab-content">

                    {{-- ─── Raw Materials Tab ─── --}}
                    <div class="tab-pane fade show active p-3" id="products-tab" role="tabpanel">
                        <div class="table-responsive-custom">
                            <table class="table-ledger" id="products-table">
                                <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th style="width: 70px;">{{ __('ui.image') }}</th>
                                    <th>{{ __('ui.product_name') }}</th>
                                    <th>{{ __('ui.category') }}</th>
                                    <th>{{ __('ui.unit') }}</th>
                                    <th style="width: 120px;">{{ __('ui.stock') }}</th>
                                    <th style="width: 120px;">{{ __('ui.min_alert') }}</th>
                                    <th style="width: 110px;">{{ __('ui.status') }}</th>
                                    <th style="width: 100px;" class="text-end">{{ __('ui.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($rawProducts as $product)
                                    @php
                                        $unit = $product->unit ?? 'unit';
                                        $unitIcon = match ($unit) {
                                            'kg', 'g', 'mg', 'lb', 'oz' => 'bi bi-weight-scale',
                                            'L', 'ml', 'gal' => 'bi bi-droplet',
                                            'piece', 'box', 'pack', 'set', 'dozen', 'pair' => 'bi bi-box',
                                            'm', 'cm', 'km', 'in', 'ft' => 'bi bi-rulers',
                                            'roll' => 'bi bi-minecart-loaded',
                                            default => 'bi bi-box-seam',
                                        };

                                        $stockClass = 'good';
                                        if ($product->is_low_stock && $product->current_stock > 0) {
                                            $stockClass = 'low';
                                        } elseif ($product->is_out_of_stock) {
                                            $stockClass = 'critical';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="num-cell">#{{ $product->id }}</td>
                                        <td>
                                            @if ($product->image)
                                                <img src="{{ Storage::url($product->image) }}" class="product-avatar">
                                            @else
                                                <div class="product-avatar placeholder">
                                                    <i class="bi bi-box-seam"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="fw-semibold text-dark">{{ $product->name }}</td>
                                        <td>
                                            <span class="badge-cat">
                                                {{ $product->category->name ?? 'Unassigned' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="num-cell" style="font-size: 0.75rem; gap: 0.2rem;">
                                                <i class="{{ $unitIcon }} num-icon" style="font-size: 0.7rem;"></i>
                                                {{ $unit }}
                                            </span>
                                            @if ($unit === 'roll' && ($product->default_kg_per_roll ?? 0) > 0)
                                                <div style="font-size: 0.6rem; color: var(--sale-gray-400);">
                                                    {{ number_format($product->default_kg_per_roll, 2) }} kg/roll
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="stock-indicator {{ $stockClass }}">
                                                <i class="bi bi-{{ $product->is_out_of_stock ? 'x-circle' : ($product->is_low_stock ? 'exclamation-triangle' : 'check-circle') }}"></i>
                                                {{ $product->current_stock }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($product->min_stock_alert > 0)
                                                <span class="num-cell" style="font-size: 0.75rem;">
                                                    <i class="bi bi-exclamation-triangle me-1" style="color: #f59e0b;"></i>
                                                    {{ $product->min_stock_alert }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($product->is_active)
                                                <span class="stock-badge in-stock">
                                                    <i class="bi bi-circle-fill"></i> {{ __('ui.active') }}
                                                </span>
                                            @else
                                                <span class="stock-badge out-of-stock">
                                                    <i class="bi bi-circle-fill"></i> {{ __('ui.inactive') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons justify-content-end">
                                                <button class="action-btn edit-product" data-id="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        data-category_id="{{ $product->category_id }}"
                                                        data-unit="{{ $product->unit }}"
                                                        data-default_kg_per_roll="{{ $product->default_kg_per_roll ?? '' }}"
                                                        data-min_stock_alert="{{ $product->min_stock_alert ?? 0 }}"
                                                        data-description="{{ $product->description }}"
                                                        data-is_active="{{ $product->is_active }}"
                                                        data-image="{{ $product->image }}" title="{{ __('ui.edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="action-btn text-danger delete-product"
                                                        data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                                        title="{{ __('ui.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ─── Finished Goods Tab ─── --}}
                    <div class="tab-pane fade p-3" id="finished-goods-tab" role="tabpanel">
                        <div class="table-responsive-custom">
                            <table class="table-ledger" id="finished-goods-table">
                                <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th style="width: 70px;">{{ __('ui.image') }}</th>
                                    <th>{{ __('ui.product_name') }}</th>
                                    <th>{{ __('ui.category') }}</th>
                                    <th>{{ __('ui.unit') }}</th>
                                    <th style="width: 110px;">{{ __('ui.status') }}</th>
                                    <th style="width: 100px;" class="text-end">{{ __('ui.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($finishedProducts as $product)
                                    @php
                                        $unit = $product->unit ?? 'unit';
                                        $unitIcon = match ($unit) {
                                            'kg', 'g', 'mg', 'lb', 'oz' => 'bi bi-weight-scale',
                                            'L', 'ml', 'gal' => 'bi bi-droplet',
                                            'piece', 'box', 'pack', 'set', 'dozen', 'pair' => 'bi bi-box',
                                            'm', 'cm', 'km', 'in', 'ft' => 'bi bi-rulers',
                                            default => 'bi bi-box-seam',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="num-cell">#{{ $product->id }}</td>
                                        <td>
                                            @if ($product->image)
                                                <img src="{{ Storage::url($product->image) }}" class="product-avatar">
                                            @else
                                                <div class="product-avatar placeholder">
                                                    <i class="bi bi-box-seam"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            {{ $product->name }}
                                            <span class="type-badge finished ms-1">
                                                <i class="bi bi-check-circle-fill"></i> FG
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-cat">
                                                {{ $product->category->name ?? 'Unassigned' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="num-cell" style="font-size: 0.75rem; gap: 0.2rem;">
                                                <i class="{{ $unitIcon }} num-icon" style="font-size: 0.7rem;"></i>
                                                {{ $unit }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($product->is_active)
                                                <span class="stock-badge in-stock">
                                                    <i class="bi bi-circle-fill"></i> {{ __('ui.active') }}
                                                </span>
                                            @else
                                                <span class="stock-badge out-of-stock">
                                                    <i class="bi bi-circle-fill"></i> {{ __('ui.inactive') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons justify-content-end">
                                                <button class="action-btn edit-finished-good"
                                                        data-id="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        data-category_id="{{ $product->category_id }}"
                                                        data-unit="{{ $product->unit }}"
                                                        data-description="{{ $product->description }}"
                                                        data-is_active="{{ $product->is_active }}"
                                                        data-image="{{ $product->image }}"
                                                        title="{{ __('ui.edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="action-btn text-danger delete-product"
                                                        data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                                        title="{{ __('ui.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ─── Categories Tab ─── --}}
                    <div class="tab-pane fade p-3" id="categories-tab" role="tabpanel">
                        <div class="table-responsive-custom">
                            <table class="table-ledger" id="categories-table">
                                <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>{{ __('ui.category_name') }}</th>
                                    <th>{{ __('ui.description') }}</th>
                                    <th class="text-center" style="width: 140px;">{{ __('ui.products') }}</th>
                                    <th style="width: 110px;">{{ __('ui.status') }}</th>
                                    <th style="width: 100px;" class="text-end">{{ __('ui.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($categories as $category)
                                    <tr>
                                        <td class="num-cell">#{{ $category->id }}</td>
                                        <td class="fw-semibold text-dark">{{ $category->name }}</td>
                                        <td class="text-muted text-truncate" style="max-width: 280px;">
                                            {{ $category->description ?: 'No description' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="header-badge"
                                                  style="background: var(--primary-bg); color: var(--primary);">
                                                <i class="bi bi-boxes me-1"></i>
                                                {{ $category->products_count ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($category->is_active)
                                                <span class="stock-badge in-stock">
                                                    <i class="bi bi-circle-fill"></i> {{ __('ui.active') }}
                                                </span>
                                            @else
                                                <span class="stock-badge out-of-stock">
                                                    <i class="bi bi-circle-fill"></i> {{ __('ui.inactive') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons justify-content-end">
                                                <button class="action-btn edit-category"
                                                        data-id="{{ $category->id }}" data-name="{{ $category->name }}"
                                                        data-description="{{ $category->description }}"
                                                        data-is_active="{{ $category->is_active }}" title="{{ __('ui.edit') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="action-btn text-danger delete-category"
                                                        data-id="{{ $category->id }}" data-name="{{ $category->name }}"
                                                        data-products-count="{{ $category->products_count ?? 0 }}"
                                                        title="{{ __('ui.delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    PRODUCT MODAL (Raw Materials)
    ============================================================ --}}
    <div class="modal fade" id="productModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header raw-materials">
                    <h5 class="modal-title">
                        <i class="bi bi-box-seam me-2" style="color: white;"></i>
                        <span id="productModalTitle" style="color: white;">{{ __('ui.add_raw_material') }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="product_id" id="product_id">
                    <input type="hidden" name="type" value="raw">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.product_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="product_name" required
                                       placeholder="{{ __('ui.enter_product_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.category') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2-category" name="category_id" id="product_category_id"
                                        required>
                                    <option value="">{{ __('ui.select_category_dots') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.unit') }}</label>
                                <select class="form-select" name="unit" id="product_unit">
                                    <option value="">{{ __('ui.select_unit') }}</option>
                                    <optgroup label="⚖️ Weight / Mass">
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="g">Gram (g)</option>
                                        <option value="mg">Milligram (mg)</option>
                                        <option value="lb">Pound (lb)</option>
                                        <option value="oz">Ounce (oz)</option>
                                    </optgroup>
                                    <optgroup label="💧 Volume / Liquid">
                                        <option value="L">Liter (L)</option>
                                        <option value="ml">Milliliter (ml)</option>
                                        <option value="gal">Gallon (gal)</option>
                                    </optgroup>
                                    <optgroup label="📦 Packaging / Containers">
                                        <option value="ctn">Carton (CTN)</option>
                                        <option value="box">Box</option>
                                        <option value="crate">Crate</option>
                                        <option value="pallet">{{ __('ui.pallet') }}</option>
                                        <option value="roll">Roll</option>
                                        <option value="ton">Ton</option>
                                        <option value="bundle">{{ __('ui.bundle') }}</option>
                                    </optgroup>
                                    <optgroup label="🔢 Count / Pieces">
                                        <option value="piece">Piece (PC)</option>
                                        <option value="pack">Pack</option>
                                        <option value="set">Set</option>
                                        <option value="dozen">Dozen (DZ)</option>
                                        <option value="pair">Pair (PR)</option>
                                        <option value="unit">{{ __('ui.unit') }}</option>
                                    </optgroup>
                                    <optgroup label="📏 Length / Dimension">
                                        <option value="m">Meter (m)</option>
                                        <option value="cm">Centimeter (cm)</option>
                                        <option value="mm">Millimeter (mm)</option>
                                        <option value="ft">Feet (ft)</option>
                                        <option value="in">Inch (in)</option>
                                        <option value="yd">Yard (yd)</option>
                                    </optgroup>
                                    <optgroup label="💼 Other">
                                        <option value="service">{{ __('ui.service') }}</option>
                                        <option value="project">{{ __('ui.project') }}</option>
                                        <option value="lot">Lot</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-6" id="product_kg_per_roll_wrapper" style="display:none;">
                                <label class="form-label">{{ __('ui.default_weight_per_roll') }} (kg)</label>
                                <input type="number" class="form-control" name="default_kg_per_roll"
                                       id="product_default_kg_per_roll" min="0.0001" step="0.0001"
                                       placeholder="e.g. 500">
                                <small class="text-muted">{{ __('ui.default_weight_per_roll_help') }}</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.min_stock_alert') }}</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="min_stock_alert"
                                           id="product_min_stock_alert" min="0" value="0" placeholder="0">
                                    <span class="input-group-text">
                                        <i class="bi bi-exclamation-triangle" style="color: #f59e0b;"></i>
                                    </span>
                                </div>
                                <small class="text-muted">{{ __('ui.min_stock_alert_help') }}</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.product_image') }}</label>
                                <div class="image-upload-wrapper">
                                    <input type="file" class="form-control form-control-sm" name="image"
                                           id="product_image" accept="image/*">
                                    <div id="current_image_container" class="d-none">
                                        <img id="current_image" class="current-image-thumb" src="">
                                    </div>
                                    <div id="product_image_preview_wrapper" class="d-none">
                                        <img id="product_image_preview" class="current-image-thumb" src="#">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('ui.description') }}</label>
                                <textarea class="form-control" name="description" id="product_description" rows="3"
                                          placeholder="{{ __('ui.optional_product_specs') }}"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="is_active"
                                           id="product_is_active" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark">
                                        {{ __('ui.active_status') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="productSubmitBtn">
                            <i class="bi bi-check2 me-1"></i> {{ __('ui.save_raw_material') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
    FINISHED GOODS MODAL
    ============================================================ --}}
    <div class="modal fade" id="finishedGoodsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header finished-goods">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle-fill me-2" style="color: white;"></i>
                        <span id="finishedGoodsModalTitle" style="color: white;">{{ __('ui.add_finished_good') }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="finishedGoodsForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="product_id" id="fg_product_id">
                    <input type="hidden" name="type" value="finished">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.product_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="fg_product_name" required
                                       placeholder="{{ __('ui.enter_finished_good_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.category') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2-fg-category" name="category_id" id="fg_product_category_id" required>
                                    <option value="">{{ __('ui.select_category_dots') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.unit') }}</label>
                                <select class="form-select" name="unit" id="fg_product_unit">
                                    <option value="">{{ __('ui.select_unit') }}</option>
                                    <optgroup label="⚖️ Weight / Mass">
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="g">Gram (g)</option>
                                        <option value="mg">Milligram (mg)</option>
                                        <option value="lb">Pound (lb)</option>
                                        <option value="oz">Ounce (oz)</option>
                                    </optgroup>
                                    <optgroup label="💧 Volume / Liquid">
                                        <option value="L">Liter (L)</option>
                                        <option value="ml">Milliliter (ml)</option>
                                        <option value="gal">Gallon (gal)</option>
                                    </optgroup>
                                    <optgroup label="📦 Packaging / Containers">
                                        <option value="ctn">Carton (CTN)</option>
                                        <option value="box">Box</option>
                                        <option value="crate">Crate</option>
                                        <option value="pallet">{{ __('ui.pallet') }}</option>
                                        <option value="roll">Roll</option>
                                        <option value="ton">Ton</option>
                                        <option value="bundle">{{ __('ui.bundle') }}</option>
                                    </optgroup>
                                    <optgroup label="🔢 Count / Pieces">
                                        <option value="piece">Piece (PC)</option>
                                        <option value="pack">Pack</option>
                                        <option value="set">Set</option>
                                        <option value="dozen">Dozen (DZ)</option>
                                        <option value="pair">Pair (PR)</option>
                                        <option value="unit">{{ __('ui.unit') }}</option>
                                    </optgroup>
                                    <optgroup label="📏 Length / Dimension">
                                        <option value="m">Meter (m)</option>
                                        <option value="cm">Centimeter (cm)</option>
                                        <option value="mm">Millimeter (mm)</option>
                                        <option value="ft">Feet (ft)</option>
                                        <option value="in">Inch (in)</option>
                                        <option value="yd">Yard (yd)</option>
                                    </optgroup>
                                    <optgroup label="💼 Other">
                                        <option value="service">{{ __('ui.service') }}</option>
                                        <option value="project">{{ __('ui.project') }}</option>
                                        <option value="lot">Lot</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.product_image') }}</label>
                                <div class="image-upload-wrapper">
                                    <input type="file" class="form-control form-control-sm" name="image"
                                           id="fg_product_image" accept="image/*">
                                    <div id="fg_current_image_container" class="d-none">
                                        <img id="fg_current_image" class="current-image-thumb" src="">
                                    </div>
                                    <div id="fg_product_image_preview_wrapper" class="d-none">
                                        <img id="fg_product_image_preview" class="current-image-thumb" src="#">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('ui.description') }}</label>
                                <textarea class="form-control" name="description" id="fg_product_description" rows="3"
                                          placeholder="{{ __('ui.optional_product_specs') }}"></textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="is_active"
                                           id="fg_product_is_active" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark">
                                        {{ __('ui.active_status') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-success" id="fgProductSubmitBtn">
                            <i class="bi bi-check2 me-1"></i> {{ __('ui.save_finished_good') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
    CATEGORY MODAL
    ============================================================ --}}
    <div class="modal fade" id="categoryModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-tag me-2" style="color: white;"></i>
                        <span id="categoryModalTitle" style="color: white;">{{ __('ui.add_category') }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="categoryForm" method="POST">
                    @csrf
                    <input type="hidden" name="category_id" id="category_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.category_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="category_name" required
                                   placeholder="{{ __('ui.enter_category_name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.description') }}</label>
                            <textarea class="form-control" name="description" id="category_description" rows="3"
                                      placeholder="Optional category description..."></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" id="category_is_active"
                                   value="1" checked>
                            <label class="form-check-label fw-semibold text-dark">
                                {{ __('ui.active_status') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="categorySubmitBtn">
                            <i class="bi bi-check2 me-1"></i> {{ __('ui.save_category') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // ─── DataTables ───
            const tableOpts = {
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [
                    [0, 'desc']
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search entries...',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries found',
                    infoFiltered: '(filtered from _MAX_ total)',
                }
            };

            $('#products-table').DataTable({
                ...tableOpts,
                columnDefs: [{
                    orderable: false,
                    targets: [1, 5, 6, 8]
                }]
            });

            $('#finished-goods-table').DataTable({
                ...tableOpts,
                columnDefs: [{
                    orderable: false,
                    targets: [1, 6]
                }]
            });

            $('#categories-table').DataTable({
                ...tableOpts,
                columnDefs: [{
                    orderable: false,
                    targets: [5]
                }]
            });

            // ─── Select2 ───
            $('.select2-category').select2({
                dropdownParent: $('#productModal'),
                width: '100%',
                placeholder: @json(__('ui.select_category_dots')),
                allowClear: true
            });

            $('.select2-fg-category').select2({
                dropdownParent: $('#finishedGoodsModal'),
                width: '100%',
                placeholder: @json(__('ui.select_category_dots')),
                allowClear: true
            });

            // ─── Image Preview (Raw Materials) ───
            $('#product_image').on('change', function(e) {
                const wrapper = $('#product_image_preview_wrapper');
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        $('#product_image_preview').attr('src', evt.target.result);
                        wrapper.removeClass('d-none');
                    }
                    reader.readAsDataURL(e.target.files[0]);
                } else {
                    wrapper.addClass('d-none');
                }
            });

            // ─── Image Preview (Finished Goods) ───
            $('#fg_product_image').on('change', function(e) {
                const wrapper = $('#fg_product_image_preview_wrapper');
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        $('#fg_product_image_preview').attr('src', evt.target.result);
                        wrapper.removeClass('d-none');
                    }
                    reader.readAsDataURL(e.target.files[0]);
                } else {
                    wrapper.addClass('d-none');
                }
            });

            // ─── Form Resets ───
            window.resetProductForm = function() {
                $('#productForm')[0].reset();
                $('#product_id').val('');
                $('#productModalTitle').text(@json(__('ui.add_raw_material')));
                $('#product_image_preview_wrapper').addClass('d-none');
                $('#current_image_container').addClass('d-none');
                $('.select2-category').val('').trigger('change');
                $('#product_is_active').prop('checked', true);
                $('#product_min_stock_alert').val(0);
                $('#product_kg_per_roll_wrapper').hide();
                $('#product_default_kg_per_roll').val('');
                $('#productSubmitBtn').html('<i class="bi bi-check2 me-1"></i> ' + @json(__('ui.save_raw_material')));
                $('#productSubmitBtn').prop('disabled', false);
                $('#product_image').val('');
            };

            window.resetFinishedGoodsForm = function() {
                $('#finishedGoodsForm')[0].reset();
                $('#fg_product_id').val('');
                $('#finishedGoodsModalTitle').text(@json(__('ui.add_finished_good')));
                $('#fg_product_image_preview_wrapper').addClass('d-none');
                $('#fg_current_image_container').addClass('d-none');
                $('.select2-fg-category').val('').trigger('change');
                $('#fg_product_is_active').prop('checked', true);
                $('#fgProductSubmitBtn').html('<i class="bi bi-check2 me-1"></i> ' + @json(__('ui.save_finished_good')));
                $('#fgProductSubmitBtn').prop('disabled', false);
                $('#fg_product_image').val('');
            };

            window.resetCategoryForm = function() {
                $('#categoryForm')[0].reset();
                $('#category_id').val('');
                $('#categoryModalTitle').text(@json(__('ui.add_category')));
                $('#category_is_active').prop('checked', true);
                $('#categorySubmitBtn').html('<i class="bi bi-check2 me-1"></i> ' + @json(__('ui.save_category')));
                $('#categorySubmitBtn').prop('disabled', false);
            };

            // ─── Toggle default kg/roll field based on unit ───
            function toggleProductKgPerRoll() {
                var isRoll = $('#product_unit').val() === 'roll';
                if (isRoll) {
                    $('#product_kg_per_roll_wrapper').show();
                } else {
                    $('#product_kg_per_roll_wrapper').hide();
                    $('#product_default_kg_per_roll').val('');
                }
            }
            $('#product_unit').on('change', toggleProductKgPerRoll);

            // ─── Edit Product (Raw Materials) ───
            $(document).on('click', '.edit-product', function() {
                resetProductForm();
                $('#productModalTitle').text(@json(__('ui.edit_raw_material')));
                $('#product_id').val($(this).data('id'));
                $('#product_name').val($(this).data('name'));
                $('#product_category_id').val($(this).data('category_id')).trigger('change');
                $('#product_unit').val($(this).data('unit'));
                toggleProductKgPerRoll();
                var defKg = $(this).data('default_kg_per_roll');
                if (defKg && defKg !== '' && defKg !== 0) {
                    $('#product_default_kg_per_roll').val(defKg);
                }
                $('#product_min_stock_alert').val($(this).data('min_stock_alert') || 0);
                $('#product_description').val($(this).data('description'));
                $('#product_is_active').prop('checked', $(this).data('is_active') == 1);

                const img = $(this).data('image');
                if (img) {
                    $('#current_image').attr('src', '/storage/' + img);
                    $('#current_image_container').removeClass('d-none');
                }
                $('#productModal').modal('show');
            });

            // ─── Edit Finished Good ───
            $(document).on('click', '.edit-finished-good', function() {
                resetFinishedGoodsForm();
                $('#finishedGoodsModalTitle').text(@json(__('ui.edit_finished_good')));
                $('#fg_product_id').val($(this).data('id'));
                $('#fg_product_name').val($(this).data('name'));
                $('#fg_product_category_id').val($(this).data('category_id')).trigger('change');
                $('#fg_product_unit').val($(this).data('unit'));
                $('#fg_product_description').val($(this).data('description'));
                $('#fg_product_is_active').prop('checked', $(this).data('is_active') == 1);

                const img = $(this).data('image');
                if (img) {
                    $('#fg_current_image').attr('src', '/storage/' + img);
                    $('#fg_current_image_container').removeClass('d-none');
                }
                $('#finishedGoodsModal').modal('show');
            });

            // ─── Edit Category ───
            $(document).on('click', '.edit-category', function() {
                resetCategoryForm();
                $('#categoryModalTitle').text(@json(__('ui.edit_category')));
                $('#category_id').val($(this).data('id'));
                $('#category_name').val($(this).data('name'));
                $('#category_description').val($(this).data('description'));
                $('#category_is_active').prop('checked', $(this).data('is_active') == 1);
                $('#categoryModal').modal('show');
            });

            // ─── Product Form Submit (Raw Materials) ───
            $('#productForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#product_id').val();
                let url = '/admin/products';
                const data = new FormData(this);

                if (id && id !== '') {
                    url += '/' + id;
                    data.append('_method', 'PUT');
                }

                $('#productSubmitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            $('#productSubmitBtn').prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Save Raw Material');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save product.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        $('#productSubmitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Save Raw Material');
                    }
                });
            });

            // ─── Finished Goods Form Submit ───
            $('#finishedGoodsForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#fg_product_id').val();
                let url = '/admin/products';
                const data = new FormData(this);

                if (id && id !== '') {
                    url += '/' + id;
                    data.append('_method', 'PUT');
                }

                $('#fgProductSubmitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            $('#fgProductSubmitBtn').prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Save Finished Good');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save finished good.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        $('#fgProductSubmitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Save Finished Good');
                    }
                });
            });

            // ─── Category Form Submit ───
            $('#categoryForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#category_id').val();
                let url = '/admin/categories';
                const formData = $(this).serialize();

                const isUpdate = id && id !== '';
                let data = formData;

                if (isUpdate) {
                    url += '/' + id;
                    data += '&_method=PUT';
                }

                $('#categorySubmitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            $('#categorySubmitBtn').prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Save Category');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save category.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        $('#categorySubmitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Save Category');
                    }
                });
            });

            // ─── Delete Handlers ───
            function confirmDelete(url, name, type) {
                Swal.fire({
                    title: `Delete ${type}?`,
                    text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', res.message || 'Failed to delete.', 'error');
                                }
                            },
                            error: function(xhr) {
                                let errorMessage = 'Failed to delete.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire('Error', errorMessage, 'error');
                            }
                        });
                    }
                });
            }

            $(document).on('click', '.delete-product', function() {
                confirmDelete(`/admin/products/${$(this).data('id')}`, $(this).data('name'), 'Product');
            });

            $(document).on('click', '.delete-category', function() {
                const count = $(this).data('products-count');
                if (count > 0) {
                    Swal.fire({
                        title: @json(__('ui.cannot_delete')),
                        text: `This category has ${count} product(s) assigned. Please reassign or delete them first.`,
                        icon: 'error',
                        confirmButtonColor: '#4F46E5'
                    });
                    return;
                }
                confirmDelete(`/admin/categories/${$(this).data('id')}`, $(this).data('name'), 'Category');
            });

            // ─── LOW STOCK NOTIFICATION SYSTEM ───

            // Close notification
            window.closeLowStockNotification = function() {
                $('#lowStockNotification').fadeOut(300);
                localStorage.setItem('lowStockClosed', Date.now());
            };

            // Show low stock notification
            function showLowStockNotification(products) {
                if (products.length === 0) {
                    $('#lowStockNotification').hide();
                    return;
                }

                let html = '';
                const criticalProducts = products.filter(p => p.current_stock <= 0);
                const lowProducts = products.filter(p => p.current_stock > 0 && p.current_stock <= p.min_stock_alert);

                // Show critical products first
                if (criticalProducts.length > 0) {
                    html += `<div class="fw-bold text-danger mb-1">⚠️ Out of Stock (${criticalProducts.length})</div>`;
                    criticalProducts.slice(0, 5).forEach(p => {
                        html += `
                            <div class="low-stock-item">
                                <span>${p.name}</span>
                                <span class="stock-badge danger">0 ${p.unit}</span>
                            </div>
                        `;
                    });
                    if (criticalProducts.length > 5) {
                        html += `<small class="text-muted">+ ${criticalProducts.length - 5} more...</small>`;
                    }
                }

                // Show low stock products
                if (lowProducts.length > 0) {
                    if (criticalProducts.length > 0) html += '<hr class="my-1">';
                    html += `<div class="fw-bold text-warning mb-1">⚠️ Low Stock (${lowProducts.length})</div>`;
                    lowProducts.slice(0, 5).forEach(p => {
                        html += `
                            <div class="low-stock-item">
                                <span>${p.name}</span>
                                <span class="stock-badge warning">${p.current_stock} ${p.unit}</span>
                            </div>
                        `;
                    });
                    if (lowProducts.length > 5) {
                        html += `<small class="text-muted">+ ${lowProducts.length - 5} more...</small>`;
                    }
                }

                $('#lowStockProductsList').html(html);

                // Determine alert type
                const hasCritical = criticalProducts.length > 0;
                const alertDiv = $('#lowStockNotification .alert');
                alertDiv.removeClass('alert-warning alert-danger');
                alertDiv.addClass(hasCritical ? 'alert-danger' : 'alert-warning');

                // Update icon
                const icon = alertDiv.find('.bi-exclamation-triangle-fill');
                icon.css('color', hasCritical ? '#ef4444' : '#f59e0b');

                $('#lowStockNotification').fadeIn(300);
            }

            // Fetch low stock products
            function fetchLowStockProducts() {
                const closedAt = localStorage.getItem('lowStockClosed');
                if (closedAt && (Date.now() - parseInt(closedAt)) < 300000) {
                    return;
                }

                $.ajax({
                    url: '/admin/products/low-stock',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.count > 0) {
                            showLowStockNotification(response.products);
                        } else {
                            $('#lowStockNotification').fadeOut(300);
                        }
                    },
                    error: function() {
                        console.log('Failed to fetch low stock products');
                    }
                });
            }

            // Initial load
            setTimeout(fetchLowStockProducts, 1000);
            setInterval(fetchLowStockProducts, 600000);

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    fetchLowStockProducts();
                }
            });
        });
    </script>
@endsection
