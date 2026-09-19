{{-- resources/views/admin/shareholders/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.add_shareholder'))

@section('css')
    <style>
        .form-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section .section-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-100);
        }
        .image-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--gray-50);
            padding: 0.5rem;
            border-radius: var(--radius-xs);
            border: 1.5px dashed var(--gray-300);
        }
        .profile-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--gray-200);
        }
        .profile-preview.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
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
                        <i class="bi bi-person-plus me-2"></i> Add <span class="accent">{{ __('ui.shareholder') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-person-badge me-1"></i> Create a new shareholder record
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.shareholders.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <form id="shareholderForm" method="POST" action="{{ route('admin.shareholders.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-person me-1"></i> Personal Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.full_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.email') }}</label>
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                                @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.phone') }}</label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}">
                                @error('joining_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.address') }}</label>
                                <textarea class="form-control" name="address" rows="2">{{ old('address') }}</textarea>
                                @error('address')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Share Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-percent me-1"></i> Share Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.share_percentage') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="share_percentage"
                                           value="{{ old('share_percentage', 0) }}" step="0.01" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Total share percentage must not exceed 100%</small>
                                @error('share_percentage')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.capital_contribution') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="capital_contribution"
                                           value="{{ old('capital_contribution', 0) }}" step="0.01" min="0">
                                </div>
                                @error('capital_contribution')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.notes') }}</label>
                                <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                                @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Profile Image -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-image me-1"></i> Profile Image
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="image-upload-wrapper">
                                    <input type="file" class="form-control form-control-sm" name="profile_image" id="profileImage" accept="image/*">
                                    <div id="imagePreviewWrapper" class="d-none">
                                        <img id="imagePreview" class="profile-preview" src="#">
                                    </div>
                                </div>
                                <small class="text-muted">Allowed: JPG, PNG, WebP. Max: 2MB</small>
                                @error('profile_image')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle me-1"></i> Summary
                        </div>

                        <div id="summaryContent">
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.name_colon') }}</span>
                                <span class="fw-semibold" id="summaryName">{{ __('ui.not_set') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.email_colon') }}</span>
                                <span class="fw-semibold" id="summaryEmail">{{ __('ui.not_set') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.share_percent_colon') }}</span>
                                <span class="fw-semibold" id="summaryShare">0%</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Capital:</span>
                                <span class="fw-semibold" id="summaryCapital">{{ $defaultCurrency?->symbol ?? '$' }}0</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.status_colon') }}</span>
                                <span class="badge bg-success">{{ __('ui.active') }}</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" name="is_active" id="isActive" value="1" checked>
                                <label class="form-check-label fw-semibold" for="isActive">{{ __('ui.active_status') }}</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <i class="bi bi-check2 me-1"></i> Create Shareholder
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> {{ __('ui.required_fields_notice') }} <span class="text-danger">*</span> are required.
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Image Preview
            $('#profileImage').on('change', function(e) {
                const wrapper = $('#imagePreviewWrapper');
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        $('#imagePreview').attr('src', evt.target.result);
                        wrapper.removeClass('d-none');
                    }
                    reader.readAsDataURL(e.target.files[0]);
                } else {
                    wrapper.addClass('d-none');
                }
            });

            // Summary Updates
            $('input[name="name"]').on('input', function() {
                $('#summaryName').text($(this).val() || 'Not set');
            });

            $('input[name="email"]').on('input', function() {
                $('#summaryEmail').text($(this).val() || 'Not set');
            });

            $('input[name="share_percentage"]').on('input', function() {
                const val = parseFloat($(this).val()) || 0;
                $('#summaryShare').text(val + '%');
                if (val > 100) {
                    $(this).addClass('is-invalid');
                    Swal.fire('Warning', 'Share percentage cannot exceed 100%', 'warning');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            $('input[name="capital_contribution"]').on('input', function() {
                const val = parseFloat($(this).val()) || 0;
                $('#summaryCapital').text('{{ $defaultCurrency?->symbol ?? '$' }}' + val.toFixed(2));
            });

            // Status toggle
            $('#isActive').on('change', function() {
                const isActive = $(this).is(':checked');
                $('.badge-status').removeClass('bg-success bg-danger')
                    .addClass(isActive ? 'bg-success' : 'bg-danger')
                    .text(isActive ? 'Active' : 'Inactive');
            });

            // Form submit
            $('#shareholderForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');
                const formData = new FormData(this);
                const sharePercent = parseFloat($('input[name="share_percentage"]').val()) || 0;

                if (sharePercent > 100) {
                    Swal.fire('Error', 'Share percentage cannot exceed 100%', 'error');
                    return;
                }

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Creating...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                window.location.href = '{{ route("admin.shareholders.index") }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Create Shareholder'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to create shareholder.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Create Shareholder'
                        );
                    }
                });
            });
        });
    </script>
@endsection
