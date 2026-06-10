<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Website;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stats = [
            'total_websites' => Website::count(),
            'open_incidents' => Incident::where('status', 'Open')->count(),
            'total_users'    => User::count(),
            'server_date'    => date('Y-m-d'),
        ];

        return view('settings.index', compact('user', 'stats'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'username'  => 'required|string|max:50|regex:/^[a-zA-Z0-9_]+$/|unique:users,username,' . $user->user_id . ',user_id',
            'full_name' => 'required|string|max:100',
            'role'      => 'required|in:admin,operator,viewer',
        ], [
            'username.regex'  => '❌ Username hanya boleh huruf, angka, dan underscore!',
            'username.unique' => '❌ Username sudah digunakan user lain!',
        ]);

        if (!$user->isAdmin()) {
            $validated['role'] = $user->role;
        }

        $user->update([
            'username'  => $validated['username'],
            'full_name' => $validated['full_name'],
            'role'      => $validated['role'],
        ]);

        \Log::info("[SettingController] Profile updated: {$user->username}");

        return redirect()->route('settings.index')
            ->with('success', '✅ Profil berhasil diperbarui!');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'new_password.confirmed' => '❌ Password baru dan konfirmasi tidak cocok!',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password_hash)) {
            return redirect()->route('settings.index')
                ->with('error', '❌ Password lama tidak sesuai!');
        }

        $user->update(['password_hash' => Hash::make($request->new_password)]);

        \Log::info("[SettingController] Password changed | user: {$user->username}");

        return redirect()->route('settings.index')
            ->with('success', '✅ Password berhasil diubah!');
    }
}