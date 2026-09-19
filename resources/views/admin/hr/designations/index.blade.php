{{-- resources/views/admin/hr/designations/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Designation Management')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        /* ─── Modern Card Styles ─── */
        .designation-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            transition: var(--transition);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .designation-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .designation-card .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 0.75rem;
        }
        .designation-card .card-icon.primary { background: var(--primary-bg); color: var(--primary); }
        .designation-card .card-icon.success { background: var(--success-bg); color: var(--success); }
        .designation-card .card-icon.warning { background: var(--warning-bg); color: var(--warning); }
        .designation-card .card-icon.danger { background: var(--danger-bg); color: var(--danger); }
        .designation-card .card-icon.info { background: var(--info-bg); color: var(--info); }
        .designation-card .card-icon.purple { background: #f3e8ff; color: #7c3aed; }

        .designation-card .designation-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }
        .designation-card .designation-dept {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        .designation-card .designation-desc {
            font-size: 0.8rem;
            color: var(--gray-600);
            margin-top: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .designation-card .employee-count {
            font-size: 0.7rem;
            color: var(--gray-400);
        }
        .designation-card .employee-count strong {
            color: var(--gray-700);
        }

        .badge-status {
            padding: 0.2rem 0.75rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .badge-status.active {
            background: var(--success-bg);
            color: var(--success);
        }
        .badge-status.inactive {
            background: var(--danger-bg);
            color: var(--danger);
        }
        .badge-status .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .badge-status.active .dot { background: var(--success); }
        .badge-status.inactive .dot { background: var(--danger); }

        /* ─── Stats Grid ─── */
        .stats-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-item {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }
        .stat-item:hover {
            box-shadow: var(--shadow-md);
        }
        .stat-item .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-item .stat-icon.purple { background: #f3e8ff; color: #7c3aed; }
        .stat-item .stat-icon.green { background: var(--success-bg); color: var(--success); }
        .stat-item .stat-icon.blue { background: var(--info-bg); color: var(--info); }
        .stat-item .stat-icon.orange { background: #fff7ed; color: #f97316; }

        .stat-item .stat-info .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
            line-height: 1.2;
        }
        .stat-item .stat-info .stat-label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        /* ─── Action Buttons ─── */
        .action-group {
            display: flex;
            gap: 0.25rem;
            justify-content: flex-end;
        }
        .action-btn-modern {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            transition: var(--transition);
            background: transparent;
            cursor: pointer;
        }
        .action-btn-modern:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        .action-btn-modern.edit:hover {
            background: var(--primary-bg);
            color: var(--primary);
        }
        .action-btn-modern.toggle:hover {
            background: var(--warning-bg);
            color: var(--warning);
        }
        .action-btn-modern.delete:hover {
            background: var(--danger-bg);
            color: var(--danger);
        }

        /* ─── Table Enhancements ─── */
        .table-modern-enhanced {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
        }
        .table-modern-enhanced thead th {
            padding: 0.75rem 1rem;
            background: var(--gray-50);
            color: var(--gray-500);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: none;
            white-space: nowrap;
        }
        .table-modern-enhanced thead th:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }
        .table-modern-enhanced thead th:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

        .table-modern-enhanced tbody tr {
            background: white;
            transition: var(--transition);
            border-radius: var(--radius-sm);
        }
        .table-modern-enhanced tbody tr:hover {
            box-shadow: var(--shadow-sm);
        }
        .table-modern-enhanced tbody td {
            padding: 0.75rem 1rem;
            border: none;
            color: var(--gray-700);
            vertical-align: middle;
        }
        .table-modern-enhanced tbody td:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }
        .table-modern-enhanced tbody td:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

        .designation-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .designation-badge .badge-icon {
            font-size: 0.6rem;
        }

        .dept-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.6rem;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 500;
            background: var(--gray-100);
            color: var(--gray-600);
        }
        .dept-tag i {
            font-size: 0.55rem;
        }

        /* ─── Search Bar ─── */
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }
        .search-wrapper .form-control {
            padding-left: 36px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            background: var(--gray-50);
            transition: var(--transition);
        }
        .search-wrapper .form-control:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }

        /* ─── Empty State ─── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state .empty-icon {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        .empty-state .empty-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--gray-700);
        }
        .empty-state .empty-desc {
            color: var(--gray-500);
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- ─── Page Header ─── -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-briefcase me-2"></i> <span class="accent">{{ __('ui.designations') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-tags me-1"></i> Manage job designations and positions across departments
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#designationModal" onclick="resetDesignationForm()">
                        <i class="bi bi-plus-circle me-1"></i> Add Designation
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── Stats ─── -->
        <div class="stats-grid-modern">
            <div class="stat-item">
                <div class="stat-icon purple">
                    <i class="bi bi-briefcase"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $designations->total() }}</div>
                    <div class="stat-label">Total Designations</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon green">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $designations->where('is_active', true)->count() }}</div>
                    <div class="stat-label">{{ __('ui.active') }}</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon orange">
                    <i class="bi bi-pause-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $designations->where('is_active', false)->count() }}</div>
                    <div class="stat-label">{{ __('ui.inactive') }}</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon blue">
                    <i class="bi bi-building"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $departments->count() }}</div>
                    <div class="stat-label">{{ __('ui.departments') }}</div>
                </div>
            </div>
        </div>

        <!-- ─── Designation Table ─── -->
        <div class="table-card">
            <div class="card-header-custom">
                <span class="fw-semibold">
                    <i class="bi bi-list-ul me-1"></i> Designation List
                </span>
                <div class="d-flex align-items-center gap-3">
                    <span class="header-badge">
                        <i class="bi bi-database me-1"></i> Total: {{ $designations->total() }}
                    </span>
                    <div class="search-wrapper">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control form-control-sm" id="designationSearch" placeholder="{{ __('ui.search_designations') }}">
                    </div>
                </div>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-modern-enhanced" id="designationsTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>{{ __('ui.designation') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.description') }}</th>
                            <th class="text-center">{{ __('ui.employees') }}</th>
                            <th style="width: 110px;">{{ __('ui.status') }}</th>
                            <th style="width: 130px;" class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($designations as $designation)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="designation-badge">
                                                <span class="badge-icon">
                                                    <i class="bi bi-tag-fill" style="color: var(--primary);"></i>
                                                </span>
                                            <span class="fw-semibold text-dark">{{ $designation->name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($designation->department)
                                        <span class="dept-tag">
                                                <i class="bi bi-building"></i>
                                                {{ $designation->department->name }}
                                            </span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-dash-circle"></i> {{ __('ui.unassigned') }}
                                            </span>
                                    @endif
                                </td>
                                <td>
                                        <span style="font-size: 0.8rem; color: var(--gray-600);">
                                            {{ $designation->description ?? '-' }}
                                        </span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-primary">{{ $designation->employees()->count() }}</span>
                                </td>
                                <td>
                                        <span class="badge-status {{ $designation->is_active ? 'active' : 'inactive' }}">
                                            <span class="dot"></span>
                                            {{ $designation->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <button class="action-btn-modern edit"
                                                data-id="{{ $designation->id }}"
                                                data-name="{{ $designation->name }}"
                                                data-department_id="{{ $designation->department_id }}"
                                                data-description="{{ $designation->description }}"
                                                data-is_active="{{ $designation->is_active }}"
                                                title="Edit Designation">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="action-btn-modern toggle"
                                                data-id="{{ $designation->id }}"
                                                data-status="{{ $designation->is_active }}"
                                                title="{{ $designation->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi bi-{{ $designation->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                        <button class="action-btn-modern delete"
                                                data-id="{{ $designation->id }}"
                                                data-name="{{ $designation->name }}"
                                                title="{{ __('ui.delete_designation') }}">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <div class="empty-title">No Designations Found</div>
                                        <div class="empty-desc">Start by adding your first job designation.</div>
                                        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#designationModal" onclick="resetDesignationForm()">
                                            <i class="bi bi-plus-circle me-1"></i> Add Designation
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $designations->firstItem() ?? 0 }} to {{ $designations->lastItem() ?? 0 }} of {{ $designations->total() }} entries
                    </div>
                    <div>
                        {{ $designations->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Designation Modal ─── -->
    <div class="modal fade" id="designationModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-briefcase me-2" style="color: white;"></i>
                        <span id="designationModalTitle" style="color: white;">Add Designation</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="designationForm" method="POST">
                    @csrf
                    <input type="hidden" name="designation_id" id="designation_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.designation_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="designation_name" required placeholder="e.g., Senior Manager">
                            @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.department') }}</label>
                            <select class="form-select select2-department" name="department_id" id="designation_department">
                                <option value="">{{ __('ui.select_department') }}</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.description') }}</label>
                            <textarea class="form-control" name="description" id="designation_description" rows="3" placeholder="Optional designation description..."></textarea>
                            @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" id="designation_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold text-dark">{{ __('ui.active_status') }}</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="designationSubmitBtn">
                            <i class="bi bi-check2 me-1"></i> Save Designation
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
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ─── Initialize Select2 ───
            $('.select2-department').select2({
                dropdownParent: $('#designationModal'),
                width: '100%',
                placeholder: 'Select Department...',
                allowClear: true
            });

            // ─── Initialize DataTable ───
            const table = $('#designationsTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [6] },
                    { orderable: false, targets: [4] } // Employees count
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search designations...',
                },
                dom: 't',
                responsive: true
            });

            // ─── Custom Search ───
            $('#designationSearch').on('keyup', function() {
                table.search($(this).val()).draw();
            });

            // ─── Form Reset ───
            window.resetDesignationForm = function() {
                $('#designationForm')[0].reset();
                $('#designation_id').val('');
                $('#designationModalTitle').text('Add Designation');
                $('#designation_is_active').prop('checked', true);
                $('#designationSubmitBtn').html('<i class="bi bi-check2 me-1"></i> Save Designation');
                $('#designationSubmitBtn').prop('disabled', false);
                $('.select2-department').val(null).trigger('change');
            };

            // ─── Edit Designation ───
            $(document).on('click', '.edit-designation', function() {
                resetDesignationForm();
                $('#designationModalTitle').text('Edit Designation');
                $('#designation_id').val($(this).data('id'));
                $('#designation_name').val($(this).data('name'));
                $('#designation_department').val($(this).data('department_id')).trigger('change');
                $('#designation_description').val($(this).data('description'));
                $('#designation_is_active').prop('checked', $(this).data('is_active') == 1);
                $('#designationModal').modal('show');
            });

            // ─── Toggle Status ───
            $(document).on('click', '.toggle', function() {
                const id = $(this).data('id');
                const currentStatus = $(this).data('status');
                const action = currentStatus ? 'deactivate' : 'activate';

                Swal.fire({
                    title: `${action.charAt(0).toUpperCase() + action.slice(1)} Designation?`,
                    text: `Are you sure you want to ${action} this designation?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4F46E5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: `Yes, ${action}!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/designations/${id}/toggle-status`,
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

            // ─── Delete Designation ───
            $(document).on('click', '.delete', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Designation?',
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
                            url: `/admin/hr/designations/${id}`,
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
                                Swal.fire('Error', 'Failed to delete designation.', 'error');
                            }
                        });
                    }
                });
            });

            // ─── Form Submit ───
            $('#designationForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#designation_id').val();
                let url = '/admin/hr/designations';
                const data = $(this).serialize();

                if (id && id !== '') {
                    url += '/' + id;
                    data += '&_method=PUT';
                }

                $('#designationSubmitBtn').prop('disabled', true).html(
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
                            $('#designationSubmitBtn').prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Save Designation'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save designation.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        $('#designationSubmitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Save Designation'
                        );
                    }
                });
            });
        });
    </script>
@endsection
