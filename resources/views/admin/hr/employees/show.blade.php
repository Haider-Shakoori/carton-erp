{{-- resources/views/admin/hr/employees/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Employee Details')

@section('css')
    <style>
        .profile-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--gray-200);
            margin: 0 auto 1rem;
        }
        .profile-avatar.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
        }
        .profile-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
        }
        .profile-role {
            font-size: 0.9rem;
            color: var(--gray-500);
        }
        .profile-id {
            font-size: 0.8rem;
            color: var(--gray-400);
            font-weight: 600;
        }
        .info-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .info-card .info-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .info-card .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        .stat-box {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 1rem;
            text-align: center;
        }
        .stat-box .number {
            font-size: 1.5rem;
            font-weight: 800;
        }
        .stat-box .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .badge-status {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-status.active { background: var(--success-bg); color: var(--success); }
        .badge-status.inactive { background: var(--danger-bg); color: var(--danger); }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--gray-300);
            border: 2px solid white;
        }
        .timeline-item.active::before {
            background: var(--primary);
        }
        .timeline-item .timeline-title {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .timeline-item .timeline-date {
            font-size: 0.7rem;
            color: var(--gray-400);
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
                        <i class="bi bi-person-badge me-2"></i> {{ __('ui.employee') }} <span class="accent">{{ __('ui.profile') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> {{ $employee->employee_id }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                    </a>
                    <button class="btn btn-outline-danger delete-employee" data-id="{{ $employee->id }}">
                        <i class="bi bi-trash me-1"></i> {{ __('ui.delete') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3">
                    @if ($employee->profile_image)
                        <img src="{{ Storage::url($employee->profile_image) }}" class="profile-avatar">
                    @else
                        <div class="profile-avatar placeholder">
                            {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="profile-name">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                    <div class="profile-role">
                        {{ $employee->designation->name ?? 'No Designation' }}
                        @if($employee->department)
                            <span class="text-muted">· {{ $employee->department->name }}</span>
                        @endif
                    </div>
                    <div class="profile-id">{{ $employee->employee_id }}</div>
                    <div class="mt-2">
                    <span class="badge-status {{ $employee->is_active ? 'active' : 'inactive' }}">
                        {{ $employee->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-grid gap-2">
                        <a href="mailto:{{ $employee->email }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-envelope me-1"></i> {{ __('ui.email') }}
                        </a>
                        @if($employee->phone)
                            <a href="tel:{{ $employee->phone }}" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-telephone me-1"></i> Call
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="number text-primary">{{ $employee->attendances()->count() }}</div>
                    <div class="label">Total Attendance</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="number text-success">{{ $employee->leaves()->where('status', 'approved')->count() }}</div>
                    <div class="label">Leaves Taken</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="number text-warning">{{ $employee->advances()->where('status', 'approved')->count() }}</div>
                    <div class="label">{{ __('ui.advances') }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <div class="number text-purple">{{ $employee->payrolls()->count() }}</div>
                    <div class="label">Payroll Records</div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Personal Information -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-person me-1"></i> Personal Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.full_name') }}</div>
                            <div class="info-value">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.employee_id') }}</div>
                            <div class="info-value">{{ $employee->employee_id }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.email') }}</div>
                            <div class="info-value">{{ $employee->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.phone') }}</div>
                            <div class="info-value">{{ $employee->phone ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.date_of_birth') }}</div>
                            <div class="info-value">{{ $employee->date_of_birth ? $employee->date_of_birth->format('M d, Y') : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.hire_date') }}</div>
                            <div class="info-value">{{ $employee->hire_date ? $employee->hire_date->format('M d, Y') : 'N/A' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">{{ __('ui.address') }}</div>
                            <div class="info-value" style="font-weight: 400;">{{ $employee->address ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-briefcase me-1"></i> Employment Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.department') }}</div>
                            <div class="info-value">{{ $employee->department->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.designation') }}</div>
                            <div class="info-value">{{ $employee->designation->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.basic_salary') }}</div>
                            <div class="info-value">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($employee->basic_salary ?? 0, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.status') }}</div>
                            <div class="info-value">
                            <span class="badge-status {{ $employee->is_active ? 'active' : 'inactive' }}">
                                {{ $employee->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-gear me-1"></i> Quick Actions
                    </h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.hr.employees.edit', $employee) }}" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> Edit Profile
                        </a>
                        <button class="btn btn-outline-primary toggle-status" data-id="{{ $employee->id }}" data-status="{{ $employee->is_active }}">
                            <i class="bi bi-{{ $employee->is_active ? 'pause-circle' : 'play-circle' }} me-1"></i>
                            {{ $employee->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button class="btn btn-outline-danger delete-employee" data-id="{{ $employee->id }}">
                            <i class="bi bi-trash me-1"></i> Delete Employee
                        </button>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock me-1"></i> Quick Info
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.created_colon') }}</span>
                            <span>{{ $employee->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.last_updated_label') }}</span>
                            <span>{{ $employee->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                        @if($employee->deleted_at)
                            <div class="d-flex justify-content-between py-1 text-danger">
                                <span>Deleted:</span>
                                <span>{{ $employee->deleted_at->format('M d, Y h:i A') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
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
                Swal.fire({
                    title: 'Delete Employee?',
                    text: 'Are you sure you want to delete this employee? This action cannot be undone.',
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
                                        window.location.href = '{{ route("admin.hr.employees.index") }}';
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
        });
    </script>
@endsection
