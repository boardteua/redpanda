<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OEmbedFetchRequest;
use App\Services\OEmbed\Exceptions\OEmbedNoProviderException;
use App\Services\OEmbed\Exceptions\OEmbedUnsafeUrlException;
use App\Services\OEmbed\Exceptions\OEmbedUpstreamException;
use App\Services\OEmbed\OEmbedProxyService;
use Illuminate\Http\JsonResponse;

class OEmbedController extends Controller
{
    public function show(OEmbedFetchRequest $request, OEmbedProxyService $oembed): JsonResponse
    {
        $url = $request->validated('url');
        $maxwidth = $request->validated('maxwidth');
        $maxheight = $request->validated('maxheight');

        try {
            $payload = $oembed->fetch($url, $maxwidth, $maxheight);
        } catch (OEmbedUnsafeUrlException|OEmbedNoProviderException) {
            return response()->json([
                'message' => 'Неможливо завантажити вбудовування для цього посилання.',
            ], 422);
        } catch (OEmbedUpstreamException) {
            return response()->json([
                'message' => 'Провайдер вбудовувань тимчасово недоступний.',
            ], 502);
        }

        return response()
            ->json($payload)
            ->header('Cache-Control', 'private, max-age=300');
    }
}
