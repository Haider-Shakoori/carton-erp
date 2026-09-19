{{-- resources/views/admin/agents/partials/scripts.blade.php --}}

<!-- DataTables -->
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function() {
        // ─── DataTable Initialization ───
        const table = $('#yajra-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.agents.fetch') }}", // FIXED: 'fetch' not 'fetch-agents'
                data: function(d) {
                    d.account_type_id = $('#account_type').val();
                    d.account_sub_category_id = $('#account-sub-category').val();
                    d.search_term = $('#search').val();
                    d.length = $('#per-page').val();
                    d.balance_filter = $('#balance_filter').val();
                    d.currency_id = $('#currency_id').val();
                }
            },
            columns: [{
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'name_with_profile',
                    name: 'name',
                    orderable: false
                },
                {
                    data: 'code',
                    name: 'code'
                },
                {
                    data: 'account_type_with_icon',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'contact_details',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'balance',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [2, 'asc']
            ],
            pageLength: 25,
            searching: false,
            lengthChange: false,
            info: true,
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ agents",
                infoEmpty: "Showing 0 to 0 of 0 agents",
                infoFiltered: "(filtered from _MAX_ total agents)",
                emptyTable: "No agents found",
                zeroRecords: "No matching agents found"
            }
        });

        // ─── Reload table on filter changes ───
        $('#account_type, #currency_id, #per-page, #balance_filter, #account-sub-category').on('change',
            function() {
                table.ajax.reload();
            });

        $('#search-btn').on('click', function() {
            table.ajax.reload();
        });

        $('#search').on('keyup', function(e) {
            if (e.key === 'Enter') {
                table.ajax.reload();
            }
        });

        // Debounced search
        let searchTimeout;
        $('#search').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                table.ajax.reload();
            }, 500);
        });

        // ─── Preview Account ───
        $(document).on('click', '.btn-preview-account', function() {
            const id = $(this).data('id');

            $('#accountPreviewContent').html(
                '<div class="d-flex justify-content-center align-items-center" style="height: 200px;"><div class="spinner-border text-primary" role="status"></div></div>'
            );

            $.get(`/admin/agents/${id}/print`, function(html) {
                $('#accountPreviewContent').html(html);
                const modal = new bootstrap.Modal(document.getElementById(
                    'accountPreviewModal'));
                modal.show();
            }).fail(function() {
                $('#accountPreviewContent').html(
                    '<div class="p-5 text-danger text-center">{{ __('ui.failed_load_preview') }}</div>'
                );
            });
        });

        // ─── Send WhatsApp ───
        $(document).on('click', '.btn-send-whatsapp', function() {
            const url = $(this).data('url');

            Swal.fire({
                title: 'Send WhatsApp?',
                text: 'Do you want to send a message to this agent?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, send it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get(url, function(res) {
                        Swal.fire('Success!', res.message, 'success');
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.error ||
                            'Failed to send message.', 'error');
                    });
                }
            });
        });

        // ─── Edit Agent ───
        $(document).on('click', '.btn-edit-agent', function(e) {
            e.preventDefault();
            const id = $(this).data('id');

            $.get(`/admin/agents/${id}/edit`, function(data) {
                $('#offcanvasAddUserLabel').text('Edit Agent');
                $('#edit_mode').val(1);
                $('#edit_id').val(data.id);
                $('#form_method').val('PUT');

                $('#account-name').val(data.name);
                $('#account-code-suffix').val(data.code.replace('AGT-', ''));
                $('#full-account-code').val(data.code);
                $('#account-contact').val(data.contact || '');
                $('#account-email').val(data.email || '');
                $('#account-whatsapp').val(data.whatsapp || '');
                $('#account-company').val(data.company || '');
                $('#account-address').val(data.address || '');
                $('#account-notes').val(data.notes || '');

                $('#code-feedback').text('Editing: ' + data.code).removeClass('text-muted')
                    .addClass(
                        'text-warning');

                $('#offcanvasAddUser').offcanvas('show');
            }).fail(function() {
                Swal.fire('Error', 'Failed to load agent data.', 'error');
            });
        });

        // ─── Delete Agent ───
        $(document).on('click', '.btn-delete-agent', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Are you sure?',
                text: `Agent "${name}" will be permanently deleted!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/agents/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message ||
                                    'Agent deleted successfully.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            table.ajax.reload(null, false);
                            fetchStats();
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed!',
                                text: 'Could not delete agent.'
                            });
                        }
                    });
                }
            });
        });

        // ─── Create/Edit Form Submission ───
        $('#createAgentForm').on('submit', function(e) {
            e.preventDefault();

            const isEdit = $('#edit_mode').val() === '1';
            const id = $('#edit_id').val();
            const method = isEdit ? 'PUT' : 'POST';
            const url = isEdit ? `/admin/agents/${id}` : "{{ route('admin.agents.store') }}";

            // Validate code
            if (!$('#full-account-code').val()) {
                Swal.fire('Warning', 'Please generate or enter a valid agent code.', 'warning');
                return;
            }

            const formData = $(this).serialize();
            const $btn = $('#submitAgentBtn');

            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span> ' + (isEdit ?
                    'Updating...' : 'Creating...'));

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#offcanvasAddUser').offcanvas('hide');
                    Swal.fire({
                        icon: 'success',
                        title: isEdit ? 'Updated!' : 'Created!',
                        text: response.message || (isEdit ?
                            'Agent updated successfully.' :
                            'Agent created successfully.'),
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        table.ajax.reload(null, false);
                        fetchStats();
                        // Reset form
                        $('#createAgentForm')[0].reset();
                        $('#edit_mode').val(0);
                        $('#edit_id').val('');
                        $('#form_method').val('POST');
                        $('#offcanvasAddUserLabel').text('Add New Agent');
                        $btn.prop('disabled', false).html(
                            '<i class="bi bi-check-circle me-1"></i> Create Agent'
                        );
                    });
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        let errorMsg = '';
                        $.each(errors, function(key, value) {
                            errorMsg += value[0] + '\n';
                        });
                        Swal.fire('Error', errorMsg, 'error');
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message ||
                            'Something went wrong.',
                            'error');
                    }
                    $btn.prop('disabled', false).html(
                        '<i class="bi bi-check-circle me-1"></i> ' +
                        (isEdit ? 'Update Agent' : 'Create Agent'));
                }
            });
        });

        // ─── Code Generation ───
        function generateAgentCode() {
            const prefix = 'AGT-';
            const random = String(Math.floor(1000 + Math.random() * 9000));
            return prefix + random;
        }

        $('#generateCodeBtn').on('click', function() {
            const code = generateAgentCode();
            const suffix = code.replace('AGT-', '');
            $('#account-code-suffix').val(suffix);
            $('#full-account-code').val(code);
            $('#code-feedback').text('Generated: ' + code).removeClass('text-muted text-warning')
                .addClass(
                    'text-success');
            checkCodeAvailability();
        });

        $('#account-code-suffix').on('input', function() {
            const suffix = $(this).val();
            const prefix = 'AGT-';
            if (suffix) {
                $('#full-account-code').val(prefix + suffix);
                $('#code-feedback').text('Code: ' + prefix + suffix).removeClass(
                    'text-muted text-warning').addClass(
                    'text-success');
            } else {
                $('#full-account-code').val('');
                $('#code-feedback').text('Enter a code suffix').removeClass('text-success text-warning')
                    .addClass(
                        'text-muted');
            }
            checkCodeAvailability();
        });

        // ─── Check Code Availability ───
        function checkCodeAvailability() {
            const code = $('#full-account-code').val();
            if (!code) {
                $('#code-feedback').text('Enter a code').removeClass('text-success text-danger text-warning')
                    .addClass(
                        'text-muted');
                return;
            }

            $.ajax({
                url: "{{ route('admin.accounts.checkCode') }}",
                method: 'POST',
                data: {
                    code: code,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.available) {
                        $('#code-feedback').text('✅ Code available').removeClass(
                                'text-danger text-warning')
                            .addClass('text-success');
                        $('#submitAgentBtn').prop('disabled', false);
                    } else {
                        $('#code-feedback').text('❌ Code already exists').removeClass(
                                'text-success text-warning')
                            .addClass('text-danger');
                        $('#submitAgentBtn').prop('disabled', true);
                    }
                }
            });
        }

        // ─── Auto-generate code on form open ───
        $('#offcanvasAddUser').on('shown.bs.offcanvas', function() {
            if (!$('#account-code-suffix').val() && $('#edit_mode').val() !== '1') {
                setTimeout(function() {
                    $('#generateCodeBtn').click();
                }, 300);
            }
        });

        // ─── Reset form when closed ───
        $('#offcanvasAddUser').on('hidden.bs.offcanvas', function() {
            if ($('#edit_mode').val() !== '1') {
                $('#createAgentForm')[0].reset();
                $('#full-account-code').val('');
                $('#code-feedback').text('Code will be auto-generated').removeClass(
                        'text-success text-danger')
                    .addClass('text-muted');
                $('#submitAgentBtn').prop('disabled', false).html(
                    '<i class="bi bi-check-circle me-1"></i> Create Agent');
                $('#offcanvasAddUserLabel').text('Add New Agent');
            }
            $('#edit_mode').val(0);
            $('#edit_id').val('');
            $('#form_method').val('POST');
        });

        // ─── Fetch Stats ───
        function fetchStats() {
            $.ajax({
                url: "{{ route('admin.agents.update-stats') }}", // FIXED: 'update-stats'
                data: {
                    account_category_id: $('#account_type').val(),
                    account_sub_category_id: $('#account-sub-category').val(),
                    currency_id: $('#currency_id').val()
                },
                success: function(data) {
                    $('#total_accounts').text(data.total_accounts || 0);
                    $('#total_credit').text(data.total_credit || '0.00');
                    $('#total_debit').text(data.total_debit || '0.00');
                    const balance = parseFloat(data.balance) || 0;
                    $('#balance').text(balance.toLocaleString()).css('color', balance < 0 ?
                        '#dc3545' :
                        '#28a745');
                }
            });
        }

        // ─── Initial Stats Load ───
        fetchStats();

        // ─── Reload stats on filter change ───
        $('#account_type, #account-sub-category, #currency_id').on('change', fetchStats);

        // ─── Sub-category loading ───
        $('#account_type').on('change', function() {
            const id = $(this).val();
            const sub = $('#account-sub-category');
            sub.empty().append('<option value="">All Sub-categories</option>');
            if (!id) return;
            $.get(`/account/subcategories/${id}`, function(data) {
                data.forEach(cat => {
                    sub.append(`<option value="${cat.id}">${cat.name}</option>`);
                });
                table.ajax.reload();
            });
        });
    });
</script>
