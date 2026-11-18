<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class FirebaseTokenService
{
    public const CERTIFICATES_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    public function verify(string $token): array
    {
        $project = config('services.firebase.project_id');
        if (! is_string($project) || $project === '') {
            throw new UnexpectedValueException('Firebase project is not configured.');
        }

        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new UnexpectedValueException('Invalid Firebase token.');
        }
        $header = json_decode(JWT::urlsafeB64Decode($segments[0]), true, 8, JSON_THROW_ON_ERROR);
        if (! is_array($header) || ($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null) || $header['kid'] === '') {
            throw new UnexpectedValueException('Invalid Firebase signing key.');
        }
        $certificates = $this->certificates();
        if (! isset($certificates[$header['kid']])) {
            // Refresh once when Google rotates signing certificates.
            $certificates = $this->certificates(true);
        }
        if (! isset($certificates[$header['kid']])) {
            throw new UnexpectedValueException('Unknown Firebase signing key.');
        }
        $claims = (array) JWT::decode($token, [
            $header['kid'] => new Key($certificates[$header['kid']], 'RS256'),
        ]);
        $now = time();
        if (($claims['aud'] ?? null) !== $project || ($claims['iss'] ?? null) !== 'https://securetoken.google.com/'.$project ||
            ! is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 128 ||
            ! is_int($claims['exp'] ?? null) || $claims['exp'] <= $now ||
            ! is_int($claims['iat'] ?? null) || $claims['iat'] > $now ||
            ! is_int($claims['auth_time'] ?? null) || $claims['auth_time'] > $now || $claims['auth_time'] < $now - 600) {
            throw new UnexpectedValueException('Invalid or stale Firebase claims.');
        }
        $firebase = (array) ($claims['firebase'] ?? []);
        $identities = (array) ($firebase['identities'] ?? []);
        $googleIds = $identities['google.com'] ?? [];
        if (($firebase['sign_in_provider'] ?? null) !== 'google.com' || ! is_array($googleIds) || count($googleIds) !== 1 ||
            ! is_string($googleIds[0] ?? null) || $googleIds[0] === '' || strlen($googleIds[0]) > 255 ||
            ($claims['email_verified'] ?? false) !== true || ! is_string($claims['email'] ?? null) ||
            strlen($claims['email']) > 255 || ! filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) {
            throw new UnexpectedValueException('A verified Google identity is required.');
        }

        // Keep the Google subject, not the Firebase UID, to preserve existing account links.
        return ['google_id' => $googleIds[0], 'email' => $claims['email'], 'name' => is_string($claims['name'] ?? null) ? $claims['name'] : 'Penjelajah'];
    }

    private function certificates(bool $refresh = false): array
    {
        $cacheKey = 'firebase:securetoken:certificates';
        if (! $refresh && is_array($cached = Cache::get($cacheKey)) && $cached !== []) {
            return $cached;
        }
        $response = Http::connectTimeout(5)->timeout(10)->get(self::CERTIFICATES_URL)->throw();
        $certificates = $response->json();
        if (! is_array($certificates) || $certificates === []) {
            throw new UnexpectedValueException('Firebase certificates are unavailable.');
        }
        foreach ($certificates as $certificate) {
            if (! is_string($certificate)) {
                throw new UnexpectedValueException('Invalid Firebase certificates.');
            }
        }
        preg_match('/max-age=(\d+)/i', $response->header('Cache-Control'), $match);
        Cache::put($cacheKey, $certificates, min(3600, max(1, (int) ($match[1] ?? 300))));

        return $certificates;
    }
}
