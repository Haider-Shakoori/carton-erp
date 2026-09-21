<script src="{{ asset('vendor/jquery360/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap-5.3.2.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const editBtn = document.getElementById('editProfileBtn');
  const form = document.getElementById('profileForm');
  const view = document.getElementById('profileView');
  const cancelBtn = document.getElementById('cancelProfileEdit');

  if (!editBtn || !form) return;

  // Toggle edit mode
  editBtn.addEventListener('click', () => {
    view.style.display = 'none';
    form.style.display = 'block';
    editBtn.style.display = 'none';
  });

  cancelBtn.addEventListener('click', () => {
    form.style.display = 'none';
    view.style.display = 'block';
    editBtn.style.display = 'inline-block';
  });

  // AJAX form submission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';

    const res = await fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
      body: new FormData(form),
    });

    const data = await res.json();
    submitBtn.disabled = false;
    submitBtn.textContent = 'Save Changes';

    if (res.ok && data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Profile Updated',
        text: data.message,
        showConfirmButton: false,
        timer: 2000
      });

      document.getElementById('profileName').textContent = data.name;
      document.getElementById('profileEmail').textContent = data.email;

      form.style.display = 'none';
      view.style.display = 'block';
      editBtn.style.display = 'inline-block';
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Update Failed',
        text: data.message || 'Something went wrong. Please try again.'
      });
    }
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const passForm = document.getElementById('passwordForm');
  if (!passForm) return;

  passForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = passForm.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Updating...';

    try {
      const res = await fetch(passForm.action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: new FormData(passForm)
      });

      const data = await res.json();

      if (res.ok && data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Password Updated',
          text: data.message || 'Your password was successfully updated.',
          showConfirmButton: false,
          timer: 2000
        });
        passForm.reset();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: data.message || Object.values(data.errors ?? {}).join('\n') || 'Something went wrong.'
        });
      }
    } catch (err) {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Network or server error. Please try again later.'
      });
    } finally {
      btn.disabled = false;
      btn.textContent = 'Update Password';
    }
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // 🔒 Toggle password visibility
  document.querySelectorAll('.toggle-pass').forEach(icon => {
    icon.addEventListener('click', () => {
      const input = document.getElementById(icon.dataset.target);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');
    });
  });
});
</script>

