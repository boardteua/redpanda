<?php

namespace App\Services\OEmbed;

use DOMDocument;
use DOMElement;
use DOMNode;

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

    /**
     * Threads oEmbed returns a rich blockquote tree (div/svg, inline styles). Scripts are stripped;
     * event handlers, iframe/object/embed, and unsafe hrefs are removed. Upstream is graph.threads.net.
     */
    public function sanitizeThreadsOembedHtml(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return null;
        }

        $stripped = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        if ($stripped === '') {
            return null;
        }

        $dom = new DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8"><div>'.$stripped.'</div>';
        @$dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->documentElement;
        if (! $root instanceof DOMElement) {
            return null;
        }

        $blockquote = null;
        foreach ($root->getElementsByTagName('blockquote') as $candidate) {
            if (! $candidate instanceof DOMElement) {
                continue;
            }
            $class = strtolower($candidate->getAttribute('class'));
            if (str_contains($class, 'text-post-media')) {
                $blockquote = $candidate;

                break;
            }
        }

        if (! $blockquote instanceof DOMElement) {
            return null;
        }

        $this->purgeDangerousNodes($blockquote);
        $this->scrubThreadsSubtreeAttributes($blockquote);

        $out = new DOMDocument;
        $out->encoding = 'UTF-8';
        $imported = $out->importNode($blockquote, true);
        if ($imported === null) {
            return null;
        }
        $out->appendChild($imported);
        $serialized = trim($out->saveHTML($imported) ?: '');

        return $serialized !== '' ? $serialized : null;
    }

    private function purgeDangerousNodes(DOMNode $node): void
    {
        if (! $node instanceof DOMElement) {
            return;
        }

        $children = [];
        foreach ($node->childNodes as $c) {
            $children[] = $c;
        }
        foreach ($children as $c) {
            $this->purgeDangerousNodes($c);
        }

        $tag = strtolower($node->nodeName);
        if (in_array($tag, [
            'script', 'iframe', 'object', 'embed', 'form', 'link', 'meta', 'base',
        ], true)) {
            $node->parentNode?->removeChild($node);
        }
    }

    private function scrubThreadsSubtreeAttributes(DOMElement $root): void
    {
        $elements = [$root];
        foreach ($root->getElementsByTagName('*') as $el) {
            if ($el instanceof DOMElement) {
                $elements[] = $el;
            }
        }

        foreach ($elements as $el) {
            $this->scrubElementAttributes($el);
        }
    }

    private function scrubElementAttributes(DOMElement $el): void
    {
        $tag = strtolower($el->nodeName);
        $removeNames = [];

        if ($el->hasAttributes()) {
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = $attr->name;
                $lname = strtolower($name);
                if (str_starts_with($lname, 'on')) {
                    $removeNames[] = $name;

                    continue;
                }
                if ($lname === 'style' && ! $this->isSafeInlineStyle($attr->value)) {
                    $removeNames[] = $name;

                    continue;
                }
                if (str_starts_with($lname, 'data-') && $this->looksLikeMarkupInjection($attr->value)) {
                    $removeNames[] = $name;

                    continue;
                }
            }
        }

        foreach ($removeNames as $n) {
            $el->removeAttribute($n);
        }

        if ($tag === 'a' && $el->hasAttribute('href')) {
            $href = $el->getAttribute('href');
            if (! $this->isAllowedThreadsAnchorHref($href)) {
                $el->removeAttribute('href');
            }
        }

        foreach (['href', 'xlink:href'] as $hrefAttr) {
            if ($tag !== 'a' && $el->hasAttribute($hrefAttr)) {
                $v = $el->getAttribute($hrefAttr);
                if (! $this->isAllowedThreadsResourceHref($v)) {
                    $el->removeAttribute($hrefAttr);
                }
            }
        }
    }

    private function looksLikeMarkupInjection(string $value): bool
    {
        return preg_match('/<\s*script/i', $value) === 1;
    }

    private function isSafeInlineStyle(string $style): bool
    {
        $s = strtolower($style);

        return ! str_contains($s, 'javascript:')
            && ! str_contains($s, 'expression(')
            && ! str_contains($s, '@import')
            && ! str_contains($s, 'behavior:');
    }

    private function isAllowedThreadsAnchorHref(string $href): bool
    {
        $href = trim($href);
        $parts = parse_url($href);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        return in_array($host, ['www.threads.com', 'threads.com', 'www.threads.net', 'threads.net'], true);
    }

    /**
     * SVG / internal references: https only, or fragment-only, or relative path without scheme.
     */
    private function isAllowedThreadsResourceHref(string $href): bool
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')) {
            return true;
        }
        if (! str_starts_with($href, 'http')) {
            return ! str_contains($href, ':');
        }
        $parts = parse_url($href);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        return true;
    }
}
