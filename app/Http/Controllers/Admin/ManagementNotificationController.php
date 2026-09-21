<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverManagementNotificationChannel;
use App\Models\ManagementNotificationDelivery;
use App\Models\ManagementNotificationPreference;
use App\Services\ManagementNotificationOrchestrator;
use Illuminate\Http\Request;

class ManagementNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30);

        return view(
            'admin.notifications.management.index',
            compact('notifications')
        );
    }

    public function open(Request $request, string $notification)
    {
        $item = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        if ($item->read_at === null) {
            $item->markAsRead();
        }

        $url = $item->data['url']
            ?? route('admin.management-notifications.index');

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $targetHost = parse_url($url, PHP_URL_HOST);

        if (
            $targetHost !== null
            && $appHost !== null
            && strcasecmp($targetHost, $appHost) !== 0
        ) {
            $url = route('admin.management-notifications.index');
        }

        return redirect()->to($url);
    }

    public function markRead(Request $request, string $notification)
    {
        $item = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        $item->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function settings(Request $request)
    {
        $preference = ManagementNotificationPreference::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        $deliveries = ManagementNotificationDelivery::query()
            ->where('recipient_user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        $hasEmail = filled($request->user()->email);
        $hasWhatsApp = filled($request->user()->notificationPhone());

        return view(
            'admin.notifications.management.settings',
            compact(
                'preference',
                'deliveries',
                'hasEmail',
                'hasWhatsApp'
            )
        );
    }

    public function updateSettings(Request $request)
    {
        $fields = [
            'in_app_enabled',
            'email_enabled',
            'whatsapp_enabled',
            'level_2_enabled',
            'level_3_enabled',
            'assignment_enabled',
            'overdue_enabled',
            'recurrence_enabled',
            'weekly_review_enabled',
        ];

        $validated = $request->validate(
            collect($fields)
                ->mapWithKeys(fn ($field) => [$field => ['required', 'boolean']])
                ->all()
        );

        $preference = ManagementNotificationPreference::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        $preference->update($validated);

        return back()->with('success', 'Notification preferences updated.');
    }

    public function retryDelivery(
        Request $request,
        ManagementNotificationDelivery $delivery
    ) {
        abort_unless(
            (int) $delivery->recipient_user_id === (int) $request->user()->id,
            403
        );

        abort_unless(
            in_array(
                $delivery->channel,
                ['email', 'whatsapp'],
                true
            ),
            422
        );

        if ($delivery->status === ManagementNotificationDelivery::STATUS_SENT) {
            return back()->with('success', 'This notification was already sent.');
        }

        $delivery->update([
            'status' => ManagementNotificationDelivery::STATUS_PENDING,
            'failed_at' => null,
            'last_error' => null,
        ]);

        DeliverManagementNotificationChannel::dispatch($delivery->id);

        return back()->with('success', 'Notification delivery queued for retry.');
    }

    public function sync(ManagementNotificationOrchestrator $orchestrator)
    {
        $result = $orchestrator->sync();

        return back()->with(
            'success',
            sprintf(
                'Notifications synchronized: %d in-app created, %d external queued, %d duplicates skipped.',
                $result['in_app'],
                $result['queued_external'],
                $result['skipped_duplicates']
            )
        );
    }
}
