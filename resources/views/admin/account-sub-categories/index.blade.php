@extends('layouts.admin.base')

@section('title', __('ui.account_sub_categories'))
@section('css')
<link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
<style>
  .datatable-header {
    margin-bottom: 1.5rem;
  }
  .table th,
  .table td {
    vertical-align: middle;
  }
</style>
@endsection

@section('content')
<div class="card shadow-sm border-0">
  <div class="card-header d-flex justify-content-between align-items-center py-3">
    <div>
      <h4 class="mb-0">{{ __('ui.account_sub_categories') }}</h4>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasForm">
      <i class="bi bi-plus-circle"></i> <span>Add Sub Category</span>
    </button>
  </div>
  <div class="card-body pt-2">
    <div class="row mb-3">
      <div class="col-md-4">
        <select id="category_filter" class="form-select">
          <option value="">Filter by Category</option>
          @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <table class="table table-striped table-bordered table-hover border align-middle" id="subcategories-table">
      <thead class="table-light">
        <tr>
          <th style="width: 5%">#</th>
          <th>{{ __('ui.name') }}</th>
          <th>{{ __('ui.category') }}</th>
          <th style="width: 15%">{{ __('ui.actions') }}</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasForm">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title">Sub Category Form</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form id="subcategoryForm">
      @csrf
      <input type="hidden" name="_method" id="formMethod" value="POST">
      <input type="hidden" name="id" id="subcategoryId">

      <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('ui.name') }}</label>
        <input type="text" name="name" id="name" class="form-control" placeholder="{{ __('ui.enter_subcategory_name') }}" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('ui.account_category') }}</label>
        <select name="account_category_id" id="account_category_id" class="form-select" required>
          <option value="">-- Select Parent Category --</option>
          @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn btn-success w-100 mt-4">
        <i class="bi bi-save me-1"></i> Save Sub Category
      </button>
    </form>
  </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
  const table = $('#subcategories-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route('admin.account-sub-categories.index') }}',
      data: function (d) {
        d.category_id = $('#category_filter').val();
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
      {
        data: 'name',
        name: 'name',
        render: (data) => `<span class="badge bg-primary badge-label">${data}</span>`
      },
      {
        data: 'category',
        name: 'category',
        render: (data) => `<span class="badge bg-info badge-label">${data}</span>`
      },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    order: [[0, 'asc']],
    responsive: true,
    autoWidth: false,
    pageLength: 10,
    searching: false,
    lengthChange: false,
    info: false
  });

  $('#category_filter').on('change', function () {
    table.ajax.reload();
  });

  $('#subcategoryForm').on('submit', function (e) {
    e.preventDefault();
    const method = $('#formMethod').val();
    const id = $('#subcategoryId').val();
    const url = id ? `/admin/account-sub-categories/${id}` : '{{ route('admin.account-sub-categories.store') }}';

    $.ajax({
      url: url,
      method: 'POST',
      data: $(this).serialize(),
      success: function () {
        $('#offcanvasForm').offcanvas('hide');
        table.ajax.reload();
        Swal.fire({ icon: 'success', title: 'Saved', text: 'Sub category saved successfully', timer: 1500, showConfirmButton: false });
      },
      error: function () {
        Swal.fire('Error', 'Something went wrong', 'error');
      }
    });
  });

  $(document).on('click', '.edit-btn', function () {
    const data = $(this).data();
    $('#subcategoryId').val(data.id);
    $('#name').val(data.name);
    $('#account_category_id').val(data.category);
    $('#formMethod').val('PUT');
    $('#subcategoryForm').attr('action', `/admin/account-sub-categories/${data.id}`);
    new bootstrap.Offcanvas('#offcanvasForm').show();
  });

  $(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Are you sure?',
      text: 'This will delete the subcategory.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `/admin/account-sub-categories/${id}`,
          type: 'DELETE',
          data: { _token: '{{ csrf_token() }}' },
          success: function () {
            table.ajax.reload();
            Swal.fire({ icon: 'success', title: 'Deleted', text: 'Sub category deleted.', timer: 1500, showConfirmButton: false });
          },
          error: function () {
            Swal.fire('Error', 'Failed to delete', 'error');
          }
        });
      }
    });
  });
});
</script>
@endsection
