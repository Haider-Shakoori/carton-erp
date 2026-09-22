<?php

namespace App\Http\Controllers;

use App\Models\WhatsappSession;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    public function index()
    {
        $session = WhatsappSession::first();

        if (! $session) {
            return redirect()->back()->with('error', 'No WhatsApp session configured.');
        }

        $status = 'disconnected';
        $qr = null;
        $baseUrl = $this->providerBaseUrl();

        if ($session->session_id) {
            $response = Http::timeout(15)
                ->withToken($session->api_key)
                ->acceptJson()
                ->get("{$baseUrl}/whatsapp-sessions/{$session->session_id}");

            $status = $response->json('status', 'disconnected');
        }

        if ($status !== 'connected') {
            $start = Http::timeout(15)
                ->withToken($session->api_key)
                ->acceptJson()
                ->post("{$baseUrl}/whatsapp-sessions/connect");

            $sessionId = $start->json('id');

            if ($sessionId) {
                $session->update(['session_id' => $sessionId]);

                $qrResponse = Http::timeout(15)
                    ->withToken($session->api_key)
                    ->acceptJson()
                    ->get("{$baseUrl}/whatsapp-sessions/{$sessionId}/qrcode");

                $qr = $qrResponse->json('qr');

                Log::info('WhatsApp QR request completed.', [
                    'status' => $qrResponse->status(),
                    'has_qr' => filled($qr),
                ]);
            }
        }

        return view('admin.settings.whatsapp.index', compact('session', 'status', 'qr'));
    }

    public function fetchSessionId(): HttpResponse
    {
        $token = trim((string) config('services.wasender.api_key'));

        if ($token === '') {
            return response('WhatsApp provider is not configured.', 503);
        }

        $response = Http::timeout(15)
            ->withToken($token)
            ->acceptJson()
            ->get($this->providerBaseUrl() . '/whatsapp-sessions');

        $sessions = $response->json();

        Log::info('WhatsApp sessions lookup completed.', [
            'status' => $response->status(),
            'session_count' => is_array($sessions) ? count($sessions) : 0,
        ]);

        if (! $response->successful() || ! is_array($sessions) || ! isset($sessions[0]['id'])) {
            return response('No session found or the WhatsApp provider request failed.', 502);
        }

        $session = WhatsappSession::first();

        if (! $session) {
            return response('No WhatsApp session is configured locally.', 409);
        }

        $session->update(['session_id' => $sessions[0]['id']]);

        return response('Session ID saved: ' . $sessions[0]['id']);
    }

    private function providerBaseUrl(): string
    {
        return rtrim(
            (string) config('services.wasender.base_url', 'https://www.wasenderapi.com/api'),
            '/'
        );
    }
}
