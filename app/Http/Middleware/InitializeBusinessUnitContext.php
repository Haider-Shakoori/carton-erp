<?php

namespace App\Http\Middleware;

use App\Support\Business\BusinessUnitContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeBusinessUnitContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(BusinessUnitContext::class);

        if ($context->enabled()) {
            $context->current();
        } else {
            session()->forget(BusinessUnitContext::SESSION_KEY);
        }

        return $next($request);
    }
}
