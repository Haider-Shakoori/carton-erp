<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
    <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add New Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('ui.close') }}"></button>
    </div>
    <div class="offcanvas-body">
        <form method="POST" id="addNewAccountForm">
            @csrf
            <input type="hidden" name="_method" value="POST" id="form_method">

            <input type="hidden" id="edit_mode" value="0">
            <input type="hidden" id="edit_id" name="edit_id">

            <input type="hidden" name="create_account_category" id="create_account_category" value="1">
            <input type="hidden" name="create_account_sub_category_id" id="create_account-sub-category" value="1">
            <div class="mb-3">
                <label for="account-name" class="form-label">{{ __('ui.customer_name') }}</label>
                <input type="text" class="form-control" name="name" id="account-name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.customer_code') }}</label>
                <div class="input-group">
                    <span class="input-group-text" id="account-code-prefix">#</span>
                    <input type="text" class="form-control" id="account-code-suffix" placeholder="{{ __('ui.code_dots') }}" required>
                </div>
                <input type="hidden" name="code" id="full-account-code">
                <small id="code-feedback" class="text-muted"></small>
            </div>

            <div class="mb-3">
                <label for="account-contact" class="form-label">{{ __('ui.contact') }}</label>
                <input type="text" class="form-control" id="account-contact" name="contact" placeholder="+9378400xxxxx" value="+93" required>
            </div>

            <div class="mb-3">
                <label for="account-address" class="form-label">{{ __('ui.address') }}</label>
                <textarea class="form-control" id="account-address" name="address" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label for="account-company" class="form-label">{{ __('ui.company') }}</label>
                <input type="text" class="form-control" id="account-company" name="company">
            </div>

            <div class="form-check form-switch mb-4">
                <input type="checkbox" class="form-check-input" id="createLoginAccount" name="create_login_account" checked>
                <label class="form-check-label" for="createLoginAccount">{{ __('ui.create_login_account') }}</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle me-1"></i> Submit
                </button>
                <button type="reset" class="btn btn-outline-secondary w-100" data-bs-dismiss="offcanvas">
                    <i class="bi bi-x-circle me-1"></i> {{ __('ui.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>
