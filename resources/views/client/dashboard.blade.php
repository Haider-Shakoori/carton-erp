<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>Rahe Arya Sarafi - Client Wallet</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />

  {{-- Shared CSS --}}
  @include('client.includes.css')
</head>

<body>
  {{-- ===== Header ===== --}}
  <div class="hdr">
  <div class="container-tight position-relative">
    <div class="hdr-row">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ asset('images/' . $setting->logo) }}" alt="{{ $setting->company_name }}" height="28">
        <div class="fw-bold">{{ $setting->company_name }}</div>
      </div>
      <div id="avatarBtn" class="avatar">{{ strtoupper(substr($account->name,0,2)) }}</div>
    </div>
    <div id="hdrDropdown" class="dropdown-card">
  <a href="#" class="item" data-target="tab-profile">
    <i class="fa-regular fa-user"></i> {{ __('ui.profile') }}
  </a>
  <a href="#" class="item" data-target="tab-notifications">
    <i class="fa-regular fa-bell"></i> {{ __('ui.notifications_label') }}
  </a>
  <a href="#" class="item" data-target="tab-password">
    <i class="fa-solid fa-key"></i> {{ __('ui.change_password') }}
  </a>
  <div class="dropdown-divider"></div>
  <form action="{{ route('client.logout') }}" method="POST">
    @csrf
    <button type="submit" class="item w-100 text-start border-0 bg-transparent">
      <i class="fa-solid fa-right-from-bracket"></i> {{ __('ui.logout') }}
    </button>
  </form>
</div>

  </div>
</div>


  {{-- ===== Main Container ===== --}}
  <main class="container-tight main" id="main">
    <div class="ptr"><div class="ptr-indicator" id="ptrTip">↓ Pull to refresh</div></div>

    {{-- ==== HOME TAB ==== --}}
    @include('client.tabs.home')

    {{-- ==== JOURNAL TAB ==== --}}
    @include('client.tabs.journal')

    {{-- ==== EXCHANGE TAB ==== --}}
    @include('client.tabs.exchange')

    {{-- ==== REMITTANCE TAB ==== --}}
    @include('client.tabs.remittance')

    {{-- ==== PROFILE TAB ==== --}}
    @include('client.tabs.profile')

    {{-- ==== PASSWORD TAB ==== --}}
    @include('client.tabs.password')

    {{-- ==== NOTIFICATIONS TAB ==== --}}
    @include('client.tabs.notifications')
  </main>

  {{-- ===== Tabbar Navigation ===== --}}
  <nav class="tabbar" id="tabbar">
    <a href="#" data-target="tab-home" class="active"><i class="fa-solid fa-chart-pie"></i><span>Home</span></a>
    <a href="#" data-target="tab-journal"><i class="fa-solid fa-file-invoice-dollar"></i><span>{{ __('ui.journal') }}</span></a>
    <a href="#" data-target="tab-exchange"><i class="fa-solid fa-right-left"></i><span>{{ __('ui.exchange') }}</span></a>
    <a href="#" data-target="tab-remit"><i class="fa-solid fa-paper-plane"></i><span>Remit</span></a>
  </nav>

  {{-- Shared JS --}}
  @include('client.includes.js')
</body>
</html>
