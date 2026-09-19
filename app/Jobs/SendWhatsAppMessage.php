<?php

namespace App\Jobs;

use App\Helpers\WhatsAppHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $phone;
    protected string $message;

    public function __construct(string $phone, string $message)
    {
        $this->phone = $phone;
        $this->message = $message;
    }

    public function handle()
    {
        // \Log::info("📤 Sending WhatsApp to {$this->phone}");

        // \Log::debug('Sending with payload', [
        //     'phone' => $this->phone,
        //     'message' => $this->message,
        // ]);

        if (empty($this->phone) || empty($this->message)) {
            \Log::error('❌ Skipped empty WhatsApp job', [
                'phone' => $this->phone,
                'message' => $this->message,
            ]);
            return;
        }

        WhatsAppHelper::sendMessage($this->phone, $this->message);
    }
}
