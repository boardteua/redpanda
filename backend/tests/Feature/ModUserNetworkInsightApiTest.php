<?php

namespace Tests\Feature;

use App\Models\BannedIp;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModUserNetworkInsightApiTest extends TestCase
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

    public function test_regular_user_cannot_view_network_insight(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->guest()->create();

        $this->from(config('app.url'))
            ->actingAs($actor, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/mod/users/{$target->id}/network-insight")
            ->assertForbidden();
    }

    public function test_moderator_gets_network_insight_from_sessions(): void
    {
        $mod = User::factory()->moderator()->create();
        $target = User::factory()->guest()->create();

        $now = time();
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $target->id,
            'ip_address' => '192.0.2.55',
            'user_agent' => 'QA-NetworkInsight/1.0',
            'payload' => serialize([]),
            'last_activity' => $now - 60,
        ]);
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $target->id,
            'ip_address' => '192.0.2.56',
            'user_agent' => 'QA-NetworkInsight/1.0',
            'payload' => serialize([]),
            'last_activity' => $now,
        ]);
        BannedIp::query()->create(['ip' => '192.0.2.55']);

        $json = $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/mod/users/{$target->id}/network-insight")
            ->assertOk()
            ->assertJsonPath('data.user_id', (int) $target->id)
            ->assertJsonPath('data.latest_session.ip_address', '192.0.2.56')
            ->assertJsonPath('data.sessions_sampled', 2)
            ->json();

        $ips = collect($json['data']['recent_ips'] ?? [])->keyBy('ip');
        $this->assertTrue((bool) $ips->get('192.0.2.55')['banned']);
        $this->assertFalse((bool) $ips->get('192.0.2.56')['banned']);
    }

    public function test_moderator_gets_empty_insight_when_no_sessions(): void
    {
        $mod = User::factory()->moderator()->create();
        $target = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/mod/users/{$target->id}/network-insight")
            ->assertOk()
            ->assertJsonPath('data.latest_session', null)
            ->assertJsonPath('data.recent_ips', [])
            ->assertJsonPath('data.sessions_sampled', 0);
    }
}
