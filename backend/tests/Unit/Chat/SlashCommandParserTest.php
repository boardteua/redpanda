<?php

namespace Tests\Unit\Chat;

use App\Chat\SlashCommands\SlashCommandParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SlashCommandParserTest extends TestCase
{
    public static function commandCases(): array
    {
        return [
            'me with args' => ['/me waves', 'me', 'waves'],
            'me no args' => ['/me', 'me', ''],
            'case' => ['/Me Hello', 'me', 'Hello'],
            'leading space' => ['  /topic new topic', 'topic', 'new topic'],
            'multiple spaces between' => ['/cmd  a  b', 'cmd', 'a  b'],
        ];
    }

    #[DataProvider('commandCases')]
    public function test_parses_command_and_args(string $raw, string $expectedName, string $expectedArgs): void
    {
        $parsed = SlashCommandParser::tryParseCommand($raw);
        $this->assertNotNull($parsed);
        $this->assertSame($expectedName, $parsed['name']);
        $this->assertSame($expectedArgs, $parsed['args']);
    }

    public function test_plain_text_returns_null(): void
    {
        $this->assertNull(SlashCommandParser::tryParseCommand('hello /not-cmd'));
        $this->assertNull(SlashCommandParser::tryParseCommand(''));
        $this->assertNull(SlashCommandParser::tryParseCommand('   '));
    }

    public function test_slash_only_returns_null(): void
    {
        $this->assertNull(SlashCommandParser::tryParseCommand('/'));
        $this->assertNull(SlashCommandParser::tryParseCommand(' / '));
    }

    public function test_looks_like_slash_command(): void
    {
        $this->assertTrue(SlashCommandParser::looksLikeSlashCommand('/x'));
        $this->assertFalse(SlashCommandParser::looksLikeSlashCommand('/'));
        $this->assertFalse(SlashCommandParser::looksLikeSlashCommand('no'));
    }
}
