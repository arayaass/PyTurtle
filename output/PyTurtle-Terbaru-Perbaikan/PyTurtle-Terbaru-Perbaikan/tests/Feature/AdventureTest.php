<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdventureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function submit(User $user, int $level = 1, ?string $code = null, array $extra = [])
    {
        return $this->actingAs($user)->postJson('/game/progress/save', array_merge(['level_id' => $level, 'code' => $code ?? Level::findOrFail($level)->definition['solution'], 'request_id' => (string) Str::uuid(), 'version' => 1], $extra));
    }

    public function test_registration_and_login_regenerate_authenticated_session(): void
    {
        $this->post('/register', ['name' => 'Raya', 'email' => 'raya@example.test', 'password' => 'Adventure123!', 'password_confirmation' => 'Adventure123!'])->assertRedirect('/home');
        $this->assertAuthenticated();
        $user = User::first();
        $this->assertNotEquals('Adventure123!', $user->password);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'raya@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => 'raya@example.test', 'password' => 'Adventure123!'])->assertRedirect('/home');
        $this->assertAuthenticated();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'wrong@example.test', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'wrong@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('email'));
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Password::createToken($user);
        $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'NewAdventure123!', 'password_confirmation' => 'NewAdventure123!'])->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'NewAdventure123!'])->assertRedirect('/home');
    }

    public function test_pages_require_login(): void
    {
        foreach (['/home', '/modules', '/maze/1', '/code-match', '/progress', '/leaderboard', '/settings', '/profile'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->postJson('/game/progress/save', [])->assertUnauthorized();
    }

    public function test_all_pages_render_for_student(): void
    {
        $user = User::factory()->create();
        foreach (['/home', '/modules', '/modules/1', '/maze', '/maze/1', '/code-match', '/progress', '/leaderboard', '/settings', '/profile'] as $path) {
            $this->actingAs($user)->get($path)->assertOk();
        }
    }

    public function test_server_validates_code_and_unlocks_next_level(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/maze/2')->assertForbidden();
        $this->submit($user, 2)->assertForbidden();
        $this->submit($user)->assertOk()->assertJsonPath('result', 'SUCCESS')->assertJsonPath('score', 100);
        $this->assertDatabaseHas('level_progress', ['user_id' => $user->id, 'level_id' => 1, 'status' => 'completed', 'best_score' => 100]);
        $this->actingAs($user)->get('/maze/2')->assertOk();
        $this->actingAs($user)->get('/game/continue')->assertRedirect('/maze/2');
        $this->assertEquals(100, $user->fresh()->xp);
    }

    public function test_partial_checkpoint_and_reload_persist_state(): void
    {
        $user = User::factory()->create();
        $this->submit($user, 1, 'forward(1)', ['phase' => 'checkpoint'])->assertOk()->assertJsonPath('result', 'PARTIAL');
        $this->assertDatabaseHas('game_progress', ['user_id' => $user->id, 'current_checkpoint' => 1, 'player_x' => 150]);
        $this->assertDatabaseHas('level_progress', ['user_id' => $user->id, 'attempts' => 0]);
        $this->actingAs($user)->get('/maze/1')->assertOk()->assertSee('forward(1)');
        $this->actingAs($user)->get('/maze/2')->assertForbidden();
    }

    public function test_user_cannot_write_another_users_progress_or_forge_score(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->submit($a, 1, null, ['user_id' => $b->id])->assertUnprocessable();
        $this->submit($a, 1, null, ['score' => 100])->assertUnprocessable();
        $this->assertDatabaseMissing('game_progress', ['user_id' => $b->id]);
        $this->submit($a, 1, 'forward(10)')->assertJsonPath('result', 'PARTIAL')->assertJsonPath('score', 0);
    }

    public function test_duplicate_sync_does_not_repeat_attempts_or_xp(): void
    {
        $u = User::factory()->create();
        $id = (string) Str::uuid();
        $this->submit($u, 1, null, ['request_id' => $id])->assertOk();
        $this->submit($u, 1, null, ['request_id' => $id])->assertOk();
        $this->assertDatabaseHas('level_progress', ['user_id' => $u->id, 'attempts' => 1, 'best_score' => 100]);
        $this->assertEquals(1, $u->codeSubmissions()->count());
        $this->assertEquals(100, $u->fresh()->xp);
    }

    public function test_all_three_levels_complete_and_best_score_never_decreases(): void
    {
        $u = User::factory()->create();
        foreach ([1, 2, 3] as $id) {
            $this->submit($u, $id)->assertOk()->assertJsonPath('result', 'SUCCESS');
        }
        $this->assertEquals(300, $u->fresh()->xp);
        $this->actingAs($u)->postJson('/game/1/hint', ['version' => 1])->assertOk();
        $this->submit($u)->assertJsonPath('score', 93)->assertJsonPath('best_score', 100);
        $this->assertEquals(300, $u->fresh()->xp);
    }

    public function test_restart_only_resets_adventure_and_rejects_old_offline_queue(): void
    {
        $u = User::factory()->create();
        $this->submit($u);
        $u->lessons()->attach(1);
        $this->actingAs($u)->post('/game/restart', [])->assertSessionHasErrors('confirm');
        $this->actingAs($u)->post('/game/restart', ['confirm' => 1])->assertRedirect('/maze');
        $this->assertDatabaseCount('level_progress', 0);
        $this->assertDatabaseCount('game_progress', 0);
        $this->assertDatabaseHas('users', ['id' => $u->id, 'xp' => 20, 'adventure_version' => 2]);
        $this->assertEquals(1, $u->lessons()->count());
        $this->submit($u)->assertStatus(409);
        $this->actingAs($u)->get('/maze/2')->assertForbidden();
    }

    public function test_code_match_is_graded_by_server_and_best_score_awards_once(): void
    {
        $u = User::factory()->create();
        $attempts = [['description' => 'a', 'snippet' => 'b']];
        foreach (['a', 'b', 'c', 'd'] as $id) {
            $attempts[] = ['description' => $id, 'snippet' => $id];
        }
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($u)->postJson('/code-match/submit', ['request_id' => (string) Str::uuid(), 'attempts' => $attempts])->assertOk()->assertJsonPath('score', 100)->assertJsonPath('accuracy', 80);
        }
        $this->assertDatabaseHas('code_match_results', ['user_id' => $u->id, 'wrong_answers' => 1, 'correct_answers' => 4]);
        $this->assertEquals(100, $u->fresh()->xp);
    }

    public function test_leaderboard_is_ordered_without_private_data(): void
    {
        $u = User::factory()->create(['name' => 'Explorer Low', 'email' => 'private@example.test']);
        $v = User::factory()->create(['name' => 'Explorer High']);
        $v->forceFill(['xp' => 100])->save();
        $this->actingAs($u)->get('/leaderboard')->assertOk()->assertSeeInOrder(['Explorer High', 'Explorer Low'])->assertDontSee('private@example.test');
    }

    public function test_modules_are_idempotent_and_settings_persist(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post('/modules/1/complete');
        $this->post('/modules/1/complete');
        $this->assertEquals(20, $u->fresh()->xp);
        $this->post('/settings',['reduced_motion' => 1, 'music_enabled' => 1])->assertSessionHas('status');
        $this->assertDatabaseHas('user_settings',['user_id' => $u->id, 'reduced_motion' => true, 'music_enabled' => true, 'sound_enabled' => false]);
    }
}
