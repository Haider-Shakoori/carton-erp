{{-- resources/views/admin/hr/settings/attendance.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.attendance_settings'))

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
        .settings-section .section-title i {
            margin-right: 0.5rem;
            color: var(--primary);
        }
        .form-help {
            font-size: 0.75rem;
            color: var(--gray-400);
            margin-top: 0.25rem;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gray-300);
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background: white;
            transition: .4s;
            border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider {
            background: var(--primary);
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        .setting-group {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .setting-group:last-child {
            border-bottom: none;
        }
        .setting-group .setting-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray-700);
        }
        .setting-group .setting-description {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        .time-input-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .time-input-group input[type="time"] {
            width: 120px;
        }
        .day-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .day-selector .day-btn {
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            background: white;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        .day-selector .day-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .day-selector .day-btn:hover {
            border-color: var(--primary);
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
                        <i class="bi bi-calendar-check me-2"></i> {{ __('ui.attendance') }} <span class="accent">{{ __('ui.settings_label') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-sliders2 me-1"></i> Configure attendance tracking settings
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <form id="settingsForm" method="POST" action="{{ route('admin.hr.settings.attendance.update') }}">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Work Hours -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-clock"></i> Work Hours Configuration
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Office Start Time</label>
                                <div class="time-input-group">
                                    <input type="time" class="form-control" name="office_start_time"
                                           value="{{ old('office_start_time', $settings['office_start_time'] ?? '09:00') }}">
                                    <span class="text-muted">AM</span>
                                </div>
                                <div class="form-help">Standard office start time for attendance calculations.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Office End Time</label>
                                <div class="time-input-group">
                                    <input type="time" class="form-control" name="office_end_time"
                                           value="{{ old('office_end_time', $settings['office_end_time'] ?? '18:00') }}">
                                    <span class="text-muted">PM</span>
                                </div>
                                <div class="form-help">Standard office end time for attendance calculations.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Late Threshold (minutes)</label>
                                <input type="number" class="form-control" name="late_threshold"
                                       value="{{ old('late_threshold', $settings['late_threshold'] ?? 15) }}"
                                       min="1" max="120">
                                <div class="form-help">Minutes after start time to be considered 'Late'.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Early Leave Threshold (minutes)</label>
                                <input type="number" class="form-control" name="early_leave_threshold"
                                       value="{{ old('early_leave_threshold', $settings['early_leave_threshold'] ?? 15) }}"
                                       min="1" max="120">
                                <div class="form-help">Minutes before end time to be considered 'Early Leave'.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Working Days</label>
                                <div class="day-selector" id="workingDays">
                                    <button type="button" class="day-btn" data-day="1">Mon</button>
                                    <button type="button" class="day-btn" data-day="2">Tue</button>
                                    <button type="button" class="day-btn" data-day="3">Wed</button>
                                    <button type="button" class="day-btn" data-day="4">Thu</button>
                                    <button type="button" class="day-btn" data-day="5">Fri</button>
                                    <button type="button" class="day-btn" data-day="6">Sat</button>
                                    <button type="button" class="day-btn" data-day="0">Sun</button>
                                </div>
                                <input type="hidden" name="working_days" id="workingDaysInput"
                                       value="{{ old('working_days', $settings['working_days'] ?? '1,2,3,4,5') }}">
                                <div class="form-help">Select the days that are considered working days.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Rules -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-list-check"></i> Attendance Rules
                        </div>

                        <div class="mt-2">
                            <div class="setting-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="setting-label">Auto Mark Absent</div>
                                        <div class="setting-description">Automatically mark employees as absent if they haven't checked in by the threshold time</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="auto_mark_absent" id="auto_mark_absent"
                                               value="1" {{ old('auto_mark_absent', $settings['auto_mark_absent'] ?? true) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="setting-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="setting-label">Require Check-In</div>
                                        <div class="setting-description">Require employees to check in before they can check out</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="require_check_in" id="require_check_in"
                                               value="1" {{ old('require_check_in', $settings['require_check_in'] ?? true) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="setting-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="setting-label">Allow Overtime</div>
                                        <div class="setting-description">Allow employees to log overtime hours beyond regular hours</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="allow_overtime" id="allow_overtime"
                                               value="1" {{ old('allow_overtime', $settings['allow_overtime'] ?? true) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="setting-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="setting-label">Overtime Threshold (minutes)</div>
                                        <div class="setting-description">Minimum minutes after end time to be considered overtime</div>
                                    </div>
                                    <div style="width: 150px;">
                                        <input type="number" class="form-control form-control-sm" name="overtime_threshold"
                                               value="{{ old('overtime_threshold', $settings['overtime_threshold'] ?? 30) }}"
                                               min="1" max="120">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Break Settings -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-cup-hot"></i> Break Settings
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Break Duration (minutes)</label>
                                <input type="number" class="form-control" name="break_duration"
                                       value="{{ old('break_duration', $settings['break_duration'] ?? 30) }}"
                                       min="0" max="120">
                                <div class="form-help">Default break duration in minutes.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Break Start Time</label>
                                <input type="time" class="form-control" name="break_start_time"
                                       value="{{ old('break_start_time', $settings['break_start_time'] ?? '13:00') }}">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="allow_break_tracking"
                                           id="allow_break_tracking" value="1"
                                        {{ old('allow_break_tracking', $settings['allow_break_tracking'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="allow_break_tracking">
                                        Track Break Time
                                    </label>
                                </div>
                                <div class="form-help">Enable break time tracking in attendance records.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle"></i> Quick Actions
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                            </button>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-clock"></i> Quick Links
                        </div>

                        <div class="list-group list-group-flush">
                            <a href="{{ route('admin.hr.settings.general') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-gear me-2"></i> {{ __('ui.general_settings_label') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                            <a href="{{ route('admin.hr.settings.payroll') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-wallet2 me-2"></i> {{ __('ui.payroll_settings') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                            <a href="{{ route('admin.hr.attendance.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-calendar-check me-2"></i> {{ __('ui.daily_attendance') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> Changes to attendance settings will affect all future attendance records.
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
            // Working Days Selector
            const workingDaysInput = $('#workingDaysInput');
            const selectedDays = workingDaysInput.val() ? workingDaysInput.val().split(',').map(Number) : [];

            $('#workingDays .day-btn').each(function() {
                const day = parseInt($(this).data('day'));
                if (selectedDays.includes(day)) {
                    $(this).addClass('active');
                }
            });

            $('#workingDays .day-btn').on('click', function() {
                $(this).toggleClass('active');
                updateWorkingDays();
            });

            function updateWorkingDays() {
                const days = [];
                $('#workingDays .day-btn.active').each(function() {
                    days.push($(this).data('day'));
                });
                workingDaysInput.val(days.join(','));
            }

            // Reset form
            window.resetForm = function() {
                Swal.fire({
                    title: 'Reset Settings?',
                    text: 'Are you sure you want to reset to the last saved values?',
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
            };

            // Form submit
            $('#settingsForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success');
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                        }
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Save Settings'
                        );
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save settings.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Save Settings'
                        );
                    }
                });
            });
        });
    </script>
@endsection
