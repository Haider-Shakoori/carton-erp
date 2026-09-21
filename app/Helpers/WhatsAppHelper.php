<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppHelper
{
    public static function sendMessage($to, $message): bool
    {
        if (empty($to) || empty($message)) {
            Log::warning('WhatsApp message not sent: missing recipient or text.', [
                'recipient' => self::maskRecipient($to),
                'message_length' => is_string($message) ? strlen($message) : 0,
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
            Log::error('WhatsApp provider rejected a message.', [
                'recipient' => self::maskRecipient($to),
                'status' => $response->status(),
                'message_length' => strlen($message),
            ]);
        } else {
            Log::info('WhatsApp message sent.', [
                'recipient' => self::maskRecipient($to),
                'message_length' => strlen($message),
            ]);
        }

        return $response->successful();
    }

    private static function maskRecipient(mixed $recipient): ?string
    {
        $value = preg_replace('/\s+/', '', (string) $recipient);

        if ($value === '') {
            return null;
        }

        $visible = min(strlen($value), 4);

        return str_repeat('*', max(strlen($value) - $visible, 0))
            .substr($value, -$visible);
    }
}

