@php use Illuminate\Support\Str; @endphp
<style>
.form-check-input:checked {
    background-color: #4361ee;
    border-color: #4361ee;
}

.form-check-label {
    font-size: 13px;
}

</style>
<div class="offcanvas offcanvas-end w-50" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
    <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('ui.close') }}"></button>
    </div>

    <div class="offcanvas-body">
        <form class="pt-0" id="addUserForm" method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.full_name') }}</label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" required />
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.username') }}</label>
                    <input type="text" name="username" class="form-control" placeholder="johndoe" required />
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.password') }}</label>
                    <input type="password" name="password" class="form-control" required />
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.repeat_password') }}</label>
                    <input type="password" name="repeat_password" class="form-control" required />
                    <div id="password-feedback" class="form-text mt-1"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('ui.status') }}</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">{{ __('ui.active') }}</label>
                    </div>
                </div>
            </div>

            <!-- Permissions Section -->
            <div class="mt-4">
                <h6 class="text-primary">{{ __('ui.assign_permissions') }}</h6>

                <div class="mb-3 d-flex flex-wrap gap-4 align-items-center">
                    <div>
                        <input type="checkbox" class="form-check-input me-1" id="select-all">
                        <label for="select-all" class="form-check-label fw-bold">{{ __('ui.select_all') }}</label>
                    </div>
                    <div>
                        <input type="checkbox" class="form-check-input me-1" id="unselect-all">
                        <label for="unselect-all" class="form-check-label fw-bold">{{ __('ui.unselect_all') }}</label>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 g-4">
                    @foreach ($permissions as $group => $perms)
                        <div class="col">
                            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid var(--primary); border-radius: 16px;">
                                <div class="card-header bg-white border-0 py-2 d-flex justify-content-between align-items-center" style="background: linear-gradient(to right, #f0f4ff, #eb5151); border-bottom: 1px solid #f0f0f0; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                                    <h6 class="mb-0 text-capitalize fw-semibold d-flex align-items-center">
                                        <i class="bi bi-lock me-2 text-primary fs-5"></i>{{ $group }}
                                    </h6>
                                    <span class="badge bg-light text-dark small px-2 py-1">{{ count($perms) }} perms</span>
                                </div>

                                <div class="card-body pt-2">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($perms as $perm)
                                            @php
                                                $action = ucfirst(explode(' ', $perm->name)[0]);
                                            @endphp
                                            <div class="form-check form-switch" style="min-width: 100px;">
                                                <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $perm->name }}" id="perm-{{ $perm->id }}">
                                                <label class="form-check-label small text-muted" for="perm-{{ $perm->id }}">
                                                    {{ $action }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary" id="submit-button" disabled>
                    <i class="bi bi-check-circle me-1"></i> Save
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">
                    <i class="bi bi-x-circle me-1"></i> {{ __('ui.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JS -->
<script>
    document.getElementById('select-all').addEventListener('change', function () {
        if (this.checked) {
            document.getElementById('unselect-all').checked = false;
        }
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('unselect-all').addEventListener('change', function () {
        if (this.checked) {
            document.getElementById('select-all').checked = false;
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        }
    });

    // Enable submit button when form is valid
    const form = document.getElementById('addUserForm');
    form.addEventListener('input', () => {
        const isValid = form.checkValidity();
        document.getElementById('submit-button').disabled = !isValid;
    });
</script>
