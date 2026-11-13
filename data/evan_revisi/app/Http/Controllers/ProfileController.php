<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $emailRules = [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users')->ignore($user->id),
        ];

        if ($user->isMahasiswa() && $request->input('email') !== $user->email) {
            $emailRules[] = 'ends_with:@student.telkomuniversity.ac.id';
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRules,
            'nomor_induk' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nomor_induk')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.ends_with' => 'Gunakan alamat email mahasiswa Telkom University.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'nomor_induk.unique' => 'Nomor induk ini sudah digunakan oleh akun lain.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $emailChanged = $validated['email'] !== $user->email;
        $passwordChanged = !empty($validated['password']);

        if ($emailChanged || $passwordChanged) {
            $request->validate([
                'current_password' => ['required', 'current_password:web'],
            ], [
                'current_password.required' => 'Kata sandi saat ini diperlukan untuk perubahan sensitif.',
                'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            ]);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->nomor_induk = $validated['nomor_induk'] ?? null;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($passwordChanged) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if ($passwordChanged) {
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $request->session()->getId())
                    ->delete();
            }

            $request->session()->regenerate();
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')
                ->with('success', 'Profil diperbarui. Verifikasi alamat email baru Anda.');
        }

        return redirect()->route('profile.edit')->with('success', 'Profil akun berhasil diperbarui.');
    }
}
