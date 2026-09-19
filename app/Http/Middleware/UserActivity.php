<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserActivity
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $id = Auth::id();
            Cache::forever("user-online-$id", true);
            Cache::put("user-last-seen-$id", now(), now()->addHours(24)); // extend to 24 hrs
        }

        return $next($request);
    }
}
