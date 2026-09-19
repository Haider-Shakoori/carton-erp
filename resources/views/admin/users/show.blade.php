@extends('layouts.admin.base')
@section('title', __('Manage') . ' ' . $user->name)
@section('css')
    <style>
        .fade-in-card {
            animation: fadeIn 0.6s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .group-label {
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 0.25rem;
            text-transform: capitalize;
        }
        .select-all-link {
            font-size: 0.85rem;
            cursor: pointer;
        }
    </style>
@endsection
@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card fade-in-card border shadow-sm">
    <div class="card-body p-4 text-center">
        <!-- Avatar with status badge -->
        <div class="position-relative d-inline-block mb-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto"
                style="width: 100px; height: 100px; font-size: 36px;">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
        </div>

        <!-- Name and type -->
        <h4 class="fw-semibold mb-1">{{ $user->name }}</h4>
        <span class="badge bg-label-{{ $user->account_type === 'client' ? 'primary' : 'info' }} mb-3">
            <i class="bi {{ $user->account_type === 'client' ? 'bi-person' : 'bi-person-workspace' }} me-1"></i>
            {{ ucfirst($user->account_type) }}
        </span>

        <!-- User info list -->
        <ul class="list-unstyled text-start small">
            <li class="mb-1"><strong>👤 Username:</strong> <span class="text-dark">{{ $user->username }}</span></li>
            <li class="mb-1"><strong>📌 Status:</strong>
                <span class="badge bg-label-{{ $user->is_active ? 'success' : 'danger' }}">{{ $user->is_active ? 'Active' : 'Deactivated' }}</span>
            </li>
            <li class="mb-1"><strong>🔒 Role:</strong> {{ $user->account_type === 'client' ? 'Customer' : 'Admin' }}</li>
            <li class="mb-1"><strong>📅 Created at:</strong> {{ $user->created_at->format('d-m-Y') }}</li>
            <li class="mb-1"><strong>🧑‍💼 Created by:</strong> {{ $user->creator->name }}</li>
        </ul>

        <!-- Action button -->
        <a href="javascript:;"
            class="btn btn-{{ $user->is_active ? 'outline-danger' : 'outline-success' }} w-100 mt-3"
            onclick="confirmStatusChange('{{ $user->id }}', '{{ $user->name }}', {{ $user->is_active ? 'false' : 'true' }})">
            <i class="bi bi-{{ $user->is_active ? 'x-circle' : 'check-circle' }} me-1"></i>
            {{ $user->is_active ? 'Deactivate' : 'Activate' }} Account
        </a>
    </div>
</div>

    </div>
    <div class="col-lg-8">
        <div class="card fade-in-card">
            <div class="card-body">
                @if ($user->account_type === 'admin')
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="toggleAllGroups(true)">Select All Permissions</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAllGroups(false)">Unselect All Permissions</button>
                </div>
                @endif
                
                <ul class="nav nav-tabs mb-3" role="tablist">
                    @if ($user->account_type === 'admin')
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#permissionsTab" role="tab">{{ __('ui.permissions') }}</a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link {{ $user->account_type !== 'admin' ? 'active' : '' }}" data-bs-toggle="tab" href="#passwordTab" role="tab">{{ __('ui.change_password') }}</a>
                    </li>
                </ul>
                <div class="tab-content">
                    @if ($user->account_type === 'admin')
                    <div class="tab-pane fade show active" id="permissionsTab" role="tabpanel">
                        <form method="POST" action="{{ route('admin.users.update-permissions', $user->id) }}">
                            @csrf
                            <div class="row">
                                @php
                                    $icons = [
                                        'view' => 'eye',
                                        'create' => 'plus-circle',
                                        'update' => 'pencil-square',
                                        'delete' => 'trash',
                                        'export' => 'file-earmark-arrow-down',
                                        'assign' => 'person-check',
                                        'approve' => 'check-circle',
                                    ];

                                    // Group permissions by module name and collect each as object
                                    $permissionsByModule = collect($allPermissions)
                                        ->map(function ($perm) {
                                            // Handle permissions with multiple words (like "view profit")
                                            $parts = explode(' ', $perm->name, 2);
                                            if (count($parts) === 1) {
                                                // For permissions without spaces (unlikely in your case)
                                                $perm->action = $parts[0];
                                                $perm->module = 'misc';
                                            } else {
                                                $perm->action = $parts[0];
                                                $perm->module = $parts[1];

                                                // Special handling for "view profit" and "view sale rate"
                                                if ($perm->module === 'profit') {
                                                    $perm->module = 'reports';
                                                } elseif ($perm->module === 'sale rate') {
                                                    $perm->module = 'reports';
                                                }
                                            }
                                            return $perm;
                                        })
                                        ->groupBy('module'); // key = module name, value = collection of permissions
                                @endphp


                                @foreach ($permissionsByModule as $module => $permissions)
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between align-items-center group-label mt-3 mb-2">
                                            <span><i class="bi bi-shield-lock me-1"></i> {{ ucfirst(str_replace('_', ' ', $module)) }}</span>
                                            <span class="text-primary select-all-link" onclick="toggleGroup('{{ Str::slug($module) }}', true)">{{ __('ui.select_all') }}</span>
                                            <span class="text-danger select-all-link ms-2" onclick="toggleGroup('{{ Str::slug($module) }}', false)">{{ __('ui.unselect_all') }}</span>
                                        </div>
                                        <div class="row mb-3" id="group-{{ Str::slug($module) }}">
                                            @foreach ($permissions as $perm)
                                                <div class="col-md-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                                            id="perm-{{ Str::slug($perm->name) }}"
                                                            {{ $user->hasPermissionTo($perm->name) ? 'checked' : '' }}>
                                                        <label class="form-check-label text-capitalize" for="perm-{{ Str::slug($perm->name) }}">
                                                            <i class="bi bi-{{ $icons[$perm->action] ?? 'gear' }} me-1"></i>{{ ucfirst($perm->action) }} {{ str_replace($perm->action . ' ', '', $perm->name) }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                            </div>

                            <div class="mt-4">
                                <button class="btn btn-primary">Save Permissions</button>
                            </div>
                        </form>

                    </div>
                    @endif
                    <div class="tab-pane fade {{ $user->account_type !== 'admin' ? 'show active' : '' }}" id="passwordTab" role="tabpanel">
                        <form method="post" action="{{ route('admin.users.update-password', $user->id) }}" class="mt-4">
                            @csrf @method('put')
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="newPassword" class="form-label">{{ __('ui.new_password') }}</label>
                                    <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirmPassword" class="form-label">{{ __('ui.confirm_password') }}</label>
                                    <input type="password" id="confirmPassword" name="new_password" class="form-control" required>
                                </div>
                            </div>
                            <ul class="mt-3 mb-0">
                                <li>Min 8 characters</li>
                                <li>At least one uppercase and lowercase</li>
                            </ul>
                            <div class="mt-4">
                                <button class="btn btn-primary me-2">{{ __('ui.save_changes') }}</button>
                                <button type="reset" class="btn btn-outline-secondary">{{ __('ui.reset') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    function confirmStatusChange(id, name, activate) {
        const actionText = activate ? 'activate' : 'deactivate';
        Swal.fire({
            title: 'Are you sure?',
            text: `You will ${actionText} ${name}'s account`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Yes, ${actionText}!`
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = `/admin/users/${id}/update-status`;
            }
        });
    }

    function toggleGroup(groupSlug, checked) {
        const checkboxes = document.querySelectorAll(`#group-${groupSlug} input[type='checkbox']`);
        checkboxes.forEach(cb => cb.checked = checked);
    }

    function toggleAllGroups(checked) {
        const allGroups = document.querySelectorAll('[id^="group-"] input[type="checkbox"]');
        allGroups.forEach(cb => cb.checked = checked);
    }
</script>
@endsection
