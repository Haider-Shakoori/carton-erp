@extends('layouts.admin.base')

@section('title', __('Settings'))

@section('content')
<div class="row g-4">
    <!-- Company Info -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold">🧾 Company Information</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

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

    </form>
</div>
@endsection
