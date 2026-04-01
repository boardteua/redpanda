<?php

namespace Tests\Unit\Ai;

use App\Models\User;
use App\Services\Ai\RudaPanda\RudaPandaIntent;
use App\Services\Ai\RudaPanda\RudaPandaIntentClassifier;
use App\Services\Ai\RudaPanda\RudaPandaModelRouter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RudaPandaModelRouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.gemini.model_flash_lite' => 'm-lite',
            'services.gemini.model_flash' => 'm-flash',
            'services.gemini.model_pro' => 'm-pro',
            'services.gemini.model_image' => 'm-img',
            'services.gemini.default_model' => 'm-default',
            'services.gemini.pro_thinking_budget' => 0,
            'services.gemini.pro_thinking_level' => '',
        ]);
    }

    private function router(): RudaPandaModelRouter
    {
        return new RudaPandaModelRouter(new RudaPandaIntentClassifier);
    }

    #[DataProvider('routeMatrixProvider')]
    public function test_route_intent_matrix(
        RudaPandaIntent $intent,
        bool $guest,
        bool $vip,
        string $expectModel,
        string $expectTier,
    ): void {
        $r = $this->router()->routeIntent($intent, $guest, $vip);
        $this->assertSame($expectModel, $r->modelId);
        $this->assertSame($expectTier, $r->tier);
        $this->assertSame($intent, $r->intent);
    }

    /**
     * @return iterable<string, array{0: RudaPandaIntent, 1: bool, 2: bool, 3: string, 4: string}>
     */
    public static function routeMatrixProvider(): iterable
    {
        yield 'simple_guest' => [RudaPandaIntent::Simple, true, false, 'm-lite', 'flash_lite'];
        yield 'simple_registered' => [RudaPandaIntent::Simple, false, false, 'm-flash', 'flash'];
        yield 'simple_vip' => [RudaPandaIntent::Simple, false, true, 'm-flash', 'flash'];
        yield 'complex_guest' => [RudaPandaIntent::Complex, true, false, 'm-flash', 'flash'];
        yield 'complex_registered' => [RudaPandaIntent::Complex, false, false, 'm-flash', 'flash'];
        yield 'complex_vip' => [RudaPandaIntent::Complex, false, true, 'm-pro', 'pro'];
        yield 'image_guest' => [RudaPandaIntent::Image, true, false, 'm-img', 'image'];
    }

    public function test_pro_thinking_budget_merges_overlay(): void
    {
        config(['services.gemini.pro_thinking_budget' => 1024]);
        $r = $this->router()->routeIntent(RudaPandaIntent::Complex, false, true);
        $this->assertNotNull($r->generationConfigOverlay);
        $this->assertSame(1024, $r->generationConfigOverlay['thinkingConfig']['thinkingBudget']);
    }

    public function test_pro_thinking_level_takes_precedence_over_budget(): void
    {
        config([
            'services.gemini.pro_thinking_budget' => 2048,
            'services.gemini.pro_thinking_level' => 'low',
        ]);
        $r = $this->router()->routeIntent(RudaPandaIntent::Complex, false, true);
        $this->assertSame('low', $r->generationConfigOverlay['thinkingConfig']['thinkingLevel'] ?? null);
        $this->assertArrayNotHasKey('thinkingBudget', $r->generationConfigOverlay['thinkingConfig']);
    }

    public function test_merge_into_payload_preserves_existing_generation_config(): void
    {
        config(['services.gemini.pro_thinking_budget' => 512]);
        $r = $this->router()->routeIntent(RudaPandaIntent::Complex, false, true);
        $payload = $r->mergeIntoPayload([
            'contents' => [],
            'generationConfig' => [
                'temperature' => 0.4,
            ],
        ]);
        $this->assertSame(0.4, $payload['generationConfig']['temperature']);
        $this->assertSame(512, $payload['generationConfig']['thinkingConfig']['thinkingBudget']);
    }

    public function test_route_for_trigger_with_guest_user(): void
    {
        $guest = User::factory()->guest()->make();
        $r = $this->router()->routeForTrigger('Привіт', $guest);
        $this->assertSame('m-lite', $r->modelId);
    }

    public function test_route_for_trigger_null_user_treated_as_guest(): void
    {
        $r = $this->router()->routeForTrigger('Привіт', null);
        $this->assertSame('m-lite', $r->modelId);
    }

    public function test_flash_lite_falls_back_to_flash_when_empty(): void
    {
        config(['services.gemini.model_flash_lite' => '']);
        $r = $this->router()->routeIntent(RudaPandaIntent::Simple, true, false);
        $this->assertSame('m-flash', $r->modelId);
    }
}
