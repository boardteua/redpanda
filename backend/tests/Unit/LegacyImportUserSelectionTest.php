<?php

namespace Tests\Unit;

use App\Services\LegacyBoardImport\LegacyImportUserSelection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LegacyImportUserSelectionTest extends TestCase
{
    #[Test]
    public function it_keeps_only_users_with_distinct_chat_ids(): void
    {
        $users = collect([
            (object) ['user_id' => 1, 'user_name' => 'a'],
            (object) ['user_id' => 2, 'user_name' => 'b'],
            (object) ['user_id' => 3, 'user_name' => 'c'],
        ]);
        $chatIds = [2, 3];

        [$filtered, $skipped] = LegacyImportUserSelection::usersHavingPublicChatPosts($users, $chatIds);

        $this->assertSame(2, $filtered->count());
        $this->assertSame(2, $filtered->first()->user_id);
        $this->assertSame(3, $filtered->last()->user_id);
        $this->assertSame(1, $skipped);
    }

    #[Test]
    public function it_returns_empty_when_no_overlap(): void
    {
        $users = collect([(object) ['user_id' => 10]]);
        [$filtered, $skipped] = LegacyImportUserSelection::usersHavingPublicChatPosts($users, [99]);

        $this->assertCount(0, $filtered);
        $this->assertSame(1, $skipped);
    }

    #[Test]
    public function it_coerces_string_chat_ids(): void
    {
        $users = collect([(object) ['user_id' => 5]]);
        [$filtered, $skipped] = LegacyImportUserSelection::usersHavingPublicChatPosts($users, ['5']);

        $this->assertCount(1, $filtered);
        $this->assertSame(0, $skipped);
    }
}
