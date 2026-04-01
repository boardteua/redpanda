<?php

namespace App\Services\Ai\Gemini;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GeminiClient
{
    public function generateContent(array $payload, ?string $model = null): array
    {
        if (! $this->enabled()) {
            throw new \RuntimeException('Gemini disabled');
        }

        $model = $this->normalizeModel($model);
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            throw new \RuntimeException('Gemini missing API key');
        }

        try {
            $resp = Http::timeout($this->timeoutSeconds())
                ->acceptJson()
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->post($this->endpointUrl($model), $payload)
                ->throw();
        } catch (ConnectionException $e) {
            Log::warning('gemini connection failed', ['model' => $model, 'error' => $e->getMessage()]);
            throw $e;
        } catch (RequestException $e) {
            Log::warning('gemini request failed', [
                'model' => $model,
                'status' => optional($e->response)->status(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        /** @var array<string, mixed> $json */
        $json = $resp->json();

        return $json;
    }

    public function isResourceExhausted429(RequestException $e): bool
    {
        $status = optional($e->response)->status();
        if ($status !== 429) {
            return false;
        }

        $json = optional($e->response)->json();
        if (! is_array($json)) {
            return true;
        }

        $message = null;
        if (is_string($json['error']['status'] ?? null)) {
            $message = $json['error']['status'];
        } elseif (is_string($json['error']['message'] ?? null)) {
            $message = $json['error']['message'];
        }

        return $message === null ? true : str_contains($message, 'RESOURCE_EXHAUSTED');
    }

    private function enabled(): bool
    {
        return (bool) config('services.gemini.enabled', false);
    }

    private function apiKey(): string
    {
        return (string) config('services.gemini.api_key', '');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
    }

    private function apiVersion(): string
    {
        $v = (string) config('services.gemini.api_version', 'v1beta');

        return $v === '' ? 'v1beta' : $v;
    }

    private function timeoutSeconds(): int
    {
        $ms = (int) config('services.gemini.timeout_ms', 8000);

        return max(1, min(60, (int) ceil($ms / 1000)));
    }

    private function normalizeModel(?string $model): string
    {
        $model = trim((string) $model);
        if ($model === '') {
            $model = (string) config('services.gemini.default_model', 'gemini-2.5-flash');
        }

        return $model;
    }

    private function endpointUrl(string $model): string
    {
        // Context7 docs: POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
        return "{$this->baseUrl()}/{$this->apiVersion()}/models/{$model}:generateContent";
    }
}

