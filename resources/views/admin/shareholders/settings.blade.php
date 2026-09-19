{{-- resources/views/admin/shareholders/settings.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.shareholder_settings'))

@section('css')
    <style>
        .settings-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .settings-section .section-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-100);
        }
        .shareholder-row {
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            margin-bottom: 0.5rem;
            background: var(--gray-50);
            transition: var(--transition);
        }
        .shareholder-row:hover {
            background: var(--gray-100);
        }
        .shareholder-row .shareholder-name {
            font-weight: 600;
            font-size: 1rem;
        }
        .shareholder-row .shareholder-code {
            font-size: 0.8rem;
            color: var(--gray-500);
        }
        .shareholder-row .shareholder-capital {
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        .percentage-input {
            max-width: 120px;
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
        }
        .percentage-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .total-percentage {
            font-size: 1.5rem;
            font-weight: 800;
        }
        .total-percentage.valid {
            color: var(--success);
        }
        .total-percentage.invalid {
            color: var(--danger);
        }
        .status-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .status-badge.valid { background: var(--success-bg); color: var(--success); }
        .status-badge.invalid { background: var(--danger-bg); color: var(--danger); }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: var(--gray-200);
            overflow: hidden;
            margin-top: 0.25rem;
        }
        .progress-bar-custom .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .progress-bar-custom .progress-fill.valid { background: var(--success); }
        .progress-bar-custom .progress-fill.invalid { background: var(--danger); }
        .action-btn {
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            border: none;
            font-weight: 600;
            font-size: 0.75rem;
            transition: var(--transition);
            cursor: pointer;
        }
        .action-btn.btn-success { background: var(--success); color: white; }
        .action-btn.btn-success:hover { background: #059669; }
        .action-btn.btn-primary { background: var(--primary); color: white; }
        .action-btn.btn-primary:hover { background: var(--primary-dark); }
        .action-btn.btn-warning { background: var(--warning); color: white; }
        .action-btn.btn-warning:hover { background: #d97706; }

        .shareholder-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }
        .shareholder-avatar.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-sliders2 me-2"></i> {{ __('ui.shareholder') }} <span class="accent">{{ __('ui.settings_label') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-percent me-1"></i> Manage shareholder share percentages and capital distribution
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-secondary" onclick="validateShares()">
                        <i class="bi bi-check-circle me-1"></i> Validate
                    </button>
                    <button class="btn btn-warning" onclick="autoDistribute()">
                        <i class="bi bi-magic me-1"></i> Auto Distribute
                    </button>
                    <a href="{{ route('admin.shareholders.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Total Summary -->
        <div class="settings-section">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="text-muted small text-uppercase">{{ __('ui.total_shareholders') }}</div>
                        <div class="fw-bold fs-3">{{ $shareholders->count() }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="text-muted small text-uppercase">Total Share Percentage</div>
                        <div class="total-percentage {{ round($totalPercentage, 2) == 100 ? 'valid' : 'invalid' }}" id="totalPercentage">
                            {{ number_format($totalPercentage, 2) }}%
                        </div>
                        <span class="status-badge {{ round($totalPercentage, 2) == 100 ? 'valid' : 'invalid' }}" id="statusBadge">
                        {{ round($totalPercentage, 2) == 100 ? '✓ Valid' : '✗ Invalid' }}
                    </span>
                        <div class="progress-bar-custom">
                            <div class="progress-fill {{ round($totalPercentage, 2) == 100 ? 'valid' : 'invalid' }}"
                                 style="width: {{ min($totalPercentage, 100) }}%;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="text-muted small text-uppercase">Total Capital</div>
                        <div class="fw-bold fs-3">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($totalCapital, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shareholder Percentages -->
        <div class="settings-section">
            <div class="section-title">
                <i class="bi bi-percent me-1"></i> Share Percentages
                <span class="text-muted" style="font-weight: 400; font-size: 0.75rem;">
                Total must equal 100%
            </span>
            </div>

            <form id="percentageForm">
                @csrf

                <div id="shareholderList">
                    @foreach($shareholders as $index => $shareholder)
                        <div class="shareholder-row">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    @if ($shareholder->profile_image)
                                        <img src="{{ Storage::url($shareholder->profile_image) }}" class="shareholder-avatar">
                                    @else
                                        <div class="shareholder-avatar placeholder">
                                            {{ strtoupper(substr($shareholder->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <div class="shareholder-name">{{ $shareholder->name }}</div>
                                    <div class="shareholder-code">{{ $shareholder->code }}</div>
                                </div>
                                <div class="col-md-2">
                                    <div class="shareholder-capital">
                                        Capital: {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($shareholder->capital_contribution ?? 0, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0">{{ __('ui.share_percent') }}</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control percentage-input shareholder-percentage"
                                               name="percentages[]"
                                               data-id="{{ $shareholder->id }}"
                                               value="{{ $shareholder->share_percentage }}"
                                               step="0.01" min="0" max="100"
                                               onchange="updateTotal()">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-muted small">
                                <span id="capital-{{ $shareholder->id }}">
                                    {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(($totalCapital * ($shareholder->share_percentage / 100)), 2) }}
                                </span>
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                            <span class="badge bg-secondary" id="share-badge-{{ $shareholder->id }}">
                                {{ $shareholder->share_percentage }}%
                            </span>
                                </div>
                            </div>
                            <input type="hidden" name="shareholder_ids[]" value="{{ $shareholder->id }}">
                        </div>
                    @endforeach
                </div>

                <!-- Action Buttons -->
                <div class="mt-3">
                    <div class="alert alert-warning" id="validationWarning" style="display: none;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <span id="warningMessage">Total share percentage must equal 100%.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="savePercentages()">
                            <i class="bi bi-save me-1"></i> Save Percentages
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Instructions -->
        <div class="settings-section">
            <div class="section-title">
                <i class="bi bi-info-circle me-1"></i> Instructions
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-center p-3 bg-light rounded">
                        <i class="bi bi-1-circle fs-2 text-primary"></i>
                        <h6 class="mt-2">Adjust Percentages</h6>
                        <p class="text-muted small">Enter the share percentage for each shareholder. Total must equal 100%.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 bg-light rounded">
                        <i class="bi bi-2-circle fs-2 text-primary"></i>
                        <h6 class="mt-2">Validate</h6>
                        <p class="text-muted small">Click "Validate" to check if your percentages total 100%.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 bg-light rounded">
                        <i class="bi bi-3-circle fs-2 text-primary"></i>
                        <h6 class="mt-2">Save</h6>
                        <p class="text-muted small">Click "Save Percentages" to apply the changes. This will update all shareholder records.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize total
            updateTotal();
        });

        // Update total percentage and display
        function updateTotal() {
            let total = 0;
            let isValid = true;

            $('.shareholder-percentage').each(function() {
                const val = parseFloat($(this).val()) || 0;
                total += val;

                // Highlight if invalid
                if (val < 0 || val > 100) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }

                // Update badge
                const id = $(this).data('id');
                $(`#share-badge-${id}`).text(val + '%');

                // Update capital preview
                const totalCapital = {{ $totalCapital }};
                const capital = (totalCapital * val) / 100;
                $(`#capital-${id}`).text('{{ $defaultCurrency?->symbol ?? '$' }}' + capital.toFixed(2));
            });

            // Update total display
            const totalRounded = Math.round(total * 100) / 100;
            const isValidTotal = totalRounded === 100;

            $('#totalPercentage').text(totalRounded.toFixed(2) + '%');
            $('#totalPercentage').removeClass('valid invalid').addClass(isValidTotal ? 'valid' : 'invalid');

            $('#statusBadge').removeClass('valid invalid').addClass(isValidTotal ? 'valid' : 'invalid')
                .text(isValidTotal ? '✓ Valid' : '✗ Invalid');

            // Update progress bar
            const progressPercent = Math.min(total, 100);
            $('.progress-fill').css('width', progressPercent + '%')
                .removeClass('valid invalid').addClass(isValidTotal ? 'valid' : 'invalid');

            // Show/hide warning
            if (!isValidTotal) {
                $('#validationWarning').show();
                $('#warningMessage').text(`Total share percentage is ${totalRounded.toFixed(2)}%. It must be exactly 100%.`);
            } else {
                $('#validationWarning').hide();
            }
        }

        // Validate shares
        function validateShares() {
            let total = 0;
            $('.shareholder-percentage').each(function() {
                total += parseFloat($(this).val()) || 0;
            });

            const totalRounded = Math.round(total * 100) / 100;

            if (totalRounded === 100) {
                Swal.fire({
                    icon: 'success',
                    title: 'Perfect!',
                    text: 'All shareholders total 100%. You can save your changes.',
                    confirmButtonColor: '#4f46e5'
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Total',
                    text: `Total share percentage is ${totalRounded.toFixed(2)}%. Please adjust to make it exactly 100%.`,
                    confirmButtonColor: '#4f46e5'
                });
            }
        }

        // Auto distribute equally
        function autoDistribute() {
            Swal.fire({
                title: 'Auto Distribute Shares?',
                text: 'This will distribute shares equally among all shareholders. This action cannot be undone.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, distribute!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("admin.shareholder-settings.auto-distribute") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Success!', res.message, 'success').then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to auto distribute.', 'error');
                        }
                    });
                }
            });
        }

        // Save percentages
        function savePercentages() {
            let total = 0;
            $('.shareholder-percentage').each(function() {
                total += parseFloat($(this).val()) || 0;
            });

            const totalRounded = Math.round(total * 100) / 100;

            if (totalRounded !== 100) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Total',
                    text: `Total share percentage is ${totalRounded.toFixed(2)}%. Please adjust to exactly 100%.`,
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            Swal.fire({
                title: 'Save Changes?',
                text: 'Are you sure you want to update shareholder percentages?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, save!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = $('#percentageForm').serialize();

                    $.ajax({
                        url: '{{ route("admin.shareholder-settings.update-percentages") }}',
                        method: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Success!', res.message, 'success').then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Failed to update percentages.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire('Error', errorMessage, 'error');
                        }
                    });
                }
            });
        }

        // Reset form to original values
        function resetForm() {
            Swal.fire({
                title: 'Reset Changes?',
                text: 'Are you sure you want to discard your changes?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6B7280',
                cancelButtonColor: '#4f46e5',
                confirmButtonText: 'Yes, reset!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        }
    </script>
@endsection
