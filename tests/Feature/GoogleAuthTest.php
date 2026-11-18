<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseTokenService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private static $key;

    private static string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['services.firebase' => ['api_key' => 'test-key', 'auth_domain' => 'test-project.firebaseapp.com', 'project_id' => 'test-project', 'app_id' => 'test-app']]);
        if (! self::$key) {
            $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
            if (is_file('C:/xampp/apache/conf/openssl.cnf')) {
                $options['config'] = 'C:/xampp/apache/conf/openssl.cnf';
            }
            self::$key = openssl_pkey_new($options);
            self::$publicKey = openssl_pkey_get_details(self::$key)['key'];
        }
        Cache::forget('firebase:securetoken:certificates');
        Http::preventStrayRequests();
        Http::fake([FirebaseTokenService::CERTIFICATES_URL => Http::response(['test-kid' => self::$publicKey], 200, ['Cache-Control' => 'public, max-age=300'])]);
    }

    private function token(array $overrides = [], string $kid = 'test-kid'): string
    {
        return JWT::encode(array_replace([
            'aud' => 'test-project', 'iss' => 'https://securetoken.google.com/test-project', 'sub' => 'firebase-user-123',
            'iat' => time() - 5, 'exp' => time() + 3600, 'auth_time' => time() - 5,
            'email' => 'new@example.test', 'email_verified' => true, 'name' => 'Google Explorer',
            'firebase' => ['sign_in_provider' => 'google.com', 'identities' => ['google.com' => ['google-123']]],
        ], $overrides), self::$key, 'RS256', $kid);
    }

    public function test_verified_firebase_google_user_registers_and_signs_in_again(): void
    {
        $this->post('/auth/google', ['id_token' => $this->token()])->assertRedirect('/home');
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->post('/logout');
        $this->post('/auth/google', ['id_token' => $this->token()])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        Http::assertSentCount(1);
    }

    public function test_existing_email_requires_authenticated_linking_and_preserves_xp(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.test']);
        $user->forceFill(['xp' => 250])->save();
        $token = $this->token(['email' => $user->email]);
        $this->post('/auth/google', ['id_token' => $token])->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->actingAs($user)->post('/profile/google', ['id_token' => $token])->assertRedirect('/profile');
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->assertSame(250, $user->fresh()->xp);
        $this->post('/logout');
        $this->post('/auth/google', ['id_token' => $token])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_old_google_subject_remains_usable_without_changing_account(): void
    {
        $user = User::factory()->create(['email' => 'new@example.test']);
        $user->forceFill(['google_id' => 'google-123', 'xp' => 100])->save();
        $this->post('/auth/google', ['id_token' => $this->token()])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(100, $user->fresh()->xp);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_wrong_project_expired_unverified_stale_and_non_google_tokens_are_rejected(): void
    {
        foreach ([
            ['aud' => 'another-project'], ['iss' => 'https://securetoken.google.com/another-project'],
            ['exp' => time() - 10], ['iat' => time() + 500], ['auth_time' => time() + 100],
            ['auth_time' => time() - 700], ['email_verified' => false], ['sub' => ''],
            ['exp' => null], ['iat' => null], ['auth_time' => null], ['sub' => str_repeat('x', 129)],
            ['email' => 'invalid'], ['firebase' => ['sign_in_provider' => 'password', 'identities' => ['google.com' => ['google-123']]]],
            ['firebase' => ['sign_in_provider' => 'google.com', 'identities' => []]],
        ] as $claims) {
            // Exercise validation without the per-minute endpoint limit affecting the claim matrix.
            try {
                app(FirebaseTokenService::class)->verify($this->token($claims));
                $this->fail('Invalid claims were accepted.');
            } catch (\UnexpectedValueException $error) {
                $this->assertNotEmpty($error->getMessage());
            }
        }
        $this->post('/auth/google', ['id_token' => $this->token(['email_verified' => false])])->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_tampered_unsigned_unknown_key_and_malformed_tokens_cannot_log_in(): void
    {
        $token = $this->token();
        [$header, $payload, $signature] = explode('.', $token);
        $parts = json_decode(JWT::urlsafeB64Decode($payload), true);
        $parts['email'] = 'victim@example.test';
        $tampered = $header.'.'.JWT::urlsafeB64Encode(json_encode($parts)).'.'.$signature;
        $unsigned = JWT::urlsafeB64Encode('{"alg":"none","kid":"test-kid"}').'.'.$payload.'.';
        foreach ([$tampered, $unsigned, $this->token([], 'unknown-kid'), 'not-a-token'] as $invalid) {
            $this->post('/auth/google', ['id_token' => $invalid])->assertRedirect('/login')->assertSessionHasErrors('google');
            $this->assertGuest();
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_certificate_rotation_and_provider_failure_are_handled(): void
    {
        Cache::put('firebase:securetoken:certificates', ['old-kid' => self::$publicKey], 300);
        $this->post('/auth/google', ['id_token' => $this->token()])->assertRedirect('/home');
        Http::assertSentCount(1);
        $this->post('/logout');
        Cache::forget('firebase:securetoken:certificates');
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([FirebaseTokenService::CERTIFICATES_URL => Http::response('unavailable', 503)]);
        $this->post('/auth/google', ['id_token' => $this->token()])->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_link_rejects_wrong_email_and_identity_already_owned_by_someone_else(): void
    {
        $owner = User::factory()->create();
        $owner->forceFill(['google_id' => 'google-123'])->save();
        $other = User::factory()->create(['email' => 'other@example.test']);
        $this->actingAs($other)->post('/profile/google', ['id_token' => $this->token(['email' => $other->email])])->assertRedirect('/profile')->assertSessionHasErrors('google');
        $owner->forceFill(['google_id' => null])->save();
        $this->post('/profile/google', ['id_token' => $this->token(['email' => 'different@example.test'])])->assertSessionHasErrors('google');
        $this->assertNull($other->fresh()->google_id);
        $this->assertAuthenticatedAs($other);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_configuration_input_authentication_and_removed_callback(): void
    {
        $this->postJson('/profile/google', ['id_token' => $this->token()])->assertUnauthorized();
        $this->postJson('/auth/google', [])->assertUnprocessable()->assertJsonValidationErrors('id_token');
        $this->get('/auth/google/callback')->assertNotFound();
        config(['services.firebase.api_key' => null]);
        $this->post('/auth/google', ['id_token' => $this->token()])->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->get('/login')->assertOk()->assertSeeText('Login Google belum diaktifkan');
        Http::assertNothingSent();
    }
}
