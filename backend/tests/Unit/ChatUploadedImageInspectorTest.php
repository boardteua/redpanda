<?php

namespace Tests\Unit;

use App\Support\ChatUploadedImageInspector;
use Tests\TestCase;

class ChatUploadedImageInspectorTest extends TestCase
{
    public function test_sanitize_display_basename_strips_path_like_segments(): void
    {
        $this->assertSame(
            'safe',
            ChatUploadedImageInspector::sanitizeDisplayBasename('../../../safe', 'fallback'),
        );
    }

    public function test_sanitize_display_basename_falls_back_when_empty(): void
    {
        $this->assertSame('avatar', ChatUploadedImageInspector::sanitizeDisplayBasename('@@@', 'avatar'));
    }
}
