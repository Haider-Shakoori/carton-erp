{{-- resources/views/admin/hr/designations/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Designation Details')

@section('css')
    <style>
        .designation-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .badge-status {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-status.active { background: var(--success-bg); color: var(--success); }
        .badge-status.inactive { background: var(--danger-bg); color: var(--danger); }

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
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-briefcase me-2"></i> {{ __('ui.designation') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> Designation #{{ $designation->id }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.designations.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('admin.hr.designations.edit', $designation) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Designation Header -->
        <div class="designation-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold">{{ $designation->name }}</h2>
                    <p class="text-muted">{{ $designation->description ?? 'No description provided.' }}</p>
                    <div class="mt-2">
                    <span class="badge-status {{ $designation->is_active ? 'active' : 'inactive' }}">
                        {{ $designation->is_active ? 'Active' : 'Inactive' }}
                    </span>
                        @if($designation->department)
                            <span class="badge bg-primary ms-2">
                            <i class="bi bi-building me-1"></i> {{ $designation->department->name }}
                        </span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-box">
                                <div class="number text-primary">{{ $designation->employees()->count() }}</div>
                                <div class="label">{{ __('ui.employees') }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box">
                                <div class="number text-warning">{{ $designation->department_id ? '1' : '0' }}</div>
                                <div class="label">{{ __('ui.department') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-1"></i> Designation Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.designation_name') }}</div>
                            <div class="info-value">{{ $designation->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.department') }}</div>
                            <div class="info-value">{{ $designation->department->name ?? 'Not Assigned' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">{{ __('ui.description') }}</div>
                            <div class="info-value" style="font-weight: 400;">{{ $designation->description ?? 'No description provided.' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.status') }}</div>
                            <div class="info-value">
                            <span class="badge-status {{ $designation->is_active ? 'active' : 'inactive' }}">
                                {{ $designation->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.employees') }}</div>
                            <div class="info-value">{{ $designation->employees()->count() }} assigned</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock me-1"></i> Quick Info
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.created_colon') }}</span>
                            <span>{{ $designation->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.last_updated_label') }}</span>
                            <span>{{ $designation->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.department_colon') }}</span>
                            <span class="fw-bold">{{ $designation->department->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.employees_colon') }}</span>
                            <span class="fw-bold">{{ $designation->employees()->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee List -->
        <div class="info-card">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-people me-1"></i> Employees with this Designation
            </h6>
            @if($designation->employees()->count() > 0)
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.employee') }}</th>
                            <th>{{ __('ui.employee_id') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($designation->employees as $employee)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                                <td>{{ $employee->employee_id }}</td>
                                <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                <td>
                                <span class="badge-status {{ $employee->is_active ? 'active' : 'inactive' }}">
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                    <p class="text-muted">No employees with this designation yet.</p>
                    <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus me-1"></i> {{ __('ui.add_employee') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
