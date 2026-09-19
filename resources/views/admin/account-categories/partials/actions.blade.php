<div class="d-flex align-items-center gap-1">
    <button class="btn btn-sm btn-outline-warning edit-btn"
        data-id="{{ $row->id }}"
        data-name="{{ $row->name }}"
        data-prefix="{{ $row->code_prefix }}"
        data-icon="{{ $row->bi_icon }}"
        data-color="{{ $row->bi_icon_color }}"
        title="{{ __('ui.edit') }}">
        <i class="bi bi-pencil"></i>
    </button>
    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="{{ $row->id }}" title="{{ __('ui.delete') }}">
    <i class="bi bi-trash"></i>
</button>
</div>
