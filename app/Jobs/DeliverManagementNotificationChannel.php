<?php

namespace App\Jobs;

use App\Helpers\WhatsAppHelper;
use App\Models\ManagementNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class DeliverManagementNotificationChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $deliveryId
    ) {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $delivery = ManagementNotificationDelivery::query()
            ->with('recipient')
            ->find($this->deliveryId);

        if (! $delivery || $delivery->status === ManagementNotificationDelivery::STATUS_SENT) {
            return;
        }

        $delivery->update([
            'attempts' => ((int) $delivery->attempts) + 1,
            'last_attempt_at' => now(),
            'last_error' => null,
        ]);

        $recipient = $delivery->recipient;
        $payload = $delivery->payload ?? [];

        if (! $recipient || ! $recipient->is_active) {
            $this->skip($delivery, 'Recipient is unavailable or inactive.');

            return;
        }

        try {
            if ($delivery->channel === 'email') {
                if (! $recipient->email) {
                    $this->skip($delivery, 'Recipient has no email address.');

                    return;
                }

                Mail::raw(
                    (string) ($payload['message'] ?? ''),
                    function ($mail) use ($recipient, $payload): void {
                        $mail->to($recipient->email)
                            ->subject((string) ($payload['title'] ?? 'ERP Management Alert'));
                    }
                );
            } elseif ($delivery->channel === 'whatsapp') {
                $phone = $recipient->notificationPhone();

                if (! $phone) {
                    $this->skip($delivery, 'Recipient has no WhatsApp-capable phone number.');

                    return;
                }

                $message = trim(
                    (string) ($payload['title'] ?? 'ERP Management Alert')
                    ."\n\n"
                    .(string) ($payload['message'] ?? '')
                    .(! empty($payload['url']) ? "\n\n".$payload['url'] : '')
                );

                if (! WhatsAppHelper::sendMessage($phone, $message)) {
                    throw new RuntimeException('WhatsApp provider rejected the message.');
                }
            } else {
                $this->skip($delivery, 'Unsupported notification channel.');

                return;
            }

            $delivery->update([
                'status' => ManagementNotificationDelivery::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $delivery->update([
                'last_error' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        ManagementNotificationDelivery::query()
            ->whereKey($this->deliveryId)
            ->update([
                'status' => ManagementNotificationDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => mb_substr(
                    $exception?->getMessage() ?: 'Notification delivery failed.',
                    0,
                    1000
                ),
            ]);
    }

    private function skip(
        ManagementNotificationDelivery $delivery,
        string $reason
    ): void {
        $delivery->update([
            'status' => ManagementNotificationDelivery::STATUS_SKIPPED,
            'failed_at' => null,
            'last_error' => $reason,
        ]);
    }
}
