<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermissionWithFeedback
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Auth::check() || !Auth::user()->can($permission)) {
            return response()->view('errors.403', ['permission' => $permission], 403);
        }

        return $next($request);
    }
}
