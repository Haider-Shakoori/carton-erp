<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class OnlineUsersController extends Controller
{
    public function index()
    {
        $users = User::where('is_active', 1)->get()->filter(fn($u) => $u->isOnline());
        return view('admin.users.online', compact('users'));
    }

    public function list()
    {
        $users = \App\Models\User::where('is_active', 1)->get()->filter(function ($u) {
            return in_array($u->onlineStatus(), ['online', 'recent']);
        });

        $data = $users->map(function ($u) {
            $status = $u->onlineStatus();
            return [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'status' => $status,
                'last_seen' => $status === 'online'
                    ? 'Active now'
                    : $u->lastSeenHuman(),
            ];
        });

        return response()->json([
            'count' => $data->count(),
            'users' => $data->values()
        ]);
    }
}
