<?php

namespace App\Jobs;

use App\Helpers\WhatsAppHelper;
use App\Models\StockNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class DeliverStockControlNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $deliveryId)
    {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $delivery = StockNotificationDelivery::query()
            ->with('user')
            ->findOrFail($this->deliveryId);

        if ($delivery->status === StockNotificationDelivery::STATUS_SENT) {
            return;
        }

        $delivery->update([
            'attempts' => $delivery->attempts + 1,
            'status' => StockNotificationDelivery::STATUS_QUEUED,
            'last_error' => null,
        ]);

        $metadata = $delivery->metadata ?? [];
        $title = trim((string) ($metadata['title'] ?? 'Stock Control Notification'));
        $message = trim((string) ($metadata['message'] ?? ''));
        $url = trim((string) ($metadata['url'] ?? ''));

        if ($message === '') {
            throw new RuntimeException('Notification message is empty.');
        }

        $user = $delivery->user;
        if (! $user || ! $user->is_active) {
            throw new RuntimeException('Notification recipient is unavailable.');
        }

        $recipient = null;

        if ($delivery->channel === 'email') {
            $recipient = $user->notificationEmail();

            if (! $recipient) {
                throw new RuntimeException('Email recipient is not configured.');
            }

            $body = $message;
            if ($url !== '') {
                $body .= "\n\nOpen in ERP: ".$url;
            }

            Mail::raw($body, function ($mail) use ($recipient, $title): void {
                $mail->to($recipient)->subject($title);
            });
        } elseif ($delivery->channel === 'whatsapp') {
            $recipient = $user->notificationPhone();

            if (! $recipient) {
                throw new RuntimeException('WhatsApp recipient is not configured.');
            }

            $body = '*'.$title.'*'."\n".$message;
            if ($url !== '') {
                $body .= "\n\n".$url;
            }

            if (! WhatsAppHelper::sendMessage($recipient, $body)) {
                throw new RuntimeException('WhatsApp provider rejected the message.');
            }
        } else {
            throw new RuntimeException('Unsupported notification channel.');
        }

        $delivery->update([
            'recipient' => StockNotificationDelivery::maskRecipient(
                $recipient,
                $delivery->channel
            ),
            'status' => StockNotificationDelivery::STATUS_SENT,
            'sent_at' => now(),
            'failed_at' => null,
            'last_error' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = StockNotificationDelivery::find($this->deliveryId);

        if (! $delivery) {
            return;
        }

        $delivery->update([
            'status' => StockNotificationDelivery::STATUS_FAILED,
            'failed_at' => now(),
            'last_error' => $exception
                ? 'Delivery failed after retries ('.class_basename($exception).').'
                : 'Notification delivery failed after retries.',
        ]);
    }
}
