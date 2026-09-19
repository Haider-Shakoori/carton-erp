@extends('layouts.admin.base')
@section('title', __('Roles'))
@section('css')
<style>
    .offcanvas-header .btn-close {
        margin: 0;
    }
    .permission-badge {
        font-size: 0.75rem;
        margin: 2px;
    }
    .table td, .table th {
        vertical-align: middle;
    }
    .table thead th {
        background-color: #f8f9fa;
    }
</style>
@endsection
@section('content')
<div class="card shadow-sm border">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Roles Management</h5>
        @can('create roles')
        <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRoleForm">
            <i class="bi bi-plus-circle"></i> <span>Add Role</span>
        </button>
        @endcan
    </div>
    <div class="card-body">
        <div class="table-responsive mt-3">
            <table class="table table-hover table-striped table-bordered text-wrap">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 180px;">{{ __('ui.name') }}</th>
                        <th style="width: 200px; max-width: 200px; white-space: normal;">{{ __('ui.permissions') }}</th>
                        <th style="width: 100px;">{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-semibold text-capitalize">{{ $role->name }}</td>
                        <td style="white-space: nowrap;">
                            @foreach($role->permissions as $perm)
                                <span class="badge permission-badge text-capitalize me-1 mb-1"
                                      style="background-color: {{ '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT) }};">
                                    {{ $perm->name }}
                                </span>
                            @endforeach
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                @can('update roles')
                                <button class="btn btn-sm btn-outline-warning edit-role-btn"
                                        data-id="{{ $role->id }}"
                                        data-name="{{ $role->name }}"
                                        data-permissions='@json($role->permissions->pluck("name"))'
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasRoleForm">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endcan
                                @can('delete roles')
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal-{{ $role->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>

                                <!-- Confirm Delete Modal -->
                                <div class="modal fade" id="confirmDeleteModal-{{ $role->id }}" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="confirmDeleteLabel">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete this role?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">{{ __('ui.delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Role Offcanvas Form -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRoleForm">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="roleFormTitle">Create Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="roleForm" method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="role_id" id="roleId">
            <div class="mb-3">
                <label class="form-label">Role Name</label>
                <input type="text" name="name" id="roleName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('ui.assign_permissions') }}</label>
                <div class="border rounded p-2" style="max-height: 250px; overflow-y: auto;">
                    @php
                        $groupedPermissions = $permissions->groupBy(fn($perm) => explode(' ', $perm->name)[1] ?? 'general');
                        $icons = ['view' => 'eye', 'create' => 'plus-circle', 'update' => 'pencil-square', 'delete' => 'trash'];
                    @endphp
                    @foreach($groupedPermissions as $group => $groupPermissions)
                        <div class="border-bottom mb-3 pb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="text-muted fw-semibold text-capitalize mb-2">{{ $group }}</h6>
                                <div>
                                    <a href="javascript:;" class="text-primary small" onclick="toggleGroup('{{ Str::slug($group) }}', true)">{{ __('ui.select_all') }}</a> /
                                    <a href="javascript:;" class="text-danger small" onclick="toggleGroup('{{ Str::slug($group) }}', false)">{{ __('ui.unselect_all') }}</a>
                                </div>
                            </div>
                            <div class="row" id="group-{{ Str::slug($group) }}">
                                @foreach($groupPermissions as $permission)
                                    @php $action = explode(' ', $permission->name)[0]; @endphp
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{ $permission->id }}">
                                            <label class="form-check-label text-capitalize" for="perm-{{ $permission->id }}">
                                                <i class="bi bi-{{ $icons[$action] ?? 'lock' }} me-1"></i>{{ ucfirst($permission->name) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-3">Save Role</button>
        </form>
    </div>
</div>
@endsection
@section('js')
<script>
    const roleForm = document.getElementById('roleForm');
    const roleTitle = document.getElementById('roleFormTitle');
    const roleName = document.getElementById('roleName');
    const roleId = document.getElementById('roleId');
    const formMethod = document.getElementById('formMethod');

    document.querySelectorAll('.edit-role-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const permissions = JSON.parse(btn.dataset.permissions);

            roleForm.action = `/admin/roles/${id}`;
            formMethod.value = 'PUT';
            roleId.value = id;
            roleName.value = name;
            roleTitle.innerText = 'Edit Role';

            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.checked = permissions.includes(cb.value);
            });
        });
    });

    document.getElementById('offcanvasRoleForm').addEventListener('hidden.bs.offcanvas', () => {
        roleForm.action = '{{ route('admin.roles.store') }}';
        formMethod.value = 'POST';
        roleForm.reset();
        roleId.value = '';
        roleTitle.innerText = 'Create Role';
    });

    function toggleGroup(groupSlug, checked) {
        const checkboxes = document.querySelectorAll(`#group-${groupSlug} input[type='checkbox']`);
        checkboxes.forEach(cb => cb.checked = checked);
    }
</script>
@endsection
