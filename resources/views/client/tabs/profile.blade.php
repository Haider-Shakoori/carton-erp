<section id="tab-profile" class="tabpane">
  <div class="section-title d-flex justify-content-between align-items-center">
    <span>{{ __('ui.profile') }}</span>
    <button id="editProfileBtn" class="btn btn-sm btn-outline-primary">{{ __('ui.edit') }}</button>
  </div>

  <div class="cardx">
    {{-- View mode --}}
    <div id="profileView">
      <p><strong>{{ __('ui.name_colon') }}</strong> <span id="profileName">{{ $account->name }}</span></p>
      <p><strong>{{ __('ui.email_colon') }}</strong> <span id="profileEmail">{{ Auth::user()->email }}</span></p>
      <p><strong>Account Code:</strong> {{ $account->code }}</p>
    </div>

    {{-- Edit mode --}}
    <form id="profileForm" action="{{ route('client.profile.update') }}" method="POST" style="display:none;">
      @csrf
      <div class="mb-3">
        <label class="form-label">{{ __('ui.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ $account->name }}" required>
      </div>
      <div class="mb-3">
        <label class="form-label">{{ __('ui.email') }}</label>
        <input type="email" name="email" class="form-control" value="{{ Auth::user()->email }}" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Save Changes</button>
      <button type="button" id="cancelProfileEdit" class="btn btn-outline-secondary w-100 mt-2">{{ __('ui.cancel') }}</button>
    </form>
  </div>
</section>
