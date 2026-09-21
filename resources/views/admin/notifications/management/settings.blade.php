@extends('layouts.admin.base')

@section('title', 'Management Notification Settings')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.management-notifications.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Notifications
            </a>
            <h1 class="h3 mt-2 mb-1">Notification Settings</h1>
            <p class="text-muted mb-0">Choose how stock-control management alerts are delivered to you.</p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('admin.management-notifications.settings.update') }}">
        @csrf
        @method('PATCH')

        <div class="row g-4 mb-4">
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Delivery Channels</strong></div>
                    <div class="card-body">
                        @foreach([
                            ['in_app_enabled', 'In-App', true, 'Uses the notification bell and notification center.'],
                            ['email_enabled', 'Email', $hasEmail, $hasEmail ? 'Queued through the configured Laravel mailer.' : 'No email address is configured for your user.'],
                            ['whatsapp_enabled', 'WhatsApp', $hasWhatsApp, $hasWhatsApp ? 'Queued through the existing WhatsApp integration.' : 'No employee/account WhatsApp phone is available for your user.'],
                        ] as [$field, $label, $available, $help])
                            <input type="hidden" name="{{ $field }}" value="0">
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox"
                                       id="{{ $field }}"
                                       name="{{ $field }}" value="1"
                                       @checked(old($field, $preference->{$field}))
                                       @disabled(! $available)>
                                <label class="form-check-label fw-semibold" for="{{ $field }}">{{ $label }}</label>
                                <div class="small text-muted">{{ $help }}</div>
                            </div>
                        @endforeach

                        <div class="alert alert-info small mb-0">
                            Email and WhatsApp are disabled by default. External delivery runs through the queue and never blocks stock or production transactions.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Alert Types</strong></div>
                    <div class="card-body row g-3">
                        @foreach([
                            ['level_3_enabled', 'Level 3 / Critical Escalations', 'Critical management-control signals.'],
                            ['level_2_enabled', 'Level 2 / High Escalations', 'High-priority management-control signals.'],
                            ['assignment_enabled', 'Assignments', 'Escalations or investigations assigned to you.'],
                            ['overdue_enabled', 'Overdue Controls', 'Overdue escalations, investigations and reviews.'],
                            ['recurrence_enabled', 'Recurrence After Corrective Action', 'Same confirmed root cause returning after corrective action.'],
                            ['weekly_review_enabled', 'Weekly Control Reviews', 'Review creation, due-soon and overdue reminders.'],
                        ] as [$field, $label, $help])
                            <div class="col-md-6">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <div class="form-check form-switch border rounded p-3 ps-5 h-100">
                                    <input class="form-check-input" type="checkbox"
                                           id="{{ $field }}"
                                           name="{{ $field }}" value="1"
                                           @checked(old($field, $preference->{$field}))>
                                    <label class="form-check-label fw-semibold" for="{{ $field }}">{{ $label }}</label>
                                    <div class="small text-muted">{{ $help }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer bg-white text-end">
                        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Preferences</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <strong>Recent Delivery Audit</strong>
            <div class="small text-muted">The system records channel, status, attempts and errors without storing your destination email or phone in this audit table.</div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Event</th><th>Channel</th><th>Status</th><th class="text-end">Attempts</th><th>Last Error</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($deliveries as $delivery)
                    @php
                        $statusBadge = match($delivery->status) {
                            'sent' => 'success',
                            'failed' => 'danger',
                            'skipped' => 'secondary',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td>{{ $delivery->created_at->format('d M Y H:i') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $delivery->event_type)) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $delivery->channel)) }}</td>
                        <td><span class="badge bg-{{ $statusBadge }}">{{ ucfirst($delivery->status) }}</span></td>
                        <td class="text-end">{{ $delivery->attempts }}</td>
                        <td><small class="text-muted">{{ $delivery->last_error ?: '—' }}</small></td>
                        <td class="text-end">
                            @if(in_array($delivery->channel, ['email', 'whatsapp'], true) && $delivery->status !== 'sent')
                                <form method="POST" action="{{ route('admin.management-notifications.deliveries.retry', $delivery) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No delivery audit entries yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
