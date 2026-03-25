<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PublicDocumentationController extends Controller
{
    /**
     * Логічний шлях від кореня монорепо (docs/…, project-specs/…).
     * На проді `docker/deploy.sh` видаляє `docs/` і `project-specs/` — тоді читаємо
     * копію з `backend/resources/public-monorepo-docs/` (оновлюється скриптом деплою).
     */
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
        $full = $this->resolvePublicDocPath($relativeFromRepoRoot);
        if ($full === null) {
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

    private function resolvePublicDocPath(string $relativeFromRepoRoot): ?string
    {
        $relativeFromRepoRoot = ltrim(str_replace('\\', '/', $relativeFromRepoRoot), '/');
        $monorepoRoot = dirname(base_path());
        $monorepoFile = $monorepoRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeFromRepoRoot);

        $bundledSuffix = $this->bundledPathSuffix($relativeFromRepoRoot);
        $bundledFile = $bundledSuffix !== null
            ? base_path('resources'.DIRECTORY_SEPARATOR.'public-monorepo-docs'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $bundledSuffix))
            : null;

        foreach (array_filter([$monorepoFile, $bundledFile]) as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return non-empty-string|null
     */
    private function bundledPathSuffix(string $relativeFromRepoRoot): ?string
    {
        if (str_starts_with($relativeFromRepoRoot, 'docs/chat-v2/')) {
            return 'chat-v2/'.substr($relativeFromRepoRoot, strlen('docs/chat-v2/'));
        }
        if (str_starts_with($relativeFromRepoRoot, 'project-specs/')) {
            return 'project-specs/'.substr($relativeFromRepoRoot, strlen('project-specs/'));
        }

        return null;
    }
}
