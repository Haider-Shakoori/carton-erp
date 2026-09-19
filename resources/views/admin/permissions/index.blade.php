@extends('layouts.admin.base')

@section('title', __('ui.permissions'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('ui.permissions') }}</h5>
            @can('create permissions')
            <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddPermission">
                <i class="bi bi-plus-circle"></i> <span>Add Permission</span>
            </button>
            @endcan
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.permissions.assign') }}">
                <div class="mb-4">
                    <label for="role_id" class="form-label">Select Role</label>
                    <select name="role_id" id="role_id" class="form-select" required>
                        <option value="">-- Choose Role --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                @csrf
                @php
                    $groupedPermissions = $permissions->groupBy(function($perm) {
                        // Extract the group from permission name (e.g., 'view dashboard' -> 'dashboard')
                        $parts = explode(' ', $perm->name);
                        if (count($parts) >= 2) {
                            return $parts[1] ?? 'general';
                        }
                        return 'general';
                    });

                    // Define icons for actions
                    $icons = [
                        'view' => 'eye',
                        'create' => 'plus-circle',
                        'edit' => 'pencil-square',
                        'delete' => 'trash',
                        'update' => 'pencil-square',
                        'assign' => 'person-plus',
                        'manage' => 'gear',
                        'export' => 'file-earmark-export',
                        'import' => 'file-earmark-import',
                        'approve' => 'check-circle',
                        'reject' => 'x-circle',
                        'print' => 'printer',
                        'download' => 'download',
                        'upload' => 'upload',
                        'process' => 'gear',
                        'export' => 'file-earmark-export',
                    ];

                    // Define section groupings with custom labels
                    $sectionLabels = [
                        // Existing
                        'dashboard' => 'Dashboard',
                        'sales' => 'Sales Orders',
                        'sale-returns' => 'Sale Returns',
                        'customers' => 'Customers',
                        'bom' => 'Bill of Materials (BOM)',
                        'production-planning' => 'Production Planning',
                        'production-orders' => 'Production Orders',
                        'work-orders' => 'Work Orders',
                        'quality' => 'Quality Control',
                        'purchase-orders' => 'Purchase Orders',
                        'stock' => 'Stock Inventory',
                        'products' => 'Products',
                        'stock-movements' => 'Stock Movements',
                        'transactions' => 'Transactions',
                        'expenses' => 'Expenses',
                        'cost-analysis' => 'Cost Analysis',
                        'suppliers' => 'Suppliers',
                        'agents' => 'Agents',
                        'sarafs' => 'Sarafan',
                        'reports' => 'Reports',
                        'users' => 'Users',
                        'roles' => 'Roles',
                        'permissions' => 'Permissions',
                        'currencies' => 'Currencies',
                        'account-categories' => 'Account Categories',
                        'account-sub-categories' => 'Account Sub Categories',
                        'settings' => 'System Settings',
                        'audit' => 'Audit Logs',
                        'app-settings' => 'App Settings',
                        'company' => 'Company Settings',
                        'categories' => 'Categories',
                        'units' => 'Units',
                        'invoice-templates' => 'Invoice Templates',
                        'bom-settings' => 'BOM Settings',

                        // HR Modules (NEW)
                        'employees' => 'Employees',
                        'attendance' => 'Attendance',
                        'leave-types' => 'Leave Types',
                        'leave-requests' => 'Leave Requests',
                        'employee-advances' => 'Employee Advances',
                        'payroll' => 'Payroll',
                        'hr-reports' => 'HR Reports',
                    ];
                @endphp

                @foreach($groupedPermissions as $group => $groupPermissions)
                    @php
                        // Clean up group name for display
                        $displayGroup = $sectionLabels[$group] ?? ucfirst(str_replace('-', ' ', $group));
                        $groupSlug = Str::slug($group);
                    @endphp

                    <div class="border-bottom mb-3 pb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="text-muted fw-semibold mb-2">
                                <i class="bi bi-tag me-1"></i>{{ $displayGroup }}
                            </h6>
                            <div>
                                <a href="javascript:;" class="text-primary small" onclick="toggleGroup('{{ $groupSlug }}', true)">{{ __('ui.select_all') }}</a> /
                                <a href="javascript:;" class="text-danger small" onclick="toggleGroup('{{ $groupSlug }}', false)">{{ __('ui.unselect_all') }}</a>
                            </div>
                        </div>
                        <div class="row" id="group-{{ $groupSlug }}">
                            @foreach($groupPermissions as $permission)
                                @php
                                    $action = explode(' ', $permission->name)[0] ?? 'view';
                                    $icon = $icons[$action] ?? 'lock';
                                @endphp
                                <div class="col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                               name="permissions[]" value="{{ $permission->name }}"
                                               id="perm-{{ $permission->id }}">
                                        <label class="form-check-label text-capitalize" for="perm-{{ $permission->id }}">
                                            <i class="bi bi-{{ $icon }} me-1"></i>{{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @can('update permissions')
                <button type="submit" class="btn btn-primary w-100">Assign Selected Permissions</button>
                @endcan
            </form>
        </div>
    </div>

    <!-- Offcanvas Create Form -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddPermission">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add New Permission</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('admin.permissions.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Permission Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. view employees" required>
                    <small class="text-muted">Format: action-resource (e.g., view-employees, create-payroll)</small>
                </div>
                <button class="btn btn-primary w-100">Create Permission</button>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        // Load permissions for selected role
        document.getElementById('role_id')?.addEventListener('change', function () {
            const roleId = this.value;
            if (!roleId) return;

            fetch(`/admin/roles/${roleId}/permissions-json`)
                .then(res => res.json())
                .then(data => {
                    document.querySelectorAll('.permission-checkbox').forEach(cb => {
                        cb.checked = data.permissions.includes(cb.value);
                    });
                })
                .catch(err => {
                    console.error('Error loading permissions:', err);
                });
        });

        // Toggle all permissions in a group
        function toggleGroup(groupSlug, checked) {
            const checkboxes = document.querySelectorAll(`#group-${groupSlug} input[type='checkbox']`);
            checkboxes.forEach(cb => cb.checked = checked);
        }

        // Initialize DataTable if exists
        $(function () {
            if ($('#permissions-table').length) {
                $('#permissions-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.permissions.data') }}',
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'group', name: 'group', orderable: false, searchable: false },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false },
                    ]
                });
            }
        });
    </script>
@endsection
