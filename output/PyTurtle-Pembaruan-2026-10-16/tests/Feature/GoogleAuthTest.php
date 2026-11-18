<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['services.google' => ['client_id' => 'test-id', 'client_secret' => 'test-secret', 'redirect' => 'http://localhost/auth/google/callback']]);
    }

    private function mockGoogle(string $email = 'new@example.test', bool $verified = true): void
    {
        $google = (new GoogleUser)->setRaw(['email_verified' => $verified])->map(['id' => 'google-123', 'email' => $email, 'name' => 'Google Explorer']);
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($google);
    }

    private function flow(?int $user = null): static
    {
        return $this->withSession(['google_flow' => ['user_id' => $user, 'started_at' => now()->timestamp]]);
    }

    public function test_redirect_uses_state_and_callback_rejects_invalid_state(): void
    {
        $response = $this->get('/auth/google')->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/', $response->headers->get('Location'));
        $response->assertSessionHas('state')->assertSessionHas('google_flow');
        $this->get('/auth/google/callback?state=wrong&code=invalid')->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_verified_google_user_can_register_and_sign_in_again(): void
    {
        $this->mockGoogle();
        $this->flow()->get('/auth/google/callback')->assertRedirect('/home');
        $this->assertAuthenticated();
        $user = User::firstOrFail();
        $this->assertEquals('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->post('/logout');
        $this->mockGoogle();
        $this->flow()->get('/auth/google/callback')->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_email_requires_authenticated_linking_and_preserves_xp(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.test']);
        $user->forceFill(['xp' => 250])->save();
        $this->mockGoogle($user->email);
        $this->flow()->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->mockGoogle($user->email);
        $this->actingAs($user)->flow($user->id)->get('/auth/google/callback')->assertRedirect('/profile');
        $this->assertEquals('google-123', $user->fresh()->google_id);
        $this->assertEquals(250, $user->fresh()->xp);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_unverified_identity_and_mismatched_link_are_rejected(): void
    {
        $this->mockGoogle('new@example.test', false);
        $this->flow()->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertGuest();
        $user = User::factory()->create();
        $this->mockGoogle('different@example.test');
        $this->actingAs($user)->flow($user->id)->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_missing_configuration_cancel_and_expired_flow_are_handled(): void
    {
        config(['services.google.client_id' => null]);
        $this->get('/auth/google')->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->get('/login')->assertOk()->assertSee('Login Google belum diaktifkan');
        $this->flow()->get('/auth/google/callback?error=access_denied')->assertSessionHasErrors('google');
        $this->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_link_cannot_claim_another_users_google_identity_or_changed_session(): void
    {
        $owner = User::factory()->create();
        $owner->forceFill(['google_id' => 'google-123'])->save();
        $other = User::factory()->create();
        $this->mockGoogle($other->email);
        $this->actingAs($other)->flow($other->id)->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertNull($other->fresh()->google_id);
        $this->assertEquals('google-123', $owner->fresh()->google_id);
        $this->flow($owner->id)->get('/auth/google/callback')->assertSessionHasErrors('google');
        $this->assertAuthenticatedAs($other);
    }
}
