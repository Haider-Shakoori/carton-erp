{{-- resources/views/admin/shareholders/edit.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Edit Shareholder')

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
        .badge-status {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-status.active { background: var(--success-bg); color: var(--success); }
        .badge-status.inactive { background: var(--danger-bg); color: var(--danger); }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-pencil me-2"></i> {{ __('ui.edit') }} <span class="accent">{{ __('ui.shareholder') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> {{ $shareholder->code }} - {{ $shareholder->name }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.shareholders.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="{{ route('admin.shareholders.show', $shareholder) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> View Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-info-circle fs-4"></i>
                <div>
                    <strong>{{ __('ui.current_status') }}</strong>
                    <span class="badge-status {{ $shareholder->is_active ? 'active' : 'inactive' }} ms-2">
                    {{ $shareholder->is_active ? 'Active' : 'Inactive' }}
                </span>
                </div>
            </div>
        </div>

        <form id="shareholderForm" method="POST" action="{{ route('admin.shareholders.update', $shareholder) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                                <input type="text" class="form-control" name="name" value="{{ old('name', $shareholder->name) }}" required>
                                @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.email') }}</label>
                                <input type="email" class="form-control" name="email" value="{{ old('email', $shareholder->email) }}">
                                @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.phone') }}</label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone', $shareholder->phone) }}">
                                @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date" value="{{ old('joining_date', $shareholder->joining_date?->format('Y-m-d')) }}">
                                @error('joining_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.address') }}</label>
                                <textarea class="form-control" name="address" rows="2">{{ old('address', $shareholder->address) }}</textarea>
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
                                           value="{{ old('share_percentage', $shareholder->share_percentage) }}"
                                           step="0.01" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Total share percentage must not exceed 100%</small>
                                @error('share_percentage')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.code') }}</label>
                                <input type="text" class="form-control" value="{{ $shareholder->code }}" disabled readonly>
                                <small class="text-muted">Code cannot be changed</small>
                                <input type="hidden" name="code" value="{{ $shareholder->code }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.capital_contribution') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="capital_contribution"
                                           value="{{ old('capital_contribution', $shareholder->capital_contribution) }}"
                                           step="0.01" min="0">
                                </div>
                                @error('capital_contribution')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.notes') }}</label>
                                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $shareholder->notes) }}</textarea>
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
                                    <div id="currentImageContainer" class="{{ $shareholder->profile_image ? '' : 'd-none' }}">
                                        <img id="currentImage" class="profile-preview" src="{{ $shareholder->profile_image ? Storage::url($shareholder->profile_image) : '#' }}">
                                        <small class="text-muted">{{ __('ui.current') }}</small>
                                    </div>
                                    <div id="imagePreviewWrapper" class="d-none">
                                        <img id="imagePreview" class="profile-preview" src="#">
                                    </div>
                                </div>
                                <small class="text-muted">Allowed: JPG, PNG, WebP. Max: 2MB. Leave empty to keep current image.</small>
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
                                <span class="fw-semibold" id="summaryName">{{ $shareholder->name }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.email_colon') }}</span>
                                <span class="fw-semibold" id="summaryEmail">{{ $shareholder->email ?? 'Not set' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.share_percent_colon') }}</span>
                                <span class="fw-semibold" id="summaryShare">{{ $shareholder->share_percentage }}%</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Capital:</span>
                                <span class="fw-semibold" id="summaryCapital">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($shareholder->capital_contribution ?? 0, 2) }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.status_colon') }}</span>
                                <span class="badge-status {{ $shareholder->is_active ? 'active' : 'inactive' }}">
                                {{ $shareholder->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" name="is_active" id="isActive" value="1" {{ $shareholder->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isActive">{{ __('ui.active_status') }}</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <i class="bi bi-check2 me-1"></i> Update Shareholder
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
                const currentContainer = $('#currentImageContainer');
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        $('#imagePreview').attr('src', evt.target.result);
                        wrapper.removeClass('d-none');
                        currentContainer.addClass('d-none');
                    }
                    reader.readAsDataURL(e.target.files[0]);
                } else {
                    wrapper.addClass('d-none');
                    if ($('#currentImage').attr('src') && $('#currentImage').attr('src') !== '#') {
                        currentContainer.removeClass('d-none');
                    }
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
                $('.badge-status').removeClass('active inactive')
                    .addClass(isActive ? 'active' : 'inactive')
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
                    '<span class="spinner-border spinner-border-sm me-1"></span> Updating...'
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
                                window.location.href = '{{ route("admin.shareholders.show", $shareholder) }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Update Shareholder'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update shareholder.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Update Shareholder'
                        );
                    }
                });
            });
        });
    </script>
@endsection
