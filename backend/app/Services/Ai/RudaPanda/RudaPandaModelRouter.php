<?php

namespace App\Services\Ai\RudaPanda;

use App\Models\ChatSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Маршрутизація моделей Gemini за наміром і роллю (T176 / T180).
 */
final class RudaPandaModelRouter
{
    private ?ChatSetting $resolvedSettings = null;

    public function __construct(
        private readonly RudaPandaIntentClassifier $classifier,
    ) {}

    public function classifyIntent(string $triggerText): RudaPandaIntent
    {
        return $this->classifier->classify($triggerText);
    }

    /**
     * Повний шлях: текст тригера → intent → model id (+ опційний thinking для Pro).
     *
     * @param  array{guest?: bool, vip?: bool}  $roles  Явні прапорці, якщо User недоступний.
     */
    public function routeIntent(RudaPandaIntent $intent, bool $guest, bool $vip): RudaPandaModelRoute
    {
        $flashLite = $this->modelFlashLite();
        $flash = $this->modelFlash();
        $pro = $this->modelPro();
        $image = $this->modelImage();

        $tier = 'flash';
        $modelId = $flash;
        $overlay = null;

        if ($intent === RudaPandaIntent::Image) {
            $modelId = $image;
            $tier = 'image';
        } elseif ($intent === RudaPandaIntent::Complex) {
            if ($vip && ! $guest) {
                $modelId = $pro;
                $tier = 'pro';
                $overlay = $this->proThinkingOverlay();
            } elseif ($guest) {
                $modelId = $flash;
                $tier = 'flash';
            } else {
                $modelId = $flash;
                $tier = 'flash';
            }
        } else {
            $modelId = $guest ? $flashLite : $flash;
            $tier = $guest ? 'flash_lite' : 'flash';
        }

        $route = new RudaPandaModelRoute(
            modelId: $modelId,
            intent: $intent,
            tier: $tier,
            generationConfigOverlay: $overlay,
        );

        Log::channel('structured')->info('ruda-panda model routed', [
            'intent' => $intent->value,
            'tier' => $tier,
            'model' => $modelId,
            'guest' => $guest,
            'vip' => $vip,
        ]);

        return $route;
    }

    public function routeForTrigger(string $triggerText, ?User $user): RudaPandaModelRoute
    {
        $intent = $this->classifyIntent($triggerText);
        $guest = $user === null || $user->guest;
        $vip = $user !== null && $user->isVip();

        return $this->routeIntent($intent, $guest, $vip);
    }

    public function routeForTriggerWithRoleFlags(string $triggerText, bool $guest, bool $vip): RudaPandaModelRoute
    {
        $intent = $this->classifyIntent($triggerText);

        return $this->routeIntent($intent, $guest, $vip);
    }

    private function settingsRow(): ?ChatSetting
    {
        return $this->resolvedSettings ??= ChatSetting::query()->first();
    }

    private function modelFlashLite(): string
    {
        $row = $this->settingsRow();
        if ($row !== null) {
            return $row->effectiveGeminiModelFlashLite();
        }

        $id = trim((string) config('services.gemini.model_flash_lite', ''));

        return $id !== '' ? $id : (string) config('services.gemini.model_flash', 'gemini-2.5-flash');
    }

    private function modelFlash(): string
    {
        $row = $this->settingsRow();
        if ($row !== null) {
            return $row->effectiveGeminiModelFlash();
        }

        return trim((string) config('services.gemini.model_flash', '')) !== ''
            ? (string) config('services.gemini.model_flash')
            : (string) config('services.gemini.default_model', 'gemini-2.5-flash');
    }

    private function modelPro(): string
    {
        $row = $this->settingsRow();
        if ($row !== null) {
            return $row->effectiveGeminiModelPro();
        }

        $id = trim((string) config('services.gemini.model_pro', ''));

        return $id !== '' ? $id : 'gemini-2.5-pro';
    }

    private function modelImage(): string
    {
        $row = $this->settingsRow();
        if ($row !== null) {
            return $row->effectiveGeminiModelImage();
        }

        $id = trim((string) config('services.gemini.model_image', ''));

        return $id !== '' ? $id : $this->modelFlash();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function proThinkingOverlay(): ?array
    {
        $level = trim((string) config('services.gemini.pro_thinking_level', ''));
        if ($level !== '') {
            return [
                'thinkingConfig' => [
                    'thinkingLevel' => $level,
                ],
            ];
        }

        $budget = (int) config('services.gemini.pro_thinking_budget', 0);
        if ($budget <= 0) {
            return null;
        }

        return [
            'thinkingConfig' => [
                'thinkingBudget' => max(128, min(8192, $budget)),
            ],
        ];
    }
}
