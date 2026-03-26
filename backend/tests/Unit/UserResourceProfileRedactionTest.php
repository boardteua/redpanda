<?php

namespace Tests\Unit;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserResourceProfileRedactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_viewer_sees_full_profile_and_real_hidden_flags(): void
    {
        $user = User::factory()->create([
            'profile_country' => 'UA',
            'profile_country_hidden' => true,
            'profile_occupation' => 'Engineer',
            'profile_occupation_hidden' => true,
            'profile_about' => 'Bio text',
            'profile_about_hidden' => false,
        ]);

        $request = Request::create('/api/v1/auth/user', 'GET');
        $request->setUserResolver(fn () => $user);

        $profile = UserResource::make($user)->toArray($request)['profile'];

        $this->assertSame('UA', $profile['country']);
        $this->assertTrue($profile['country_hidden']);
        $this->assertSame('Engineer', $profile['occupation']);
        $this->assertTrue($profile['occupation_hidden']);
        $this->assertSame('Bio text', $profile['about']);
        $this->assertFalse($profile['about_hidden']);
    }

    public function test_other_regular_user_gets_null_for_hidden_values_and_masked_flags(): void
    {
        $target = User::factory()->create([
            'profile_country' => 'PL',
            'profile_country_hidden' => true,
            'profile_region' => 'Mazovia',
            'profile_region_hidden' => false,
            'profile_age' => 40,
            'profile_age_hidden' => true,
            'profile_sex' => 'male',
            'profile_sex_hidden' => false,
            'profile_occupation' => 'Secret',
            'profile_occupation_hidden' => true,
            'profile_about' => 'About me',
            'profile_about_hidden' => true,
        ]);
        $viewer = User::factory()->create();

        $request = Request::create('/api/v1/auth/user', 'GET');
        $request->setUserResolver(fn () => $viewer);

        $profile = UserResource::make($target)->toArray($request)['profile'];

        $this->assertNull($profile['country']);
        $this->assertFalse($profile['country_hidden']);
        $this->assertSame('Mazovia', $profile['region']);
        $this->assertFalse($profile['region_hidden']);
        $this->assertNull($profile['age']);
        $this->assertFalse($profile['age_hidden']);
        $this->assertSame('male', $profile['sex']);
        $this->assertFalse($profile['sex_hidden']);
        $this->assertNull($profile['occupation']);
        $this->assertFalse($profile['occupation_hidden']);
        $this->assertNull($profile['about']);
        $this->assertFalse($profile['about_hidden']);
    }

    public function test_moderator_viewer_sees_unredacted_target_profile(): void
    {
        $target = User::factory()->create([
            'profile_occupation' => 'Visible to mod',
            'profile_occupation_hidden' => true,
        ]);
        $mod = User::factory()->moderator()->create();

        $request = Request::create('/x', 'GET');
        $request->setUserResolver(fn () => $mod);

        $profile = UserResource::make($target->fresh())->toArray($request)['profile'];

        $this->assertSame('Visible to mod', $profile['occupation']);
        $this->assertTrue($profile['occupation_hidden']);
    }

    public function test_unauthenticated_request_redacts_hidden_fields(): void
    {
        $target = User::factory()->create([
            'profile_occupation' => 'Secret',
            'profile_occupation_hidden' => true,
        ]);

        $request = Request::create('/x', 'GET');
        $request->setUserResolver(fn () => null);

        $profile = UserResource::make($target)->toArray($request)['profile'];

        $this->assertNull($profile['occupation']);
        $this->assertFalse($profile['occupation_hidden']);
    }
}
