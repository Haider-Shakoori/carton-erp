<?php

use App\Models\Account;
use App\Models\User;
use Spatie\Permission\Models\Role;

function createClientPortalUser(string $suffix): array
{
    $account = Account::query()->create([
        'name' => "Client {$suffix}",
        'code' => "CLIENT-{$suffix}",
        'account_type' => Account::TYPE_CUSTOMER,
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'account_id' => $account->id,
        'account_type' => 'client',
        'is_active' => true,
    ]);

    $user->assignRole(Role::firstOrCreate(['name' => 'client']));

    return [$user, $account];
}

it('allows a client to request data for its own account', function () {
    [$user, $account] = createClientPortalUser('OWN');

    $this->actingAs($user)
        ->get("/client/{$account->id}/journal")
        ->assertOk()
        ->assertJsonPath('recordsTotal', 0);
});

it('blocks a client from every account-scoped endpoint belonging to another client', function () {
    [$user] = createClientPortalUser('A');
    [, $otherAccount] = createClientPortalUser('B');

    $paths = [
        "/client/{$otherAccount->id}/journal",
        "/client/{$otherAccount->id}/journal-summary",
        "/client/{$otherAccount->id}/statement",
        "/client/{$otherAccount->id}/exchanges",
        "/client/{$otherAccount->id}/exchanges-summary",
        "/client/{$otherAccount->id}/remittances",
        "/client/{$otherAccount->id}/remittances-summary",
    ];

    foreach ($paths as $path) {
        $this->actingAs($user)->get($path)->assertForbidden();
    }
});

it('blocks authenticated non-client users from the client portal', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get('/client/dashboard')
        ->assertForbidden();
});
