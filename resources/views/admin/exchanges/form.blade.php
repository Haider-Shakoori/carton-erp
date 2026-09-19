<form id="exchangeForm">
    @csrf
    <input type="hidden" name="exchange_id" id="exchange_id">

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 border-start border-primary border-4 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted fw-bold mb-3">
                        <i class="bi bi-people-fill text-primary me-1"></i> Select Accounts
                    </h6>
                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.customer_account') }}</label>
                        <select name="customer_account_id" class="select2 form-select" required>
                            <option value="">{{ __('ui.select_customer') }}</option>
                            @foreach ($accounts as $customer)
                                <option value="{{ $customer->id }}">[{{ $customer->code ?? '-' }}] - {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.office_account') }}</label>
                        <select name="office_account_id" class="select2 form-select" required>
                            <option value="">{{ __('ui.select_office_account') }}</option>
                            @foreach ($officeAccounts as $office)
                                <option value="{{ $office->id }}">[{{ $office->code ?? '-' }}] - {{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 border-start border-info border-4 shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted fw-bold mb-1">
                            <i class="bi bi-bank2 text-info me-1"></i> {{ __('ui.office_account') }}
                            <span class="badge bg-light text-dark fw-normal float-end" id="office_code">#-</span>
                        </h6>
                        <h5 class="fw-semibold text-dark mb-1" id="office_name">-</h5>
                        <div class="row g-2" id="office_balance_cards"></div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <h6 class="text-muted fw-bold mb-1">
                            <i class="bi bi-person-lines-fill text-info me-1"></i> Saraf / Bank Account
                            <span class="badge bg-light text-dark fw-normal float-end" id="detail_code">#-</span>
                        </h6>
                        <h5 class="fw-semibold text-dark mb-1" id="detail_name">-</h5>
                        <div class="row g-2" id="customer_balance_cards"></div>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <div class="card border-start border-warning mb-4 border-4 shadow-sm">
        <div class="card-body">
            <h6 class="text-muted fw-bold mb-3">
                <i class="bi bi-wallet2 text-warning me-1"></i> Currency & Amount
            </h6>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">From Currency</label>
                    <select name="base_currency_id" id="base_currency" class="select2 form-select" required>
                        <option value="">{{ __('ui.please_select') }}</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}" data-code="{{ $currency->code }}" @if($currency->code == 'CNY') selected @endif>{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.amount_from') }}</label>
                    <input type="text" name="base_amount" id="exchange_amount" class="form-control" placeholder="0.00" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.to_currency') }}</label>
                    <select name="target_currency_id" id="target_currency" class="select2 form-select" required>
                        <option value="">{{ __('ui.please_select') }}</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->id }}" data-code="{{ $currency->code }}">{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>
    </div>

    <div class="card border-start border-secondary mb-4 border-4 shadow-sm">
        <div class="card-body">
            <h6 class="text-muted fw-bold mb-3">
                <i class="bi bi-calculator text-secondary me-1"></i> Exchange Calculation
            </h6>
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.exchange_rate') }}</label>
                    <input type="text" name="rate" id="exchange_rate" class="form-control bg-light">
                    <input type="hidden" name="cost_rate" id="cost_rate">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('ui.received_amount') }}</label>
                    <input type="text" name="target_amount" id="converted_amount" class="form-control bg-light" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label" id="profit_label">{{ __('ui.profit') }}</label>
                    <input type="text" name="target_profit" id="target_profit" class="form-control bg-light" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">Note</label>
        <textarea class="form-control" name="note" rows="2" placeholder="{{ __('ui.optional_note') }}"></textarea>
    </div>

    <div class="text-end">
        <button class="btn btn-success px-4" type="submit">
            <i class="bi bi-check-circle me-1"></i> Save Exchange
        </button>
    </div>
</form>
