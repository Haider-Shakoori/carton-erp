<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

Route::middleware(['set_locale'])->group(function () {
    Route::get('/', function () {
        if (auth()->check()) {
            return view('welcome'); // Logged-in users
        }

        return redirect()->route('login'); // Guests
    });

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('client') || $user->account_type === 'client') {
            return redirect()->route('client.dashboard');
        }

        return view('welcome');
    })->middleware('auth')->name('dashboard');

    Route::post('/logout-ping', function () {
        if (Auth::check()) {
            Cache::forget('user-online-' . Auth::id());
            Cache::forget('user-last-seen-' . Auth::id());
        }
        return response()->noContent();
    });

    // web.php
    Route::post('/user/ping', function () {
        if (auth()->check()) {
            Cache::forever('user-online-' . auth()->id(), true);
            Cache::put('user-last-seen-' . auth()->id(), now(), now()->addHours(12));
        }
        return response()->noContent();
    })->name('user.ping');


    // Locale switch route
    Route::get('/lang/{locale}', function ($locale) {
        $available = ['en', 'fa', 'ps'];
        if (in_array($locale, $available)) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    })->name('lang.switch');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__ . '/admin.php';
    require __DIR__ . '/client.php';
});

require __DIR__ . '/auth.php';
