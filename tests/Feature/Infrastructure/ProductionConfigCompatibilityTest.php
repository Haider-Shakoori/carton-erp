<?php

use App\Helpers\WhatsAppHelper;
use App\Http\Controllers\WhatsAppController;
use App\Models\WhatsappSession;
use Illuminate\Support\Facades\Http;

it('sends WhatsApp messages from Laravel service configuration', function () {
    config()->set('services.wasender.api_key', 'cached-token');
    config()->set('services.wasender.api_url', 'https://provider.example/messages');

    Http::fake([
        'https://provider.example/messages' => Http::response(['ok' => true], 200),
    ]);

    expect(WhatsAppHelper::sendMessage('+93700123456', 'Cache-safe message'))->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://provider.example/messages'
            && $request->hasHeader('Authorization', 'Bearer cached-token')
            && $request['to'] === '+93700123456'
            && $request['text'] === 'Cache-safe message';
    });
});

it('fetches the WhatsApp session id from Laravel service configuration', function () {
    config()->set('services.wasender.api_key', 'cached-token');
    config()->set('services.wasender.base_url', 'https://provider.example/api');

    $session = WhatsappSession::query()->create([
        'name' => 'Main Office',
        'session_id' => 'old-session',
        'api_key' => 'session-specific-token',
        'status' => 'disconnected',
    ]);

    Http::fake([
        'https://provider.example/api/whatsapp-sessions' => Http::response([
            ['id' => 'new-session'],
        ], 200),
    ]);

    $response = app(WhatsAppController::class)->fetchSessionId();

    expect($response->getStatusCode())->toBe(200)
        ->and($session->fresh()->session_id)->toBe('new-session');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://provider.example/api/whatsapp-sessions'
            && $request->hasHeader('Authorization', 'Bearer cached-token');
    });
});

it('fails safely when the global WhatsApp provider token is not configured', function () {
    config()->set('services.wasender.api_key', null);

    Http::fake();

    $response = app(WhatsAppController::class)->fetchSessionId();

    expect($response->getStatusCode())->toBe(503);
    Http::assertNothingSent();
});
