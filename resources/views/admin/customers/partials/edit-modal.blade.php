{{-- resources/views/admin/customers/partials/edit-modal.blade.php --}}
@php
    $customer = $customer ?? null;
@endphp

<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none;">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i> Edit Customer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCustomerForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit_customer_id" name="customer_id" value="{{ $customer->id ?? '' }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.customer_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name"
                                value="{{ $customer->name ?? '' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.customer_code') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"
                                    style="background: var(--profile-gray-50); font-weight: 600; color: var(--profile-primary);">
                                    CUS-
                                </span>
                                <input type="text" class="form-control" id="edit_code" name="code"
                                    value="{{ isset($customer->code) ? str_replace('CUS-', '', $customer->code) : '' }}"
                                    required>
                            </div>
                            <small id="edit_code_feedback" class="text-muted">Full code will be CUS-XXXX</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.contact') }}</label>
                            <input type="text" class="form-control" id="edit_contact" name="contact"
                                value="{{ $customer->contact ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.whatsapp') }}</label>
                            <input type="text" class="form-control" id="edit_whatsapp" name="whatsapp"
                                value="{{ $customer->whatsapp ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.email') }}</label>
                            <input type="email" class="form-control" id="edit_email" name="email"
                                value="{{ $customer->email ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.company') }}</label>
                            <input type="text" class="form-control" id="edit_company" name="company"
                                value="{{ $customer->company ?? '' }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ __('ui.address') }}</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2">{{ $customer->address ?? '' }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">{{ __('ui.notes') }}</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="2">{{ $customer->notes ?? '' }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active"
                                    value="1"
                                    {{ isset($customer->is_active) && $customer->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="edit_is_active">
                                    Active Customer
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"
                    style="background: var(--profile-gray-50); border-top: 1px solid var(--profile-gray-200);">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-warning" id="updateCustomerBtn">
                        <i class="bi bi-check-lg"></i> Update Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
