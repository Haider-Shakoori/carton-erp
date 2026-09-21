@extends('layouts.admin.base')

@section('title', __('ui.exchange_rates'))
@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('ui.exchange_rates') }}</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#sendRatesModal">
                <i class="bi bi-whatsapp me-2"></i> Send Rates
            </button>
            <button class="btn btn-primary rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#addRateCanvas">
                <i class="bi bi-plus-circle me-2"></i> Add Exchange Rate
            </button>
        </div>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-hover" id="exchange-rates-table">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 10%">{{ __('ui.date') }}</th>
                    <th style="width: 10%">Base</th>
                    <th style="width: 10%">Target</th>
                    <th style="width: 15%">Min Amount</th>
                    <th style="width: 15%">Max Amount</th>
                    <th style="width: 15%">{{ __('ui.exchange_rate') }}</th>
                    <th style="width: 15%">{{ __('ui.cost_rate') }}</th>
                    <th style="width: 15%">{{ __('ui.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Add/Edit Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="addRateCanvas">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title text-primary fw-bold">
      <i class="bi bi-currency-exchange me-2"></i> {{ __('ui.exchange_rate') }}
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body">
    <form id="rateForm">
      @csrf
      <input type="hidden" name="id" id="rate_id">

      <div class="row g-3 mb-3">
        <div class="col-md-12">
          <label class="form-label fw-semibold text-muted">💱 Base Currency</label>
          <select name="base_currency_id" class="form-select" required>
            @foreach($currencies as $currency)
              <option value="{{ $currency->id }}" @if($currency->code == 'USD') selected @endif>{{ $currency->code }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label fw-semibold text-muted">💰 Target Currency</label>
          <select name="target_currency_id" class="form-select" required>
              <option value="" disabled selected hidden>Select currency</option>
            @foreach($currencies as $currency)
              <option value="{{ $currency->id }}">{{ $currency->code }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold text-muted">🔽 Min Amount</label>
          <input type="number" step="any" name="min_amount" class="form-control" placeholder="e.g. 1000" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold text-muted">🔼 Max Amount</label>
          <input type="number" step="any" name="max_amount" class="form-control" placeholder="e.g. 10000" required>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold text-muted">📈 Exchange Rate</label>
        <input type="number" name="rate" step="any" class="form-control" placeholder="e.g. 6.70" required>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold text-muted">📈 Cost Rate</label>
        <input type="number" name="cost_rate" step="any" class="form-control" placeholder="e.g. 6.50" required>
      </div>

      <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-save"></i> <span>Save Exchange Rate</span>
      </button>
    </form>
  </div>
</div>

<!-- WhatsApp Rate Send Modal -->
<div class="modal fade" id="sendRatesModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white pb-4">
                <h5 class="modal-title text-white"><i class="bi bi-whatsapp me-1"></i> Send Rate Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="whatsappRateForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Select Customer</label>
                            <select name="customer_id" class="form-select select2" required>
                                <option value="" disabled selected>Choose customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->name }} ({{ $customer->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        @php
                            $rates = [7.18, 7.17, 7.16, 0];
                            $amounts = [150000, 100000, 20000, 20000];
                        @endphp
                        @for ($i = 1; $i <= 4; $i++)
                            @php
                                $rate = $rates[$i - 1];
                                $amount = $amounts[$i - 1];
                            @endphp
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rate {{ $i }}</label>
                            <input type="number" step="any" name="rates[{{ $i }}][rate]" class="form-control" value="{{ number_format($rate, 2) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Amount {{ $i }}
                                @if ($i === 4)
                                    (AliPay-WeChatPay - greater than 20,000)
                                @else
                                    (greater than {{ number_format($amount) }})
                                @endif
                            </label>
                            <input type="number" step="any" name="rates[{{ $i }}][amount]" class="form-control" value="{{ $amount }}" required>
                        </div>
                        @endfor
                    </div>
                </div>

                <div class="progress mb-3 d-none" id="progressWrapper">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="progressBar"
                        role="progressbar" style="width: 0%">0%</div>
                </div>

                <div class="modal-footer bg-light pt-4">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-send me-2"></i> Send via WhatsApp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $('.select2').select2({
        dropdownParent: $('#sendRatesModal'),
        width: '100%',
        placeholder: 'Choose customer',
        allowClear: true
    });


    const table = $('#exchange-rates-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.exchange-rates.data') }}',
            data: d => d.base_currency_id = $('#currency_filter').val()
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            {
                data: 'updated_at',
                name: 'updated_at',
                render: function(data) {
                    const date = new Date(data);
                    return `
                        <span style="font-size: 12px;">
                            ${date.toLocaleString('en-CA', { year: 'numeric', month: '2-digit', day: '2-digit' })}
                        </span>
                        <br>
                        <span class="badge rounded-pill bg-light text-dark" style="font-size: 12px;">
                            ${new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: 'numeric', hour12: true }).format(date)}
                        </span>
                    `;
                }
            },
            { data: 'base_currency', name: 'base_currency.name' },
            { data: 'target_currency', name: 'target_currency.name' },
            { data: 'min_amount', name: 'min_amount', render: (data, type, row) => `<strong>${row.base_currency}</strong> ${data.toLocaleString()}` },
            { data: 'max_amount', name: 'max_amount', render: (data, type, row) => `<strong>${row.base_currency}</strong> ${data.toLocaleString()}` },
            { data: 'rate', name: 'rate' },
            { data: 'cost_rate', name: 'cost_rate' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#currency_filter').change(() => table.draw());

    $('#rateForm').submit(function(e) {
        e.preventDefault();
        const id = $('#rate_id').val();
        const url = id ? `/admin/exchange-rates/${id}` : `{{ route('admin.exchange-rates.store') }}`;
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');

        $.post(url, data)
            .done(res => {
                $('#rateForm')[0].reset();
                $('#addRateCanvas').offcanvas('hide');
                $('#rate_id').val('');
                table.draw();
                Swal.fire({ icon: 'success', title: res.message, timer: 2000, showConfirmButton: false });
            })
            .fail(() => Swal.fire({ icon: 'error', title: 'Failed', text: 'Could not save rate.' }));
    });

    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/exchange-rates/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' }
                }).done(res => {
                    table.draw();
                    Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 2000, showConfirmButton: false });
                });
            }
        });
    });

    $(document).on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        $.get(`/admin/exchange-rates/${id}`, function(data) {
            const form = $('#rateForm');
            $('#rate_id').val(data.id);
            form.find('[name="base_currency_id"]').val(data.base_currency_id).trigger('change');
            form.find('[name="target_currency_id"]').val(data.target_currency_id).trigger('change');
            form.find('[name="min_amount"]').val(data.min_amount);
            form.find('[name="max_amount"]').val(data.max_amount);
            form.find('[name="rate"]').val(data.rate);
            form.find('[name="cost_rate"]').val(data.cost_rate);
            new bootstrap.Offcanvas('#addRateCanvas').show();
        });
    });

    function updateCurrencyOptions() {
        const baseVal = $('[name="base_currency_id"]').val();
        const targetVal = $('[name="target_currency_id"]').val();

        $('[name="target_currency_id"] option').show();
        if (baseVal) {
            $('[name="target_currency_id"] option[value="' + baseVal + '"]').hide();
            if (baseVal === targetVal) {
                $('[name="target_currency_id"]').val('').trigger('change');
            }
        }

        $('[name="base_currency_id"] option').show();
        if (targetVal) {
            $('[name="base_currency_id"] option[value="' + targetVal + '"]').hide();
            if (baseVal === targetVal) {
                $('[name="base_currency_id"]').val('').trigger('change');
            }
        }
    }

    // Attach event listeners
    $('[name="base_currency_id"]').on('change', updateCurrencyOptions);
    $('[name="target_currency_id"]').on('change', updateCurrencyOptions);

    // Initial setup when form is loaded
    updateCurrencyOptions();


    $('#whatsappRateForm').submit(function (e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.post("{{ route('admin.exchange-rates.send-whatsapp') }}", formData)
            .done(res => {
                Swal.fire({ icon: 'success', title: res.message, timer: 2000, showConfirmButton: false });
                $('#sendRatesModal').modal('hide');
            })
            .fail(err => {
                Swal.fire({ icon: 'error', title: 'Failed to send message' });
            });
    });

</script>
@endsection
