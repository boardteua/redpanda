<?php

namespace Tests\Unit;

use App\Chat\Slash\SlashCommandLineParser;
use PHPUnit\Framework\TestCase;

class SlashCommandLineParserTest extends TestCase
{
    public function test_parses_command_and_arguments(): void
    {
        $p = SlashCommandLineParser::parse('/me waves hi');
        $this->assertNotNull($p);
        $this->assertSame('me', $p->command);
        $this->assertSame('waves hi', $p->args);
    }

    public function test_returns_null_without_leading_slash(): void
    {
        $this->assertNull(SlashCommandLineParser::parse('plain text'));
    }

    public function test_command_without_arguments(): void
    {
        $p = SlashCommandLineParser::parse('/manual');
        $this->assertNotNull($p);
        $this->assertSame('manual', $p->command);
        $this->assertSame('', $p->args);
    }

    public function test_lowercases_command(): void
    {
        $p = SlashCommandLineParser::parse('/AwAy gone');
        $this->assertNotNull($p);
        $this->assertSame('away', $p->command);
    }
}
