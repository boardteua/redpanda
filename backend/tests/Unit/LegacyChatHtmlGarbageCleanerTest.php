<?php

namespace Tests\Unit;

use App\Support\LegacyChatHtmlGarbageCleaner;
use PHPUnit\Framework\TestCase;

class LegacyChatHtmlGarbageCleanerTest extends TestCase
{
    private LegacyChatHtmlGarbageCleaner $cleaner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleaner = new LegacyChatHtmlGarbageCleaner;
    }

    public function test_removes_empty_junk_style_span(): void
    {
        $in = '<p>x</p><span style=" color:; background:;"></span><p>y</p>';
        $out = $this->cleaner->clean($in);
        $this->assertSame('<p>x</p><p>y</p>', $out);
    }

    public function test_removes_junk_span_with_swapped_properties(): void
    {
        $in = '<span style="background:; color:; "></span>';
        $this->assertSame('', $this->cleaner->clean($in));
    }

    public function test_removes_multiline_broken_fancybox_inside_junk_span(): void
    {
        $in = '<span style=" color:; background:;"><a href=" 

" class="fancybox"><img src="

"/></a> </span>';
        $this->assertSame('', $this->cleaner->clean($in));
    }

    public function test_unwraps_junk_span_when_inner_has_text(): void
    {
        $in = '<span style=" color:; background:;">Привіт</span>';
        $this->assertSame('Привіт', $this->cleaner->clean($in));
    }

    public function test_does_not_touch_span_with_real_colors(): void
    {
        $in = '<span style="color:#fff;background:#000;">ok</span>';
        $this->assertSame($in, $this->cleaner->clean($in));
    }

    public function test_would_change(): void
    {
        $this->assertTrue($this->cleaner->wouldChange('<span style="color:;background:;"></span>'));
        $this->assertFalse($this->cleaner->wouldChange('plain'));
    }
}
