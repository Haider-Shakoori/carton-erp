@extends('layouts.admin.base')

@section('content')
    <div class="container-xxl">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">{{ __('ui.categories') }}</h4>

            <button type="button" class="btn btn-primary" id="addCategoryBtn">
                + Add Category
            </button>
        </div>

        {{-- TABLE --}}
        <div class="card">
            <div class="card-body">

                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.name') }}</th>
                            <th width="180">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        {{-- Example loop --}}
                        @foreach ([] as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $category->name }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning">{{ __('ui.edit') }}</button>
                                    <button class="btn btn-sm btn-danger">{{ __('ui.delete') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>

    </div>

    {{-- ================= MODAL ================= --}}
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.add_category') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form method="POST" action="{{ route('admin.app.categories.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.category_name') }}</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                Save
                            </button>
                        </div>

                    </form>

                </div>

            </div>

        </div>
    </div>
@endsection

{{-- ================= JS ================= --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const addBtn = document.getElementById('addCategoryBtn');

            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    const modalEl = document.getElementById('addModal');

                    if (modalEl) {
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                });
            }

        });
    </script>
@endpush
