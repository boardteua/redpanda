<?php

namespace App\Services\OEmbed;

use DOMDocument;
use DOMElement;

class OEmbedHtmlSanitizer
{
    /**
     * @param  list<string>  $allowedHosts
     * @return string|null Sanitized HTML fragment (iframes only) or null if nothing safe remains
     */
    public function sanitizeIframeHtml(?string $html, array $allowedHosts): ?string
    {
        if ($html === null || $html === '') {
            return null;
        }

        $allowed = array_fill_keys(array_map(strtolower(...), $allowedHosts), true);

        $dom = new DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8"><div>'.$html.'</div>';
        @$dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $iframes = $dom->getElementsByTagName('iframe');
        $parts = [];
        for ($i = 0; $i < $iframes->length; $i++) {
            $node = $iframes->item($i);
            if (! $node instanceof DOMElement) {
                continue;
            }
            $src = $node->getAttribute('src');
            if (! $this->isAllowedSrc($src, $allowed)) {
                continue;
            }

            $attrs = [
                'src' => $src,
            ];
            foreach (['width', 'height', 'title', 'allow', 'allowfullscreen', 'loading', 'referrerpolicy'] as $attr) {
                if (! $node->hasAttribute($attr)) {
                    continue;
                }
                $val = $node->getAttribute($attr);
                if ($this->looksLikeEventHandler($attr, $val)) {
                    continue;
                }
                $attrs[$attr] = $val;
            }

            $parts[] = $this->buildIframeTag($attrs);
        }

        if ($parts === []) {
            return null;
        }

        return implode('', $parts);
    }

    /**
     * @param  array<string, true>  $allowedHosts
     */
    private function isAllowedSrc(string $src, array $allowedHosts): bool
    {
        $src = trim($src);
        if ($src === '') {
            return false;
        }

        $parts = parse_url($src);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        return isset($allowedHosts[$host]);
    }

    private function looksLikeEventHandler(string $attr, string $value): bool
    {
        if (str_starts_with(strtolower($attr), 'on')) {
            return true;
        }

        $v = strtolower(trim($value));

        return str_starts_with($v, 'javascript:');
    }

    /**
     * @param  array<string, string>  $attrs
     */
    private function buildIframeTag(array $attrs): string
    {
        $pairs = [];
        foreach ($attrs as $name => $value) {
            $safeName = preg_replace('/[^a-z0-9_-]/i', '', $name);
            if ($safeName === '' || $safeName === null) {
                continue;
            }
            $pairs[] = sprintf(
                '%s="%s"',
                $safeName,
                htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            );
        }

        return '<iframe '.implode(' ', $pairs).'></iframe>';
    }
}
