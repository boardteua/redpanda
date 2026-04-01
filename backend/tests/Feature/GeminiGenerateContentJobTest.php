<?php

namespace Tests\Feature;

use App\Jobs\GeminiGenerateContentJob;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiGenerateContentJobTest extends TestCase
{
    public function test_job_throws_on_429_so_queue_backoff_can_retry(): void
    {
        config()->set('services.gemini.enabled', true);
        config()->set('services.gemini.api_key', 'test-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'status' => 'RESOURCE_EXHAUSTED',
                    'message' => 'RESOURCE_EXHAUSTED',
                ],
            ], 429),
        ]);

        $job = new GeminiGenerateContentJob(
            payload: ['contents' => [['parts' => [['text' => 'hi']]]]],
            model: 'gemini-2.5-flash',
            meta: ['room_id' => 1],
        );

        $this->expectException(RequestException::class);
        $job->handle(app(\App\Services\Ai\Gemini\GeminiClient::class));
    }
}

