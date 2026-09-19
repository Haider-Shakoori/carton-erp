{{-- resources/views/admin/agents/partials/create-form.blade.php --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
    <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add New Agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('ui.close') }}"></button>
    </div>
    <div class="offcanvas-body">
        <form method="POST" id="createAgentForm">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <input type="hidden" name="create_account_sub_category_id" value="3">

            <div class="mb-3">
                <label for="account-name" class="form-label">{{ __('ui.agent_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="account-name"
                    placeholder="Enter agent name..." required>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.agent_code') }} <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" id="account-code-prefix">AGT-</span>
                    <input type="text" class="form-control" id="account-code-suffix" placeholder="0001" required>
                    <button class="btn btn-outline-secondary" type="button" id="generateCodeBtn">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
                <input type="hidden" name="code" id="full-account-code">
                <small id="code-feedback" class="text-muted">Code will be auto-generated</small>
            </div>

            <div class="mb-3">
                <label for="account-contact" class="form-label">{{ __('ui.contact') }}</label>
                <input type="text" class="form-control" id="account-contact" name="contact"
                    placeholder="+93 700 000 000">
            </div>

            <div class="mb-3">
                <label for="account-email" class="form-label">{{ __('ui.email') }}</label>
                <input type="email" class="form-control" id="account-email" name="email"
                    placeholder="agent@example.com">
            </div>

            <div class="mb-3">
                <label for="account-whatsapp" class="form-label">{{ __('ui.whatsapp') }}</label>
                <input type="text" class="form-control" id="account-whatsapp" name="whatsapp"
                    placeholder="+93 700 000 000">
            </div>

            <div class="mb-3">
                <label for="account-company" class="form-label">{{ __('ui.company') }}</label>
                <input type="text" class="form-control" id="account-company" name="company"
                    placeholder="Company name">
            </div>

            <div class="mb-3">
                <label for="account-address" class="form-label">{{ __('ui.address') }}</label>
                <textarea class="form-control" id="account-address" name="address" rows="2" placeholder="Enter address..."></textarea>
            </div>

            <div class="mb-3">
                <label for="account-notes" class="form-label">{{ __('ui.notes') }}</label>
                <textarea class="form-control" id="account-notes" name="notes" rows="2" placeholder="{{ __('ui.additional_notes_dots') }}"></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100" id="submitAgentBtn">
                    <i class="bi bi-check-circle me-1"></i> Create Agent
                </button>
                <button type="reset" class="btn btn-outline-secondary w-100" data-bs-dismiss="offcanvas">
                    <i class="bi bi-x-circle me-1"></i> {{ __('ui.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>
