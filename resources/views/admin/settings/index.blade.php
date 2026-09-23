@extends('layouts.admin.base')

@section('title', __('Settings'))

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
    @csrf
<div class="row g-4">
    <!-- Company Info -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold">🧾 Company Information</h5>
            </div>
            <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('ui.company_name') }}</label>
                            <input type="text" name="company_name" class="form-control" value="{{ $setting->company_name }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('ui.contact') }}</label>
                            <input type="text" name="contact" class="form-control" value="{{ $setting->contact }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('ui.email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ $setting->email }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">English Note</label>
                            <input type="text" name="note_en" class="form-control" value="{{ $setting->note_en }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Dari Note</label>
                            <input type="text" name="note_fa" class="form-control" style="direction: rtl;" value="{{ $setting->note_fa }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Pashto Note</label>
                            <input type="text" name="note_ps" class="form-control" style="direction: rtl;" value="{{ $setting->note_ps }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('ui.address') }}</label>
                            <textarea name="address" class="form-control" rows="3">{{ $setting->address }}</textarea>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <!-- Preferences -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold">⚙️ Preferences</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Default Language</label>
                    <select name="default_language" class="form-select">
                        <option value="en" @selected($setting->default_language === 'en')>{{ __('ui.english') }}</option>
                        <option value="fa" @selected($setting->default_language === 'fa')>{{ __('ui.dari') }}</option>
                        <option value="ps" @selected($setting->default_language === 'ps')>{{ __('ui.pashto') }}</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Default Currency</label>
                    <select name="currency" class="form-select">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->code }}" @selected($setting->currency === $currency->code)>
                                {{ $currency->name }} ({{ $currency->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Units -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">🏭 Business Unit Separation</h5>
                    <div class="small text-muted">Keep customers and employees shared while separating operational dashboards by business.</div>
                </div>
                <span class="badge {{ $setting->separate_business_units_enabled ? 'bg-success' : 'bg-secondary' }}">
                    {{ $setting->separate_business_units_enabled ? 'Enabled' : 'Unified Mode' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-7">
                        <input type="hidden" name="separate_business_units_enabled" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="separateBusinessUnitsEnabled"
                                   name="separate_business_units_enabled"
                                   value="1"
                                   @checked(old('separate_business_units_enabled', $setting->separate_business_units_enabled))>
                            <label class="form-check-label fw-semibold" for="separateBusinessUnitsEnabled">
                                Enable separate business units
                            </label>
                        </div>
                        <div class="form-text mt-2">
                            When disabled, the ERP behaves as one unified business. When enabled, users can switch between
                            <strong>3D Carton</strong> and <strong>Syrup Pack</strong> from the top navigation.
                            Customers and employees remain shared master data.
                        </div>
                        @error('separate_business_units_enabled')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-lg-5">
                        <label for="defaultBusinessUnitId" class="form-label fw-semibold">Default Business Unit</label>
                        <select class="form-select @error('default_business_unit_id') is-invalid @enderror"
                                id="defaultBusinessUnitId"
                                name="default_business_unit_id">
                            @forelse($settingsBusinessUnits as $businessUnit)
                                <option value="{{ $businessUnit->id }}"
                                    @selected((int) old('default_business_unit_id', $setting->default_business_unit_id) === (int) $businessUnit->id)>
                                    {{ $businessUnit->name }}
                                </option>
                            @empty
                                <option value="" disabled selected>No business units available — run database migrations.</option>
                            @endforelse
                        </select>
                        @error('default_business_unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Used when business separation is first enabled or a user has not selected a business yet.</div>
                    </div>
                </div>

                <div class="alert alert-info mt-4 mb-0 small">
                    <i class="bi bi-diagram-3 me-1"></i>
                    This switch controls whether business-unit context is active. Shared customers and HR records are not duplicated.
                    Operational records will be separated by business unit as the business-unit rollout is applied.
                </div>
            </div>
        </div>
    </div>

    <!-- Production Controls -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-1 fw-bold">🧭 Production Controls</h5>
                <div class="small text-muted">Optional maker-checker control for starting production.</div>
            </div>
            <div class="card-body">
                <input type="hidden" name="production_approval_required" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           role="switch"
                           id="productionApprovalRequired"
                           name="production_approval_required"
                           value="1"
                           @checked(old('production_approval_required', $setting->production_approval_required))>
                    <label class="form-check-label fw-semibold" for="productionApprovalRequired">
                        Require supervisor approval before Start Production
                    </label>
                </div>
                <div class="form-text mt-2">
                    When enabled, a pending production order must be approved before raw material can be allocated or consumed.
                    Close, reopen and completion reversal remain controlled actions with mandatory reasons and audit history.
                </div>
            </div>
        </div>
    </div>

    <!-- Procurement Controls -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-1 fw-bold">🧾 Procure-to-Pay Controls</h5>
                <div class="small text-muted">Maker-checker purchasing with receipts, supplier invoices and three-way matching.</div>
            </div>
            <div class="card-body">
                <input type="hidden" name="purchase_approval_required" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="purchaseApprovalRequired" name="purchase_approval_required" value="1"
                           @checked(old('purchase_approval_required', $setting->purchase_approval_required))>
                    <label class="form-check-label fw-semibold" for="purchaseApprovalRequired">
                        Require purchase approval before Shipping / Arrival
                    </label>
                </div>
                <div class="form-text mt-2">
                    When enabled, purchase orders must be independently approved before progressing. Goods receipt and supplier invoice matching provide the operational audit trail.
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Upload -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold">🖼️ Company Logo</h5>
            </div>
            <div class="card-body">
                <label class="form-label fw-semibold">Current Logo</label>
                <div class="mb-3">
                    @if($setting->logo)
                        <img src="{{ asset('images/' . $setting->logo) }}" alt="Logo" class="img-fluid rounded shadow-sm border" style="max-height: 80px;">
                    @else
                        <p class="text-muted">No logo uploaded</p>
                    @endif
                </div>
                <label class="form-label fw-semibold">Upload New Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="col-12">
        <div class="text-end">
            <button type="submit" class="btn btn-success btn-lg px-5 mt-2 shadow-sm">💾 Save Settings</button>
        </div>
    </div>

</div>
</form>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('separateBusinessUnitsEnabled');
    const defaultSelect = document.getElementById('defaultBusinessUnitId');

    const syncBusinessUnitSettings = function () {
        if (!toggle || !defaultSelect) return;
        defaultSelect.disabled = !toggle.checked;
    };

    if (toggle) {
        toggle.addEventListener('change', syncBusinessUnitSettings);
    }

    syncBusinessUnitSettings();
});
</script>
@endsection
