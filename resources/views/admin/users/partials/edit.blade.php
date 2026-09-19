<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditUser" aria-labelledby="offcanvasEditUserLabel">
    <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditUserLabel" class="offcanvas-title">Edit User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form method="POST" id="editUserForm">
            @csrf
            @method('PUT')

            <input type="hidden" name="user_id" id="edit-user-id">

            <div class="mb-3">
                <label class="form-label">{{ __('ui.full_name') }}</label>
                <input type="text" name="name" id="edit-name" class="form-control" required />
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('ui.username') }}</label>
                <input type="text" name="username" id="edit-username" class="form-control" required />
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="edit-is-active" name="is_active">
                <label class="form-check-label" for="edit-is-active">{{ __('ui.active') }}</label>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>
