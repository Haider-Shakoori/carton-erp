<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\{
    DashboardController,
    ProfileController
};
use Illuminate\Support\Facades\Auth;

Route::middleware(['auth', 'client.portal'])->prefix('client')->name('client.')->group(function () {

    // 🏠 Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 📘 Journal (Ledger)
    Route::get('/{account}/journal', [DashboardController::class, 'journal'])->name('journal');
    Route::get('/{account}/journal-summary', [DashboardController::class, 'journalSummary'])->name('journal-summary');

    Route::get('/{account}/statement', [DashboardController::class, 'statement'])->name('statement');

    // 💱 Exchange
    Route::get('/{account}/exchanges', [DashboardController::class, 'exchanges'])->name('exchanges');
    Route::get('/{account}/exchanges-summary', [DashboardController::class, 'exchangesSummary'])->name('exchanges-summary');

    // 💸 Remittances
    Route::get('/{account}/remittances', [DashboardController::class, 'remittances'])->name('remittances');
    Route::get('/{account}/remittances-summary', [DashboardController::class, 'remittancesSummary'])->name('remittances-summary');

    // 👤 Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // 🔑 Password
    Route::get('/password/change', [ProfileController::class, 'changePasswordForm'])->name('password.change');
    Route::post('/password/update', [ProfileController::class, 'updatePassword'])->name('password.update');

    // 🚪 Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});
