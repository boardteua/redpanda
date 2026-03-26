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

    public function test_regular_user_cannot_access_staff_user_api(): void
    {
        $user = User::factory()->create(['user_rank' => User::RANK_USER]);
        $victim = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?q=test')
            ->assertForbidden();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}", ['vip' => true])
            ->assertForbidden();
    }

    public function test_moderator_cannot_access_staff_user_api(): void
    {
        $mod = User::factory()->moderator()->create();
        $victim = User::factory()->create(['user_name' => 'unique_staff_find_xyz', 'vip' => false]);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?q=unique_staff_find')
            ->assertForbidden();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?q=staff_search_email@')
            ->assertForbidden();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}", ['vip' => true])
            ->assertForbidden();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}/profile", [
                'profile' => [
                    'occupation' => 'newjob',
                    'about' => 'newbio',
                ],
            ])
            ->assertForbidden();

        $this->assertFalse($victim->fresh()->vip);
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

    public function test_admin_can_toggle_vip_on_lower_rank_user(): void
    {
        $admin = User::factory()->admin()->create();
        $victim = User::factory()->create(['vip' => false]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}", ['vip' => true])
            ->assertOk()
            ->assertJsonPath('data.vip', true);

        $this->assertTrue($victim->fresh()->vip);
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

    public function test_admin_cannot_patch_vip_on_peer_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$otherAdmin->id}", ['vip' => true])
            ->assertForbidden();
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
                    'occupation_hidden' => true,
                    'about_hidden' => true,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.profile.occupation_hidden', true)
            ->assertJsonPath('data.profile.about_hidden', true);

        $fresh = $victim->fresh();
        $this->assertSame('PL', $fresh->profile_country);
        $this->assertTrue($fresh->profile_occupation_hidden);
        $this->assertTrue($fresh->profile_about_hidden);
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

    public function test_staff_user_index_requires_query_or_browse_or_filters(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users')
            ->assertStatus(422);
    }

    public function test_admin_can_browse_user_catalog(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/users?browse=1&per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total', 'current_page']]);
    }

    public function test_moderator_cannot_bulk_or_create_users(): void
    {
        $mod = User::factory()->moderator()->create();
        $victim = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/mod/users/bulk', [
                'user_ids' => [$victim->id],
                'action' => 'set_vip',
            ])
            ->assertForbidden();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/mod/users', [
                'user_name' => 'new_staff_user',
                'email' => 'new_staff_user@example.com',
            ])
            ->assertForbidden();
    }

    public function test_admin_bulk_set_vip_and_show_user(): void
    {
        $admin = User::factory()->admin()->create();
        $a = User::factory()->create(['vip' => false]);
        $b = User::factory()->create(['vip' => false]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/mod/users/bulk', [
                'user_ids' => [$a->id, $b->id],
                'action' => 'set_vip',
            ])
            ->assertOk()
            ->assertJsonPath('data.affected', 2);

        $this->assertTrue($a->fresh()->vip);
        $this->assertTrue($b->fresh()->vip);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/mod/users/{$a->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $a->id)
            ->assertJsonPath('data.vip', true);
    }

    public function test_admin_store_user_returns_generated_password_when_omitted(): void
    {
        $admin = User::factory()->admin()->create();

        $res = $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/mod/users', [
                'user_name' => 'created_by_admin',
                'email' => 'created_by_admin@example.com',
            ]);

        $res->assertCreated()
            ->assertJsonPath('data.user_name', 'created_by_admin')
            ->assertJsonStructure(['meta' => ['generated_password']]);

        $this->assertNotEmpty($res->json('meta.generated_password'));
    }

    public function test_admin_can_soft_disable_registered_user_via_patch(): void
    {
        $admin = User::factory()->admin()->create();
        $victim = User::factory()->create(['account_disabled_at' => null]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson("/api/v1/mod/users/{$victim->id}", ['account_disabled' => true])
            ->assertOk()
            ->assertJsonPath('data.account_disabled', true);

        $this->assertNotNull($victim->fresh()->account_disabled_at);
    }
}
