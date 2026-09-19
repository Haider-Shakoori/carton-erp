{{-- resources/views/admin/hr/departments/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Department Management')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <style>
        .badge-status {
            padding: 0.2rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-status.active {
            background: var(--success-bg);
            color: var(--success);
        }
        .badge-status.inactive {
            background: var(--danger-bg);
            color: var(--danger);
        }
        .department-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .department-icon.primary { background: var(--primary-bg); color: var(--primary); }
        .department-icon.success { background: var(--success-bg); color: var(--success); }
        .department-icon.warning { background: var(--warning-bg); color: var(--warning); }
        .department-icon.danger { background: var(--danger-bg); color: var(--danger); }
        .department-icon.info { background: var(--info-bg); color: var(--info); }
        .department-icon.purple { background: #eef2ff; color: #4f46e5; }

        .employee-count {
            font-weight: 700;
            font-size: 1.1rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-building me-2"></i> <span class="accent">{{ __('ui.departments') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-grid-3x3-gap me-1"></i> Manage organizational departments and their employees
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal" onclick="resetDepartmentForm()">
                        <i class="bi bi-plus-circle me-1"></i> Add Department
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-building"></i>
                    </div>
                </div>
                <div class="stat-label">Total Departments</div>
                <div class="stat-value">{{ $departments->total() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.active') }}</div>
                <div class="stat-value">{{ $departments->where('is_active', true)->count() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_employees') }}</div>
                <div class="stat-value">{{ $departments->sum('employees_count') }}</div>
            </div>
        </div>

        <!-- Department Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> Department List
            </span>
                <span class="header-badge">
                <i class="bi bi-database me-1"></i> Total: {{ $departments->total() }}
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="departmentsTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 50px;">Icon</th>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.description') }}</th>
                            <th class="text-center">{{ __('ui.employees') }}</th>
                            <th style="width: 110px;">{{ __('ui.status') }}</th>
                            <th style="width: 130px;" class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($departments as $department)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="department-icon {{ $department->bi_icon_color ?? 'primary' }}">
                                        <i class="bi {{ $department->bi_icon ?? 'bi-building' }}"></i>
                                    </div>
                                </td>
                                <td class="fw-semibold text-dark">{{ $department->name }}</td>
                                <td>{{ $department->description ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="employee-count">{{ $department->employees_count ?? 0 }}</span>
                                </td>
                                <td>
                                <span class="badge-status {{ $department->is_active ? 'active' : 'inactive' }}">
                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                </td>
                                <td>
                                    <div class="action-buttons justify-content-end">
                                        <button class="action-btn edit-department"
                                                data-id="{{ $department->id }}"
                                                data-name="{{ $department->name }}"
                                                data-description="{{ $department->description }}"
                                                data-is_active="{{ $department->is_active }}"
                                                title="{{ __('ui.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="action-btn toggle-status"
                                                data-id="{{ $department->id }}"
                                                data-status="{{ $department->is_active }}"
                                                title="{{ $department->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi bi-{{ $department->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                        <button class="action-btn text-danger delete-department"
                                                data-id="{{ $department->id }}"
                                                data-name="{{ $department->name }}"
                                                data-employees="{{ $department->employees_count ?? 0 }}"
                                                title="{{ __('ui.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $departments->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Department Modal -->
    <div class="modal fade" id="departmentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-building me-2" style="color: white;"></i>
                        <span id="departmentModalTitle" style="color: white;">Add Department</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="departmentForm" method="POST">
                    @csrf
                    <input type="hidden" name="department_id" id="department_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.department_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="department_name" required placeholder="{{ __('ui.enter_department_name') }}">
                            @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.description') }}</label>
                            <textarea class="form-control" name="description" id="department_description" rows="3" placeholder="Optional department description..."></textarea>
                            @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" id="department_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold text-dark"> {{ __('ui.active_status') }}</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="departmentSubmitBtn">
                            <i class="bi bi-check2 me-1"></i> Save Department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // DataTable
            $('#departmentsTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: [1, 6] }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search departments...',
                }
            });

            // Form Reset
            window.resetDepartmentForm = function() {
                $('#departmentForm')[0].reset();
                $('#department_id').val('');
                $('#departmentModalTitle').text('Add Department');
                $('#department_is_active').prop('checked', true);
                $('#departmentSubmitBtn').html('<i class="bi bi-check2 me-1"></i> Save Department');
                $('#departmentSubmitBtn').prop('disabled', false);
            };

            // Edit Department
            $(document).on('click', '.edit-department', function() {
                resetDepartmentForm();
                $('#departmentModalTitle').text('Edit Department');
                $('#department_id').val($(this).data('id'));
                $('#department_name').val($(this).data('name'));
                $('#department_description').val($(this).data('description'));
                $('#department_is_active').prop('checked', $(this).data('is_active') == 1);
                $('#departmentModal').modal('show');
            });

            // Toggle Status
            $(document).on('click', '.toggle-status', function() {
                const id = $(this).data('id');
                const currentStatus = $(this).data('status');
                const action = currentStatus ? 'deactivate' : 'activate';

                Swal.fire({
                    title: `${action.charAt(0).toUpperCase() + action.slice(1)} Department?`,
                    text: `Are you sure you want to ${action} this department?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4F46E5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: `Yes, ${action}!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/departments/${id}/toggle-status`,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Updated!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to update status.', 'error');
                            }
                        });
                    }
                });
            });

            // Delete Department
            $(document).on('click', '.delete-department', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const employeeCount = $(this).data('employees');

                if (employeeCount > 0) {
                    Swal.fire({
                        title: 'Cannot Delete',
                        text: `This department has ${employeeCount} employee(s) assigned. Please reassign or delete them first.`,
                        icon: 'error',
                        confirmButtonColor: '#4F46E5'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Delete Department?',
                    text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/departments/${id}`,
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete department.', 'error');
                            }
                        });
                    }
                });
            });

            // Form Submit
            $('#departmentForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#department_id').val();
                let url = '/admin/hr/departments';
                const data = $(this).serialize();

                if (id && id !== '') {
                    url += '/' + id;
                    data += '&_method=PUT';
                }

                $('#departmentSubmitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            $('#departmentSubmitBtn').prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Save Department'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save department.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        $('#departmentSubmitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Save Department'
                        );
                    }
                });
            });
        });
    </script>
@endsection
