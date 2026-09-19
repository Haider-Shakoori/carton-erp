{{-- resources/views/admin/hr/employees/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Employee Management')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }
        .employee-avatar.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }
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
        .image-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--gray-50);
            padding: 0.5rem;
            border-radius: var(--radius-xs);
            border: 1.5px dashed var(--gray-300);
        }
        .current-image-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-xs);
            border: 1.5px solid var(--gray-200);
            padding: 2px;
            background: white;
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
                        <i class="bi bi-people me-2"></i> {{ __('ui.employee') }} <span class="accent">{{ __('ui.management') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-person-badge me-1"></i> Manage all employee records and information
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal" onclick="resetEmployeeForm()">
                        <i class="bi bi-person-plus me-1"></i> {{ __('ui.add_employee') }}
                    </button>
                    <a href="{{ route('admin.hr.employees.export') }}" class="btn btn-outline-primary">
                        <i class="bi bi-download me-1"></i> Export
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid mb-4">
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_employees') }}</div>
                <div class="stat-value">{{ $employees->total() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-person-check"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.active') }}</div>
                <div class="stat-value">{{ $employees->where('is_active', true)->count() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-building"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.departments') }}</div>
                <div class="stat-value">{{ $departments->count() }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-briefcase"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.designations') }}</div>
                <div class="stat-value">{{ $designations->count() }}</div>
            </div>
        </div>

        <!-- Employee Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-person-badge me-1"></i> {{ __('ui.employee_list') }}
            </span>
                <span class="header-badge">
                <i class="bi bi-database me-1"></i> Total: {{ $employees->total() }}
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="employeesTable">
                        <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 60px;">{{ __('ui.avatar') }}</th>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.employee_id') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.designation') }}</th>
                            <th>{{ __('ui.salary') }}</th>
                            <th style="width: 110px;">{{ __('ui.status') }}</th>
                            <th style="width: 130px;" class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td class="num-cell">#{{ $employee->id }}</td>
                                <td>
                                    @if ($employee->profile_image)
                                        <img src="{{ Storage::url($employee->profile_image) }}" class="employee-avatar">
                                    @else
                                        <div class="employee-avatar placeholder">
                                            {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-semibold text-dark">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </td>
                                <td>{{ $employee->employee_id }}</td>
                                <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                <td>{{ $employee->designation->name ?? 'N/A' }}</td>
                                <td class="num-cell">{{ $defaultCurrency->symbol }}{{ number_format($employee->basic_salary ?? 0, 2) }}</td>
                                <td>
                                <span class="badge-status {{ $employee->is_active ? 'active' : 'inactive' }}">
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                </td>
                                <td>
                                    <div class="action-buttons justify-content-end">
                                        <button class="action-btn edit-employee"
                                                data-id="{{ $employee->id }}"
                                                data-first_name="{{ $employee->first_name }}"
                                                data-last_name="{{ $employee->last_name }}"
                                                data-email="{{ $employee->email }}"
                                                data-phone="{{ $employee->phone }}"
                                                data-address="{{ $employee->address }}"
                                                data-date_of_birth="{{ $employee->date_of_birth }}"
                                                data-hire_date="{{ $employee->hire_date }}"
                                                data-department_id="{{ $employee->department_id }}"
                                                data-designation_id="{{ $employee->designation_id }}"
                                                data-basic_salary="{{ $employee->basic_salary }}"
                                                data-is_active="{{ $employee->is_active }}"
                                                data-profile_image="{{ $employee->profile_image }}"
                                                title="{{ __('ui.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="action-btn toggle-status"
                                                data-id="{{ $employee->id }}"
                                                data-status="{{ $employee->is_active }}"
                                                title="{{ $employee->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi bi-{{ $employee->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                        <button class="action-btn text-danger delete-employee"
                                                data-id="{{ $employee->id }}"
                                                data-name="{{ $employee->first_name }} {{ $employee->last_name }}"
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
                    {{ $employees->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge me-2" style="color: white;"></i>
                        <span id="employeeModalTitle" style="color: white;">{{ __('ui.add_employee') }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="employeeForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="employee_id" id="employee_id">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.first_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="employee_first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.last_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" id="employee_last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.email') }} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="employee_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.phone') }}</label>
                                <input type="text" class="form-control" name="phone" id="employee_phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.date_of_birth') }}</label>
                                <input type="date" class="form-control" name="date_of_birth" id="employee_dob">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.hire_date') }}</label>
                                <input type="date" class="form-control" name="hire_date" id="employee_hire_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.department') }}</label>
                                <select class="form-select" name="department_id" id="employee_department">
                                    <option value="">{{ __('ui.select_department') }}</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.designation') }}</label>
                                <select class="form-select" name="designation_id" id="employee_designation">
                                    <option value="">{{ __('ui.select_designation') }}</option>
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.basic_salary') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="basic_salary" id="employee_salary" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profile Image</label>
                                <div class="image-upload-wrapper">
                                    <input type="file" class="form-control form-control-sm" name="profile_image" id="employee_image" accept="image/*">
                                    <div id="current_image_container" class="d-none">
                                        <img id="current_employee_image" class="current-image-thumb" src="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.address') }}</label>
                                <textarea class="form-control" name="address" id="employee_address" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="employee_is_active" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark">{{ __('ui.active_status') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="employeeSubmitBtn">
                            <i class="bi bi-check2 me-1"></i> Save Employee
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
            $('#employeesTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[0, 'desc']],
                columnDefs: [{ orderable: false, targets: [1, 8] }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search employees...',
                }
            });

            // Form Reset
            window.resetEmployeeForm = function() {
                $('#employeeForm')[0].reset();
                $('#employee_id').val('');
                $('#employeeModalTitle').text('Add Employee');
                $('#employee_is_active').prop('checked', true);
                $('#current_image_container').addClass('d-none');
                $('#employeeSubmitBtn').html('<i class="bi bi-check2 me-1"></i> Save Employee');
                $('#employeeSubmitBtn').prop('disabled', false);
                $('#employee_image').val('');
            };

            // Edit Employee
            $(document).on('click', '.edit-employee', function() {
                resetEmployeeForm();
                $('#employeeModalTitle').text('Edit Employee');
                $('#employee_id').val($(this).data('id'));
                $('#employee_first_name').val($(this).data('first_name'));
                $('#employee_last_name').val($(this).data('last_name'));
                $('#employee_email').val($(this).data('email'));
                $('#employee_phone').val($(this).data('phone'));
                $('#employee_address').val($(this).data('address'));
                $('#employee_dob').val($(this).data('date_of_birth'));
                $('#employee_hire_date').val($(this).data('hire_date'));
                $('#employee_department').val($(this).data('department_id'));
                $('#employee_designation').val($(this).data('designation_id'));
                $('#employee_salary').val($(this).data('basic_salary'));
                $('#employee_is_active').prop('checked', $(this).data('is_active') == 1);

                const img = $(this).data('profile_image');
                if (img) {
                    $('#current_employee_image').attr('src', '/storage/' + img);
                    $('#current_image_container').removeClass('d-none');
                }
                $('#employeeModal').modal('show');
            });

            // Toggle Status
            $(document).on('click', '.toggle-status', function() {
                const id = $(this).data('id');
                const currentStatus = $(this).data('status');
                const action = currentStatus ? 'deactivate' : 'activate';

                Swal.fire({
                    title: `${action.charAt(0).toUpperCase() + action.slice(1)} Employee?`,
                    text: `Are you sure you want to ${action} this employee?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4F46E5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: `Yes, ${action}!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/employees/${id}/toggle-status`,
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

            // Delete Employee
            $(document).on('click', '.delete-employee', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Employee?',
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
                            url: `/admin/hr/employees/${id}`,
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
                                Swal.fire('Error', 'Failed to delete employee.', 'error');
                            }
                        });
                    }
                });
            });

            // Form Submit
            $('#employeeForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#employee_id').val();
                let url = '/admin/hr/employees';
                const data = new FormData(this);

                if (id && id !== '') {
                    url += '/' + id;
                    data.append('_method', 'PUT');
                }

                $('#employeeSubmitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            $('#employeeSubmitBtn').prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Save Employee'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save employee.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        $('#employeeSubmitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Save Employee'
                        );
                    }
                });
            });
        });
    </script>
@endsection
