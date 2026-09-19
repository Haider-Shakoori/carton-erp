<section id="tab-exchange" class="tabpane" data-reload="exchange">
  <div class="section-title d-flex justify-content-between align-items-center">
    <span>{{ __('ui.exchanges') }}</span>
    <button id="toggleExchangeFilters" class="btn btn-sm btn-outline-secondary">
      <i class="fa-solid fa-filter me-1"></i> Filters
    </button>
  </div>

  <div id="xSumBase" class="summary-row mb-2"></div>
  <div id="xSumTarget" class="summary-row mb-3"></div>

  <!-- Hidden Filters Panel -->
  <div id="exchangeFiltersPanel" class="cardx mb-3" style="display:none;">
    <div class="filters">
      <div>
        <label class="form-label small mb-1">{{ __('ui.currency') }}</label>
        <select id="xCur" class="form-select form-select-sm">
          <option value="">{{ __('ui.all') }}</option>
          @foreach($currencies as $c)
            <option value="{{ $c->id }}">{{ $c->code }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="form-label small mb-1">From</label>
        <input id="xFrom" type="date" class="form-control form-control-sm">
      </div>
      <div>
        <label class="form-label small mb-1">To</label>
        <input id="xTo" type="date" class="form-control form-control-sm">
      </div>
      <div class="wide d-grid">
        <button id="xApply" class="btn btn-sm btn-primary">
          <i class="fa-solid fa-check me-1"></i> {{ __('ui.apply_filters') }}
        </button>
      </div>
    </div>
  </div>

  <div id="xList" class="list"></div>
  <div class="d-grid mt-2">
    <button id="xMore" class="btn btn-sm btn-ghost">{{ __('ui.load_more') }}</button>
  </div>
</section>
