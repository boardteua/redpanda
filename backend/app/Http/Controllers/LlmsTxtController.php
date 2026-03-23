<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class LlmsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $path = resource_path('content/llms.txt');
        if (! is_readable($path)) {
            abort(500, 'llms.txt template missing');
        }

        $body = file_get_contents($path);
        if ($body === false) {
            abort(500);
        }
        $body = str_replace('__APP_URL__', rtrim((string) config('app.url'), '/'), $body);

        return response($body, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
