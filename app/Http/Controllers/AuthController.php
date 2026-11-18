<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:40', 'email' => 'required|email|max:255|unique:users', 'password' => ['required', 'confirmed', PasswordRule::min(8)]]);
        $user = User::create($data + ['display_name' => $data['name']]);
        Auth::login($user);
        $r->session()->regenerate();

        return redirect('/home');
    }

    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = Str::lower($data['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Terlalu banyak percobaan. Coba kembali dalam '.RateLimiter::availableIn($key).' detik.']);
        }
        if (! Auth::attempt($data, $r->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi belum cocok. Silakan periksa kembali.']);
        }
        RateLimiter::clear($key);
        $r->session()->regenerate();

        return redirect()->intended('/home');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/login');
    }

    public function forgot(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));

        return back()->with('status', 'Jika email terdaftar, tautan pemulihan kata sandi akan dikirim.');
    }

    public function reset(Request $r)
    {
        $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(8)]]);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset ? redirect('/login')->with('status', 'Kata sandi diperbarui. Silakan masuk.') : back()->withErrors(['email' => 'Tautan pemulihan tidak valid atau kedaluwarsa. Minta tautan baru.']);
    }
}
