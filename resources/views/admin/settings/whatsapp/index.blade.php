@extends('layouts.admin.base')

@section('title', 'WhatsApp Connection')

@section('content')
<div class="card mt-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">WhatsApp Connection</h5>
    </div>
    <div class="card-body text-center">
        <p>{{ __('ui.status_colon') }}
            <span class="badge bg-{{ $status === 'connected' ? 'success' : 'danger' }}">
                {{ strtoupper($status) }}
            </span>
        </p>

        @if ($status === 'connected')
            <p class="text-success fw-bold">✅ WhatsApp is connected.</p>
        @elseif($qr)
            <p>Scan this QR code with your WhatsApp:</p>
            <img src="{{ $qr }}" class="img-fluid mt-2" style="max-width: 300px;">
        @else
            <p class="text-muted">Unable to load QR. Try again later.</p>
        @endif
    </div>
</div>
@endsection
