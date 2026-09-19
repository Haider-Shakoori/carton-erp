@extends('layouts.admin.base')
@section('title', __('ui.online_users'))

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('ui.active_now') }}</h5>
  </div>
  <div class="card-body">
    <ul class="list-group list-group-flush">
      @forelse($users as $user)
      <li class="list-group-item d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <div class="rounded-circle bg-success me-3" style="width:12px;height:12px;"></div>
          <div>
            <strong>{{ $user->name }}</strong>
            <div class="text-muted small">{{ '@' . $user->username }}</div>
          </div>
        </div>
        <span class="text-muted small">
          {{ $user->lastSeen() ? \Carbon\Carbon::parse($user->lastSeen())->diffForHumans() : __('ui.active_now') }}
        </span>
      </li>
      @empty
      <li class="list-group-item text-muted text-center">{{ __('ui.no_one_online') }}</li>
      @endforelse
    </ul>
  </div>
</div>
@endsection
