@extends('layouts.admin.base')

@section('title', 'Customers Report')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.css') }}">
@endsection

@section('content')
    <div class="card rounded-4 border-0 shadow">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start flex-wrap gap-3">
            <h4 class="card-title">Customers Report</h4>
            <div class="d-flex justify-content-end align-items-center">
                <button class="btn btn-warning" onclick="confirmRecalculateBalances()">Recalculate Balances</button>
                <script>
                    function confirmRecalculateBalances() {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "This will recalculate all balances",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, recalculate'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "{{ url('/admin/recalculate-balances') }}";
                            }
                        });
                    }
                </script>
            </div>
            <div class="d-flex w-100 flex-wrap gap-2">
                <div class="flex-fill" style="min-width: 200px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-diagram-3"></i></span>
                        <select class="form-select-sm rounded-start-0 form-select" id="filter_sub" name="sub_category_id">
                            <option value="">{{ __('ui.all_sub_categories') }}</option>
                            @foreach ($subCategories as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex-fill" style="min-width: 200px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <select class="form-select-sm rounded-start-0 form-select" id="filter_customer" name="customer_id">
                            <option value="">{{ __('ui.all_customers') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex-fill" style="min-width: 160px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-currency-dollar"></i></span>
                        <select class="form-select-sm rounded-start-0 form-select" id="filter_currency" name="currency_id">
                            <option value="">{{ __('ui.all_currencies') }}</option>
                            @foreach ($currencies as $cur)
                                <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="" style="min-width: 150px; min-height: 45px;">
                    <a href="#" id="exportPdfBtn" class="btn btn-sm btn-danger w-100" style="height: 47px;">{{ __('ui.export_pdf') }}</a>
                </div>
            </div>
        </div>

        <div class="card-body">

            <div id="totalsCards" class="row g-3 mb-4">
                {{-- Cards will be inserted here dynamically --}}
            </div>

            <div class="table-responsive">
                <table class="table-bordered table-hover table text-center align-middle" id="reportTable" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.customer') }}</th>
                            <th>{{ __('ui.currency') }}</th>
                            <th>{{ __('ui.credit') }}</th>
                            <th>Debit</th>
                            <th>{{ __('ui.balance') }}</th>
                        </tr>
                    </thead>

                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/pdfmake-0.1.36.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/vfs_fonts-0.1.36.js') }}"></script>
    <script>
        let table = $('#reportTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: '{{ route('admin.reports.customers.data') }}',
                data: function(d) {
                    d.sub_category_id = $('#filter_sub').val();
                    d.currency_id = $('#filter_currency').val();
                    d.customer_id = $('#filter_customer').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'customer',
                    name: 'account.name'
                },
                {
                    data: 'currency',
                    name: 'currency.code'
                },
                {
                    data: 'credit',
                    name: 'credit'
                },
                {
                    data: 'debit',
                    name: 'debit'
                },
                {
                    data: 'balance',
                    name: 'balance'
                }
            ],

            footerCallback: function(row, data, start, end, display) {
                const api = this.api();
                const json = api.ajax.json();

                // Update footer (optional)
                // const footer = $('#reportTable tfoot');
                // if (json.totals_by_currency) {
                //     let html = '';
                //     for (let currency in json.totals_by_currency) {
                //         let row = json.totals_by_currency[currency];
                //         html += `
                //             <tr class="bg-light fw-bold">
                //                 <td colspan="2" class="text-start">${currency} Totals</td>
                //                 <td>${currency}</td>
                //                 <td>${row.credit}</td>
                //                 <td>${row.debit}</td>
                //                 <td>${row.balance}</td>
                //             </tr>
                //         `;
                //     }
                //     footer.html(html);
                // }

                // Render summary cards above
                const $totalsCards = $('#totalsCards');
                $totalsCards.empty();

                if (json.totals_by_currency) {
                    for (let currency in json.totals_by_currency) {
                        const totals = json.totals_by_currency[currency];
                        $totalsCards.append(`
                            <div class="col-md-4">
                                <div class="card shadow border-0 text-white h-100" style="background: linear-gradient(135deg, #00264d, #005580); border-radius: 1rem;">
                                    <div class="card-body d-flex flex-column justify-content-center align-items-start py-4 px-4">
                                        <h5 class="fw-bold mb-3 text-info" style="font-size: 1.4rem;">${currency} Totals</h5>
                                        <p class="mb-2" style="font-size: 1.2rem;"><strong>Credit:</strong> ${totals.credit} ${totals.symbol}</p>
                                        <p class="mb-2" style="font-size: 1.2rem;"><strong>Debit:</strong> ${totals.debit} ${totals.symbol}</p>
                                        <p class="mb-0" style="font-size: 1.2rem;"><strong>{{ __('ui.balance_colon') }}</strong> ${totals.balance} ${totals.symbol}</p>
                                    </div>
                                </div>
                            </div>
                        `);
                    }
                }
            }


        });

        // Load customers when sub-category changes
        $('#filter_sub').on('change', function () {
            const subCategoryId = $(this).val();
            $('#filter_customer').html('<option value="">{{ __('ui.loading') }}</option>');

            $.get(`{{ route('admin.reports.getCustomersBySubCategory') }}?sub_category_id=${subCategoryId}`, function (data) {
                let options = '<option value="">{{ __('ui.all_customers') }}</option>';
                data.forEach(c => {
                    options += `<option value="${c.id}">${c.name} - ${c.code}</option>`;
                });
                $('#filter_customer').html(options);
            });

            table.ajax.reload();
        });

        // Trigger table reload on any filter change
        $('#filter_currency, #filter_customer').on('change', function () {
            table.ajax.reload();
        });


        $('#exportPdfBtn').on('click', function(e) {
            e.preventDefault();
            const sub = $('#filter_sub').val() || '';
            const currency = $('#filter_currency').val() || '';
            const customer = $('#filter_customer').val() || '';
            const url = `{{ route('admin.reports.customers.export.pdf') }}?sub_category_id=${sub}&currency_id=${currency}&customer_id=${customer}`;
            window.open(url, '_blank');
        });

    </script>
@endsection
