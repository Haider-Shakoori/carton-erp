<form id="remittanceForm">
    @csrf
    <input type="hidden" name="remittance_id" id="remittance_id">
    <div class="row g-4">
        <div class="col-md-6">
            <label class="form-label">{{ __('ui.customer_account') }}</label>
            <select name="account_id" class="form-select select2" required>
                <option value="">{{ __('ui.select_customer') }}</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }} - {{ $account->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('ui.currency') }}</label>
            <select name="currency_id" class="form-select select2" required>
                <option value="">Select a currency</option>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('ui.amount') }}</label>
            <input type="text" name="amount" class="form-control" placeholder="Enter amount" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('ui.bank_name') }}</label>
            <input type="text" name="bank_name" class="form-control" placeholder="Bank name" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Account Holder Name</label>
            <input type="text" name="account_holder" class="form-control" placeholder="Account holder" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Bank Account Number</label>
            <input type="text" name="bank_account_number" class="form-control" placeholder="Account number" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('ui.bank_address') }}</label>
            <input type="text" name="bank_address" class="form-control" placeholder="{{ __('ui.bank_address') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('ui.phone_number') }}</label>
            <input type="text" name="phone_number" class="form-control" placeholder="Phone number">
        </div>

        <div class="col-12">
            <label class="form-label">Note</label>
            <textarea name="note" class="form-control" rows="2" placeholder="{{ __('ui.optional_note') }}"></textarea>
        </div>
    </div>

    <div class="text-end mt-4">
        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-check-circle me-1"></i> Submit
        </button>
    </div>
</form>
