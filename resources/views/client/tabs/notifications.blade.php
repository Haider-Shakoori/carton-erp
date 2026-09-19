<section id="tab-notifications" class="tabpane">
  <div class="section-title">{{ __('ui.notifications') }}</div>
  <div class="list">
    {{-- @forelse($notifications as $n)
      <div class="rowcard">
        <div class="ic yellow"><i class="fa-regular fa-bell"></i></div>
        <div>
          <div class="title">{{ $n->title }}</div>
          <div class="sub">{{ $n->created_at?->format('Y-m-d') }}</div>
        </div>
      </div>
    @empty
      <div class="text-muted">{{ __('ui.no_notifications') }}</div>
    @endforelse --}}

    <div class="text-muted">{{ __('ui.no_notifications') }}</div>
  </div>
</section>
