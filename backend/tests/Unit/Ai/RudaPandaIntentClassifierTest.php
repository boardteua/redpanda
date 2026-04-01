<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\RudaPanda\RudaPandaIntent;
use App\Services\Ai\RudaPanda\RudaPandaIntentClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RudaPandaIntentClassifierTest extends TestCase
{
    private RudaPandaIntentClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new RudaPandaIntentClassifier;
    }

    #[DataProvider('imageProvider')]
    public function test_classifies_image(string $text): void
    {
        $this->assertSame(RudaPandaIntent::Image, $this->classifier->classify($text));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function imageProvider(): iterable
    {
        yield 'ua_generate' => ['Згенеруй картинку кота'];
        yield 'ua_draw' => ['Намалюй зірку'];
        yield 'en_draw' => ['draw me a red panda'];
        yield 'en_image_of' => ['image of a bicycle'];
    }

    #[DataProvider('complexProvider')]
    public function test_classifies_complex(string $text): void
    {
        $this->assertSame(RudaPandaIntent::Complex, $this->classifier->classify($text));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function complexProvider(): iterable
    {
        yield 'ua_detail' => ['Поясни детально, як працює кеш'];
        yield 'en_steps' => ['Explain step by step how TLS works'];
        yield 'long_text' => [str_repeat('а', 500)];
    }

    #[DataProvider('simpleProvider')]
    public function test_classifies_simple(string $text): void
    {
        $this->assertSame(RudaPandaIntent::Simple, $this->classifier->classify($text));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function simpleProvider(): iterable
    {
        yield 'hi' => ['Привіт!'];
        yield 'short_question' => ['Як справи?'];
        yield 'empty' => ['   '];
    }

    public function test_image_wins_over_complex_phrases(): void
    {
        $text = 'Згенеруй детальний опис картинки з step by step';
        $this->assertSame(RudaPandaIntent::Image, $this->classifier->classify($text));
    }
}
