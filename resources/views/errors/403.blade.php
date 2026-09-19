@extends('layouts.admin.base')

@section('title', '403 - Unauthorized')

@section('css')
<link href="{{ asset('vendor/bootstrap-icons/1.10.5/bootstrap-icons.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center">
        <h1 class="display-1 fw-bold text-danger">403</h1>
        <p class="fs-3 text-dark">🚫 <strong>Access Denied</strong></p>
        <p class="lead text-muted">
            You don’t have permission to access this page.
        </p>
        @if(isset($permission))
            <div class="alert alert-warning mt-3">
                <i class="bi bi-shield-lock"></i>
                <strong>Missing Permission:</strong> <code>{{ $permission }}</code>
            </div>
        @endif
        <a href="{{ url()->previous() }}" class="btn btn-outline-primary mt-3">
            <i class="bi bi-arrow-left me-1"></i> Go Back
        </a>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary mt-3 ms-2">
            <i class="bi bi-house-door me-1"></i> Dashboard
        </a>
    </div>
</div>
@endsection
