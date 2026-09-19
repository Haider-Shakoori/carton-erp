<div class="d-flex align-items-center gap-1">
    <!-- Edit Button -->
    <button class="btn btn-sm btn-outline-warning edit-btn"
        data-id="{{ $row->id }}"
        data-name="{{ $row->name }}"
        data-category="{{ $row->account_category_id }}"
        title="{{ __('ui.edit') }}">
        <i class="bi bi-pencil"></i>
    </button>

    <!-- Delete Button -->
    <button class="btn btn-sm btn-outline-danger delete-btn"
        data-id="{{ $row->id }}"
        title="{{ __('ui.delete') }}">
        <i class="bi bi-trash"></i>
    </button>
</div>
