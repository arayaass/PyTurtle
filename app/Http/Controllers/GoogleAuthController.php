<?php

namespace App\Http\Controllers;

use App\Services\FirebaseTokenService;
use App\Services\GoogleAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function login(Request $request, FirebaseTokenService $tokens, GoogleAccountService $accounts)
    {
        return $this->authenticate($request, $tokens, $accounts, false);
    }

    public function link(Request $request, FirebaseTokenService $tokens, GoogleAccountService $accounts)
    {
        return $this->authenticate($request, $tokens, $accounts, true);
    }

    private function authenticate(Request $request, FirebaseTokenService $tokens, GoogleAccountService $accounts, bool $link)
    {
        $target = $link ? '/profile' : '/login';
        if (! config('services.firebase.project_id') || ! config('services.firebase.api_key') ||
            ! config('services.firebase.auth_domain') || ! config('services.firebase.app_id')) {
            return redirect($target)->withErrors(['google' => 'Login Google melalui Firebase belum diaktifkan oleh pengelola.']);
        }
        $input = $request->validate(['id_token' => 'required|string|max:16000']);
        try {
            $identity = $tokens->verify($input['id_token']);
            $user = $accounts->resolve($identity, $link ? $request->user() : null);
        } catch (ValidationException $error) {
            return redirect($target)->withErrors($error->errors());
        } catch (Throwable $error) {
            // Never log tokens, claims, credentials, or provider response bodies.
            Log::warning('Firebase Google sign-in failed', ['exception_class' => $error::class]);

            return redirect($target)->withErrors(['google' => 'Login Google belum dapat diverifikasi. Coba lagi dari tombol Google.']);
        }
        if ($link) {
            $request->session()->regenerate();

            return redirect('/profile')->with('status', 'Akun Google berhasil dihubungkan. Progres dan XP kamu tetap tersimpan.');
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/home');
    }
}
