<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAutocompleteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocomplete_requires_authentication(): void
    {
        $this->getJson('/api/v1/users/autocomplete?q=ab')
            ->assertUnauthorized();
    }

    public function test_autocomplete_validates_query_length(): void
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);

        $this->getJson('/api/v1/users/autocomplete?q=a')
            ->assertUnprocessable();

        $this->getJson('/api/v1/users/autocomplete?q='.str_repeat('x', 65))
            ->assertUnprocessable();
    }

    public function test_autocomplete_returns_prefix_matches_limited_and_ordered(): void
    {
        $viewer = User::factory()->create(['user_name' => 'viewer_ac']);
        $a = User::factory()->create(['user_name' => 'ac_alpha_one']);
        $b = User::factory()->create(['user_name' => 'ac_beta_two']);
        User::factory()->create(['user_name' => 'ac_gamma_other']);
        User::factory()->create(['user_name' => 'no_match_here']);

        Sanctum::actingAs($viewer);

        $res = $this->getJson('/api/v1/users/autocomplete?q=ac_')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.user_name', 'ac_alpha_one')
            ->assertJsonPath('data.1.user_name', 'ac_beta_two')
            ->assertJsonPath('data.2.user_name', 'ac_gamma_other');

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertSame($a->id, $ids[0]);
        $this->assertContains($b->id, $ids);
    }

    public function test_autocomplete_excludes_self_guests_and_disabled_users(): void
    {
        $viewer = User::factory()->create(['user_name' => 'selfuser']);
        User::factory()->create(['user_name' => 'ac_peer_ok']);
        User::factory()->guest()->create(['user_name' => 'ac_guest_x']);
        User::factory()->create([
            'user_name' => 'ac_disabled_x',
            'account_disabled_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/users/autocomplete?q=ac')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_name', 'ac_peer_ok');
    }

    public function test_autocomplete_escapes_like_wildcards_in_prefix(): void
    {
        $viewer = User::factory()->create();
        User::factory()->create(['user_name' => 'lit_percent_ac']);
        User::factory()->create(['user_name' => 'lit_underscore_ac']);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/users/autocomplete?q=%_lit')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_guest_session_user_can_autocomplete(): void
    {
        $guest = User::factory()->guest()->create();
        User::factory()->create(['user_name' => 'uniqreg_ac_peer']);

        Sanctum::actingAs($guest);

        $this->getJson('/api/v1/users/autocomplete?q=uniq')
            ->assertOk()
            ->assertJsonPath('data.0.user_name', 'uniqreg_ac_peer');
    }
}
