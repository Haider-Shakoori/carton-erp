<div class="d-flex flex-column gap-1" style="min-width: 100px;">
    @if ($r->status === 'pending')
        <div style="width: 100%;">
            <button class="btn btn-sm btn-outline-success btn-approve w-100 d-flex justify-content-center align-items-center gap-1" data-id="{{ $r->id }}"
                title="{{ __('ui.approve_remittance') }}">
                <i class="bi bi-check-circle-fill"></i> <span>{{ __('ui.approve') }}</span>
            </button>
        </div>
    @endif

    <div style="width: 100%;">
        <button class="btn btn-sm btn-outline-warning btn-edit w-100 d-flex justify-content-center align-items-center gap-1" data-id="{{ $r->id }}" title="Edit Remittance">
            <i class="bi bi-pencil-fill"></i> <span>{{ __('ui.edit') }}</span>
        </button>
    </div>

    <div style="width: 100%;">
        <a href="{{ route('admin.remittances.print', $r->id) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 d-flex justify-content-center align-items-center gap-1"
            title="{{ __('ui.print_remittance') }}">
            <i class="bi bi-printer-fill"></i> <span>Print</span>
        </a>
    </div>

    @if ($r->status === 'processed')
        <div style="width: 100%;">
            <a href="{{ route('admin.remittances.whatsapp', $r->id) }}" class="btn btn-sm btn-outline-success w-100 d-flex justify-content-center align-items-center gap-1"
                title="{{ __('ui.send_whatsapp') }}">
                <i class="bi bi-whatsapp"></i> <span>{{ __('ui.whatsapp') }}</span>
            </a>
        </div>
    @endif
    @if($r->receipt_file)
<div style="width: 100%;">
  <a href="{{ url($r->receipt_file) }}" target="_blank"
     class="btn btn-sm btn-outline-info w-100 d-flex justify-content-center align-items-center gap-1">
     <i class="bi bi-file-earmark-image"></i> <span>Receipt</span>
  </a>
</div>
@endif

    <div style="width: 100%;">
        <button class="btn btn-sm btn-outline-danger btn-delete w-100 d-flex justify-content-center align-items-center gap-1" data-id="{{ $r->id }}"
            title="{{ __('ui.delete_remittance') }}">
            <i class="bi bi-trash3-fill"></i> <span>{{ __('ui.delete') }}</span>
        </button>
    </div>
</div>
