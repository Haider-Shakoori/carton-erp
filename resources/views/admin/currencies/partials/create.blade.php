<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCreateCurrency">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Add Currency</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('ui.close') }}"></button>
    </div>
    <div class="offcanvas-body">
        <form method="POST" action="{{ route('admin.currencies.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">{{ __('ui.name') }}</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.currency_code') }}</label>
                <input list="currency-codes" name="code" id="currency-code" class="form-control" placeholder="USD, AFN, RMB..." required>
                <datalist id="currency-codes">
                    @foreach ([
                        'USD', 'AFN', 'RMB', 'IRR', 'PKR', 'EUR', 'GBP', 'AED', 'INR', 'TRY',
                        'CAD', 'AUD', 'SAR', 'KWD', 'NPR', 'BDT', 'JPY', 'CHF', 'SEK', 'NOK',
                        'DKK', 'ZAR', 'MYR', 'SGD', 'THB', 'KRW', 'IDR', 'EGP', 'HKD', 'BRL',
                        'MXN', 'RUB', 'ILS', 'CZK', 'PLN', 'HUF', 'NGN', 'VND', 'TWD', 'ARS',
                        'QAR', 'OMR', 'BHD', 'LKR', 'JOD', 'DZD', 'MAD', 'LBP', 'SDG', 'DOP',
                        'UYU', 'CLP', 'PEN', 'COP', 'KZT', 'AZN', 'ALL', 'GEL', 'XAF', 'XOF',
                        'ETB', 'TZS', 'KES'
                    ] as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.symbol') }}</label>
                <input type="text" name="symbol" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.country') }}</label>
                <input type="text" name="country" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.exchange_rate') }}</label>
                <input type="number" step="0.000001" name="exchange_rate" class="form-control" required>
            </div>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                <label class="form-check-label" for="is_default">{{ __('ui.set_default') }}</label>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                <label class="form-check-label" for="is_active">{{ __('ui.active') }}</label>
            </div>

            <input type="hidden" name="flag" id="flag-path">
            <img id="flag-preview" src="/assets/flags/unknown.svg" width="30" height="20" class="mt-2">

            <div class="mt-3">
                <button type="submit" class="btn btn-primary w-100">Save</button>
            </div>
        </form>
    </div>
</div>
