@php
    $baseUrl = rtrim((string) config('app.url'), '/');
    $reqPath = trim(request()->path(), '/');
    $canonicalSegments = $reqPath === '' ? [] : array_values(array_filter(explode('/', $reqPath), fn (string $s): bool => $s !== ''));
    $canonicalUrl = $canonicalSegments === []
        ? $baseUrl.'/'
        : $baseUrl.'/'.collect($canonicalSegments)->map(fn (string $seg): string => rawurlencode($seg))->join('/');

    $ogImagePath = (string) config('seo.og_image_path', '/brand/og-default.png');
    $ogImageUrl = $baseUrl.(str_starts_with($ogImagePath, '/') ? $ogImagePath : '/'.$ogImagePath);

    $orgLogoPath = (string) config('seo.organization_logo_path', '/brand/board-te-ua-orange.png');
    $orgLogoUrl = $baseUrl.(str_starts_with($orgLogoPath, '/') ? $orgLogoPath : '/'.$orgLogoPath);

    $metaTitle = __('seo.meta_title');
    $metaDescription = __('seo.meta_description');
    $ogTitle = __('seo.og_title');
    $ogDescription = __('seo.og_description');
    $ogSiteName = __('seo.og_site_name');
    $ogType = __('seo.og_type');
    $ogLocale = __('seo.og_locale');

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                'name' => __('seo.jsonld_website_name'),
                'url' => $baseUrl.'/',
                'description' => $metaDescription,
            ],
            [
                '@type' => 'Organization',
                'name' => __('seo.jsonld_organization_name'),
                'url' => $baseUrl.'/',
                'logo' => $orgLogoUrl,
            ],
        ],
    ];

    $faqRaw = \Illuminate\Support\Facades\Lang::get('seo.faq');
    if (is_array($faqRaw) && $faqRaw !== []) {
        $mainEntity = [];
        foreach ($faqRaw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $q = isset($item['question']) ? trim((string) $item['question']) : '';
            $a = isset($item['answer']) ? trim((string) $item['answer']) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a,
                ],
            ];
        }
        if ($mainEntity !== []) {
            $jsonLd['@graph'][] = [
                '@type' => 'FAQPage',
                'mainEntity' => $mainEntity,
            ];
        }
    }

    $jsonLdEncoded = json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp
        <title>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">

        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta property="og:site_name" content="{{ $ogSiteName }}">
        <meta property="og:locale" content="{{ $ogLocale }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ __('seo.twitter_title') }}">
        <meta name="twitter:description" content="{{ __('seo.twitter_description') }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">

        <script type="application/ld+json">
{!! $jsonLdEncoded !!}
        </script>
