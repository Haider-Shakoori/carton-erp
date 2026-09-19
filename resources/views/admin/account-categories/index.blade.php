@extends('layouts.admin.base')

@section('title', __('ui.account_categories'))
@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <style>
        .badge-label {
            font-size: 0.75rem;
            padding: 0.35em 0.6em;
        }
    </style>
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('ui.account_categories') }}</h5>
            <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAdd">
                <i class="bi bi-plus-circle"></i> <span>Add New</span>
            </button>
        </div>
        <div class="card-body">
            <table class="table-hover table-striped table align-middle" id="categories-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('ui.category') }}</th>
                        <th>{{ __('ui.code_prefix') }}</th>
                        <th>Icon Preview</th>
                        <th>{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Offcanvas Add/Edit Form -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAdd">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">{{ __('ui.add_category') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" id="categoryForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="category_id" id="categoryId">

                <div class="mb-3">
                    <label class="form-label">{{ __('ui.name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('ui.code_prefix') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code_prefix" id="code_prefix" class="form-control" required maxlength="10" pattern="^[\w\s]{1,10}$" title="Only 10 words allowed">
                </div>
                <div class="mb-3">
                    <label class="form-label">Bootstrap Icon</label>
                    <div class="input-group mb-2">
                        <input type="text" name="bi_icon" id="bi_icon" class="form-control" placeholder="e.g. wallet">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#iconPickerModal">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </button>
                    </div>
                    <div class="mb-3 text-center">
                        <i id="iconPreview" class="bi fs-1" style="display:none;"></i>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Icon Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="colorPicker" value="#28a745" title="{{ __('ui.choose_color') }}">
                        <input type="text" name="bi_icon_color" id="bi_icon_color" class="form-control" value="#28a745">
                    </div>
                </div>
                <button class="btn btn-primary w-100">Save Category</button>
            </form>
        </div>
    </div>

    <!-- Icon Picker Modal -->
    <div class="modal fade" id="iconPickerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select an Icon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" id="icon-grid">
                        <!-- Icons will be populated by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        // Hardcoded Bootstrap Icons (reliable fallback)
        const allIcons = [
            'wallet', 'cash-coin', 'credit-card', 'currency-dollar', 'bank', 'building', 'shop',
            'gear', 'tools', 'person', 'people', 'person-circle', 'person-badge',
            'book', 'book-half', 'journal-text', 'clipboard',
            'calendar', 'clock', 'alarm',
            'envelope', 'chat', 'telephone', 'globe', 'geo-alt',
            'cloud', 'cloud-upload', 'cloud-download',
            'star', 'heart', 'shield', 'lock', 'unlock',
            'key', 'tag', 'tags', 'barcode',
            'image', 'camera', 'film', 'play-btn', 'pause-btn',
            'music-note', 'mic', 'volume-up',
            'search', 'filter', 'sliders', 'columns-gap',
            'folder', 'file-earmark', 'archive', 'trash', 'upload',
            'bell', 'flag', 'map', 'compass', 'location',
            'wifi', 'bluetooth', 'usb',
            'question-circle', 'exclamation-triangle', 'info-circle'
        ];

        $(document).ready(function() {
            const iconGrid = $('#icon-grid');
            const iconSearch = $('<input type="text" class="form-control mb-3" placeholder="{{ __('ui.search_icons') }}">');
            iconGrid.before(iconSearch);

            function populateIconGrid(iconList) {
                iconGrid.empty();
                iconList.forEach(icon => {
                    const button = `<div class="col-2 text-center">
                <button type="button" class="btn btn-light w-100 pick-icon" data-icon="${icon}">
                    <i class="bi bi-${icon} fs-3"></i>
                </button>
            </div>`;
                    iconGrid.append(button);
                });
            }

            populateIconGrid(allIcons);

            iconSearch.on('input', function() {
                const query = $(this).val().toLowerCase();
                const filtered = allIcons.filter(icon => icon.includes(query));
                populateIconGrid(filtered);
            });

            function updateIconPreview() {
                const icon = $('#bi_icon').val();
                const color = $('#bi_icon_color').val();
                if (icon) {
                    $('#iconPreview')
                        .attr('class', 'bi bi-' + icon + ' fs-1')
                        .css('color', color || '#333')
                        .show();
                } else {
                    $('#iconPreview').hide();
                }
            }

            $('#bi_icon').on('input', updateIconPreview);
            $('#bi_icon_color').on('input', updateIconPreview);

            $('#colorPicker').on('input', function() {
                $('#bi_icon_color').val($(this).val()).trigger('input');
            });

            $(document).on('click', '.pick-icon', function() {
                const icon = $(this).data('icon');
                $('#bi_icon').val(icon).trigger('input');
                $('#iconPickerModal').modal('hide');
            });

            updateIconPreview();

            // Edit button support
            $(document).on('click', '.edit-btn', function() {
                const data = $(this).data();
                $('#categoryId').val(data.id);
                $('#name').val(data.name);
                $('#code_prefix').val(data.prefix);
                $('#bi_icon').val(data.icon);
                $('#bi_icon_color').val(data.color);
                $('#colorPicker').val(data.color);
                $('#formMethod').val('PUT');
                $('#categoryForm').attr('action', `/admin/account-categories/${data.id}`);
                updateIconPreview();
                new bootstrap.Offcanvas('#offcanvasAdd').show();
            });

            // Delete button with Swal
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will permanently delete the category.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/account-categories/${id}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function() {
                                $('#categories-table').DataTable().ajax.reload();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Category has been deleted.',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete category.', 'error');
                            }
                        });
                    }
                });
            });

            // AJAX form submit with toast
            $('#categoryForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const method = $('#formMethod').val();
                const action = form.attr('action');
                const formData = form.serialize();

                $.ajax({
                    url: action,
                    method: method === 'PUT' ? 'POST' : 'POST',
                    data: formData,
                    success: function(res) {
                        $('#offcanvasAdd').offcanvas('hide');
                        $('#categories-table').DataTable().ajax.reload(null, false);

                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: 'Category saved successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(err) {
                        alert(`Error: ${err.responseText}`);
                    }
                });
            });


            $('#categories-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.account-categories.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: (data) => `<span class="badge bg-primary badge-label">${data}</span>`
                    },
                    {
                        data: 'code_prefix',
                        name: 'code_prefix',
                        render: (data) => `<span class="badge bg-info badge-label">${data}</span>`
                    },
                    {
                        data: 'bi_icon',
                        name: 'bi_icon',
                        render: (data, type, row) => `<span style="background-color: ${row.bi_icon_color}" class="badge d-inline-block">${data} <i class="bi bi-${data} ms-1"></i></span>`

                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });
    </script>
@endsection
