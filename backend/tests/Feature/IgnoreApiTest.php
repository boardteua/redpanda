<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserIgnore;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IgnoreApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_add_list_remove_ignore(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->actingAs($a, 'web');
        $this->postJson('/api/v1/ignores/'.$b->id)
            ->assertStatus(201);

        $this->getJson('/api/v1/ignores')
            ->assertOk()
            ->assertJsonPath('data.0.user.id', $b->id);

        $this->deleteJson('/api/v1/ignores/'.$b->id)
            ->assertOk();

        $this->assertSame(0, UserIgnore::query()->where('user_id', $a->id)->count());
    }

    public function test_plain_user_cannot_ignore_moderator(): void
    {
        $plain = User::factory()->create();
        $mod = User::factory()->moderator()->create();

        $this->actingAs($plain, 'web');
        $this->postJson('/api/v1/ignores/'.$mod->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Неможливо ігнорувати модератора або адміністратора.');

        $this->assertSame(0, UserIgnore::query()->where('user_id', $plain->id)->count());
    }

    public function test_plain_user_cannot_ignore_admin(): void
    {
        $plain = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($plain, 'web');
        $this->postJson('/api/v1/ignores/'.$admin->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Неможливо ігнорувати модератора або адміністратора.');
    }

    public function test_moderator_can_ignore_plain_user(): void
    {
        $mod = User::factory()->moderator()->create();
        $plain = User::factory()->create();

        $this->actingAs($mod, 'web');
        $this->postJson('/api/v1/ignores/'.$plain->id)
            ->assertStatus(201);
    }
}
