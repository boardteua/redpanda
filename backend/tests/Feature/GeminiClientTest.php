<?php

namespace Tests\Feature;

use App\Services\Ai\Gemini\GeminiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiClientTest extends TestCase
{
    public function test_generate_content_posts_to_expected_endpoint_with_api_key_header(): void
    {
        config()->set('services.gemini.enabled', true);
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.base_url', 'https://generativelanguage.googleapis.com');
        config()->set('services.gemini.api_version', 'v1beta');
        config()->set('services.gemini.timeout_ms', 1500);

        Http::fake(function (Request $request) {
            $this->assertSame('POST', $request->method());
            $this->assertSame(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
                (string) $request->url()
            );
            $this->assertSame('test-key', $request->header('x-goog-api-key')[0] ?? null);

            $json = $request->data();
            $this->assertIsArray($json);
            $this->assertArrayHasKey('contents', $json);

            return Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'role' => 'model',
                            'parts' => [['text' => 'ok']],
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 1,
                    'candidatesTokenCount' => 1,
                    'totalTokenCount' => 2,
                ],
            ], 200);
        });

        $client = app(GeminiClient::class);
        $resp = $client->generateContent([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => 'hello']],
                ],
            ],
        ], 'gemini-2.5-flash');

        $this->assertSame('ok', $resp['candidates'][0]['content']['parts'][0]['text'] ?? null);
    }

    public function test_is_resource_exhausted_429_detects_status_and_resource_exhausted_marker(): void
    {
        config()->set('services.gemini.enabled', true);
        config()->set('services.gemini.api_key', 'test-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'status' => 'RESOURCE_EXHAUSTED',
                    'message' => 'RESOURCE_EXHAUSTED: Quota exceeded',
                ],
            ], 429),
        ]);

        $client = app(GeminiClient::class);

        try {
            $client->generateContent(['contents' => [['parts' => [['text' => 'hi']]]]], 'gemini-2.5-flash');
            $this->fail('Expected RequestException');
        } catch (RequestException $e) {
            $this->assertTrue($client->isResourceExhausted429($e));
        }
    }
}

