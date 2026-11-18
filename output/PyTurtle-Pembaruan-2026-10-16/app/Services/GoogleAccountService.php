<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as GoogleUser;

class GoogleAccountService
{
    public function resolve(GoogleUser $google, ?User $linkingUser = null): User
    {
        $email = Str::lower(trim((string) $google->getEmail()));
        $id = (string) $google->getId();
        $verified = $google->user['email_verified'] ?? $google->user['verified_email'] ?? false;
        if ($verified !== true || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $id === '' || strlen($id) > 255) {
            throw ValidationException::withMessages(['google' => 'Google belum memberikan identitas dan email terverifikasi. Silakan gunakan akun Google dengan email terverifikasi.']);
        }

        return DB::transaction(function () use ($google, $linkingUser, $email, $id) {
            $owner = User::where('google_id', $id)->lockForUpdate()->first();
            if ($linkingUser) {
                $user = User::whereKey($linkingUser->id)->lockForUpdate()->firstOrFail();
                if (($owner && $owner->id !== $user->id) || ($user->google_id && $user->google_id !== $id)) {
                    throw ValidationException::withMessages(['google' => 'Akun Google sudah ditautkan ke akun lain. Pilih akun Google yang sesuai.']);
                }
                if (Str::lower($user->email) !== $email) {
                    throw ValidationException::withMessages(['google' => 'Gunakan akun Google dengan email yang sama dengan akun PyTurtle ini.']);
                }
                $user->forceFill(['google_id' => $id, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

                return $user;
            }
            if ($owner) {
                return $owner;
            }
            // Never merge by email alone: require the existing user's authenticated session.
            if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages(['google' => 'Email ini sudah memiliki akun PyTurtle. Masuk dengan kata sandi terlebih dahulu, lalu pilih Hubungkan Google di Profil saya.']);
            }
            $name = Str::limit(trim((string) $google->getName()) ?: 'Penjelajah', 40, '');
            $user = new User(['name' => $name, 'display_name' => $name, 'email' => $email, 'password' => Str::random(64)]);
            $user->forceFill(['google_id' => $id, 'email_verified_at' => now()])->save();

            return $user;
        });
    }
}
