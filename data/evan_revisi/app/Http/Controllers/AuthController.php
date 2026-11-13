<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function showRegisterForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'ends_with:@student.telkomuniversity.ac.id', 'unique:users,email'],
            'nomor_induk' => ['required', 'string', 'regex:/^\d{10}$/', 'unique:users,nomor_induk'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.ends_with' => 'Gunakan alamat email mahasiswa Telkom University.',
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
            'nomor_induk.required' => 'Nomor Induk Mahasiswa (NIM) wajib diisi.',
            'nomor_induk.regex' => 'NIM harus terdiri dari 10 digit angka.',
            'nomor_induk.unique' => 'NIM ini sudah terdaftar di sistem.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // Kunci peran secara kaku di level backend: hanya mahasiswa
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nomor_induk' => $validated['nomor_induk'],
            'password' => Hash::make($validated['password']),
            'role' => 'mahasiswa',
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', 'Akun berhasil dibuat. Periksa email Anda untuk menyelesaikan verifikasi.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang di CeLOE LMS, ' . Auth::user()->name . ' (' . ucfirst(Auth::user()->role) . ')!');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari CeLOE LMS.');
    }
}
