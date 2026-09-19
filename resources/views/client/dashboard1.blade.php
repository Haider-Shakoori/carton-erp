{{-- resources/views/client/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ __('ui.client_wallet') }}</title>

  <link rel="stylesheet" href="{{ asset('vendor/fontawesome/6.5.0/css/all.min.css') }}">
  <link href="{{ asset('vendor/bootstrap/css/bootstrap-5.3.2.min.css') }}" rel="stylesheet">
  <script src="{{ asset('vendor/chartjs/chart-4.4.1.min.js') }}"></script>

  <style>
    :root{
      --bg:#f7f9fc; --fg:#0f1830; --muted:#6b7386; --line:#e9ecf2;
      --card:#ffffff; --brand:#0e7afe; --good:#0fbf61; --bad:#ef3e4a; --warn:#f59e0b;
      --radius:16px; --shadow:0 8px 26px rgba(19,33,68,.08);
    }
    html,body{background:var(--bg); color:var(--fg); font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
    .container-tight{max-width:520px; margin:0 auto}

    /* Header */
    .hdr{position:sticky; top:0; z-index:20; background:linear-gradient(180deg,#fff,rgba(255,255,255,.92)); backdrop-filter:saturate(160%) blur(8px); border-bottom:1px solid var(--line)}
    .hdr-row{padding:12px 14px; display:flex; align-items:center; justify-content:space-between}
    .avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:#0f1830; color:#fff; font-weight:700; cursor:pointer}

    /* Dropdown */
    .dropdown-card{position:absolute; right:14px; top:60px; width:220px; background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.12); display:none; z-index:30}
    .dropdown-card.show{display:block}
    .dropdown-card .item{display:flex; align-items:center; gap:10px; padding:10px 12px; color:var(--fg); text-decoration:none}
    .dropdown-card .item:hover{background:#f6f8ff}
    .dropdown-divider{height:1px; background:var(--line); margin:4px 0}

    /* Main */
    .main{padding:12px 10px 90px}
    .section-title{font-weight:800; font-size:15px; margin:14px 4px 10px}
    .wallets{display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px}
    .wallet{border-radius:16px; padding:14px; background:var(--card); border:1px solid var(--line); box-shadow:var(--shadow)}
    .wallet .code{color:#6b7386; font-size:12px}
    .wallet .amt{font-weight:800; font-size:18px; margin-top:4px}
    .wallet.positive .amt{color:var(--good)}
    .wallet.negative .amt{color:var(--bad)}
    .wallet .meta{color:#8a93a7; font-size:11px}

    .cardx{background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); padding:14px}
    .cardx h6{margin:0 0 8px; font-weight:800}
    .muted{color:var(--muted)}
    .list{display:flex; flex-direction:column; gap:10px}
    .rowcard{display:flex; gap:12px; padding:12px; border-radius:14px; background:#fff; border:1px solid var(--line); box-shadow:var(--shadow)}
    .rowcard .ic{width:36px;height:36px;border-radius:12px; display:grid;place-items:center; background:#eef4ff; color:#0e7afe}
    .rowcard .ic.green{background:#ebfff5; color:#0fbf61}
    .rowcard .ic.yellow{background:#fff7e9; color:#f59e0b}
    .rowcard .ic.red{background:#ffeef0; color:#ef3e4a}
    .rowcard .title{font-weight:700; font-size:14px}
    .rowcard .sub{font-size:12px; color:#8a93a7}
    .rowcard .amt{margin-left:auto; font-weight:800}
    .amt.pos{color:var(--good)} .amt.neg{color:var(--bad)}

    /* Filters */
    .filters{display:grid; grid-template-columns:1fr 1fr; gap:8px}
    .filters .wide{grid-column:1/-1}
    .filters .btn-ghost{border:1px solid var(--line); background:#ffffff; color:#0f1830}
    select, input[type="date"]{background:#fff; color:#0f1830; border:1px solid var(--line); border-radius:12px; padding:8px 10px}

    /* Chips */
    .chip{display:inline-flex; align-items:center; gap:8px; background:#f1f5ff; color:#0f1830; border:1px solid #e2e8ff; padding:8px 12px; border-radius:999px; font-size:12px; box-shadow:var(--shadow)}

    /* Bottom Tabbar */
    .tabbar{position:fixed; bottom:0; left:0; right:0; background:#fff; border-top:1px solid var(--line); display:flex; justify-content:space-around; padding:6px 4px 8px; z-index:25}
    .tabbar a{display:flex; flex-direction:column; align-items:center; gap:4px; text-decoration:none; color:#6b7386; font-size:11px; padding:6px 10px; border-radius:12px}
    .tabbar a.active{color:#0e7afe; background:#eef4ff}

    /* Tabs */
    .tabpane{display:none; min-height:60vh}
    .tabpane.active{display:block; animation:fade .18s ease-out}
    @keyframes fade{from{opacity:.3; transform:translateY(6px)} to{opacity:1; transform:none}}

    /* Pull to refresh */
    .ptr{position:relative; height:0; overflow:visible}
    .ptr-indicator{position:absolute; top:-30px; left:50%; transform:translateX(-50%); background:#fff; color:#0f1830; border:1px solid var(--line); border-radius:999px; padding:4px 10px; font-size:12px; box-shadow:var(--shadow); opacity:0; transition:opacity .15s}
    .ptr-indicator.show{opacity:1}

    /* Dashboard welcome */
    .hero{background:linear-gradient(135deg,#eff6ff,#ffffff); border:1px solid var(--line); border-radius:18px; box-shadow:var(--shadow); padding:16px; display:flex; gap:12px; align-items:center}
    .hero .badge{background:#eef4ff; color:#0e7afe; border:1px solid #e2ecff}
  </style>
</head>
<body>
<div class="hdr">
  <div class="container-tight position-relative">
    <div class="hdr-row">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-wallet text-primary"></i>
        <div class="fw-bold">{{ __('ui.client_wallet') }}</div>
      </div>
      <div id="avatarBtn" class="avatar" title="{{ __('ui.account') }}">{{ strtoupper(substr($account->name,0,2)) }}</div>
    </div>

    {{-- Floating dropdown (closes on outside tap) --}}
    <div id="hdrDropdown" class="dropdown-card">
      <a href="#" class="item" data-target="tab-profile"><i class="fa-regular fa-user"></i> {{ __('ui.profile') }}</a>
      <a href="#" class="item" data-target="tab-notifications"><i class="fa-regular fa-bell"></i> {{ __('ui.notifications_label') }}</a>
      <a href="#" class="item" data-target="tab-password"><i class="fa-solid fa-key"></i> {{ __('ui.change_password') }}</a>
      <div class="dropdown-divider"></div>
      <form action="{{ route('client.logout') }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="item w-100 text-start"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
      </form>
    </div>
  </div>
</div>

<main class="container-tight main" id="main">

  {{-- pull-to-refresh indicator --}}
  <div class="ptr"><div class="ptr-indicator" id="ptrTip">↓ Pull to refresh</div></div>

  {{-- ======= DASHBOARD ======= --}}
  <section id="tab-home" class="tabpane active" data-reload="home">
    {{-- Welcome hero --}}
    <div class="hero mb-3">
      <div class="ic">
        <div class="avatar" style="width:44px;height:44px">{{ strtoupper(substr($account->name,0,2)) }}</div>
      </div>
      <div>
        <div class="small text-muted">{{ __('ui.welcome') }}</div>
        <div class="fs-5 fw-bold">{{ $account->name }}</div>
        <div class="badge rounded-pill mt-1">Code: {{ $account->code }}</div>
      </div>
    </div>

    <div class="section-title">Wallets</div>
    <div id="walletGrid" class="wallets mb-2">
      @forelse($balances as $b)
        @php $sign = ($b->balance ?? 0) >= 0 ? 'positive' : 'negative'; @endphp
        <div class="wallet {{ $sign }}">
          <div class="d-flex justify-content-between">
            <div class="code">{{ $b->currency?->code ?? 'CUR' }}</div>
            <div class="code">{{ $b->currency?->symbol }}</div>
          </div>
          <div class="amt">{{ $b->currency?->symbol }}{{ number_format($b->balance,2) }}</div>
          <div class="meta">Cr {{ number_format($b->credit,2) }} · Dr {{ number_format($b->debit,2) }}</div>
        </div>
      @empty
        <div class="text-muted">No balances.</div>
      @endforelse
    </div>

    {{-- Charts --}}
    <div class="cardx mb-3">
      <h6>Transaction Trend (6 months)</h6>
      <canvas id="chartJournal" height="140"></canvas>
    </div>
    <div class="cardx mb-3">
      <h6>Exchanges (Base vs Target)</h6>
      <canvas id="chartExchange" height="140"></canvas>
    </div>
    <div class="cardx mb-3">
      <h6>{{ __('ui.balances_by_currency') }}</h6>
      <canvas id="chartBalancePie" height="160"></canvas>
    </div>

    <div class="cardx">
      <h6>{{ __('ui.recent_transactions') }}</h6>
      <div id="recentList" class="list">
        @forelse($recentTransactions as $t)
          @php $isCr = $t->transaction_type==='credit'; @endphp
          <div class="rowcard">
            <div class="ic {{ $isCr ? 'green':'red' }}"><i class="fa-solid {{ $isCr ? 'fa-arrow-down':'fa-arrow-up' }}"></i></div>
            <div>
              <div class="title">{{ $t->note ?? '—' }}</div>
              <div class="sub">{{ $t->created_at?->format('Y-m-d') }} · {{ $t->currency?->code }}</div>
            </div>
            <div class="amt {{ $isCr ? 'pos':'neg' }}">
              {{ $t->currency?->symbol }}{{ $isCr?'+':'-' }}{{ number_format($t->amount,2) }}
            </div>
          </div>
        @empty
          <div class="text-muted">No transactions.</div>
        @endforelse
      </div>
    </div>
  </section>

  {{-- ======= JOURNAL ======= --}}
  <section id="tab-journal" class="tabpane" data-reload="journal">
    <div class="section-title">{{ __('ui.journal') }}</div>

    {{-- Top balances (ONLY here) --}}
    <div class="mb-2 d-flex flex-wrap gap-2" id="topBalances">
      @forelse($balances as $b)
        @php $sym = $b->currency?->symbol; @endphp
        <div class="chip">
          <i class="fa-regular fa-circle-dot text-primary"></i>
          {{ $b->currency?->code }}: <strong>{{ $sym }}{{ number_format($b->balance,2) }}</strong>
        </div>
      @empty
        <div class="text-muted small">No balances.</div>
      @endforelse
    </div>

    <div class="cardx mb-3">
      <div class="filters">
        <div>
          <label class="form-label small mb-1">{{ __('ui.currency') }}</label>
          <select id="jCur" class="form-select form-select-sm">
            <option value="">{{ __('ui.all') }}</option>
            @foreach($currencies as $c)
              <option value="{{ $c->id }}">{{ $c->code }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="form-label small mb-1">{{ __('ui.type') }}</label>
          <select id="jType" class="form-select form-select-sm">
            <option value="">{{ __('ui.all') }}</option>
            <option value="credit">{{ __('ui.credit') }}</option>
            <option value="debit">Debit</option>
          </select>
        </div>
        <div><label class="form-label small mb-1">From</label><input id="jFrom" type="date" class="form-control form-control-sm"></div>
        <div><label class="form-label small mb-1">To</label><input id="jTo" type="date" class="form-control form-control-sm"></div>
        <div class="wide d-grid"><button id="jApply" class="btn btn-sm btn-ghost">{{ __('ui.apply_filters') }}</button></div>
      </div>
    </div>

    <div class="d-flex gap-2 mb-2">
      <div class="chip">{{ __('ui.balance') }} <strong id="jBal" class="ms-1">—</strong></div>
      <div class="chip">{{ __('ui.credit') }} <strong id="jCr" class="ms-1">—</strong></div>
      <div class="chip">Debit <strong id="jDr" class="ms-1">—</strong></div>
    </div>
    <div id="jSumRow" class="d-flex gap-2 flex-wrap mb-3"></div>
    <div id="jList" class="list"></div>
    <div class="d-grid mt-2"><button id="jMore" class="btn btn-sm btn-ghost">{{ __('ui.load_more') }}</button></div>
  </section>

  {{-- ======= EXCHANGE ======= --}}
  <section id="tab-exchange" class="tabpane" data-reload="exchange">
    <div class="section-title">{{ __('ui.exchanges') }}</div>

    {{-- Summary (Base & Target) --}}
    <div class="d-flex flex-wrap gap-2 mb-2" id="xSumBase"></div>
    <div class="d-flex flex-wrap gap-2 mb-3" id="xSumTarget"></div>

    <div class="cardx mb-3">
      <div class="filters">
        <div>
          <label class="form-label small mb-1">{{ __('ui.currency') }}</label>
          <select id="xCur" class="form-select form-select-sm">
            <option value="">{{ __('ui.all') }}</option>
            @foreach($currencies as $c)
              <option value="{{ $c->id }}">{{ $c->code }}</option>
            @endforeach
          </select>
        </div>
        <div><label class="form-label small mb-1">From</label><input id="xFrom" type="date" class="form-control form-control-sm"></div>
        <div><label class="form-label small mb-1">To</label><input id="xTo" type="date" class="form-control form-control-sm"></div>
        <div class="wide d-grid"><button id="xApply" class="btn btn-sm btn-ghost">{{ __('ui.apply_filters') }}</button></div>
      </div>
    </div>

    <div id="xList" class="list"></div>
    <div class="d-grid mt-2"><button id="xMore" class="btn btn-sm btn-ghost">{{ __('ui.load_more') }}</button></div>
  </section>

  {{-- ======= REMITTANCE ======= --}}
  <section id="tab-remit" class="tabpane" data-reload="remit">
    <div class="section-title">{{ __('ui.remittances') }}</div>

    {{-- Pending / Approved summary --}}
    <div class="d-flex gap-2 mb-3" id="rTop">
      <div class="chip"><i class="fa-solid fa-hourglass-half text-warning"></i> {{ __('ui.pending') }} <strong id="rPending" class="ms-1">—</strong></div>
      <div class="chip"><i class="fa-solid fa-circle-check text-success"></i> {{ __('ui.approved') }} <strong id="rApproved" class="ms-1">—</strong></div>
    </div>

    <div class="cardx mb-3">
      <div class="filters">
        <div>
          <label class="form-label small mb-1">{{ __('ui.currency') }}</label>
          <select id="rCur" class="form-select form-select-sm">
            <option value="">{{ __('ui.all') }}</option>
            @foreach($currencies as $c)
              <option value="{{ $c->id }}">{{ $c->code }}</option>
            @endforeach
          </select>
        </div>
        <div><label class="form-label small mb-1">From</label><input id="rFrom" type="date" class="form-control form-control-sm"></div>
        <div><label class="form-label small mb-1">To</label><input id="rTo" type="date" class="form-control form-control-sm"></div>
        <div class="wide d-grid"><button id="rApply" class="btn btn-sm btn-ghost">{{ __('ui.apply_filters') }}</button></div>
      </div>
    </div>

    <div id="rSumRow" class="d-flex gap-2 flex-wrap mb-3"></div>
    <div id="rList" class="list"></div>
    <div class="d-grid mt-2"><button id="rMore" class="btn btn-sm btn-ghost">{{ __('ui.load_more') }}</button></div>
  </section>

  {{-- ======= PROFILE (edit) ======= --}}
  <section id="tab-profile" class="tabpane" data-reload="profile">
    <div class="section-title">Edit Profile</div>
    <div class="cardx">
      <form id="profileForm">
        @csrf
        <div class="mb-3">
          <label class="form-label small">{{ __('ui.full_name') }}</label>
          <input type="text" name="name" class="form-control" value="{{ Auth::user()->name }}">
        </div>
        <div class="mb-3">
          <label class="form-label small">{{ __('ui.email') }}</label>
          <input type="email" name="email" class="form-control" value="{{ Auth::user()->email }}">
        </div>
        <div class="mb-3">
          <label class="form-label small">{{ __('ui.phone') }}</label>
          <input type="text" name="phone" class="form-control" value="{{ Auth::user()->phone ?? '' }}">
        </div>
        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
      </form>
      <div id="profileMsg" class="small mt-2"></div>
    </div>
  </section>

  {{-- ======= PASSWORD ======= --}}
  <section id="tab-password" class="tabpane" data-reload="password">
    <div class="section-title">{{ __('ui.change_password') }}</div>
    <div class="cardx">
      <form id="passwordForm">
        @csrf
        <div class="mb-3">
          <label class="form-label small">{{ __('ui.current_password') }}</label>
          <input type="password" name="current_password" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label small">{{ __('ui.new_password') }}</label>
          <input type="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label small">{{ __('ui.confirm_password') }}</label>
          <input type="password" name="password_confirmation" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">{{ __('ui.update_password') }}</button>
      </form>
      <div id="passwordMsg" class="small mt-2"></div>
    </div>
  </section>

  {{-- ======= NOTIFICATIONS ======= --}}
  <section id="tab-notifications" class="tabpane" data-reload="notifications">
    <div class="section-title">{{ __('ui.notifications') }}</div>
    <div class="list" id="notifList">
      {{-- Optional: load via AJAX later --}}
      <div class="rowcard">
        <div class="ic yellow"><i class="fa-regular fa-bell"></i></div>
        <div>
          <div class="title">Remittance Approved</div>
          <div class="sub">Just now · System</div>
        </div>
        <div class="ms-auto"><span class="chip">Unread</span></div>
      </div>
    </div>
  </section>

</main>

<nav class="tabbar" id="tabbar">
  <a href="#" data-target="tab-home" class="active"><i class="fa-solid fa-chart-pie"></i><span>Home</span></a>
  <a href="#" data-target="tab-journal"><i class="fa-solid fa-file-invoice-dollar"></i><span>{{ __('ui.journal') }}</span></a>
  <a href="#" data-target="tab-exchange"><i class="fa-solid fa-right-left"></i><span>{{ __('ui.exchange') }}</span></a>
  <a href="#" data-target="tab-remit"><i class="fa-solid fa-paper-plane"></i><span>Remit</span></a>
</nav>

<script src="{{ asset('vendor/jquery360/jquery-3.6.0.min.js') }}"></script>
<script>
(() => {
  const $ = window.jQuery;
  const fmt = n => Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  const num = v => Number(String(v ?? '0').replace(/[^0-9.\-]/g,'')) || 0; // robust numeric parser
  const curMap = {
    @foreach($currencies as $c)
      {{ $c->id }}: {code:'{{ $c->code }}', symbol:'{{ $c->symbol }}'},
    @endforeach
  };

  const PAGE_SIZE = 20;

function handleLoadMoreButton($btn, isLoading) {
  if (!$btn.length) return;
  if (isLoading) {
    $btn.prop('disabled', true).text('Loading…');
  } else {
    $btn.prop('disabled', false).text('Load more');
  }
}

function updateLoadMoreVisibility($btn, loaded, total, lastBatchLen) {
  // hide if all loaded or server returned less than a full page
  if (loaded >= total || lastBatchLen < PAGE_SIZE) {
    $btn.hide();
  } else {
    $btn.show();
  }
}


  // ----- Header dropdown (close on outside) -----
  const menu = document.getElementById('hdrDropdown');
  document.getElementById('avatarBtn').addEventListener('click', (e) => {
    e.stopPropagation();
    menu.classList.toggle('show');
  });
  document.addEventListener('click', (e)=>{
    if(!menu.contains(e.target) && e.target.id !== 'avatarBtn'){ menu.classList.remove('show'); }
  });
  // Dropdown -> tab navigation
  $('#hdrDropdown [data-target]').on('click', function(e){
    e.preventDefault();
    const tab = $(this).data('target');
    menu.classList.remove('show');
    showTab(tab);
  });

  // ----- Tabs + swipe gestures -----
  const panes = ['tab-home','tab-journal','tab-exchange','tab-remit','tab-profile','tab-password','tab-notifications'];
  function showTab(id){
    document.querySelectorAll('.tabpane').forEach(p=>p.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('.tabbar a').forEach(a=>a.classList.remove('active'));
    const nav = document.querySelector(`.tabbar a[data-target="${id}"]`);
    if(nav) nav.classList.add('active');
    window.scrollTo({top:0, behavior:'smooth'});
  }
  document.querySelectorAll('.tabbar a').forEach(a=>{
    a.addEventListener('click', e=>{ e.preventDefault(); showTab(a.dataset.target); });
  });
  // Swipe left/right
  let startX=0, startY=0, swiping=false;
  const main = document.getElementById('main');
  main.addEventListener('touchstart', e=>{
    const t = e.touches[0]; startX = t.clientX; startY = t.clientY; swiping = true;
  }, {passive:true});
  main.addEventListener('touchmove', e=>{
    if(!swiping) return;
    const t = e.touches[0]; const dx = t.clientX - startX; const dy = t.clientY - startY;
    if(Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
      swiping = false;
      const activeId = document.querySelector('.tabpane.active').id;
      let i = panes.indexOf(activeId);
      if(dx < 0 && i < panes.length-1) showTab(panes[i+1]); // left -> next
      if(dx > 0 && i > 0) showTab(panes[i-1]);             // right -> prev
    }
  }, {passive:true});
  main.addEventListener('touchend', ()=>{ swiping=false; });

  // ----- Pull-to-refresh -----
  let pullStartY=0, pulling=false, pulled=false;
  const ptrTip = document.getElementById('ptrTip');
  document.addEventListener('touchstart', e=>{
    if(window.scrollY === 0){
      pullStartY = e.touches[0].clientY; pulling = true; pulled = false;
    }
  }, {passive:true});
  document.addEventListener('touchmove', e=>{
    if(!pulling) return;
    const dist = e.touches[0].clientY - pullStartY;
    if(dist > 60){ ptrTip.textContent = '↻ Release to refresh'; ptrTip.classList.add('show'); pulled = true; }
    else if(dist > 15){ ptrTip.textContent = '↓ Pull to refresh'; ptrTip.classList.add('show'); }
    else { ptrTip.classList.remove('show'); }
  }, {passive:true});
  document.addEventListener('touchend', ()=>{
    if(pulling && pulled){
      ptrTip.textContent = 'Refreshing…';
      reloadActiveTab().finally(()=>{ setTimeout(()=>ptrTip.classList.remove('show'), 400); });
    } else { ptrTip.classList.remove('show'); }
    pulling = false; pulled = false;
  });

  // ===================== JOURNAL =====================
const journalUrl = "{{ route('client.journal', $account->id) }}";
const journalSummaryUrl = "{{ route('client.journal-summary', $account->id) }}";

let jPage = 1, jTotal = 0, jLoaded = 0;
function renderJournalCard(x){
  const isCr = (x.transaction_type === 'credit') || (x.credit && x.credit !== '');
  const amt = num(x.amount ?? (isCr ? x.credit : x.debit));
  const cur = x.currency?.code || x.currency || '';
  const sym = x.currency?.symbol || Object.values(curMap).find(c=>c.code===cur)?.symbol || '';
  return `
    <div class="rowcard">
      <div class="ic ${isCr?'green':'red'}"><i class="fa-solid ${isCr?'fa-arrow-down':'fa-arrow-up'}"></i></div>
      <div>
        <div class="title">${x.note ?? x.description ?? '-'}</div>
        <div class="sub">${(x.created_at ?? '').toString().slice(0,10)} · ${cur}</div>
      </div>
      <div class="amt ${isCr?'pos':'neg'}">${sym}${isCr?'+':'-'}${fmt(amt)}</div>
    </div>`;
}

function loadJournal(reset=false){
  if (reset) {
    jPage = 1; jTotal = 0; jLoaded = 0;
    $('#jList').empty();
    $('#jMore').show();
  }
  const $btn = $('#jMore');
  handleLoadMoreButton($btn, true);

  return $.get(journalUrl, {
    start: (jPage-1)*PAGE_SIZE,
    length: PAGE_SIZE,
    currency_id: $('#jCur').val(),
    type: $('#jType').val(),
    start_date: $('#jFrom').val(),
    end_date: $('#jTo').val()
  })
  .done(res=>{
    const arr = res.data || [];
    if (typeof res.recordsTotal !== 'undefined') {
      jTotal = Number(res.recordsTotal) || 0;
    }
    if (arr.length) {
      $('#jList').append(arr.map(renderJournalCard).join(''));
      jLoaded += arr.length;
      jPage++;
    } else if (jTotal === 0 && reset) {
      $('#jList').html('<div class="text-muted">{{ __('ui.no_results') }}</div>');
    }
    updateLoadMoreVisibility($btn, jLoaded, jTotal, arr.length);
  })
  .always(()=> handleLoadMoreButton($btn, false));
}

function refreshJournalSummary(){
  return $.get(journalSummaryUrl, {
    currency_id: $('#jCur').val(),
    type: $('#jType').val(),
    start_date: $('#jFrom').val(),
    end_date: $('#jTo').val()
  }).done(res=>{
    $('#jBal').text(fmt(res.balance)); $('#jCr').text(fmt(res.credit)); $('#jDr').text(fmt(res.debit));
    const row = $('#jSumRow').empty();
    (res.by_currency||[]).forEach(it=>{
      const code = curMap[it.currency_id]?.code || 'CUR';
      const sym  = curMap[it.currency_id]?.symbol || '';
      const bal  = num(it.credit) - num(it.debit);
      row.append(`<div class="chip">${code}: <strong>${sym}${fmt(bal)}</strong></div>`);
    });
  });
}

$('#jApply').on('click', ()=>{ $.when(loadJournal(true), refreshJournalSummary()); });
$('#jMore').on('click', ()=> loadJournal(false));
function reloadJournal(){ $.when(loadJournal(true), refreshJournalSummary()); }
reloadJournal();

// ===================== EXCHANGE (NaN-proof + paginate) =====================
const xUrl = "{{ route('client.exchanges', $account->id) }}";
const xSumUrl = "{{ route('client.exchanges-summary', $account->id) }}";
let xPage=1, xTotal=0, xLoaded=0;

function renderXCard(x){
  const baseAmt = num(x.base_amount);
  const tgtAmt  = num(x.target_amount);
  const rate    = (x.rate ?? '').toString();
  const from    = x.base_currency?.code || x.base_currency || '';
  const to      = x.target_currency?.code || x.target_currency || '';
  return `
    <div class="rowcard">
      <div class="ic"><i class="fa-solid fa-right-left"></i></div>
      <div>
        <div class="title">${from} → ${to}</div>
        <div class="sub">${(x.created_at ?? '').toString().slice(0,10)} · rate ${rate}</div>
      </div>
      <div class="text-end ms-auto">
        <div class="amt pos">${fmt(tgtAmt)}</div>
        <div class="sub">${fmt(baseAmt)} base</div>
      </div>
    </div>`;
}

function loadX(reset=false){
  if (reset) {
    xPage=1; xTotal=0; xLoaded=0;
    $('#xList').empty();
    $('#xMore').show();
  }
  const $btn = $('#xMore');
  handleLoadMoreButton($btn, true);

  return $.get(xUrl, {
    start: (xPage-1)*PAGE_SIZE,
    length: PAGE_SIZE,
    currency_id: $('#xCur').val(),
    start_date: $('#xFrom').val(),
    end_date: $('#xTo').val()
  })
  .done(res=>{
    const arr = res.data || [];
    if (typeof res.recordsTotal !== 'undefined') xTotal = Number(res.recordsTotal) || 0;
    if (arr.length) {
      $('#xList').append(arr.map(renderXCard).join(''));
      xLoaded += arr.length;
      xPage++;
    } else if (xTotal === 0 && reset) {
      $('#xList').html('<div class="text-muted">{{ __('ui.no_results') }}</div>');
    }
    updateLoadMoreVisibility($btn, xLoaded, xTotal, arr.length);
  })
  .always(()=> handleLoadMoreButton($btn, false));
}

function refreshXSummary(){
  return $.get(xSumUrl).done(res=>{
    const base = res.base || [], target = res.target || [];
    const bMap = {}, tMap = {};
    base.forEach(b=>{ bMap[b.base_currency_id]   = (bMap[b.base_currency_id]   || 0) + num(b.total); });
    target.forEach(t=>{ tMap[t.target_currency_id] = (tMap[t.target_currency_id] || 0) + num(t.total); });

    const xb = $('#xSumBase').empty();
    Object.entries(bMap).forEach(([cid,total])=>{
      const code = curMap[cid]?.code || 'CUR';
      const sym  = curMap[cid]?.symbol || '';
      xb.append(`<div class="chip"><i class="fa-solid fa-arrow-trend-up text-primary"></i> Base ${code}: <strong>${sym}${fmt(total)}</strong></div>`);
    });

    const xt = $('#xSumTarget').empty();
    Object.entries(tMap).forEach(([cid,total])=>{
      const code = curMap[cid]?.code || 'CUR';
      const sym  = curMap[cid]?.symbol || '';
      xt.append(`<div class="chip"><i class="fa-solid fa-arrow-trend-down text-success"></i> Target ${code}: <strong>${sym}${fmt(total)}</strong></div>`);
    });
  });
}

$('#xApply').on('click', ()=> loadX(true));
$('#xMore').on('click', ()=> loadX(false));
function reloadExchange(){ $.when(loadX(true), refreshXSummary()); }
reloadExchange();

  // ===================== REMITTANCE (paginate) =====================
const rUrl = "{{ route('client.remittances', $account->id) }}";
const rSumUrl = "{{ route('client.remittances-summary', $account->id) }}";
let rPage=1, rTotal=0, rLoaded=0;

function renderRCard(x){
  const st = (x.status||'').toLowerCase();
  const ic = st==='processed' ? 'green' : (st==='pending'?'yellow':'red');
  const chip = st ? `<span class="chip">${x.status}</span>` : '';
  const amt = num(x.amount);
  const cur = x.currency?.code || x.currency || '';
  return `
    <div class="rowcard">
      <div class="ic ${ic}"><i class="fa-solid fa-paper-plane"></i></div>
      <div>
        <div class="title">${cur} · ${fmt(amt)}</div>
        <div class="sub">${(x.created_at ?? '').toString().slice(0,10)}</div>
      </div>
      <div class="ms-auto">${chip}</div>
    </div>`;
}

function loadR(reset=false){
  if (reset) {
    rPage=1; rTotal=0; rLoaded=0;
    $('#rList').empty();
    $('#rMore').show();
  }
  const $btn = $('#rMore');
  handleLoadMoreButton($btn, true);

  return $.get(rUrl, {
    start: (rPage-1)*PAGE_SIZE,
    length: PAGE_SIZE,
    currency_id: $('#rCur').val(),
    start_date: $('#rFrom').val(),
    end_date: $('#rTo').val()
  })
  .done(res=>{
    const arr = res.data || [];
    if (typeof res.recordsTotal !== 'undefined') rTotal = Number(res.recordsTotal) || 0;
    if (arr.length) {
      $('#rList').append(arr.map(renderRCard).join(''));
      rLoaded += arr.length;
      rPage++;
    } else if (rTotal === 0 && reset) {
      $('#rList').html('<div class="text-muted">{{ __('ui.no_results') }}</div>');
    }
    updateLoadMoreVisibility($btn, rLoaded, rTotal, arr.length);
  })
  .always(()=> handleLoadMoreButton($btn, false));
}

function refreshRSummary(){
  return $.get(rSumUrl).done(res=>{
    const row = $('#rSumRow').empty();
    (res.by_currency||[]).forEach(it=>{
      const code = curMap[it.currency_id]?.code || 'CUR';
      const sym  = curMap[it.currency_id]?.symbol || '';
      row.append(`<div class="chip">${code}: <strong>${sym}${fmt(num(it.total))}</strong></div>`);
    });
    if(typeof res.pending_total !== 'undefined') $('#rPending').text(fmt(num(res.pending_total)));
    if(typeof res.approved_total !== 'undefined') $('#rApproved').text(fmt(num(res.approved_total)));
  });
}

$('#rApply').on('click', ()=> loadR(true));
$('#rMore').on('click', ()=> loadR(false));
function reloadRemit(){ $.when(loadR(true), refreshRSummary()); }
reloadRemit();

  // ----- Dashboard charts -----
  const jLabels = {!! json_encode(array_keys($monthlyNet->toArray())) !!};
  const jData   = {!! json_encode(array_values($monthlyNet->toArray())) !!};
  new Chart(document.getElementById('chartJournal'), {
    type:'line',
    data:{ labels:jLabels, datasets:[{label:'Net', data:jData, borderColor:'#0e7afe', tension:.35}] },
    options:{ plugins:{legend:{display:false}}, scales:{x:{grid:{color:'#eef2f8'}}, y:{grid:{color:'#eef2f8'}, ticks:{callback:v=>fmt(v)}}}}
  });

  const exBase = {!! json_encode($exBase) !!};
  const exTarget = {!! json_encode($exTarget) !!};
  const exCids = Array.from(new Set([ ...Object.keys(exBase), ...Object.keys(exTarget) ]));
  const exLabels = exCids.map(cid => (curMap[cid]?.code)||'CUR');
  const exDataBase = exCids.map(cid => num(exBase[cid]));
  const exDataTarget = exCids.map(cid => num(exTarget[cid]));
  new Chart(document.getElementById('chartExchange'), {
    type:'bar',
    data:{ labels:exLabels, datasets:[
      {label:'Base', data:exDataBase, backgroundColor:'#0e7afe'},
      {label:'Target', data:exDataTarget, backgroundColor:'#0fbf61'}
    ]},
    options:{ plugins:{legend:{position:'top'}}, scales:{x:{grid:{color:'#eef2f8'}}, y:{grid:{color:'#eef2f8'}, ticks:{callback:v=>fmt(v)}}}}
  });

  const balLabels = [ @foreach($balances as $b) '{{ $b->currency?->code }}', @endforeach ];
  const balData = [ @foreach($balances as $b) {{ (float)$b->balance }}, @endforeach ];
  new Chart(document.getElementById('chartBalancePie'), {
    type:'pie',
    data:{ labels:balLabels, datasets:[{data:balData, backgroundColor:['#0fbf61','#0e7afe','#f59e0b','#ef3e4a','#8b5cf6','#22c55e']}] },
    options:{ plugins:{legend:{position:'bottom'}}}
  });

  // ----- AJAX: Profile + Password
  function flash(el, msg, ok=true){
    el.textContent = msg;
    el.className = 'small mt-2 ' + (ok?'text-success':'text-danger');
    setTimeout(()=>{ el.textContent=''; }, 4000);
  }
  $('#profileForm').on('submit', function(e){
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]').prop('disabled',true);
    fetch("{{ url('/client/profile') }}", {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': $('input[name=_token]').val()},
      body: new FormData(this)
    }).then(async r=>{
      $btn.prop('disabled',false);
      if(r.ok){ flash(document.getElementById('profileMsg'),'Profile updated'); }
      else{ const t=await r.text(); flash(document.getElementById('profileMsg'),'Update failed',false); console.warn(t); }
    }).catch(err=>{ $btn.prop('disabled',false); flash(document.getElementById('profileMsg'),'Network error',false); });
  });

  $('#passwordForm').on('submit', function(e){
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]').prop('disabled',true);
    fetch("{{ url('/client/password/change') }}", {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': $('input[name=_token]').val()},
      body: new FormData(this)
    }).then(async r=>{
      $btn.prop('disabled',false);
      if(r.ok){ this.reset(); flash(document.getElementById('passwordMsg'),'Password updated'); }
      else{ const t=await r.text(); flash(document.getElementById('passwordMsg'),'Update failed',false); console.warn(t); }
    }).catch(err=>{ $btn.prop('disabled',false); flash(document.getElementById('passwordMsg'),'Network error',false); });
  });

  // Reload current tab (for pull-to-refresh)
  function reloadActiveTab(){
  const active = document.querySelector('.tabpane.active');
  const kind = active?.dataset?.reload || 'home';
  if(kind==='journal')   return new Promise(r=>{ reloadJournal(); r(); });
  if(kind==='exchange')  return new Promise(r=>{ reloadExchange(); r(); });
  if(kind==='remit')     return new Promise(r=>{ reloadRemit(); r(); });
  return Promise.resolve(); // home
}

})();
</script>
</body>
</html>
