<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\RudaPanda\RudaPandaTriggerDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RudaPandaTriggerDetectorTest extends TestCase
{
    private RudaPandaTriggerDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new RudaPandaTriggerDetector;
    }

    #[DataProvider('positiveProvider')]
    public function test_triggers_on_address_variants(string $text): void
    {
        $this->assertTrue($this->detector->shouldRespond($text));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function positiveProvider(): iterable
    {
        yield 'cyrillic_name' => ['Руда Панда, привіт'];
        yield 'vocative' => ['Пандо, ти тут?'];
        yield 'typo_panad' => ['панад, ти є?'];
        yield 'latin_ruda_panda' => ['ruda panda hello'];
        yield 'latin_rudapanda' => ['@rudapanda hi'];
        yield 'at_panda' => ['@Panda: питання'];
        yield 'glued_uk' => ['рудапанда скажи'];
    }

    public function test_does_not_trigger_on_empty_or_unrelated(): void
    {
        $this->assertFalse($this->detector->shouldRespond(''));
        $this->assertFalse($this->detector->shouldRespond('   '));
        $this->assertFalse($this->detector->shouldRespond('просто текст без згадки'));
    }
}
