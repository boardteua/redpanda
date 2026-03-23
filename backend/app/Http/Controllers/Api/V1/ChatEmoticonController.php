<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatEmoticonResource;
use App\Models\ChatEmoticon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ChatEmoticonController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = ChatEmoticon::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => ChatEmoticonResource::collection($rows)->resolve(),
        ], Response::HTTP_OK, [], JSON_UNESCAPED_UNICODE);
    }
}
