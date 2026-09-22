<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverStockControlNotification;
use App\Models\StockNotificationDelivery;
use App\Services\StockControlNotificationService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class StockNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->when(
                $request->boolean('unread'),
                fn ($q) => $q->whereNull('read_at')
            )
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $preference = app(StockControlNotificationService::class)
            ->preferenceFor($user);

        $deliveries = StockNotificationDelivery::query()
            ->where('user_id', $user->id)
            ->whereIn('channel', ['email', 'whatsapp'])
            ->latest()
            ->limit(50)
            ->get();

        return view(
            'admin.stock-notifications.index',
            compact('notifications', 'preference', 'deliveries')
        );
    }

    public function open(DatabaseNotification $notification)
    {
        $this->ensureOwned($notification);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = data_get($notification->data, 'url');

        if (is_string($url) && $this->isSafeNotificationUrl($url)) {
            return redirect()->to($url);
        }

        return redirect()->route('admin.stock-notifications.index');
    }

    public function markRead(DatabaseNotification $notification)
    {
        $this->ensureOwned($notification);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function updateSettings(
        Request $request,
        StockControlNotificationService $notifications
    ) {
        $validated = $request->validate([
            'minimum_escalation_level' => ['required', 'integer', 'between:1,3'],
        ]);

        $preference = $notifications->preferenceFor(Auth::user());

        $preference->update([
            'in_app_enabled' => $request->boolean('in_app_enabled'),
            'email_enabled' => $request->boolean('email_enabled'),
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'minimum_escalation_level' => (int) $validated['minimum_escalation_level'],
            'assignment_alerts_enabled' => $request->boolean('assignment_alerts_enabled'),
            'overdue_reminders_enabled' => $request->boolean('overdue_reminders_enabled'),
            'recurrence_alerts_enabled' => $request->boolean('recurrence_alerts_enabled'),
            'weekly_review_alerts_enabled' => $request->boolean('weekly_review_alerts_enabled'),
        ]);

        return back()->with('success', 'Notification settings updated.');
    }

    public function retryDelivery(StockNotificationDelivery $delivery)
    {
        abort_unless((int) $delivery->user_id === (int) Auth::id(), 404);
        abort_unless(
            in_array($delivery->channel, ['email', 'whatsapp'], true),
            422
        );
        abort_unless(
            in_array(
                $delivery->status,
                [
                    StockNotificationDelivery::STATUS_FAILED,
                    StockNotificationDelivery::STATUS_SKIPPED,
                ],
                true
            ),
            422
        );

        $delivery->update([
            'status' => StockNotificationDelivery::STATUS_QUEUED,
            'failed_at' => null,
            'last_error' => null,
            'queued_at' => now(),
        ]);

        DeliverStockControlNotification::dispatch($delivery->id);

        return back()->with('success', 'Notification delivery queued for retry.');
    }

    private function isSafeNotificationUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true)
            && $host !== ''
            && $appHost !== ''
            && strcasecmp($host, $appHost) === 0;
    }

    private function ensureOwned(DatabaseNotification $notification): void
    {
        abort_unless(
            $notification->notifiable_type === \App\Models\User::class
            && (int) $notification->notifiable_id === (int) Auth::id(),
            404
        );
    }
}
