<section id="tab-home" class="tabpane active" data-reload="home">
  <div class="hero mb-3">
    <div class="avatar" style="width:44px;height:44px">{{ strtoupper(substr($account->name,0,2)) }}</div>
    <div>
      <div class="small text-muted">{{ __('ui.welcome') }}</div>
      <div class="fs-5 fw-bold">{{ $account->name }}</div>
      <div class="badge rounded-pill mt-1 text-secondary">Code: {{ $account->code }}</div>
    </div>
  </div>

  <div class="section-title">{{ __('ui.balances') }}</div>
  <div id="walletGrid" class="wallets mb-3">
  @foreach($balances as $b)
    @php
      $sign = ($b->balance ?? 0) >= 0 ? 'positive' : 'negative';
      $code = $b->currency?->code ?? '';
      $symbol = $b->currency?->symbol ?? '';
      $balance = number_format($b->balance ?? 0, 2);
      $credit = number_format($b->credit ?? 0, 2);
      $debit  = number_format($b->debit ?? 0, 2);
    @endphp

    <div class="wallet-tile {{ $sign }}">
      <div class="wt-top">
        <span class="wt-code">{{ $code }}</span>
        <span class="wt-symbol">{{ $symbol }}</span>
      </div>
      <div class="wt-amount">{{ $symbol }}{{ $balance }}</div>
      <div class="wt-meta">
        <span class="cr-label">CR</span> {{ $symbol }}{{ $credit }}
        <span class="divider">|</span>
        <span class="dr-label">DR</span> {{ $symbol }}{{ $debit }}
      </div>
    </div>
  @endforeach
</div>

<div class="section-title mt-4 text-center fw-semibold">Latest Exchange Rates</div>

<div id="exchangeRates" class="cardx mb-3">
  @forelse($latestRates as $r)
    <div class="rate-row border-bottom py-3" style="text-align:center;">
      <div style="display:flex;justify-content:center;align-items:center;margin-bottom:6px;">
        <span style="font-weight:600;color:#111;">{{ $r->baseCurrency->code }}</span>
        <i class="fa-solid fa-arrow-right mx-2" style="color:#6c757d;"></i>
        <span style="font-weight:600;color:#111;">{{ $r->targetCurrency->code }}</span>
      </div>
      <div style="font-size:1.25rem;font-weight:700;color:#0d6efd;">
        {{ number_format($r->rate, 4) }}
      </div>
      <div style="font-size:0.85rem;color:#6c757d;margin-top:3px;">
        Range:
        <span style="font-weight:600;">{{ number_format($r->min_amount, 0) }}</span> –
        <span style="font-weight:600;">{{ number_format($r->max_amount, 0) }}</span>
      </div>
    </div>
  @empty
    <div class="text-muted text-center py-3">No exchange rates available for today.</div>
  @endforelse
</div>



  <div class="cardx mb-3"><h6>Transaction Trend</h6><canvas id="chartJournal" height="120"></canvas></div>
  <div class="cardx mb-3"><h6>{{ __('ui.exchanges') }}</h6><canvas id="chartExchange" height="120"></canvas></div>
  <div class="cardx mb-3"><h6>{{ __('ui.balances_by_currency') }}</h6><canvas id="chartBalancePie" height="160"></canvas></div>

  <div class="cardx">
    <h6>{{ __('ui.recent_transactions') }}</h6>
    <div id="recentList" class="list">
      @foreach($recentTransactions as $t)
        @php $isCr = $t->transaction_type === 'credit'; @endphp
        <div class="rowcard">
          <div class="ic {{ $isCr?'green':'red' }}"><i class="fa-solid {{ $isCr?'fa-arrow-down':'fa-arrow-up' }}"></i></div>
          <div>
            <div class="title">{{ $t->note ?? '—' }}</div>
            <div class="sub">{{ $t->created_at?->format('Y-m-d') }} · {{ $t->currency?->code }}</div>
          </div>
          <div class="amt {{ $isCr?'pos':'neg' }}">{{ $t->currency?->symbol }}{{ $isCr?'+':'-' }}{{ number_format($t->amount,2) }}</div>
        </div>
      @endforeach
    </div>
  </div>
</section>
