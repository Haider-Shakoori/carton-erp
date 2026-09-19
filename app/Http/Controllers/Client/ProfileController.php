<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $account = $user->account;
        return view('client.profile', compact('user', 'account'));
    }

    public function update(Request $r)
    {
        $r->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
        ]);

        $user = Auth::user();
        $account = $user->account;

        $user->update(['email' => $r->email]);
        $account->update(['name' => $r->name]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'name' => $r->name,
            'email' => $r->email,
        ]);
    }

    public function changePasswordForm()
    {
        return view('client.password');
    }

    public function updatePassword(Request $r)
    {
        $r->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($r->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        $user->update(['password' => Hash::make($r->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.'
        ]);
    }
}
