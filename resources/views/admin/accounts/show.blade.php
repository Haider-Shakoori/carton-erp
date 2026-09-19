@extends('layouts.admin.base')

@section('title', 'Account Details')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('vendor/fontawesome/7.3.1/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
<link href="{{ asset('vendor/fonts/droid-arabic-kufi/droidarabickufi.css') }}" rel="stylesheet">
<style>
    * {
        color: initial;
        background-color: initial;
    }
</style>

<style>
    html { font-size: 15px; }

    .export-box {
        background: #fff;
        max-width: 750px;
        margin: 2rem auto;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.05);
    }

    .export-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #ddd;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
    }

    .export-header img { height: 40px; }

    .balance-positive {
        color: white !important;
        text-shadow: 0 0 5px black;
    }

    .balance-negative {
        color: yellow !important;
        font-weight: 700;
        text-shadow: 0 0 5px black;
    }

    .export-footer {
        text-align: center;
        font-size: 0.85rem;
        color: #666;
    }

    .export-actions {
        max-width: 750px;
        margin: 1rem auto;
        text-align: right;
    }
</style>

<style>
    .safe-export * {
        color: #000 !important;
        background-color: #fff !important;
        border-color: #ccc !important;
        box-shadow: none !important;
        text-shadow: none !important;
    }
</style>

@endsection

