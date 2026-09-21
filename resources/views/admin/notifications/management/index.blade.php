@extends('layouts.admin.base')

@section('title', 'Management Notifications')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Management Notifications</h1>
            <p class="text-muted mb-0">Stock-control escalations, assignments, overdue actions, recurrence alerts and weekly review reminders.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.management-notifications.settings') }}" class="btn btn-outline-secondary">
                <i class="bi bi-gear me-1"></i> Notification Settings
            </a>
            @can('review stock reconciliations')
            <form method="POST" action="{{ route('admin.management-notifications.sync') }}">
                @csrf
                <button class="btn btn-outline-primary">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync Now
                </button>
            </form>
            @endcan
            <form method="POST" action="{{ route('admin.management-notifications.mark-all-read') }}">
                @csrf
                <button class="btn btn-primary" @disabled(auth()->user()->unreadNotifications()->count() === 0)>
                    <i class="bi bi-envelope-open me-1"></i> Mark All Read
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card border-0 shadow-sm">
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
                <div class="list-group-item p-3 {{ $notification->read_at ? '' : 'bg-light' }}">
                    <div class="d-flex gap-3">
                        <div class="pt-1">
                            <span class="badge bg-{{ $badge }}">
                                {{ ucfirst($severity) }}
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <a href="{{ route('admin.management-notifications.open', $notification->id) }}"
                                       class="fw-semibold text-decoration-none text-body">
                                        {{ $data['title'] ?? 'Management Alert' }}
                                    </a>
                                    @if(! $notification->read_at)
                                        <span class="badge bg-primary ms-1">New</span>
                                    @endif
                                </div>
                                <small class="text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="text-muted mt-1">{{ $data['message'] ?? '' }}</div>
                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('admin.management-notifications.open', $notification->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Open
                                </a>
                                @if(! $notification->read_at)
                                    <form method="POST" action="{{ route('admin.management-notifications.mark-read', $notification->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-light">Mark Read</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                    No management notifications yet.
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
