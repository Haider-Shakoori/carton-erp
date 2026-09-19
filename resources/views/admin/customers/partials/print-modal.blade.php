{{-- resources/views/admin/customers/partials/print-modal.blade.php --}}
<div class="modal fade" id="printStatementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; border: none;">
                <h5 class="modal-title">
                    <i class="bi bi-printer me-2"></i> Print Statement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Select the currency for the statement you want to print:</p>
                <form id="printStatementForm" action="{{ route('admin.customers.print', $customer->id ?? 0) }}"
                    method="GET" target="_blank">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">{{ __('ui.currency') }} <span class="text-danger">*</span></label>
                        <select name="currency_id" class="form-select form-select-lg" required>
                            <option value="">{{ __('ui.select_currency') }}</option>
                            @foreach ($currencies ?? [] as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-printer me-2"></i> Print Statement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- PDF Export Modal --}}
<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #dc3545, #b91c1c); color: white; border: none;">
                <h5 class="modal-title">
                    <i class="bi bi-file-pdf me-2"></i> {{ __('ui.export_pdf') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Select the currency for the PDF export:</p>
                <form id="exportPdfForm" action="{{ route('admin.customers.export-pdf', $customer->id ?? 0) }}"
                    method="GET">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">{{ __('ui.currency') }} <span class="text-danger">*</span></label>
                        <select name="currency_id" class="form-select form-select-lg" required>
                            <option value="">{{ __('ui.select_currency') }}</option>
                            @foreach ($currencies ?? [] as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="bi bi-file-pdf me-2"></i> Download PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- CSV Export Modal --}}
<div class="modal fade" id="exportCsvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #059669, #047857); color: white; border: none;">
                <h5 class="modal-title">
                    <i class="bi bi-file-spreadsheet me-2"></i> Export CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Select the currency for the CSV export:</p>
                <form id="exportCsvForm" action="{{ route('admin.customers.export-csv', $customer->id ?? 0) }}"
                    method="GET">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">{{ __('ui.currency') }} <span class="text-danger">*</span></label>
                        <select name="currency_id" class="form-select form-select-lg" required>
                            <option value="">{{ __('ui.select_currency') }}</option>
                            @foreach ($currencies ?? [] as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-file-spreadsheet me-2"></i> Download CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
