<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappSession;
use Illuminate\Support\Facades\Http;

class WhatsappController extends Controller
{
    public function index()
    {
        $session = WhatsappSession::first();

        if (!$session) {
            return redirect()->back()->with('error', 'No WhatsApp session configured.');
        }

        $status = 'disconnected';
        $qr = null;

        if ($session->session_id) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $session->api_key
            ])->get("https://www.wasenderapi.com/api/whatsapp-sessions/{$session->session_id}");

            $status = $response->json()['status'] ?? 'disconnected';
        }

        if ($status !== 'connected') {
            // Start a new connection
            $start = Http::withHeaders([
                'Authorization' => 'Bearer ' . $session->api_key
            ])->post("https://www.wasenderapi.com/api/whatsapp-sessions/connect");

            $session_id = $start->json()['id'] ?? null;
            if ($session_id) {
                $session->update(['session_id' => $session_id]);

                $qrRes = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $session->api_key
                ])->get("https://www.wasenderapi.com/api/whatsapp-sessions/{$session_id}/qrcode");
                \Log::info('QR response:', ['data' => $qrRes->body()]); // Add this to debug
                $qr = $qrRes->json()['qr'] ?? null;
            }
        }

        return view('admin.settings.whatsapp.index', compact('session', 'status', 'qr'));
    }

    public function fetchSessionId()
{
    $token = env('WASENDER_API_KEY');

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token
    ])->get('https://www.wasenderapi.com/api/whatsapp-sessions');

    \Log::info('WhatsApp sessions response:', ['body' => $response->body()]);

    $sessions = $response->json();

    if (isset($sessions[0]['id'])) {
        $session = \App\Models\WhatsappSession::first();
        $session->update(['session_id' => $sessions[0]['id']]);

        return "Session ID saved: " . $sessions[0]['id'];
    }

    return "No session found or something went wrong.";
}


}
