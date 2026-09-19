<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppHelper
{
    public static function sendMessage($to, $message): bool
    {
        if (empty($to) || empty($message)) {
            Log::warning('❌ WhatsApp message not sent: missing to/text', [
                'to' => $to,
                'message' => $message,
            ]);
            return false;
        }

        $response = retry(3, function () use ($to, $message) {
            return Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . env('WASENDER_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->post(env('WASENDER_API_URL'), [
                'to'   => $to,
                'text' => $message,
            ]);
        }, 500); // retry 3 times, wait 500ms between tries


        if (!$response->successful()) {
            Log::error('❌ Failed to send WhatsApp message', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } else {
            Log::info('✅ WhatsApp message sent', [
                'to' => $to,
                'length' => strlen($message),
            ]);
        }

        return $response->successful();
    }
}

