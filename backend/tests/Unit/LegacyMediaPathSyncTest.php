<?php

namespace Tests\Unit;

use App\Support\LegacyMediaPathSync;
use PHPUnit\Framework\TestCase;

class LegacyMediaPathSyncTest extends TestCase
{
    public function test_remote_source_enables_ssh_transport_flag(): void
    {
        $this->assertTrue(LegacyMediaPathSync::sourceUsesSshTransport('user@board.te.ua:/var/www/x/'));
        $this->assertFalse(LegacyMediaPathSync::sourceUsesSshTransport('/var/www/board.te.ua/html/avatar/'));
    }

    public function test_rsync_argv_for_local_source_omits_ssh_e(): void
    {
        $argv = LegacyMediaPathSync::rsyncArgv('/var/src', '/var/dst', false);
        $joined = implode(' ', $argv);
        $this->assertStringNotContainsString('-e', $joined);
        $this->assertStringContainsString('rsync', $argv[0]);
    }

    public function test_rsync_argv_for_remote_source_includes_ssh_e(): void
    {
        $argv = LegacyMediaPathSync::rsyncArgv('u@h.example:/var/src/', '/var/dst', false);
        $this->assertContains('-e', $argv);
        $this->assertContains('ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new', $argv);
    }
}
