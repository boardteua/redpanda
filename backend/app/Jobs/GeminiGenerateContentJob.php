<?php

namespace App\Jobs;

use App\Services\Ai\Gemini\GeminiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GeminiGenerateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * We don't want to retry forever on quota throttling.
     */
    public int $tries = 6;

    /**
     * Cap runtime per attempt.
     */
    public int $timeout = 30;

    /**
     * Retry delays (seconds) per attempt (1-indexed).
     *
     * Laravel will pick the delay for the current attempt number.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [2, 4, 8, 16, 32, 64];
    }

    /**
     * @param  array<string, mixed>  $payload  Gemini request body (contents, optional generationConfig, etc.)
     * @param  array<string, mixed>  $meta  safe metadata for logs only (no user text)
     */
    public function __construct(
        public array $payload,
        public ?string $model,
        public array $meta = [],
    ) {}

    public function handle(GeminiClient $gemini): void
    {
        try {
            $resp = $gemini->generateContent($this->payload, $this->model);
        } catch (RequestException $e) {
            if ($gemini->isResourceExhausted429($e)) {
                Log::channel('structured')->warning('gemini throttled (429); will retry via queue backoff', [
                    'attempt' => $this->attempts(),
                    'model' => $this->model,
                    'meta' => $this->meta,
                ]);
            }

            Log::channel('structured')->warning('gemini job failed (request)', [
                'status' => optional($e->response)->status(),
                'attempt' => $this->attempts(),
                'model' => $this->model,
                'meta' => $this->meta,
            ]);

            throw $e;
        }

        Log::channel('structured')->info('gemini job completed', [
            'attempt' => $this->attempts(),
            'model' => $this->model,
            'usage' => is_array($resp['usageMetadata'] ?? null) ? $resp['usageMetadata'] : null,
            'meta' => $this->meta,
        ]);
    }
}

