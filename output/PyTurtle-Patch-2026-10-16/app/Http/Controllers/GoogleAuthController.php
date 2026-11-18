<?php

namespace App\Http\Controllers;

use App\Services\GoogleAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $target = $request->user() ? '/profile' : '/login';
        if (! config('services.google.client_id') || ! config('services.google.client_secret') || ! config('services.google.redirect')) {
            return redirect($target)->withErrors(['google' => 'Login Google belum diaktifkan oleh pengelola. Untuk sementara, gunakan email dan kata sandi.']);
        }
        $request->session()->put('google_flow', ['user_id' => $request->user()?->id, 'started_at' => now()->timestamp]);

        return Socialite::driver('google')->setScopes(['openid', 'email', 'profile'])
            ->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, GoogleAccountService $accounts)
    {
        $flow = $request->session()->pull('google_flow');
        $target = $request->user() ? '/profile' : '/login';
        if (! is_array($flow) || now()->timestamp - ($flow['started_at'] ?? 0) > 600 ||
            ($flow['user_id'] ?? null) !== $request->user()?->id) {
            $request->session()->forget('state');

            return redirect($target)->withErrors(['google' => 'Sesi login Google berakhir atau berubah. Silakan mulai kembali.']);
        }
        if ($request->has('error')) {
            $request->session()->forget('state');

            return redirect($target)->withErrors(['google' => 'Login Google dibatalkan. Kamu bisa mencoba lagi atau masuk dengan kata sandi.']);
        }
        try {
            // Stateful Socialite validates OAuth state before exchanging the code.
            $google = Socialite::driver('google')->user();
            $user = $accounts->resolve($google, $request->user());
        } catch (ValidationException $error) {
            return redirect($target)->withErrors($error->errors());
        } catch (InvalidStateException $error) {
            return redirect($target)->withErrors(['google' => 'Sesi login Google tidak valid. Mulai kembali dari tombol Google.']);
        } catch (Throwable $error) {
            // Do not log OAuth tokens, authorization codes, or provider response bodies.
            Log::warning('Google sign-in failed', ['exception_class' => $error::class]);

            return redirect($target)->withErrors(['google' => 'Google belum dapat dihubungkan. Coba lagi beberapa saat atau masuk dengan kata sandi.']);
        }

        if ($flow['user_id'] !== null) {
            $request->session()->regenerate();

            return redirect('/profile')->with('status', 'Akun Google berhasil dihubungkan. Progres dan XP kamu tetap tersimpan.');
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/home');
    }
}
