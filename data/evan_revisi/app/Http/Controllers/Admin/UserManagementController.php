<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('role')->orderBy('name')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,dosen,mahasiswa'],
        ]);

        if ($user->is(auth()->user()) && $validated['role'] !== 'admin') {
            return back()->with('error', 'Admin tidak dapat menurunkan role akun yang sedang digunakan.');
        }

        $user->role = $validated['role'];
        $user->save();

        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Admin tidak dapat menghapus akun yang sedang digunakan.');
        }

        $user->delete();
        return back()->with('success', 'Akun pengguna berhasil dihapus.');
    }
}
