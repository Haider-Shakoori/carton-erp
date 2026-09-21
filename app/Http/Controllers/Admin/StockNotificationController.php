<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        if (is_string($url) && $url !== '') {
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
            'overdue_reminders_enabled' => $request->boolean('overdue_reminders_enabled'),
            'recurrence_alerts_enabled' => $request->boolean('recurrence_alerts_enabled'),
            'weekly_review_alerts_enabled' => $request->boolean('weekly_review_alerts_enabled'),
        ]);

        return back()->with('success', 'Notification settings updated.');
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
