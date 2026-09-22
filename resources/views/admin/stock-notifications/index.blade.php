@extends('layouts.admin.base')

@section('title', 'Stock Control Notifications')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.management-control.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Management Control
            </a>
            <h1 class="h3 mt-2 mb-1">Stock Control Notifications</h1>
            <p class="text-muted mb-0">In-app alerts and delivery status for management-control events.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.stock-notifications.index', ['unread' => 1]) }}"
               class="btn btn-outline-primary">
                <i class="bi bi-envelope-exclamation me-1"></i> Unread Only
            </a>
            <form method="POST" action="{{ route('admin.stock-notifications.read-all') }}">
                @csrf
                <button class="btn btn-primary">
                    <i class="bi bi-envelope-open me-1"></i> Mark All Read
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Notification Center</strong>
                    <span class="badge bg-primary">{{ auth()->user()->unreadNotifications()->count() }} unread</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $severity = $data['severity'] ?? 'normal';
                            $badge = match($severity) {
                                'critical' => 'danger',
                                'high' => 'warning',
                                default => 'secondary',
                            };
                        @endphp
                        <div class="list-group-item py-3 {{ $notification->read_at ? '' : 'bg-light' }}">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <span class="badge rounded-pill bg-{{ $badge }}">
                                        {{ ucfirst($severity) }}
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div class="fw-semibold">{{ $data['title'] ?? 'Stock Control Notification' }}</div>
                                        <small class="text-muted text-nowrap">{{ $notification->created_at?->diffForHumans() }}</small>
                                    </div>
                                    <div class="text-muted mt-1">{{ $data['message'] ?? '—' }}</div>
                                    <div class="d-flex gap-2 mt-2">
                                        <a href="{{ route('admin.stock-notifications.open', $notification) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Open
                                        </a>
                                        @if(! $notification->read_at)
                                            <form method="POST" action="{{ route('admin.stock-notifications.read', $notification) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary">Mark Read</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            No stock-control notifications yet.
                        </div>
                    @endforelse
                </div>
                @if($notifications->hasPages())
                    <div class="card-footer bg-white">{{ $notifications->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Email / WhatsApp Delivery Audit</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Event</th>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>Recipient</th>
                                <th class="text-end">Attempts</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($deliveries as $delivery)
                            @php
                                $statusBadge = match($delivery->status) {
                                    'sent' => 'success',
                                    'failed' => 'danger',
                                    'skipped' => 'secondary',
                                    'queued' => 'warning',
                                    default => 'info',
                                };
                            @endphp
                            <tr>
                                <td>{{ $delivery->created_at?->format('d M Y H:i') }}</td>
                                <td>
                                    <div>{{ ucfirst(str_replace('_', ' ', $delivery->event_type)) }}</div>
                                    @if($delivery->last_error)
                                        <small class="text-danger">{{ IlluminateSupportStr::limit($delivery->last_error, 100) }}</small>
                                    @endif
                                </td>
                                <td>{{ ucfirst($delivery->channel) }}</td>
                                <td><span class="badge bg-{{ $statusBadge }}">{{ ucfirst($delivery->status) }}</span></td>
                                <td>{{ \App\Models\StockNotificationDelivery::maskRecipient($delivery->recipient, $delivery->channel) ?: 'Not configured' }}</td>
                                <td class="text-end">{{ $delivery->attempts }}</td>
                                <td class="text-end">
                                    @if(in_array($delivery->status, ['failed', 'skipped'], true))
                                        <form method="POST" action="{{ route('admin.stock-notifications.deliveries.retry', $delivery) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary">Retry</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No external delivery attempts yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><strong>Notification Settings</strong></div>
                <div class="card-body">
                    <div class="alert alert-info small">
                        In-app alerts are enabled by default. Email and WhatsApp are opt-in so external messages are never sent unexpectedly.
                    </div>

                    <form method="POST" action="{{ route('admin.stock-notifications.settings') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" name="in_app_enabled" value="1" id="inApp"
                                   @checked($preference->in_app_enabled)>
                            <label class="form-check-label" for="inApp">In-app notifications</label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" name="email_enabled" value="1" id="emailEnabled"
                                   @checked($preference->email_enabled)>
                            <label class="form-check-label" for="emailEnabled">Email notifications</label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" name="whatsapp_enabled" value="1" id="whatsappEnabled"
                                   @checked($preference->whatsapp_enabled)>
                            <label class="form-check-label" for="whatsappEnabled">WhatsApp notifications</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Minimum Escalation Level</label>
                            <select name="minimum_escalation_level" class="form-select">
                                <option value="1" @selected($preference->minimum_escalation_level === 1)>Level 1 and above</option>
                                <option value="2" @selected($preference->minimum_escalation_level === 2)>Level 2 and above</option>
                                <option value="3" @selected($preference->minimum_escalation_level === 3)>Level 3 only</option>
                            </select>
                        </div>

                        <hr>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" name="assignment_alerts_enabled" value="1" id="assignmentEnabled"
                                   @checked($preference->assignment_alerts_enabled)>
                            <label class="form-check-label" for="assignmentEnabled">Assignment alerts</label>
                            <div class="small text-muted">Notify me when a stock-control escalation or variance investigation is assigned to me.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" name="overdue_reminders_enabled" value="1" id="overdueEnabled"
                                   @checked($preference->overdue_reminders_enabled)>
                            <label class="form-check-label" for="overdueEnabled">Overdue reminders</label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" class="form-check-input" name="recurrence_alerts_enabled" value="1" id="recurrenceEnabled"
                                   @checked($preference->recurrence_alerts_enabled)>
                            <label class="form-check-label" for="recurrenceEnabled">Recurrence alerts</label>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input type="checkbox" class="form-check-input" name="weekly_review_alerts_enabled" value="1" id="reviewEnabled"
                                   @checked($preference->weekly_review_alerts_enabled)>
                            <label class="form-check-label" for="reviewEnabled">Weekly review alerts</label>
                        </div>

                        <button class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> Save Notification Settings
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Delivery Addresses</strong></div>
                <div class="card-body small">
                    <div class="mb-3">
                        <div class="text-muted">Email</div>
                        <div>{{ auth()->user()->maskedNotificationEmail() ?: 'Not configured' }}</div>
                    </div>
                    <div>
                        <div class="text-muted">WhatsApp</div>
                        <div>{{ auth()->user()->maskedNotificationPhone() ?: 'Not configured' }}</div>
                    </div>
                    <div class="text-muted mt-3">
                        These addresses come from the user/account profile. Notification settings do not alter contact master data.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
