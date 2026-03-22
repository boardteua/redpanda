<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\ChatThrottleRules;
use Tests\TestCase;

class ChatThrottleRulesTest extends TestCase
{
    public function test_posts_per_minute_guest(): void
    {
        $u = User::factory()->guest()->make();

        $this->assertSame(15, ChatThrottleRules::postsPerMinute($u));
    }

    public function test_posts_per_minute_registered(): void
    {
        $u = User::factory()->make(['guest' => false, 'vip' => false]);
        $u->forceFill(['user_rank' => User::RANK_USER]);

        $this->assertSame(30, ChatThrottleRules::postsPerMinute($u));
    }

    public function test_posts_per_minute_vip(): void
    {
        $u = User::factory()->make(['guest' => false, 'vip' => true]);
        $u->forceFill(['user_rank' => User::RANK_USER]);

        $this->assertSame(60, ChatThrottleRules::postsPerMinute($u));
    }

    public function test_posts_per_minute_moderator_beats_vip(): void
    {
        $u = User::factory()->make(['guest' => false, 'vip' => true]);
        $u->forceFill(['user_rank' => User::RANK_MODERATOR]);

        $this->assertSame(90, ChatThrottleRules::postsPerMinute($u));
    }

    public function test_posts_per_minute_admin_same_as_moderator_tier(): void
    {
        $u = User::factory()->make(['guest' => false]);
        $u->forceFill(['user_rank' => User::RANK_ADMIN]);

        $this->assertSame(90, ChatThrottleRules::postsPerMinute($u));
    }

    public function test_image_uploads_per_minute_tiers(): void
    {
        $guest = User::factory()->guest()->make();
        $this->assertSame(5, ChatThrottleRules::imageUploadsPerMinute($guest));

        $plain = User::factory()->make(['guest' => false, 'vip' => false]);
        $plain->forceFill(['user_rank' => User::RANK_USER]);
        $this->assertSame(20, ChatThrottleRules::imageUploadsPerMinute($plain));

        $vip = User::factory()->make(['guest' => false, 'vip' => true]);
        $vip->forceFill(['user_rank' => User::RANK_USER]);
        $this->assertSame(30, ChatThrottleRules::imageUploadsPerMinute($vip));

        $mod = User::factory()->make(['guest' => false, 'vip' => false]);
        $mod->forceFill(['user_rank' => User::RANK_MODERATOR]);
        $this->assertSame(30, ChatThrottleRules::imageUploadsPerMinute($mod));
    }
}
