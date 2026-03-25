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
        $expected = 'https://board.te.ua/upload/74691fb63e046e623c217f76a5367a9c.jpeg';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_unwraps_fancybox_when_class_before_href(): void
    {
        $in = '<a class="fancybox" href="https://example.com/a.png"><img src="https://example.com/a.png" alt="x"></a>';
        $expected = 'https://example.com/a.png';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_keeps_fancybox_anchor_when_href_differs_but_strips_inner_img_to_url(): void
    {
        $in = '<a href="https://board.te.ua/a.jpeg" class="fancybox"><img src="https://board.te.ua/b.jpeg"></a>';
        $expected = '<a href="https://board.te.ua/a.jpeg" class="fancybox">https://board.te.ua/b.jpeg</a>';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_does_not_unwrap_anchor_without_fancybox_class_but_strips_img_to_url(): void
    {
        $in = '<a href="https://board.te.ua/x.jpeg"><img src="https://board.te.ua/x.jpeg"></a>';
        $expected = '<a href="https://board.te.ua/x.jpeg">https://board.te.ua/x.jpeg</a>';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_replaces_standalone_img_with_bare_url(): void
    {
        $in = '<p>Hi <img src="https://ex.com/a.png" alt="a"> end</p>';
        $expected = '<p>Hi https://ex.com/a.png end</p>';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_escapes_ampersand_in_img_src_url(): void
    {
        $in = '<img src="https://ex.com/x?a=1&amp;b=2" />';
        $expected = 'https://ex.com/x?a=1&amp;b=2';
        $this->assertSame($expected, $this->cleaner->clean($in));
    }

    public function test_removes_img_without_src(): void
    {
        $in = '<p><img alt="x"></p>';
        $this->assertSame('<p></p>', $this->cleaner->clean($in));
    }

    public function test_would_change(): void
    {
        $this->assertTrue($this->cleaner->wouldChange('<span style="color:;background:;"></span>'));
        $this->assertFalse($this->cleaner->wouldChange('plain'));
    }
}
