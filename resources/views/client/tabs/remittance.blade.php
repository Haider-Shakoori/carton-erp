<section id="tab-remit" class="tabpane" data-reload="remit">
  <div class="section-title d-flex justify-content-between align-items-center">
    <span>{{ __('ui.remittances') }}</span>
    <button id="toggleRemitFilters" class="btn btn-sm btn-outline-secondary">
      <i class="fa-solid fa-filter me-1"></i> Filters
    </button>
  </div>

  <!-- Summary Row -->
  <div class="summary-row mb-2" id="rTop">
    <div class="summary-chip"><small>{{ __('ui.pending') }}</small><strong id="rPending">—</strong></div>
    <div class="summary-chip"><small>{{ __('ui.approved') }}</small><strong id="rApproved">—</strong></div>
  </div>

  <!-- Hidden Filter Panel -->
  <div id="remitFiltersPanel" class="cardx mb-3" style="display:none;">
    <div class="filters">
      <div>
        <label class="form-label small mb-1">{{ __('ui.currency') }}</label>
        <select id="rCur" class="form-select form-select-sm">
          <option value="">{{ __('ui.all') }}</option>
          @foreach($currencies as $c)
            <option value="{{ $c->id }}">{{ $c->code }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label small mb-1">From</label>
        <input id="rFrom" type="date" class="form-control form-control-sm">
      </div>
      <div>
        <label class="form-label small mb-1">To</label>
        <input id="rTo" type="date" class="form-control form-control-sm">
      </div>
      <div class="wide d-grid">
        <button id="rApply" class="btn btn-sm btn-primary">
          <i class="fa-solid fa-check me-1"></i> {{ __('ui.apply_filters') }}
        </button>
      </div>
    </div>
  </div>

  <div id="rSumRow" class="summary-row mb-3"></div>
  <div id="rList" class="list"></div>
  <div class="d-grid mt-2">
    <button id="rMore" class="btn btn-sm btn-ghost">{{ __('ui.load_more') }}</button>
  </div>
</section>

<!-- Remittance Detail Modal -->
<div class="modal fade" id="remitModal" tabindex="-1" aria-labelledby="remitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="remitModalLabel">Remittance Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
      </div>
      <div class="modal-body" id="remitModalBody">
        <!-- Filled dynamically -->
      </div>
    </div>
  </div>
</div>