@section('content')
<div class="row g-4">
    <!-- Left: Account Summary -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-2">
                {{-- <div class="export-actions mb-2 text-end">
                    <a href="{{ route('admin.accounts.export.pdf', $account->id) }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf"></i> {{ __('ui.export_pdf') }}
                    </a>

                    <a href="{{ route('admin.accounts.export.image', $account->id) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-image"></i> {{ __('ui.export_image') }}
                    </a>
                </div> --}}

                <div id="export-area" class="export-box">
                    <div class="export-header">
                        <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}">
                        <div>
                            <h6 class="mb-0" style="font-size: 1rem;">{{ __('ui.account_summary') }}</h6>
                            <small style="color: #6c757d;">{{ now()->format('Y-m-d H:i:s') }}</small>
                        </div>
                    </div>

                    <div class="row gy-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-2">
                                    <h6 class="text-primary fw-semibold mb-2" style="font-size: 0.95rem;">
                                        <i class="fas fa-user-circle me-1"></i> {{ __('ui.client_information') }}
                                    </h6>
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $account->code }}</div>
                                            <div class="text-muted small">{{ __('ui.code') }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $account->name }}</div>
                                            <div class="text-muted small">{{ __('ui.name') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-2">
                                    <h6 class="text-primary fw-semibold mb-2" style="font-size: 0.95rem;">
                                        <i class="fas fa-balance-scale me-1"></i> {{ __('ui.balances') }}
                                    </h6>
                                    <div class="d-flex flex-column gap-2">
                                        @php
                                            $currencies = \App\Models\Currency::where('is_active', 1)->get();
                                            $displayCurrencies = [];
                                            foreach ($currencies as $currency) {
                                                $displayCurrencies[$currency->code] = $currency;
                                            }

                                            foreach ($summariesByCurrency as $currency => $summary) {
                                                if (isset($displayCurrencies[$currency])) {
                                                    unset($displayCurrencies[$currency]);
                                                }
                                            }

                                            foreach ($displayCurrencies as $currency => $displayCurrency) {
                                                $summariesByCurrency[$currency] = [
                                                    'credit' => 0,
                                                    'debit' => 0,
                                                ];
                                            }
                                        @endphp

                                        @foreach ($summariesByCurrency as $currency => $summary)
                                        @php
                                            $balance = isset($summary['credit']) && isset($summary['debit']) ? $summary['credit'] - $summary['debit'] : 0;
                                            $balanceClass = $balance >= 0 ? 'balance-positive' : 'balance-negative';
                                            $currencyFlag = strtolower(substr($currency, 0, 2));
                                        @endphp
                                        <div class="card border-0 text-white shadow-sm"
                                            style="background: linear-gradient(135deg, #187ad6, #0f58e1);">
                                            <div class="card-body p-2">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <img src="/assets/flags/{{ $currencyFlag ?? 'default' }}.svg" width="30" height="20" class="border" style="object-fit: cover;" onerror="this.src='/assets/flags/default.svg'">
                                                    <div class="fw-semibold text-uppercase text-white" style="font-size: 0.85rem;">{{ $currency }}</div>
                                                    <div class="ms-auto fw-bold {{ $balanceClass }}">{{ number_format($balance, 2) }}</div>
                                                </div>
                                            </div>
                                        </div> @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="export-footer
                            mt-4 px-2">
                        <div class="rounded-3 p-3 shadow-sm"
                            style="background: linear-gradient(135deg, #f25959, #ef5550); border: 1px solid #e4e4e4;">
                            <div class="mb-2" style="font-size: 0.85rem; font-weight: 500; color: white; text-shadow: 0 0 2px orange;">
                                <span style="font-size: 1rem;">&#x1F4CB;</span>
                                <span style="font-weight: normal;">{{ $setting->note_en }}</span>
                            </div>
                            <div
                                style="font-size: 0.85rem; font-family: 'Droid Arabic Kufi', sans-serif; direction: rtl; color: white; text-shadow: 0 0 2px black;">
                                {{ $setting->note_fa }}
                            </div>
                            <div
                                style="font-size: 0.85rem; font-family: 'Droid Arabic Kufi', sans-serif; direction: rtl; color: white; text-shadow: 0 0 2px black;">
                                {{ $setting->note_ps }}
                            </div>
                        </div>
                        <div class="small text-muted mt-3" style="font-size: 0.80rem; color: black;">
                            {{ $setting->company_name }} &middot; {{ $setting->email }} &middot; {{ $setting->contact }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Table -->
    <div class="col-md-8">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header border-bottom bg-white">
                <h5 class="card-title mb-0 d-flex justify-content-between align-items-center">
                    📊 Account Statement <span class="badge bg-dark">{{ $account->code }}</span>
                    <div class="text-end mt-3">
                        <a href="{{ route('admin.accounts.exportPdf', $account->id) }}" class="btn btn-outline-primary">
                            <i class="bx bx-download me-1"></i> Download PDF Report
                        </a>
                    </div>
                </h5>
            </div>
            <div class="card-body pt-0">
                <div class="row">
                    @foreach ($summariesByCurrency as $currency => $summary)
                        @php
                            $credit = $summary['credit'];
                            $debit = $summary['debit'];
                        @endphp
                        <div class="col-6 col-md-4 col-lg-3 p-2">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #187ad6, #0f58e1);">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center mb-1 gap-2">
                                        <img src="/assets/flags/{{ strtolower(substr($currency, 0, 2)) }}.svg" width="30" height="20" class="border" style="object-fit: cover;"
                                            onerror="this.src='/assets/flags/default.svg'">
                                        <div class="fw-semibold text-uppercase text-white" style="font-size: 0.85rem;">{{ $currency }}</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold text-white" style="font-size: 0.85rem;">CR:</div>
                                        <div class="fw-bold ms-auto text-white">{{ number_format($credit, 2) }}</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold text-white" style="font-size: 0.85rem;">DR:</div>
                                        <div class="fw-bold ms-auto" style="color: yellow;">{{ number_format($debit, 2) }}</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold text-white" style="font-size: 0.85rem;">BALANCE:</div>
                                        <div class="fw-bold ms-auto" style="color: @if($credit - $debit < 0)yellow @else white @endif;">{{ number_format($credit - $debit, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive mt-3">
                    <table class="table-hover table-sm table" id="current-table" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('ui.amount') }}</th>
                                <th>{{ __('ui.currency') }}</th>
                                <th>{{ __('ui.type') }}</th>
                                <th>Note</th>
                                <th>{{ __('ui.date') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>


    @if (auth()->user()->can('delete customers'))
    @if ($account->account_sub_category_id != 4)
        <div class="col-md-12">
            <div class="card mt-2 border-danger shadow-sm">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Danger Zone
                </div>
                <div class="card-body">
                    <p class="text-danger fw-semibold mb-2 py-2" style="font-size: 1rem;">
                        Deleting this account will permanently remove all its transactions, history, and associated data.
                        <strong>This action cannot be undone.</strong>
                    </p>
                    <button type="button" class="btn btn-danger w-20" onclick="deleteAccountPermanently({{ $account->id }})">
                        <i class="bi bi-trash-fill me-1"></i> Delete Account Permanently
                    </button>
                </div>
            </div>

            <script>
                function deleteAccountPermanently(id) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Deleting this account will permanently remove all its transactions, history, and associated data. This action cannot be undone.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `/admin/accounts/${id}`,
                                type: 'POST',
                                data: {
                                    _method: 'DELETE',
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function() {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: 'Account deleted successfully.',
                                        timer: 3000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.replace("{{ route('admin.accounts.index') }}");
                                    });
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: 'Something went wrong!',
                                        timer: 3000,
                                        showConfirmButton: false
                                    });
                                }
                            });
                        }
                    });
                }
            </script>
        </div>
    @endif
@endif
</div>

@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/moment/moment-2.29.4.min.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            const currentTable = $('#current-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.accounts.transactionData', [$account->id, 'current']) }}'
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'currency',
                        name: 'currency'
                    },
                    {
                        data: 'transaction_type',
                        name: 'transaction_type'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    }
                ]
            });

        });
    </script>

@endsection
