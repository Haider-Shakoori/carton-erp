<section id="tab-journal" class="tabpane" data-reload="journal">
  <div class="section-title d-flex justify-content-between align-items-center">
    <span>{{ __('ui.journal') }}</span>
    <button id="toggleFilters" class="btn btn-sm btn-outline-secondary">
      <i class="fa-solid fa-filter me-1"></i> Filters
    </button>
  </div>

  <div id="journalBalances" class="mb-3"></div>

  <!-- Hidden Filters Panel -->
  <div id="filtersPanel" class="cardx mb-3" style="display:none;">
    <div class="filters">
      <div>
        <label class="form-label small mb-1">{{ __('ui.currency') }}</label>
        <select id="jCur" class="form-select form-select-sm">
          <option value="">{{ __('ui.all') }}</option>
          @foreach($currencies as $c)
            <option value="{{ $c->id }}">{{ $c->code }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label small mb-1">{{ __('ui.type') }}</label>
        <select id="jType" class="form-select form-select-sm">
          <option value="">{{ __('ui.all') }}</option>
          <option value="credit">{{ __('ui.credit') }}</option>
          <option value="debit">Debit</option>
        </select>
      </div>
      <div>
        <label class="form-label small mb-1">From</label>
        <input id="jFrom" type="date" class="form-control form-control-sm">
      </div>
      <div>
        <label class="form-label small mb-1">To</label>
        <input id="jTo" type="date" class="form-control form-control-sm">
      </div>
      <div class="wide d-grid">
        <button id="jApply" class="btn btn-sm btn-primary">
          <i class="fa-solid fa-check me-1"></i> {{ __('ui.apply_filters') }}
        </button>
      </div>
    </div>
  </div>


  <!-- Full Width Download Button -->
  <div class="d-grid mb-3">
    <button id="jDownload" class="btn btn-primary w-100">
      <i class="fa-solid fa-file-pdf me-1"></i> Download Statement
    </button>
  </div>

  <div id="jList" class="list"></div>
  <div class="d-grid mt-2">
    <button id="jMore" class="btn btn-sm btn-ghost">{{ __('ui.load_more') }}</button>
  </div>
</section>
