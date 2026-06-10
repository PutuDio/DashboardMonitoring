<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByDesc('created_at')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:50|regex:/^[a-zA-Z0-9_]+$/|unique:users,username',
            'full_name' => 'required|string|max:100',
            'role'      => 'required|in:admin,operator,viewer',
            'password'  => 'required|min:6|confirmed',
        ], [
            'username.regex'  => '⛔ Username hanya boleh huruf, angka, dan underscore!',
            'username.unique' => '⛔ Username sudah digunakan!',
        ]);

        $user = User::create([
            'username'      => $request->username,
            'password_hash' => Hash::make($request->password),
            'full_name'     => $request->full_name,
            'role'          => strtolower($request->role),
        ]);

        \Log::info("[UserController] User created: {$user->username} | by: " . Auth::user()->username);

        return redirect()->route('users.index')
            ->with('success', '✅ User baru berhasil ditambahkan!');
    }

    public function edit(int $id)
    {
        if ($id == Auth::id()) {
            return redirect()->route('settings.index')
                ->with('info', 'ℹ️ Untuk mengedit profil sendiri, gunakan halaman Pengaturan.');
        }

        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username'     => 'required|string|max:50|regex:/^[a-zA-Z0-9_]+$/|unique:users,username,' . $id . ',user_id',
            'full_name'    => 'required|string|max:100',
            'role'         => 'required|in:admin,operator,viewer',
            'new_password' => 'nullable|min:6',
        ]);

        $updateData = [
            'username'  => $request->username,
            'full_name' => $request->full_name,
            'role'      => strtolower($request->role),
        ];

        if ($request->filled('new_password')) {
            $updateData['password_hash'] = Hash::make($request->new_password);
        }

        $user->update($updateData);

        \Log::info("[UserController] User updated: {$user->username} | by: " . Auth::user()->username);

        return redirect()->route('users.index')
            ->with('success', "✅ Data user '{$user->username}' berhasil diperbarui!");
    }

    public function destroy(int $id)
    {
        if ($id == Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', '⛔ Anda tidak dapat menghapus akun sendiri!');
        }

        $user = User::findOrFail($id);
        $username = $user->username;
        $user->delete();

        \Log::info("[UserController] User deleted: {$username} | by: " . Auth::user()->username);

        return redirect()->route('users.index')
            ->with('success', "✅ User '{$username}' berhasil dihapus!");
    }
}