<script>
(() => {
  const $ = window.jQuery;
  const fmt = n => Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  const num = v => Number(String(v ?? '0').replace(/[^0-9.\-]/g,'')) || 0;
  const PAGE_SIZE = 20;

  // Map of currencies
  const curMap = {
    @foreach($currencies as $c)
      {{ $c->id }}: {code:'{{ $c->code }}', symbol:'{{ $c->symbol }}'},
    @endforeach
  };

  // Prevent horizontal scroll
  document.documentElement.style.overflowX = 'hidden';
  document.body.style.overflowX = 'hidden';

  // --- Toggle Journal Filter Panel ---
$('#toggleFilters').on('click', function () {
  $('#filtersPanel').slideToggle(180);
});

// --- Toggle Exchange Filters ---
$('#toggleExchangeFilters').on('click', function () {
  $('#exchangeFiltersPanel').slideToggle(180);
});

// --- Toggle Remittance Filters ---
$('#toggleRemitFilters').on('click', function () {
  $('#remitFiltersPanel').slideToggle(180);
});

  // Tabs
  const panes = ['tab-home','tab-journal','tab-exchange','tab-remit','tab-profile','tab-password','tab-notifications'];
  function showTab(id){
    $('.tabpane').removeClass('active'); $('#'+id).addClass('active');
    $('.tabbar a').removeClass('active'); $('.tabbar a[data-target="'+id+'"]').addClass('active');
    window.scrollTo({top:0,behavior:'smooth'});
  }
  $('.tabbar a').on('click', function(e){ e.preventDefault(); showTab($(this).data('target')); });

  // Swipe navigation
  let startX=0,startY=0,swiping=false;
  const main=document.getElementById('main');
  main.addEventListener('touchstart',e=>{
    const t=e.touches[0];startX=t.clientX;startY=t.clientY;swiping=true;
  },{passive:true});
  main.addEventListener('touchmove',e=>{
    if(!swiping)return;
    const t=e.touches[0],dx=t.clientX-startX,dy=t.clientY-startY;
    if(Math.abs(dx)>50&&Math.abs(dx)>Math.abs(dy)){
      swiping=false;
      const activeId=document.querySelector('.tabpane.active').id;
      let i=panes.indexOf(activeId);
      if(dx<0&&i<panes.length-1)showTab(panes[i+1]);
      if(dx>0&&i>0)showTab(panes[i-1]);
    }
  },{passive:true});
  main.addEventListener('touchend',()=>swiping=false);

  // Pull to refresh
  let pullStartY=0,pulling=false,pulled=false;
  const ptrTip=document.getElementById('ptrTip');
  document.addEventListener('touchstart',e=>{
    if(window.scrollY===0){pullStartY=e.touches[0].clientY;pulling=true;pulled=false;}
  },{passive:true});
  document.addEventListener('touchmove',e=>{
    if(!pulling)return;
    const dist=e.touches[0].clientY-pullStartY;
    if(dist>60){ptrTip.textContent='↻ Release to refresh';ptrTip.classList.add('show');pulled=true;}
    else if(dist>15){ptrTip.textContent='↓ Pull to refresh';ptrTip.classList.add('show');}
    else ptrTip.classList.remove('show');
  },{passive:true});
  document.addEventListener('touchend',()=>{
    if(pulling&&pulled){ptrTip.textContent='Refreshing…';
      reloadActiveTab().finally(()=>setTimeout(()=>ptrTip.classList.remove('show'),400));
    }else ptrTip.classList.remove('show');
    pulling=false;pulled=false;
  });

  // Utility
  function handleLoadMoreButton($btn, isLoading){ if(!$btn.length)return; $btn.prop('disabled',isLoading).text(isLoading?'Loading…':'Load more'); }
  function updateLoadMoreVisibility($btn, loaded, total, lastBatchLen){
    if(loaded>=total||lastBatchLen<PAGE_SIZE)$btn.hide(); else $btn.show();
  }

  // ================= JOURNAL =================
  const journalUrl="{{ route('client.journal',$account->id) }}";
  const journalSummaryUrl="{{ route('client.journal-summary',$account->id) }}";
  let jPage=1,jTotal=0,jLoaded=0;

  function renderCurrencyCards(balances) {
  const $wrap = $('#journalBalances').empty();
  (balances || []).forEach(b => {
    const sym  = curMap[b.currency_id]?.symbol || b.currency?.symbol || '';
    const code = curMap[b.currency_id]?.code || b.currency?.code || '';
    const bal  = num(b.balance ?? (b.credit - b.debit));
    const isPositive = bal >= 0;

    $wrap.append(`
        <div class="currency-card">
            <div class="head">
            <div class="code fw-bold">${code}</div>
            <div class="muted">${sym}</div>
            </div>

            <div class="amount d-flex justify-content-between align-items-center"
                style="color:${isPositive ? 'var(--good)' : 'var(--bad)'}">
            <small class="fw-semibold text-muted">{{ __('ui.balance') }}</small>
            <strong>${sym}${fmt(bal)}</strong>
            </div>

            <div class="summary-row mt-2">
            <div class="summary-chip credit">
                <small>{{ __('ui.credit') }}</small>
                <strong style="color:var(--good)">${sym}${fmt(num(b.credit))}</strong>
            </div>
            <div class="summary-chip debit">
                <small>Debit</small>
                <strong style="color:var(--bad)">${sym}${fmt(num(b.debit))}</strong>
            </div>
            </div>
        </div>
    `);

  });
}


  function renderJournalCard(x){
    const isCr=(x.transaction_type==='credit')||(!!x.credit&&x.credit!=='');
    const amt=num(x.amount??(isCr?x.credit:x.debit));
    const cur=x.currency?.code||x.currency||'';
    const sym=x.currency?.symbol||Object.values(curMap).find(c=>c.code===cur)?.symbol||'';
    return `
      <div class="rowcard">
        <div class="ic ${isCr?'green':'red'}"><i class="fa-solid ${isCr?'fa-arrow-down':'fa-arrow-up'}"></i></div>
        <div><div class="title">${x.note??x.description??'-'}</div>
        <div class="sub">${(x.created_at??'').toString().slice(0,10)} · ${cur}</div></div>
        <div class="amt ${isCr?'pos':'neg'}">${sym}${isCr?'+':'-'}${fmt(amt)}</div>
      </div>`;
  }

  function loadJournal(reset=false){
    if(reset){jPage=1;jTotal=0;jLoaded=0;$('#jList').empty();$('#jMore').show();}
    const $btn=$('#jMore');handleLoadMoreButton($btn,true);
    return $.get(journalUrl,{
      start:(jPage-1)*PAGE_SIZE,length:PAGE_SIZE,
      currency_id:$('#jCur').val(),type:$('#jType').val(),
      start_date:$('#jFrom').val(),end_date:$('#jTo').val()
    }).done(res=>{
      const arr=res.data||[];if(typeof res.recordsTotal!=='undefined')jTotal=Number(res.recordsTotal)||0;
      if(arr.length){$('#jList').append(arr.map(renderJournalCard).join(''));jLoaded+=arr.length;jPage++;}
      else if(jTotal===0&&reset)$('#jList').html('<div class="text-muted">{{ __('ui.no_results') }}</div>');
      updateLoadMoreVisibility($btn,jLoaded,jTotal,arr.length);
    }).always(()=>handleLoadMoreButton($btn,false));
  }

  function refreshJournalSummary(){
    return $.get(journalSummaryUrl,{
      currency_id:$('#jCur').val(),type:$('#jType').val(),
      start_date:$('#jFrom').val(),end_date:$('#jTo').val()
    }).done(res=>{
      const row=$('#jSumRow').empty();
      (res.by_currency||[]).forEach(it=>{
        const code=curMap[it.currency_id]?.code||'CUR';
        const sym=curMap[it.currency_id]?.symbol||'';
        const bal=num(it.credit)-num(it.debit);
        row.append(`<div class="summary-chip"><small>${code}</small><strong>${sym}${fmt(bal)}</strong></div>`);
      });
      if(res.by_currency)renderCurrencyCards(res.by_currency);
    });
  }

  $('#jApply').on('click',()=>$.when(loadJournal(true),refreshJournalSummary()));
  $('#jMore').on('click',()=>loadJournal(false));
  function reloadJournal(){ $.when(loadJournal(true),refreshJournalSummary()); }
  reloadJournal();

  const statementUrl = "{{ route('client.statement', $account->id) }}";

$('#jDownload').on('click', function () {
  const params = new URLSearchParams({
    currency_id: $('#jCur').val() || '',
    type: $('#jType').val() || '',
    start_date: $('#jFrom').val() || '',
    end_date: $('#jTo').val() || ''
  });
  window.open(`${statementUrl}?${params.toString()}`, '_blank');
});


  // ================= EXCHANGE =================
  const xUrl="{{ route('client.exchanges',$account->id) }}";
  const xSumUrl="{{ route('client.exchanges-summary',$account->id) }}";
  let xPage=1,xTotal=0,xLoaded=0;

  function renderXCard(x){
    const baseAmt=num(x.base_amount), tgtAmt=num(x.target_amount);
    const rate=(x.rate??'').toString();
    const from=x.base_currency?.code||x.base_currency||'';
    const to=x.target_currency?.code||x.target_currency||'';
    const symFrom=Object.values(curMap).find(c=>c.code===from)?.symbol||'';
    const symTo=Object.values(curMap).find(c=>c.code===to)?.symbol||'';
    return `
      <div class="rowcard">
        <div class="ic"><i class="fa-solid fa-right-left"></i></div>
        <div><div class="title">${from} → ${to}</div>
        <div class="sub">${(x.created_at??'').toString().slice(0,10)} · rate ${rate}</div></div>
        <div class="text-end ms-auto">
          <div class="amt pos">${symTo}${fmt(tgtAmt)}</div>
          <div class="sub">${symFrom}${fmt(baseAmt)}</div>
        </div>
      </div>`;
  }

  function loadX(reset=false){
    if(reset){xPage=1;xTotal=0;xLoaded=0;$('#xList').empty();$('#xMore').show();}
    const $btn=$('#xMore');handleLoadMoreButton($btn,true);
    return $.get(xUrl,{
      start:(xPage-1)*PAGE_SIZE,length:PAGE_SIZE,
      currency_id:$('#xCur').val(),start_date:$('#xFrom').val(),end_date:$('#xTo').val()
    }).done(res=>{
      const arr=res.data||[];if(typeof res.recordsTotal!=='undefined')xTotal=Number(res.recordsTotal)||0;
      if(arr.length){$('#xList').append(arr.map(renderXCard).join(''));xLoaded+=arr.length;xPage++;}
      else if(xTotal===0&&reset)$('#xList').html('<div class="text-muted">{{ __('ui.no_results') }}</div>');
      updateLoadMoreVisibility($btn,xLoaded,xTotal,arr.length);
    }).always(()=>handleLoadMoreButton($btn,false));
  }

  function refreshXSummary() {
  return $.get(xSumUrl).done(res => {
    const xb = $('#xSumBase').empty(), xt = $('#xSumTarget').empty();

    // Define gradient palette per currency (stable colors)
    const palette = {
      USD: 'linear-gradient(135deg, #0e7afe, #00c6ff)',
      AFN: 'linear-gradient(135deg, #f59e0b, #fcd34d)',
      EUR: 'linear-gradient(135deg, #9333ea, #c084fc)',
      PKR: 'linear-gradient(135deg, #06b6d4, #3b82f6)',
      DEFAULT: 'linear-gradient(135deg, #3b82f6, #06b6d4)'
    };

    const getGradient = code => palette[code] || palette.DEFAULT;

    const createCard = (code, sym, total, title) => `
      <div class="gradient-card w-100">
        <div class="g-top">
          <div class="g-title">${title}</div>
          <div class="g-code">${code}</div>
        </div>
        <div class="g-bottom">
          <div class="g-symbol">${sym}</div>
          <div class="g-value">${total}</div>
        </div>
      </div>
    `;

    // Base currencies
    (res.base || []).forEach(b => {
      const code = curMap[b.base_currency_id]?.code || 'CUR';
      const sym  = curMap[b.base_currency_id]?.symbol || '';
      const total = fmt(num(b.total));
      const gradient = getGradient(code);
      xb.append(createCard(code, sym, total, 'Base Currency'));
      xb.find('.gradient-card:last').css('background', gradient);
    });

    // Target currencies
    (res.target || []).forEach(t => {
      const code = curMap[t.target_currency_id]?.code || 'CUR';
      const sym  = curMap[t.target_currency_id]?.symbol || '';
      const total = fmt(num(t.total));
      const gradient = getGradient(code);
      xt.append(createCard(code, sym, total, 'Target Currency'));
      xt.find('.gradient-card:last').css('background', gradient);
    });
  });
}


  $('#xApply').on('click',()=>loadX(true));
  $('#xMore').on('click',()=>loadX(false));
  function reloadExchange(){ $.when(loadX(true),refreshXSummary()); }
  reloadExchange();

  // ================= REMITTANCE =================
  const rUrl="{{ route('client.remittances',$account->id) }}";
  const rSumUrl="{{ route('client.remittances-summary',$account->id) }}";
  let rPage=1,rTotal=0,rLoaded=0;

  function renderRCard(x){
    const st=(x.status||'').toLowerCase();
    const ic=st==='processed'?'green':(st==='pending'?'yellow':'red');
    const chip=st?`<span class="chip">${x.status}</span>`:'';
    const amt=num(x.amount), cur=x.currency?.code||x.currency||'';
    const sym=curMap[x.currency_id]?.symbol||x.currency?.symbol||'';
    const data=encodeURIComponent(JSON.stringify(x));
    return `
      <div class="rowcard remit-row" data-remit='${data}'>
        <div class="ic ${ic}"><i class="fa-solid fa-paper-plane"></i></div>
        <div><div class="title">${cur} · ${sym}${fmt(amt)}</div>
        <div class="sub">${(x.created_at??'').toString().slice(0,10)}</div></div>
        <div class="ms-auto">${chip}</div>
      </div>`;
  }

  function loadR(reset=false){
    if(reset){rPage=1;rTotal=0;rLoaded=0;$('#rList').empty();$('#rMore').show();}
    const $btn=$('#rMore');handleLoadMoreButton($btn,true);
    return $.get(rUrl,{
      start:(rPage-1)*PAGE_SIZE,length:PAGE_SIZE,
      currency_id:$('#rCur').val(),start_date:$('#rFrom').val(),end_date:$('#rTo').val()
    }).done(res=>{
      const arr=res.data||[];if(typeof res.recordsTotal!=='undefined')rTotal=Number(res.recordsTotal)||0;
      if(arr.length){$('#rList').append(arr.map(renderRCard).join(''));rLoaded+=arr.length;rPage++;}
      else if(rTotal===0&&reset)$('#rList').html('<div class="text-muted">{{ __('ui.no_results') }}</div>');
      updateLoadMoreVisibility($btn,rLoaded,rTotal,arr.length);
    }).always(()=>handleLoadMoreButton($btn,false));
  }

  function refreshRSummary() {
  return $.get(rSumUrl).done(res => {
    const row = $('#rSumRow').empty();

    const palette = {
      USD: 'linear-gradient(135deg, #0e7afe, #3b82f6)',
      AFN: 'linear-gradient(135deg, #f59e0b, #fbbf24)',
      EUR: 'linear-gradient(135deg, #9333ea, #a855f7)',
      PKR: 'linear-gradient(135deg, #06b6d4, #0891b2)',
      DEFAULT: 'linear-gradient(135deg, #334155, #64748b)',
    };
    const getGradient = code => palette[code] || palette.DEFAULT;

    // Currency Totals
    (res.by_currency || []).forEach(it => {
      const code = curMap[it.currency_id]?.code || 'CUR';
      const sym  = curMap[it.currency_id]?.symbol || '';
      const total = fmt(num(it.total));
      const gradient = getGradient(code);

      row.append(`
        <div class="remit-summary-card" style="background:${gradient}">
          <div class="rs-left">
            <div class="rs-code">${code}</div>
            <div class="rs-symbol">${sym}</div>
          </div>
          <div class="rs-right">${sym}${total}</div>
        </div>
      `);
    });

    // Pending & Approved Status
    $('#rPending').html(`
      <div class="status-tile pending">
        <div class="s-label"><i class="fa-solid fa-hourglass-half"></i> {{ __('ui.pending') }}</div>
        <div class="s-value">${fmt(num(res.pending_total || 0))}</div>
      </div>
    `);
    $('#rApproved').html(`
      <div class="status-tile approved">
        <div class="s-label"><i class="fa-solid fa-check-circle"></i> {{ __('ui.approved') }}</div>
        <div class="s-value">${fmt(num(res.approved_total || 0))}</div>
      </div>
    `);
  });
}


  $('#rApply').on('click',()=>loadR(true));
  $('#rMore').on('click',()=>loadR(false));
  function reloadRemit(){ $.when(loadR(true),refreshRSummary()); }
  reloadRemit();

$(document).on('click', '.remit-row', function () {
  const data = JSON.parse(decodeURIComponent($(this).data('remit')));
  const sym = data.currency?.symbol || curMap[data.currency_id]?.symbol || '';
  const amt = fmt(num(data.amount));

  // Build receipt link only if receipt_file exists
  let receiptHtml = '';
  if (data.receipt_file) {
    const receiptUrl = `${window.location.origin}/${data.receipt_file}`;
    receiptHtml = `
      <div class="text-center mt-3">
        <a href="${receiptUrl}" target="_blank" download
           class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
           <i class="fa-solid fa-download me-1"></i>Download Receipt
        </a>
      </div>
    `;
  } else {
    receiptHtml = `
      <div class="text-center mt-3 text-muted small fst-italic">
        No receipt uploaded for this remittance.
      </div>
    `;
  }

  const html = `
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <tr><th style="width:40%">{{ __('ui.amount') }}</th><td>${sym}${amt}</td></tr>
        <tr><th>{{ __('ui.status') }}</th><td><span class="badge bg-${data.status === 'processed' ? 'success' : 'warning'} text-uppercase">${data.status}</span></td></tr>
        <tr><th>{{ __('ui.date') }}</th><td>${(data.created_at ?? '').toString().slice(0, 10)}</td></tr>
        <tr><th>{{ __('ui.bank_name') }}</th><td>${data.bank_name || '—'}</td></tr>
        <tr><th>Account Holder</th><td>${data.account_holder || '—'}</td></tr>
        <tr><th>Account Number</th><td>${data.bank_account_number || '—'}</td></tr>
        <tr><th>{{ __('ui.bank_address') }}</th><td>${data.bank_address || '—'}</td></tr>
        <tr><th>{{ __('ui.phone_number') }}</th><td>${data.phone_number || '—'}</td></tr>
        <tr><th>Note</th><td>${data.note || '—'}</td></tr>
      </table>
    </div>
    ${receiptHtml}
  `;

  $('#remitModalBody').html(html);
  new bootstrap.Modal(document.getElementById('remitModal')).show();
});


  // Charts (unchanged)
  const jLabels={!! json_encode(array_keys($monthlyNet->toArray())) !!};
  const jData={!! json_encode(array_values($monthlyNet->toArray())) !!};
  new Chart(document.getElementById('chartJournal'),{
    type:'line',
    data:{labels:jLabels,datasets:[{label:'Net',data:jData,borderColor:'#0e7afe',tension:.35}]},
    options:{plugins:{legend:{display:false}},scales:{x:{grid:{color:'#eef2f8'}},y:{grid:{color:'#eef2f8'},ticks:{callback:v=>fmt(v)}}}}
  });

  const exBase={!! json_encode($exBase) !!};
  const exTarget={!! json_encode($exTarget) !!};
  const exCids=Array.from(new Set([...Object.keys(exBase),...Object.keys(exTarget)]));
  const exLabels=exCids.map(cid=>curMap[cid]?.code||'CUR');
  const exDataBase=exCids.map(cid=>num(exBase[cid]));
  const exDataTarget=exCids.map(cid=>num(exTarget[cid]));
  new Chart(document.getElementById('chartExchange'),{
    type:'bar',
    data:{labels:exLabels,datasets:[
      {label:'Base',data:exDataBase,backgroundColor:'#0e7afe'},
      {label:'Target',data:exDataTarget,backgroundColor:'#0fbf61'}
    ]},
    options:{plugins:{legend:{display:false}},scales:{y:{ticks:{callback:v=>fmt(v)}}}}
  });

  const balLabels=@json($balances->pluck('currency.code'));
  const balData=@json($balances->pluck('balance'));
  new Chart(document.getElementById('chartBalancePie'),{
    type:'doughnut',
    data:{labels:balLabels,datasets:[{data:balData,backgroundColor:['#0e7afe','#0fbf61','#ef3e4a','#f59e0b']}]},
    options:{plugins:{legend:{position:'bottom'}}}
  });

  function reloadActiveTab(){
    const id=$('.tabpane.active').attr('id');
    if(id==='tab-journal')return reloadJournal();
    if(id==='tab-exchange')return reloadExchange();
    if(id==='tab-remit')return reloadRemit();
    return Promise.resolve();
  }

  // Dropdown + tab switching
  const avatarBtn=document.getElementById('avatarBtn');
  const menu=document.getElementById('hdrDropdown');
  if(avatarBtn&&menu){
    avatarBtn.addEventListener('click',e=>{
      e.stopPropagation();
      const rect=avatarBtn.getBoundingClientRect();
      menu.style.top=`${rect.bottom+8}px`;
      menu.style.right=`${window.innerWidth-rect.right}px`;
      menu.classList.toggle('show');
    });
    document.addEventListener('click',e=>{
      if(!menu.contains(e.target)&&!avatarBtn.contains(e.target))
        menu.classList.remove('show');
    });
  }

  document.addEventListener('click',e=>{
    const link=e.target.closest('[data-target]');
    if(!link)return;
    e.preventDefault();
    const id=link.getAttribute('data-target');
    const tab=document.querySelector('.tabpane#'+id);
    const tabBtn=document.querySelector('.tabbar a[data-target="'+id+'"]');
    if(tab){
      document.querySelectorAll('.tabpane').forEach(el=>el.classList.remove('active'));
      tab.classList.add('active');
      document.querySelectorAll('.tabbar a').forEach(el=>el.classList.remove('active'));
      if(tabBtn)tabBtn.classList.add('active');
      if(menu)menu.classList.remove('show');
      window.scrollTo({top:0,behavior:'smooth'});
    }
  });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const initTS = (selector, {search=true} = {}) => {
    const el = document.querySelector(selector);
    if (!el) return;
    if (!el) return;

    new TomSelect(el, {
      create: false,
      searchField: search ? ['text'] : [],
      shouldLoad: () => true,
      maxOptions: 500,
      dropdownParent: 'body',   // 👈 menu escapes any overflow & opens over content
      render: {
        option: (data, escape) => {
          // If you have a symbol map available in JS, you can show it here.
          return `<div class="d-flex align-items-center gap-2">
                    <span>${escape(data.text)}</span>
                  </div>`;
        },
        item: (data, escape) => `<div>${escape(data.text)}</div>`
      }
    });
  };

  // Journal
  initTS('#jCur');
  initTS('#jType', {search:false});

  // Exchange
  initTS('#xCur');

  // Remittance
  initTS('#rCur');
});
</script>
