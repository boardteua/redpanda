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

    public function test_unwraps_fancybox_anchor_when_href_matches_img_src(): void
    {
        $in = '<a href="https://board.te.ua/upload/74691fb63e046e623c217f76a5367a9c.jpeg" class="fancybox"><img src="https://board.te.ua/upload/74691fb63e046e623c217f76a5367a9c.jpeg"></a>';
        $expected = '<img src="https://board.te.ua/upload/74691fb63e046e623c217f76a5367a9c.jpeg">';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_unwraps_fancybox_when_class_before_href(): void
    {
        $in = '<a class="fancybox" href="https://example.com/a.png"><img src="https://example.com/a.png" alt="x"></a>';
        $expected = '<img src="https://example.com/a.png" alt="x">';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_does_not_unwrap_fancybox_when_href_differs_from_src(): void
    {
        $in = '<a href="https://board.te.ua/a.jpeg" class="fancybox"><img src="https://board.te.ua/b.jpeg"></a>';
        $this->assertSame($in, $this->cleaner->clean($in));
    }

    public function test_does_not_unwrap_anchor_without_fancybox_class(): void
    {
        $in = '<a href="https://board.te.ua/x.jpeg"><img src="https://board.te.ua/x.jpeg"></a>';
        $this->assertSame($in, $this->cleaner->clean($in));
    }

    public function test_would_change(): void
    {
        $this->assertTrue($this->cleaner->wouldChange('<span style="color:;background:;"></span>'));
        $this->assertFalse($this->cleaner->wouldChange('plain'));
    }
}
