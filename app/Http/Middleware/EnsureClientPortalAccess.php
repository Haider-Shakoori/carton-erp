<?php

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active, 403);

        $isClient = $user->hasRole('client') || $user->account_type === 'client';

        abort_unless($isClient, 403);

        $accountId = $user->account_id
            ?? Account::query()->where('user_id', $user->id)->value('id');

        $hasActiveCustomerAccount = $accountId
            && Account::query()
                ->whereKey($accountId)
                ->where('account_type', Account::TYPE_CUSTOMER)
                ->where('is_active', true)
                ->exists();

        abort_unless($hasActiveCustomerAccount, 403);

        return $next($request);
    }
}
