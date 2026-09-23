<div id="simpleBomBuilder">
    <div class="alert alert-success border-0 shadow-sm mb-4" style="background:#ecfdf5;color:#065f46;">
        <div class="d-flex gap-2 align-items-start">
            <i class="bi bi-magic fs-5"></i>
            <div>
                <strong>Quick BOM Builder</strong>
                <div class="small mt-1">
                    Enter the carton dimensions, choose a board preset and printing option.
                    Paper layers, GSM, adhesive, landed material rates, wastage and the standard
                    40% commercial work/profit are calculated automatically.
                </div>
            </div>
        </div>
    </div>

    @if(($boardProfiles ?? collect())->isEmpty())
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            No board presets are available. Run the standard seeders or use Advanced BOM Builder below.
        </div>
    @else
        <form id="simpleBomForm" novalidate>
            @csrf

            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-box-seam"></i> Carton Specification
                    <span class="badge bg-success ms-2">Recommended</span>
                </div>

                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label">Finished Product <span class="text-danger">*</span></label>
                        <select class="form-select" name="product_id" id="simpleProductId" required>
                            <option value="">Select product...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">The same product can have multiple size-specific BOMs.</small>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Board Preset <span class="text-danger">*</span></label>
                        <select class="form-select" name="board_profile_id" id="simpleBoardProfile" required>
                            <option value="">Select board preset...</option>
                            @foreach($boardProfiles as $profile)
                                <option value="{{ $profile->id }}"
                                        data-ply="{{ $profile->ply }}"
                                        data-flute="{{ $profile->flute_type }}"
                                        data-wastage="{{ $profile->wastage_percentage }}">
                                    {{ $profile->name }} · {{ $profile->ply }} Ply{{ $profile->flute_type ? ' · '.$profile->flute_type : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Dimension Unit</label>
                        <select class="form-select" name="dimension_unit" id="simpleDimensionUnit">
                            @foreach($simpleBomOptions['units'] as $unit)
                                <option value="{{ $unit }}" @selected($unit === 'mm')>{{ strtoupper($unit) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Box Style</label>
                        <select class="form-select" name="box_style" id="simpleBoxStyle">
                            @foreach($simpleBomOptions['box_styles'] as $style)
                                <option value="{{ $style['value'] }}"
                                    @selected($style['value'] === $simpleBomOptions['default_box_style'])>
                                    {{ $style['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Printing</label>
                        <select class="form-select" name="printing_option" id="simplePrintingOption">
                            @foreach($simpleBomOptions['printing'] as $printing)
                                <option value="{{ $printing['value'] }}"
                                        data-default-cost="{{ $printing['print_cost_afn'] }}">
                                    {{ $printing['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Print Charge / Carton (AFN)</label>
                        <input type="number" class="form-control" name="print_cost_afn"
                               id="simplePrintCost" min="0" step="0.0001" value="0">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label">Standard Work / Profit</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="work_percentage"
                                   value="{{ $simpleBomOptions['standard_work_percentage'] }}" readonly>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Company commercial standard; not production cost.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Wastage</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="wastage_percentage"
                                   id="simpleWastage"
                                   value="{{ $simpleBomOptions['default_wastage_percentage'] }}"
                                   min="0" max="100" step="0.1">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Filled from the selected board preset; adjustable when needed.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">USD → AFN</label>
                        <input type="number" class="form-control" name="exchange_rate"
                               value="{{ $exchangeRate ?? 85 }}" min="0.0001" step="0.0001">
                        <small class="text-muted">Used for latest landed material costs.</small>
                    </div>
                </div>

                <input type="hidden" name="profit_margin_percentage" value="0">
            </div>

            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div class="section-title border-0 mb-0 pb-0">
                        <i class="bi bi-rulers"></i> Sizes
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addSimpleSize">
                        <i class="bi bi-plus-lg me-1"></i> Add Another Size
                    </button>
                </div>

                <div id="simpleSizeRows"></div>

                <template id="simpleSizeTemplate">
                    <div class="simple-size-row border rounded-3 p-3 mb-3 bg-light position-relative">
                        <button type="button" class="btn btn-sm btn-link text-danger position-absolute top-0 end-0 mt-1 me-1 remove-simple-size"
                                title="Remove size">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3">
                                <label class="form-label">Size / BOM Name</label>
                                <input type="text" class="form-control size-name" placeholder="e.g. 200ml Carton">
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <label class="form-label">Length <span class="text-danger">*</span></label>
                                <input type="number" class="form-control size-length" min="0.0001" step="0.01" required>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <label class="form-label">Width <span class="text-danger">*</span></label>
                                <input type="number" class="form-control size-width" min="0.0001" step="0.01" required>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <label class="form-label">Height <span class="text-danger">*</span></label>
                                <input type="number" class="form-control size-height" min="0.0001" step="0.01" required>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary" id="previewSimpleBom">
                        <i class="bi bi-calculator me-1"></i> Calculate Preview
                    </button>
                    <button type="button" class="btn btn-primary" id="saveSimpleBom">
                        <i class="bi bi-check2-circle me-1"></i> Create BOM
                    </button>
                </div>
            </div>

            <div class="form-section d-none" id="simplePreviewSection">
                <div class="section-title">
                    <i class="bi bi-receipt"></i> BOM Preview
                </div>
                <div id="simplePreviewResults"></div>
                <details class="mt-3">
                    <summary class="fw-semibold text-primary" style="cursor:pointer;">
                        Advanced calculation details
                    </summary>
                    <div class="small text-muted mt-2">
                        Shows generated raw-material rows, GSM, physical kg and landed-cost basis.
                    </div>
                    <div id="simplePreviewAdvanced" class="mt-2"></div>
                </details>
            </div>
        </form>
    @endif
</div>

<div class="d-flex align-items-center gap-3 my-4">
    <hr class="flex-grow-1">
    <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleAdvancedBom">
        <i class="bi bi-sliders me-1"></i> Advanced BOM Builder
    </button>
    <hr class="flex-grow-1">
</div>
