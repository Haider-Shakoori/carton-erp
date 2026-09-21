<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppHelper
{
    public static function sendMessage($to, $message): bool
    {
        if (empty($to) || empty($message)) {
            Log::warning('WhatsApp message not sent: missing destination or text.', [
                'has_destination' => ! empty($to),
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


        $maskedDestination = self::maskDestination((string) $to);

        if (!$response->successful()) {
            Log::error('WhatsApp message delivery failed.', [
                'destination' => $maskedDestination,
                'status' => $response->status(),
                'message_length' => strlen((string) $message),
            ]);
        } else {
            Log::info('WhatsApp message sent.', [
                'destination' => $maskedDestination,
                'message_length' => strlen((string) $message),
            ]);
        }

        return $response->successful();
    }

    private static function maskDestination(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '[empty]';
        }

        return str_repeat('*', max(strlen($value) - 4, 4))
            .substr($value, -4);
    }
}

