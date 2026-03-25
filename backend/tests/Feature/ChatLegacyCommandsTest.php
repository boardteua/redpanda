<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ChatLegacyCommandsTest extends TestCase
{
    use RefreshDatabase;

    private string $originalLegacyDatabase = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLegacyDatabase = (string) config('database.connections.legacy.database');
    }

    protected function tearDown(): void
    {
        Config::set('database.connections.legacy.database', $this->originalLegacyDatabase);
        parent::tearDown();
    }

    public function test_legacy_inspect_fails_when_legacy_database_not_configured(): void
    {
        Config::set('database.connections.legacy.database', '');

        $this->artisan('chat:legacy-inspect')->assertFailed();
    }

    public function test_legacy_import_dry_run_fails_when_not_mysql(): void
    {
        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('Тест орієнтований на sqlite у phpunit.xml');
        }

        Config::set('database.connections.legacy.database', 'legacy_dummy');
        Config::set('database.connections.legacy.host', '127.0.0.1');

        $this->artisan('chat:legacy-import-staging', ['--dry-run' => true])
            ->assertFailed();

        $this->artisan('chat:legacy-import-production', ['--dry-run' => true])
            ->assertFailed();
    }

    public function test_legacy_sync_avatars_fails_when_not_configured(): void
    {
        Config::set('legacy.avatar_rsync_source', '');
        Config::set('legacy.avatar_rsync_dest', '');

        $this->artisan('chat:legacy-sync-avatars')
            ->assertFailed();
    }
}
