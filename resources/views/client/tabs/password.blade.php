<section id="tab-password" class="tabpane">
  <div class="section-title">{{ __('ui.change_password') }}</div>

  <form id="passwordForm" action="{{ route('client.password.update') }}" method="POST">
    @csrf
    <div class="mb-3 position-relative">
      <label class="form-label">{{ __('ui.current_password') }}</label>
      <input type="password" name="current_password" class="form-control" id="currentPassword" required>
      <i class="fa-solid fa-eye toggle-pass" data-target="currentPassword"></i>
    </div>

    <div class="mb-3 position-relative">
      <label class="form-label">{{ __('ui.new_password') }}</label>
      <input type="password" name="password" class="form-control" id="newPassword" required>
      <i class="fa-solid fa-eye toggle-pass" data-target="newPassword"></i>
    </div>

    <div class="mb-3 position-relative">
      <label class="form-label">{{ __('ui.confirm_password') }}</label>
      <input type="password" name="password_confirmation" class="form-control" id="confirmPassword" required>
      <i class="fa-solid fa-eye toggle-pass" data-target="confirmPassword"></i>
    </div>

    <button class="btn btn-primary w-100" type="submit">{{ __('ui.update_password') }}</button>
  </form>
</section>
