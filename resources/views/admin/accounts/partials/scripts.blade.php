<!-- DataTables -->
<script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>

<script>
        $(document).on('click', '.btn-edit-account', function(e) {
            e.preventDefault();
            const id = $(this).data('id');

            fetch(`/admin/accounts/${id}/edit`)
                .then(res => res.json())
                .then(data => {
                    $('#offcanvasAddUserLabel').text('Edit Account');
                    $('#account-name').val(data.name);
                    $('#account-code-prefix').text(data.code.charAt(0));
                    $('#account-code-suffix').val(data.code.substring(1));
                    $('#full-account-code').val(data.code);
                    $('#account-contact').val(data.contact);
                    $('#account-address').val(data.address);
                    $('#account-company').val(data.company);
                    $('#edit_id').val(data.id);
                    $('#edit_mode').val(1);
                    $('#form_method').val('PUT');

                    $('#offcanvasAddUser').offcanvas('show');
                });
        });

        $(document).on('click', '.btn-preview-account', function() {
            const id = $(this).data('id');

            $('#accountPreviewContent').html('<div class="p-5 text-center">{{ __('ui.loading_preview') }}</div>');

            $.get(`/admin/accounts/${id}/print`, function(html) {
                $('#accountPreviewContent').html(html);
                const modal = new bootstrap.Modal(document.getElementById('accountPreviewModal'));
                modal.show();
            }).fail(function() {
                $('#accountPreviewContent').html('<div class="p-5 text-danger text-center">{{ __('ui.failed_load_preview') }}</div>');
            });
        });

        $(document).on('click', '.btn-send-whatsapp', function() {
            const url = $(this).data('url');

            Swal.fire({
                title: 'Send WhatsApp?',
                text: 'Do you want to send a message to this customer?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, send it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get(url, function(res) {
                        Swal.fire('Success!', res.message, 'success');
                    }).fail(function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.error || 'Failed to send message.', 'error');
                        console.log(xhr.responseJSON.error);

                    });
                }
            });
        });



        // Override form submission
        $('#addNewAccountForm').on('submit', function(e) {
            const isEdit = $('#edit_mode').val() === '1';
            const id = $('#edit_id').val();
            const method = isEdit ? 'PUT' : 'POST';
            const url = isEdit ?
                `/admin/accounts/${id}` :
                "{{ route('admin.accounts.store') }}";

            e.preventDefault();

            const formData = $(this).serialize();

            $.ajax({
                url,
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(res) {
                    $('#offcanvasAddUser').offcanvas('hide');
                    Swal.fire({
                        title: 'Success',
                        text: isEdit ? 'Account updated.' : 'Account created.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                },
                error: function(err) {
                    alert('Something went wrong. Check fields.');
                }
            });
        });

        $(document).on('click', '.btn-delete-account', function(e) {
            e.preventDefault();
            const url = $(this).data('url');

            Swal.fire({
                title: 'Are you sure?',
                text: "This account will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message || 'Account deleted successfully.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $('#yajra-datatable').DataTable().ajax.reload(null, false);
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed!',
                                text: 'Could not delete account.'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-create-user', function() {
            const accountId = $(this).data('id');
            $('#account_id').val(accountId);

            // Clear old values first
            $('#createUserForm input[name="name"]').val('');
            $('#createUserForm input[name="username"]').val('');

            // Fetch account info via AJAX
            $.ajax({
                url: `/admin/accounts/${accountId}/info`,
                method: 'GET',
                success: function(data) {
                    $('#createUserForm input[name="name"]').val(data.name);
                    $('#createUserForm input[name="username"]').val(data.code);
                    $('#createUserModal').modal('show');
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not fetch account information.'
                    });
                }
            });
        });


        $('#createUserForm').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('admin.accounts.create-user') }}",
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#createUserModal').modal('hide');
                        $('#yajra-datatable').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: xhr.responseJSON?.message || 'Something went wrong.'
                    });
                }
            });
        });


        $('#account_type, #currency_id, #per-page').on('change', () => table.draw());
        $('#account-sub-category').on('change', () => table.draw());
        $('#search-btn').on('click', () => table.draw());
        $('#search').on('keyup', e => e.key === 'Enter' && table.draw());
        $('#balance_filter, #currency_id').on('change', function() {
            $('#yajra-datatable').DataTable().ajax.reload();
        });

        $('#account_type').on('change', function() {
            let id = $(this).val();
            let sub = $('#account-sub-category');
            sub.empty().append('<option value="">{{ __('ui.category') }}</option>');
            if (!id) return;
            $.get(`/account/subcategories/${id}`, data => {
                data.forEach(cat => sub.append(`<option value="${cat.id}">${cat.name}</option>`));
                table.draw();
            });
        });

        function fetchStats() {
            $.get("{{ route('admin.accounts.update-stats') }}", {
                account_category_id: $('#account_type').val(),
                account_sub_category_id: $('#account-sub-category').val(),
                currency_id: $('#currency_id').val()
            }, function(data) {
                $('#total_accounts').text(data.total_accounts);
                $('#total_credit').text(`${data.currency} ${Number(data.total_credit.replace(/,/g, '')).toLocaleString()}`);
                $('#total_debit').text(`${data.currency} ${Number(data.total_debit.replace(/,/g, '')).toLocaleString()}`);
                const balance = Number(data.balance.replace(/,/g, ''));
                $('#balance').text(`${data.currency} ${balance.toLocaleString()}`)
                    .css('color', balance < 0 ? '#b23434' : '#2b2bc7');
            });
        }

        fetchStats();
        $('#account_type, #account-sub-category, #currency_id').on('change', fetchStats);

        // Create Form interactions
        const catSelect = document.getElementById('create_account_category');
        const subCategorySelect = document.getElementById('create_account-sub-category');
        const prefix = document.getElementById('account-code-prefix');
        const suffix = document.getElementById('account-code-suffix');
        const fullCode = document.getElementById('full-account-code');
        const feedback = document.getElementById('code-feedback');
        const submitBtn = document.querySelector('form button[type="submit"]');

        function updateFullCode() {
            const val = `${prefix.textContent.trim()}${suffix.value.trim().toUpperCase()}`;
            suffix.value = val.replace(prefix.textContent.trim(), '');
            fullCode.value = val;
        }

        function checkCodeAvailability() {
            const code = fullCode.value;
            if (!code) return;
            feedback.innerHTML = `<span class='text-muted'><span class='spinner-border spinner-border-sm'></span> {{ __('ui.checking') }}</span>`;
            fetch("{{ route('admin.accounts.checkCode') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        code
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.available) {
                        feedback.innerHTML = `<span class='text-success'>✅ Available</span>`;
                        suffix.classList.add('is-valid');
                        suffix.classList.remove('is-invalid');
                        submitBtn.disabled = false;
                    } else {
                        feedback.innerHTML = `<span class='text-danger'>❌ Already exists</span>`;
                        suffix.classList.add('is-invalid');
                        suffix.classList.remove('is-valid');
                        submitBtn.disabled = true;
                    }
                });
        }

        catSelect.addEventListener('change', () => {
            const id = catSelect.value;
            if (!id) {
                prefix.textContent = '--';
                subCategorySelect.innerHTML = '<option value="">{{ __('ui.select_subcategory') }}</option>';
                updateFullCode();
                return;
            }
            fetch(`/admin/account/subcategories/${id}`)
                .then(response => response.json())
                .then(data => {
                    subCategorySelect.innerHTML = '<option value="">{{ __('ui.select_subcategory') }}</option>';
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subCategorySelect.appendChild(option);
                    });
                });

            fetch(`/admin/account/category-prefix/${id}`)
                .then(res => res.json())
                .then(data => {
                    prefix.textContent = data.prefix || '--';
                    updateFullCode();
                    checkCodeAvailability();
                });
        });

        suffix.addEventListener('input', () => {
            updateFullCode();
            checkCodeAvailability();
        });
    });
</script>
