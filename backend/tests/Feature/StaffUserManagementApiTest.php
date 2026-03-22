<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffUserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    public function test_regular_user_cannot_search_mod_users(): void
    {
        $user = User::factory()->create(['user_rank' => User::RANK_USER]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?q=test')
            ->assertForbidden();
    }

    public function test_moderator_can_search_by_nick(): void
    {
        $mod = User::factory()->moderator()->create();
        $target = User::factory()->create(['user_name' => 'unique_staff_find_xyz']);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?q=unique_staff_find')
            ->assertOk()
            ->assertJsonPath('data.0.id', $target->id)
            ->assertJsonMissingPath('data.0.email');
    }

    public function test_moderator_cannot_find_by_email_fragment(): void
    {
        $mod = User::factory()->moderator()->create();
        User::factory()->create(['email' => 'staff_search_email@example.com']);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?q=staff_search_email@')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_admin_search_includes_email_match_and_field(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['email' => 'admin_find_mail@example.com']);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?q=admin_find_mail@')
            ->assertOk()
            ->assertJsonPath('data.0.id', $target->id)
            ->assertJsonPath('data.0.email', 'admin_find_mail@example.com');
    }

    public function test_moderator_can_toggle_vip_on_lower_rank_user(): void
    {
        $mod = User::factory()->moderator()->create();
        $victim = User::factory()->create(['vip' => false]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}", ['vip' => true])
            ->assertOk()
            ->assertJsonPath('data.vip', true);

        $this->assertTrue($victim->fresh()->vip);
    }

    public function test_moderator_cannot_change_user_rank(): void
    {
        $mod = User::factory()->moderator()->create();
        $victim = User::factory()->create(['user_rank' => User::RANK_USER]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}", ['user_rank' => User::RANK_MODERATOR])
            ->assertForbidden();
    }

    public function test_admin_can_change_user_rank(): void
    {
        $admin = User::factory()->admin()->create();
        $victim = User::factory()->create(['user_rank' => User::RANK_USER]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}", ['user_rank' => User::RANK_MODERATOR])
            ->assertOk()
            ->assertJsonPath('data.user_rank', User::RANK_MODERATOR);
    }

    public function test_moderator_cannot_act_on_peer_admin(): void
    {
        $mod = User::factory()->moderator()->create();
        $otherMod = User::factory()->moderator()->create();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$otherMod->id}", ['vip' => true])
            ->assertForbidden();
    }

    public function test_moderator_can_patch_occupation_and_about_only(): void
    {
        $mod = User::factory()->moderator()->create();
        $victim = User::factory()->create([
            'profile_occupation' => 'old',
            'profile_about' => 'bio',
            'profile_country' => 'UA',
        ]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}/profile", [
                'profile' => [
                    'occupation' => 'newjob',
                    'about' => 'newbio',
                ],
            ])
            ->assertOk();

        $victim->refresh();
        $this->assertSame('newjob', $victim->profile_occupation);
        $this->assertSame('newbio', $victim->profile_about);
        $this->assertSame('UA', $victim->profile_country);
    }

    public function test_admin_can_patch_full_profile_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $victim = User::factory()->create(['profile_country' => null]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}/profile", [
                'profile' => [
                    'country' => 'PL',
                    'occupation' => 'dev',
                ],
            ])
            ->assertOk();

        $this->assertSame('PL', $victim->fresh()->profile_country);
    }

    public function test_profile_patch_rejects_guest_target(): void
    {
        $admin = User::factory()->admin()->create();
        $guest = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$guest->id}/profile", [
                'profile' => ['about' => 'x'],
            ])
            ->assertStatus(422);
    }
}
