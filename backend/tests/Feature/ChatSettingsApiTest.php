<?php

namespace Tests\Feature;

use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSettingsApiTest extends TestCase
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

    public function test_guest_can_get_chat_settings_public_slice(): void
    {
        $guest = User::factory()->guest()->create();

        $response = $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/chat/settings');
        $response
            ->assertOk()
            ->assertJsonPath('data.message_edit_window_hours', 24)
            ->assertJsonPath('data.room_create_min_public_messages', 100)
            ->assertJsonPath('data.public_message_count_scope', ChatSetting::SCOPE_ALL_PUBLIC_ROOMS)
            ->assertJsonPath('data.message_count_room_id', null)
            ->assertJsonPath('data.slash_command_max_per_window', 45)
            ->assertJsonPath('data.slash_command_window_seconds', 60)
            ->assertJsonPath('data.mod_slash_default_mute_minutes', 30)
            ->assertJsonPath('data.mod_slash_default_kick_minutes', 60)
            ->assertJsonPath('data.sound_on_every_post', false)
            ->assertJsonPath('data.max_attachment_bytes', 4 * 1024 * 1024)
            ->assertJsonPath('data.landing_settings.links', [])
            ->assertJsonPath('data.registration_flags.registration_open', true);

        $eff = (int) $response->json('data.max_chat_image_upload_bytes');
        $cfg = (int) $response->json('data.max_attachment_bytes');
        $this->assertGreaterThan(0, $eff);
        $this->assertLessThanOrEqual($cfg, $eff);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('transactional_mail_from_name', $data);
        $this->assertArrayNotHasKey('mail_template_overrides', $data);
    }

    public function test_non_admin_patch_returns_403(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'room_create_min_public_messages' => 10,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_patch_threshold_and_scope(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'room_create_min_public_messages' => 42,
                'public_message_count_scope' => ChatSetting::SCOPE_DEFAULT_ROOM_ONLY,
            ])
            ->assertOk()
            ->assertJsonPath('data.room_create_min_public_messages', 42)
            ->assertJsonPath('data.public_message_count_scope', ChatSetting::SCOPE_DEFAULT_ROOM_ONLY);

        $this->assertSame(42, ChatSetting::current()->room_create_min_public_messages);
    }

    public function test_admin_can_patch_message_edit_window_hours(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'message_edit_window_hours' => 72,
            ])
            ->assertOk()
            ->assertJsonPath('data.message_edit_window_hours', 72);

        $this->assertSame(72, (int) ChatSetting::current()->message_edit_window_hours);
    }

    public function test_switching_scope_to_all_public_rooms_clears_room_id(): void
    {
        $admin = User::factory()->admin()->create();
        $room = Room::query()->create([
            'room_name' => 'Public A',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        $row = ChatSetting::current();
        $row->update([
            'public_message_count_scope' => ChatSetting::SCOPE_DEFAULT_ROOM_ONLY,
            'message_count_room_id' => $room->room_id,
        ]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'public_message_count_scope' => ChatSetting::SCOPE_ALL_PUBLIC_ROOMS,
            ])
            ->assertOk()
            ->assertJsonPath('data.message_count_room_id', null);

        $this->assertNull(ChatSetting::current()->message_count_room_id);
    }

    public function test_unauthenticated_get_returns_401(): void
    {
        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/chat/settings')
            ->assertUnauthorized();
    }

    public function test_admin_can_patch_slash_rate_limits(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'slash_command_max_per_window' => 30,
                'slash_command_window_seconds' => 120,
            ])
            ->assertOk()
            ->assertJsonPath('data.slash_command_max_per_window', 30)
            ->assertJsonPath('data.slash_command_window_seconds', 120);

        $row = ChatSetting::current();
        $this->assertSame(30, $row->slash_command_max_per_window);
        $this->assertSame(120, $row->slash_command_window_seconds);
    }

    public function test_admin_can_patch_mod_slash_default_minutes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'mod_slash_default_mute_minutes' => 45,
                'mod_slash_default_kick_minutes' => 90,
            ])
            ->assertOk()
            ->assertJsonPath('data.mod_slash_default_mute_minutes', 45)
            ->assertJsonPath('data.mod_slash_default_kick_minutes', 90);

        $row = ChatSetting::current();
        $this->assertSame(45, $row->mod_slash_default_mute_minutes);
        $this->assertSame(90, $row->mod_slash_default_kick_minutes);
    }

    public function test_admin_can_patch_max_attachment_bytes(): void
    {
        $admin = User::factory()->admin()->create();
        $target = 8 * 1024 * 1024;

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'max_attachment_bytes' => $target,
            ])
            ->assertOk()
            ->assertJsonPath('data.max_attachment_bytes', $target);

        $this->assertSame($target, (int) ChatSetting::current()->max_attachment_bytes);
    }

    public function test_admin_cannot_set_max_attachment_bytes_above_cap(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'max_attachment_bytes' => ChatSetting::ADMIN_MAX_ATTACHMENT_BYTES_CAP + 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_attachment_bytes']);
    }

    public function test_admin_get_includes_mail_template_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/chat/settings')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'transactional_mail_from_name',
                    'mail_template_overrides' => [
                        'password_reset',
                        'welcome_registered',
                        'account_security_notice',
                    ],
                ],
            ]);
    }

    public function test_admin_can_patch_transactional_mail_from_name_and_templates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'transactional_mail_from_name' => 'Панда з чату',
                'mail_template_overrides' => [
                    'password_reset' => [
                        'subject' => 'Кастомний сабджект',
                        'html_body' => '<p>{{ app_name }} — <a href="{{ reset_url }}">скинути</a></p>',
                        'text_body' => '{{ app_name }}: {{ reset_url }}',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.transactional_mail_from_name', 'Панда з чату')
            ->assertJsonPath('data.mail_template_overrides.password_reset.subject', 'Кастомний сабджект');

        $row = ChatSetting::current();
        $this->assertSame('Панда з чату', $row->transactional_mail_from_name);
        $this->assertStringContainsString('скинути', (string) ($row->resolvedMailTemplateOverrides()['password_reset']['html_body'] ?? ''));
    }

    public function test_admin_cannot_set_message_count_room_to_non_public_room(): void
    {
        $admin = User::factory()->admin()->create();
        $vipRoom = Room::query()->create([
            'room_name' => 'VIP only',
            'topic' => null,
            'access' => Room::ACCESS_VIP,
        ]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/chat/settings', [
                'public_message_count_scope' => ChatSetting::SCOPE_DEFAULT_ROOM_ONLY,
                'message_count_room_id' => $vipRoom->room_id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message_count_room_id']);
    }
}
