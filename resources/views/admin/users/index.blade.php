@extends('layouts.admin.base')
@section('title', 'Users Management')
@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
@endsection
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">User Accounts</h5>
            <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasAddUser">
                <i class="bi bi-plus-circle"></i>
                <span>Create Account</span>
            </button>
        </div>

        <div class="card-body">
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                        <select class="form-select" id="account_type">
                            <option value="">User Type</option>
                            <option value="admin">{{ __('ui.admin') }}</option>
                            <option value="client">Client</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search" placeholder="{{ __('ui.search_dots') }}">
                        <button class="btn btn-outline-secondary" type="button" id="search-btn">
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-bordered table-hover table-responsive table" id="yajra-datatable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.account') }}</th>
                            <th>{{ __('ui.username') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.permissions') }}</th>
                            <th>{{ __('ui.status') }}</th>
                            <th>{{ __('ui.created') }}</th>
                            <th style="min-width: 120px;">Manage</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('admin.users.partials.create')
    @include('admin.users.partials.edit')

@endsection

@section('js')
    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(function() {
            const table = $('#yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.users.fetch') }}",
                    data: function(d) {
                        d.account_type = $('#account_type').val();
                        d.search_term = $('#search').val();
                    }
                },
                columns: [{
                        data: null,
                        name: 'row_index',
                        searchable: false,
                        orderable: false,
                        render: (data, type, row, meta) => meta.row + 1
                    },
                    {
                        data: 'name_with_profile',
                        name: 'users.name'
                    },
                    {
                        data: 'username',
                        name: 'users.username',
                        render: data => `<span class="badge bg-label-primary">${data}</span>`
                    },
                    {
                        data: 'account_type',
                        name: 'users.account_type'
                    },
                    {
                        data: 'total_permissions',
                        name: 'users.total_permissions'
                    },
                    {
                        data: 'is_active',
                        name: 'users.is_active'
                    },
                    {
                        data: 'created_at',
                        name: 'users.created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [2, 'asc']
                ],
                pageLength: 10,
                searching: false,
                lengthChange: false,
                info: false
            });

            // Filters
            $('#account_type, #search-btn').on('change click', () => table.draw());
            $('#search').on('keyup', e => e.key === 'Enter' && table.draw());

            // Prevent spaces in username
            $('#account-username').on('input', function() {
                this.value = this.value.replace(/\s/g, '');
            });

            // Password match validation
            $('#account-password, #account-repeat-password').on('input', function() {
                const pass = $('#account-password').val();
                const repeat = $('#account-repeat-password').val();
                const feedback = $('#password-feedback');
                const submit = $('#submit-button');

                if (!repeat) {
                    feedback.html('');
                    submit.prop('disabled', true);
                    return;
                }

                if (pass === repeat) {
                    feedback.html('<span class="text-success">✅ Passwords match</span>');
                    submit.prop('disabled', false);
                } else {
                    feedback.html('<span class="text-danger">❌ Passwords do not match</span>');
                    submit.prop('disabled', true);
                }
            });
        });
    </script>
    <script>
        $(document).on('click', '.edit-user-btn', function() {
            const id = $(this).data('id');
            $.get(`/admin/users/${id}/edit`, function(user) {
                $('#offcanvasEditUserLabel').text('Edit User');
                $('#edit-user-id').val(user.id);
                $('#edit-name').val(user.name);
                $('#edit-username').val(user.username);
                $('#edit-is-active').prop('checked', user.is_active);
                $('#editUserForm').attr('action', `/admin/users/${user.id}`);
                const offcanvas = new bootstrap.Offcanvas('#offcanvasEditUser');
                offcanvas.show();
            });
        });

        // Submit Edit Form
        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const url = form.attr('action');

            $.ajax({
                type: 'POST', // must be POST (not PUT), since Laravel detects PUT via _method
                url: url,
                data: form.serialize(),
                success: function() {
                    bootstrap.Offcanvas.getInstance(document.getElementById('offcanvasEditUser'))
                        .hide();
                    $('#yajra-datatable').DataTable().ajax.reload();
                    Swal.fire('Updated!', 'User updated successfully.', 'success');
                },
                error: function() {
                    Swal.fire('Error', 'Something went wrong.', 'error');
                }
            });
        });


        // Delete User
        $(document).on('click', '.delete-user-btn', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the user permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/users/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#yajra-datatable').DataTable().ajax.reload();
                            Swal.fire('Deleted!', response.message, 'success');
                        },
                        error: function(xhr) {
                            let errorMessage = 'Something went wrong.';

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            Swal.fire('Error', errorMessage, 'error');
                        }
                    });
                }
            });
        });
    </script>
@endsection
