<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserIgnore;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

        Sanctum::actingAs($a);
        $this->postJson('/api/v1/ignores/'.$b->id)
            ->assertStatus(201);

        $this->getJson('/api/v1/ignores')
            ->assertOk()
            ->assertJsonPath('data.0.user.id', $b->id);

        $this->deleteJson('/api/v1/ignores/'.$b->id)
            ->assertOk();

        $this->assertSame(0, UserIgnore::query()->where('user_id', $a->id)->count());
    }
}
