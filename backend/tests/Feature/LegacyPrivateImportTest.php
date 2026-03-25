<?php

namespace Tests\Feature;

use App\Models\PrivateMessage;
use App\Models\User;
use App\Services\LegacyBoardImport\LegacyPrivateMessageImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class LegacyPrivateImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $originalLegacyConfig = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLegacyConfig = config('database.connections.legacy');
        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('legacy');
        Schema::connection('legacy')->create('private', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hunter', 191);
            $table->string('target', 191);
            $table->text('message');
            $table->unsignedInteger('time')->default(0);
        });
    }

    protected function tearDown(): void
    {
        Config::set('database.connections.legacy', $this->originalLegacyConfig);
        DB::purge('legacy');
        parent::tearDown();
    }

    public function test_import_private_messages_maps_nicks_and_inserts_rows(): void
    {
        $alice = User::factory()->create(['user_name' => 'Alice']);
        $bob = User::factory()->create(['user_name' => 'Bob']);

        DB::connection('legacy')->table('private')->insert([
            ['hunter' => 'Alice', 'target' => 'Bob', 'message' => 'hello', 'time' => 1700000000],
            ['hunter' => 'bob', 'target' => 'alice', 'message' => 'reply', 'time' => 1700000001],
            ['hunter' => 'Alice', 'target' => 'Nobody', 'message' => 'orphan', 'time' => 1700000002],
        ]);

        $svc = app(LegacyPrivateMessageImportService::class);
        $report = $svc->import(false);

        $this->assertSame(3, $report['legacy_rows']);
        $this->assertSame(2, $report['inserted']);
        $this->assertSame(1, $report['skipped_no_target']);

        $this->assertSame(2, PrivateMessage::query()->count());

        $this->assertTrue(
            PrivateMessage::query()
                ->where('sender_id', $alice->id)
                ->where('recipient_id', $bob->id)
                ->where('body', 'hello')
                ->exists()
        );
    }

    public function test_import_private_dry_run_does_not_insert(): void
    {
        User::factory()->create(['user_name' => 'A']);
        User::factory()->create(['user_name' => 'B']);
        DB::connection('legacy')->table('private')->insert([
            ['hunter' => 'A', 'target' => 'B', 'message' => 'x', 'time' => 1],
        ]);

        $svc = app(LegacyPrivateMessageImportService::class);
        $svc->import(true);

        $this->assertSame(0, PrivateMessage::query()->count());
    }

    public function test_import_private_fails_when_target_table_not_empty(): void
    {
        $a = User::factory()->create(['user_name' => 'X']);
        $b = User::factory()->create(['user_name' => 'Y']);
        PrivateMessage::query()->create([
            'sender_id' => $a->id,
            'recipient_id' => $b->id,
            'body' => 'existing',
            'sent_at' => 1,
            'sent_time' => null,
            'client_message_id' => '00000000-0000-0000-0000-000000000001',
        ]);

        DB::connection('legacy')->table('private')->insert([
            ['hunter' => 'X', 'target' => 'Y', 'message' => 'n', 'time' => 2],
        ]);

        $this->expectException(\RuntimeException::class);
        app(LegacyPrivateMessageImportService::class)->import(false);
    }
}
