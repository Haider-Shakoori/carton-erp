<?php
// app/Http/Controllers/Admin/AuditLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->user_id) {
            $query->where('causer_id', $request->user_id);
        }

        // Filter by event
        if ($request->event) {
            $query->where('event', $request->event);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by subject type
        if ($request->subject_type) {
            $query->where('subject_type', 'like', "%{$request->subject_type}%");
        }

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                    ->orWhere('subject_type', 'like', "%{$request->search}%")
                    ->orWhere('log_name', 'like', "%{$request->search}%");
            });
        }

        $logs = $query->paginate(15);

        // Get unique users for filter
        $users = Activity::whereNotNull('causer_id')
            ->distinct('causer_id')
            ->with('causer')
            ->get()
            ->pluck('causer')
            ->filter()
            ->unique('id');

        // Get unique events for filter
        $events = Activity::distinct('event')->pluck('event')->filter();

        // Get unique subject types for filter
        $subjectTypes = Activity::distinct('subject_type')
            ->pluck('subject_type')
            ->filter()
            ->map(function ($type) {
                return class_basename($type);
            })
            ->unique();

        // Summary statistics
        $stats = [
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', today())->count(),
            'this_week' => Activity::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => Activity::whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.audit.index', compact(
            'logs',
            'users',
            'events',
            'subjectTypes',
            'stats'
        ));
    }

    /**
     * Show the specified audit log.
     */
    public function show($id)
    {
        try {
            $log = Activity::with('causer')->findOrFail($id);

            // Get the subject model
            $subject = null;
            if ($log->subject_type && $log->subject_id) {
                try {
                    $subject = $log->subject_type::withTrashed()->find($log->subject_id);
                } catch (\Exception $e) {
                    // Subject model might not exist
                }
            }

            $data = [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'subject_type' => class_basename($log->subject_type ?? 'N/A'),
                'subject_id' => $log->subject_id,
                'log_name' => $log->log_name,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'created_at_formatted' => $log->created_at->format('M d, Y h:i A'),
                'created_at_diff' => $log->created_at->diffForHumans(),
                'properties' => $log->properties,
                'user_name' => $log->causer?->name ?? 'System',
                'user_email' => $log->causer?->email ?? '',
                'user_initials' => $log->causer ? strtoupper(substr($log->causer->name, 0, 2)) : 'SY',
                'user_avatar' => $log->causer && $log->causer->profile_photo ? Storage::url($log->causer->profile_photo) : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Log not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get audit log data for AJAX.
     */
    public function data(Request $request)
    {
        $query = Activity::with('causer')
            ->orderBy('created_at', 'desc');

        if ($request->user_id) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->event) {
            $query->where('event', $request->event);
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->search) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $logs = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->causer?->name ?? 'System',
                    'event' => $log->event,
                    'description' => $log->description,
                    'subject' => class_basename($log->subject_type ?? ''),
                    'log_name' => $log->log_name,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'properties' => $log->properties,
                ];
            })
        ]);
    }

    /**
     * Clear old audit logs.
     */
    public function clear(Request $request)
    {
        try {
            $days = $request->days ?? 30;
            $date = now()->subDays($days);

            $deleted = Activity::whereDate('created_at', '<=', $date)->delete();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} audit logs older than {$days} days."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export audit logs to CSV.
     */
    public function export(Request $request)
    {
        try {
            $query = Activity::with('causer')
                ->orderBy('created_at', 'desc');

            if ($request->start_date) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $logs = $query->limit(1000)->get();

            // Create CSV
            $filename = "audit_logs_" . date('Y-m-d') . ".csv";
            $handle = fopen('php://temp', 'w+');

            // Headers
            fputcsv($handle, [
                'ID',
                'User',
                'Event',
                'Description',
                'Subject',
                'Log Name',
                'Created At',
                'Properties',
            ]);

            // Data
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->causer?->name ?? 'System',
                    $log->event,
                    $log->description,
                    class_basename($log->subject_type ?? ''),
                    $log->log_name,
                    $log->created_at->format('Y-m-d H:i:s'),
                    json_encode($log->properties),
                ]);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit log statistics for dashboard.
     */
    public function getStats(Request $request)
    {
        try {
            $stats = [
                'total' => Activity::count(),
                'today' => Activity::whereDate('created_at', today())->count(),
                'this_week' => Activity::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => Activity::whereMonth('created_at', now()->month)->count(),
                'events' => Activity::distinct('event')
                    ->pluck('event')
                    ->filter()
                    ->mapWithKeys(function ($event) {
                        return [$event => Activity::where('event', $event)->count()];
                    }),
                'top_users' => Activity::whereNotNull('causer_id')
                    ->selectRaw('causer_id, count(*) as count')
                    ->groupBy('causer_id')
                    ->orderByDesc('count')
                    ->limit(5)
                    ->with('causer')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'user' => $item->causer?->name ?? 'Unknown',
                            'count' => $item->count,
                        ];
                    }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
