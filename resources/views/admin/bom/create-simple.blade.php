@extends('layouts.admin.base')

@section('title', 'Create BOM')

@section('css')
    <style>
        .simple-bom-page { max-width: 1180px; margin: 0 auto; }
        .simple-bom-hero {
            display:flex; align-items:center; justify-content:space-between; gap:1rem;
            padding:1.15rem 1.25rem; margin-bottom:1rem; border:1px solid #e2e8f0;
            border-radius:16px; background:linear-gradient(135deg,#fff,#f8fafc);
        }
        .simple-bom-hero h1 { margin:0; font-size:1.45rem; font-weight:800; color:#0f172a; }
        .simple-bom-hero p { margin:.25rem 0 0; color:#64748b; font-size:.8rem; }
        .simple-bom-card {
            border:1px solid #e2e8f0; border-radius:14px; background:#fff;
            box-shadow:0 8px 28px rgba(15,23,42,.045); overflow:hidden; margin-bottom:1rem;
        }
        .simple-bom-card .card-head {
            display:flex; align-items:center; justify-content:space-between; gap:.75rem;
            padding:.8rem 1rem; background:#f8fafc; border-bottom:1px solid #e2e8f0;
        }
        .simple-bom-card .card-head strong { color:#0f172a; font-size:.82rem; }
        .simple-bom-card .card-body { padding:1rem; }
        .simple-bom-label { font-size:.7rem; font-weight:700; color:#475569; margin-bottom:.35rem; }
        .simple-bom-page .form-control, .simple-bom-page .form-select {
            min-height:42px; border-radius:9px; border-color:#dbe3ee; font-size:.8rem;
        }
        .simple-bom-note {
            padding:.75rem .85rem; border:1px solid #bfdbfe; border-radius:10px;
            background:#eff6ff; color:#1e3a8a; font-size:.74rem; line-height:1.45;
        }
        .simple-bom-summary {
            display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.7rem;
        }
        .simple-bom-stat {
            padding:.8rem; border:1px solid #e2e8f0; border-radius:11px; background:#fff;
        }
        .simple-bom-stat .k { color:#64748b; font-size:.63rem; font-weight:700; text-transform:uppercase; }
        .simple-bom-stat .v { margin-top:.2rem; color:#0f172a; font-size:1rem; font-weight:800; }
        .simple-bom-stat.primary { background:#eef2ff; border-color:#c7d2fe; }
        .simple-bom-stat.success { background:#ecfdf5; border-color:#a7f3d0; }
        .simple-bom-table th {
            color:#64748b; background:#f8fafc; font-size:.61rem; text-transform:uppercase;
            letter-spacing:.04em; white-space:nowrap;
        }
        .simple-bom-table td { font-size:.72rem; vertical-align:middle; }
        .simple-bom-actions { display:flex; gap:.5rem; justify-content:flex-end; flex-wrap:wrap; }
        .simple-bom-actions .btn { min-height:40px; border-radius:9px; font-weight:700; font-size:.75rem; }
        .profile-description { color:#64748b; font-size:.68rem; margin-top:.3rem; line-height:1.35; }
        .preview-empty { padding:2rem 1rem; text-align:center; color:#94a3b8; font-size:.8rem; }
        .advanced-box { padding:.85rem; border:1px dashed #cbd5e1; border-radius:10px; background:#f8fafc; }
        @media(max-width:900px){ .simple-bom-summary{grid-template-columns:repeat(2,minmax(0,1fr));} }
        @media(max-width:650px){
            .simple-bom-hero{align-items:flex-start;flex-direction:column;}
            .simple-bom-summary{grid-template-columns:1fr 1fr;}
            .simple-bom-actions{width:100%;}
            .simple-bom-actions .btn{flex:1;}
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="simple-bom-page">
        <div class="simple-bom-hero">
            <div>
                <h1><i class="bi bi-box-seam me-2 text-primary"></i>Create Carton BOM</h1>
                <p>Enter what the client actually knows. Paper layers, adhesive recipe, landed rates and the 40% commercial work are calculated automatically.</p>
            </div>
            <a href="{{ route('bom.create-advanced') }}" class="btn btn-outline-secondary">
                <i class="bi bi-sliders me-1"></i> Advanced Builder
            </a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('bom.store-simple') }}" id="simpleBomForm">
            @csrf

            <div class="simple-bom-card">
                <div class="card-head">
                    <strong><i class="bi bi-1-circle me-1 text-primary"></i> Carton & dimensions</strong>
                    <span class="badge bg-light text-dark border">Normal mode</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <label class="simple-bom-label">Finished Product <span class="text-danger">*</span></label>
                            <select class="form-select @error('product_id') is-invalid @enderror" name="product_id" id="simpleProduct" required>
                                <option value="">Select finished product…</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected((string) old('product_id', $simpleBomDefaults['product_id'] ?? '') === (string) $product->id)>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-lg-2 col-sm-4">
                            <label class="simple-bom-label">Length <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="length" id="simpleLength" step="0.01" min="0.01" value="{{ old('length') }}" required>
                        </div>
                        <div class="col-lg-2 col-sm-4">
                            <label class="simple-bom-label">Width <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="width" id="simpleWidth" step="0.01" min="0.01" value="{{ old('width') }}" required>
                        </div>
                        <div class="col-lg-2 col-sm-4">
                            <label class="simple-bom-label">Height <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="height" id="simpleHeight" step="0.01" min="0.01" value="{{ old('height') }}" required>
                        </div>
                        <div class="col-lg-1 col-sm-4">
                            <label class="simple-bom-label">Unit</label>
                            <select class="form-select" name="dimension_unit" id="simpleUnit">
                                @foreach($lengthUnits as $unit)
                                    <option value="{{ $unit }}" @selected(old('dimension_unit', $simpleBomDefaults['dimension_unit'] ?? 'inch') === $unit)>{{ strtoupper($unit) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="simple-bom-card">
                <div class="card-head">
                    <strong><i class="bi bi-2-circle me-1 text-primary"></i> Board & printing</strong>
                    <span class="text-muted small">Choose standards instead of rebuilding material rows</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="simple-bom-label">Board Profile <span class="text-danger">*</span></label>
                            <select class="form-select" name="board_profile_id" id="simpleBoardProfile" required>
                                <option value="">Select board profile…</option>
                                @foreach($boardProfiles as $profile)
                                    <option value="{{ $profile->id }}"
                                            data-ply="{{ $profile->ply }}"
                                            data-flute="{{ $profile->flute_type }}"
                                            data-waste="{{ $profile->wastage_percentage }}"
                                            data-description="{{ $profile->description }}"
                                            @selected((string) old('board_profile_id', $simpleBomDefaults['board_profile_id'] ?? '') === (string) $profile->id)>
                                        {{ $profile->name }} · {{ $profile->ply }} Ply{{ $profile->flute_type ? ' · '.$profile->flute_type : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="profile-description" id="profileDescription">The profile controls paper materials, GSM, ply and flute configuration.</div>
                        </div>
                        <div class="col-lg-3">
                            <label class="simple-bom-label">Printing</label>
                            <select class="form-select" name="printing_option" id="simplePrinting">
                                @foreach($printingOptions as $key => $option)
                                    <option value="{{ $key }}" @selected(old('printing_option', $simpleBomDefaults['printing_option'] ?? 'none') === $key)>
                                        {{ $option['label'] ?? ucfirst($key) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="simple-bom-label">Customer / Internal Description</label>
                            <input type="text" class="form-control" name="quotation_description" value="{{ old('quotation_description') }}" maxlength="2000" placeholder="Optional">
                        </div>
                    </div>

                    <div class="simple-bom-note mt-3">
                        <i class="bi bi-magic me-1"></i>
                        <strong>Automatic:</strong> paper rows, GSM/layers, reel dimensions, current landed AFN/kg rates, adhesive recipe, {{ number_format($defaultWastagePercentage, 1) }}% default wastage and {{ number_format($defaultWorkPercentage, 1) }}% Standard Work / Profit.
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#simpleAdvanced">
                            <i class="bi bi-gear me-1"></i> Advanced overrides
                        </button>
                        <div class="collapse mt-2" id="simpleAdvanced">
                            <div class="advanced-box">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="simple-bom-label">Box Style</label>
                                        <select class="form-select" name="box_style" id="simpleBoxStyle">
                                            @foreach($boxStyles as $key => $style)
                                                <option value="{{ $key }}" @selected(old('box_style', $simpleBomDefaults['box_style'] ?? config('carton.default_box_style', 'RSC')) === $key)>
                                                    {{ $style['label'] ?? $key }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="simple-bom-label">Wastage %</label>
                                        <input type="number" class="form-control" name="wastage_percentage" id="simpleWaste" value="{{ old('wastage_percentage', $simpleBomDefaults['wastage_percentage'] ?? $defaultWastagePercentage) }}" min="0" max="100" step="0.1">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="simple-bom-label">Standard Work / Profit %</label>
                                        <input type="number" class="form-control" name="work_percentage" id="simpleWork" value="{{ old('work_percentage', $simpleBomDefaults['work_percentage'] ?? $defaultWorkPercentage) }}" min="0" step="0.1">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="simple-bom-label">Custom Print Cost (AFN)</label>
                                        <input type="number" class="form-control" name="print_cost_afn" id="simplePrintCost" value="{{ old('print_cost_afn', $simpleBomDefaults['print_cost_afn'] ?? '') }}" min="0" step="0.01" placeholder="Use printing preset">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="simple-bom-card">
                <div class="card-head">
                    <strong><i class="bi bi-calculator me-1 text-primary"></i> Calculation preview</strong>
                    <span class="text-muted small">Same calculation engine used by quotations and production</span>
                </div>
                <div class="card-body">
                    <div id="simplePreviewEmpty" class="preview-empty">
                        <i class="bi bi-calculator fs-2 d-block mb-2"></i>
                        Enter dimensions and choose a board profile, then click Calculate.
                    </div>

                    <div id="simplePreview" style="display:none;">
                        <div class="simple-bom-summary">
                            <div class="simple-bom-stat">
                                <div class="k">Paper / Carton</div>
                                <div class="v" id="previewPaper">0 kg</div>
                            </div>
                            <div class="simple-bom-stat">
                                <div class="k">Adhesive / Carton</div>
                                <div class="v" id="previewAdhesive">0 kg</div>
                            </div>
                            <div class="simple-bom-stat">
                                <div class="k">Physical Material Cost</div>
                                <div class="v" id="previewPhysical">؋0.00</div>
                            </div>
                            <div class="simple-bom-stat">
                                <div class="k">Standard Work / Profit</div>
                                <div class="v" id="previewWork">؋0.00</div>
                            </div>
                            <div class="simple-bom-stat">
                                <div class="k">Print</div>
                                <div class="v" id="previewPrint">؋0.00</div>
                            </div>
                            <div class="simple-bom-stat success">
                                <div class="k">Standard Customer Rate</div>
                                <div class="v" id="previewSelling">؋0.00</div>
                            </div>
                            <div class="simple-bom-stat">
                                <div class="k">Reel / Blank</div>
                                <div class="v" id="previewReel">—</div>
                            </div>
                            <div class="simple-bom-stat primary">
                                <div class="k">Stock</div>
                                <div class="v" id="previewStock">—</div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#technicalPreview">
                                <i class="bi bi-list-check me-1"></i> Technical details
                            </button>
                            <div class="collapse mt-2" id="technicalPreview">
                                <div class="table-responsive border rounded-3">
                                    <table class="table table-sm simple-bom-table mb-0">
                                        <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Type</th>
                                            <th class="text-end">GSM</th>
                                            <th class="text-end">Layer</th>
                                            <th class="text-end">Kg / Carton</th>
                                            <th class="text-end">Kg incl. waste</th>
                                            <th class="text-end">Landed AFN/kg</th>
                                        </tr>
                                        </thead>
                                        <tbody id="technicalRows"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="simplePreviewError" class="alert alert-danger mt-3 mb-0" style="display:none;"></div>
                </div>
            </div>

            <div class="simple-bom-actions mb-4">
                <a href="{{ route('bom.index') }}" class="btn btn-light border">Cancel</a>
                <button type="button" class="btn btn-outline-primary" id="calculateSimpleBom">
                    <i class="bi bi-calculator me-1"></i> Calculate
                </button>
                <button type="submit" class="btn btn-outline-primary" name="save_action" value="another">
                    <i class="bi bi-copy me-1"></i> Save & Add Another Size
                </button>
                <button type="submit" class="btn btn-primary" id="saveSimpleBom" name="save_action" value="save">
                    <i class="bi bi-check2-circle me-1"></i> Calculate & Save BOM
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('simpleBomForm');
    const calculateButton = document.getElementById('calculateSimpleBom');
    const profile = document.getElementById('simpleBoardProfile');
    const profileDescription = document.getElementById('profileDescription');
    let previewValid = false;

    function updateProfileHelp() {
        const option = profile.options[profile.selectedIndex];
        if (!option || !option.value) {
            profileDescription.textContent = 'The profile controls paper materials, GSM, ply and flute configuration.';
            return;
        }

        const parts = [];
        if (option.dataset.ply) parts.push(option.dataset.ply + ' ply');
        if (option.dataset.flute) parts.push(option.dataset.flute + ' flute');
        if (option.dataset.waste) parts.push(option.dataset.waste + '% waste');

        profileDescription.textContent =
            (option.dataset.description || 'Configured board standard') +
            (parts.length ? ' · ' + parts.join(' · ') : '');

        document.getElementById('simpleWaste').value = option.dataset.waste || {{ json_encode($defaultWastagePercentage) }};
    }

    function payload() {
        const data = new FormData(form);
        return new URLSearchParams(data);
    }

    function n(value, digits = 4) {
        const number = Number(value || 0);
        return Number.isFinite(number) ? number.toFixed(digits) : '0.0000';
    }

    async function calculate() {
        previewValid = false;
        document.getElementById('simplePreviewError').style.display = 'none';

        const requiredIds = ['simpleProduct','simpleLength','simpleWidth','simpleHeight','simpleBoardProfile'];
        const missing = requiredIds.some(id => {
            const el = document.getElementById(id);
            return !el || !String(el.value || '').trim();
        });

        if (missing) {
            document.getElementById('simplePreviewError').textContent = 'Select a product/profile and enter all three dimensions first.';
            document.getElementById('simplePreviewError').style.display = '';
            return false;
        }

        calculateButton.disabled = true;
        calculateButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Calculating…';

        try {
            const response = await fetch(@json(route('bom.preview-simple')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: payload().toString()
            });

            const json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Could not calculate the BOM.');
            }

            const d = json.data;
            document.getElementById('previewPaper').textContent = n(d.paper.physical_kg_per_unit, 6) + ' kg';
            document.getElementById('previewAdhesive').textContent = n(d.adhesive.kg_per_unit, 6) + ' kg';
            document.getElementById('previewPhysical').textContent = '؋' + n(d.physical.material_cost_afn_per_unit, 2);
            document.getElementById('previewWork').textContent = '؋' + n(d.commercial.work_profit_afn_per_unit, 2);
            document.getElementById('previewPrint').textContent = '؋' + n(d.commercial.print_cost_afn_per_unit, 2);
            document.getElementById('previewSelling').textContent = '؋' + n(d.commercial.selling_price_afn_per_unit, 2);
            document.getElementById('previewReel').textContent =
                n(d.spec.reel_length_inch, 2) + ' × ' + n(d.spec.reel_height_inch, 2) + ' in';
            document.getElementById('previewStock').textContent =
                d.shortages.has_shortage ? 'Shortage' : 'Available';
            document.getElementById('previewStock').className =
                'v ' + (d.shortages.has_shortage ? 'text-danger' : 'text-success');

            const rows = (d.rows || []).map(row => `
                <tr>
                    <td><strong>${row.material_name || '—'}</strong></td>
                    <td>${row.component_type || '—'}</td>
                    <td class="text-end">${row.paper_gsm || '—'}</td>
                    <td class="text-end">${row.multiplication_layer || '—'}</td>
                    <td class="text-end">${n(row.kg_per_unit, 6)}</td>
                    <td class="text-end">${n(row.kg_with_wastage, 6)}</td>
                    <td class="text-end">${n(row.cost_per_unit_afn, 4)}</td>
                </tr>
            `).join('');

            document.getElementById('technicalRows').innerHTML = rows ||
                '<tr><td colspan="7" class="text-center text-muted py-3">No technical rows generated.</td></tr>';

            document.getElementById('simplePreviewEmpty').style.display = 'none';
            document.getElementById('simplePreview').style.display = '';
            previewValid = true;
            return true;
        } catch (error) {
            document.getElementById('simplePreviewError').textContent = error.message;
            document.getElementById('simplePreviewError').style.display = '';
            return false;
        } finally {
            calculateButton.disabled = false;
            calculateButton.innerHTML = '<i class="bi bi-calculator me-1"></i> Calculate';
        }
    }

    profile.addEventListener('change', function () {
        updateProfileHelp();
        previewValid = false;
    });

    ['simpleProduct','simpleLength','simpleWidth','simpleHeight','simpleUnit','simplePrinting','simpleBoxStyle','simpleWaste','simpleWork','simplePrintCost']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => previewValid = false);
            if (el && el.tagName === 'INPUT') el.addEventListener('input', () => previewValid = false);
        });

    calculateButton.addEventListener('click', calculate);

    form.addEventListener('submit', async function (event) {
        if (previewValid) return;

        event.preventDefault();
        const valid = await calculate();
        if (valid) form.submit();
    });

    updateProfileHelp();
});
</script>
@endsection
