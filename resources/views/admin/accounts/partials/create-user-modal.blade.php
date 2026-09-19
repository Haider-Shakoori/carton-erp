<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="createUserForm">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createUserModalLabel">Create User for Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="account_id" id="account_id">

                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.full_name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.username') }}</label>
                        <input type="text" name="username" class="form-control" readonly required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.password') }}</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('ui.repeat_password') }}</label>
                        <input type="password" name="repeat_password" class="form-control" required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">Create & Link</button>
                </div>
            </form>
        </div>
    </div>
</div>
