<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PublicDocumentationController extends Controller
{
    public function openapiYaml(): SymfonyResponse
    {
        return $this->repoFile('docs/chat-v2/openapi.yaml', 'application/yaml; charset=utf-8');
    }

    public function aiAgentFriendly(): SymfonyResponse
    {
        return $this->repoFile('docs/chat-v2/AI-AGENT-FRIENDLY.md', 'text/markdown; charset=utf-8');
    }

    public function chatV2Setup(): SymfonyResponse
    {
        return $this->repoFile('project-specs/chat-v2-setup.md', 'text/markdown; charset=utf-8');
    }

    private function repoFile(string $relativeFromRepoRoot, string $contentType): SymfonyResponse
    {
        $full = dirname(base_path()).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeFromRepoRoot);
        if (! is_readable($full)) {
            abort(404);
        }

        $body = file_get_contents($full);
        if ($body === false) {
            abort(500);
        }

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=600',
        ]);
    }
}
