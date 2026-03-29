<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- Як legacy board.te.ua: простий viewport без interactive-widget (у Chrome дає стабільнішу поведінку) --}}
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('partials.seo-head')

        <script>
            window.__RP_SEO_WELCOME_LEAD__ = @json(__('seo.welcome_lead'));
        </script>

        {{-- Як legacy board.te.ua: лише .ico у вкладці; PWA-іконки лишаються в manifest --}}
        <link rel="icon" href="{{ url('/board-te-ua-favicon.ico') }}" type="image/x-icon">
        <link rel="alternate" type="text/markdown" href="{{ url('/llms.txt') }}" title="LLM context (Тернопільський Анонімний Чат)">

        @php
            $rpPwaManifestHref = null;
            if (! file_exists(public_path('hot')) && is_readable(public_path('build/manifest.webmanifest'))) {
                $rpPwaManifestHref = asset('build/manifest.webmanifest');
            }
            $path = trim(request()->path(), '/');
            $useChatCss =
                $path === 'chat'
                || str_starts_with($path, 'chat/')
                || $path === 'archive'
                || str_starts_with($path, 'archive/');
            $rpInitialCss = $useChatCss ? 'chat' : 'welcome';

            /** Ранній fetch woff2 — менший CLS на вітальні при font-display:swap (T145). Без hot / з production manifest. */
            $rpFontPreloadHrefs = [];
            if (! file_exists(public_path('hot'))) {
                $manifestPath = public_path('build/manifest.json');
                if (is_readable($manifestPath)) {
                    $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
                    foreach ([
                        'node_modules/@fontsource/instrument-sans/files/instrument-sans-latin-400-normal.woff2',
                        'node_modules/@fontsource/instrument-sans/files/instrument-sans-latin-600-normal.woff2',
                        'node_modules/@fontsource/instrument-sans/files/instrument-sans-latin-700-normal.woff2',
                        'node_modules/@fontsource/instrument-sans/files/instrument-sans-latin-ext-400-normal.woff2',
                    ] as $fontKey) {
                        if (! empty($manifest[$fontKey]['file'])) {
                            $rpFontPreloadHrefs[] = asset('build/'.$manifest[$fontKey]['file']);
                        }
                    }
                }
            }
        @endphp
        @foreach ($rpFontPreloadHrefs as $fontHref)
            <link rel="preload" href="{{ $fontHref }}" as="font" type="font/woff2" crossorigin>
        @endforeach
        @if ($rpPwaManifestHref)
            {{-- T163: узгоджено з vite-plugin-pwa manifest (theme_color) --}}
            <link rel="manifest" href="{{ $rpPwaManifestHref }}">
            <meta name="theme-color" content="#c2410c">
            <link rel="apple-touch-icon" href="{{ url('/pwa/apple-touch-icon-180.png') }}" sizes="180x180">
        @endif
        @php
            $rpWebPushPublicKey = trim((string) config('services.web_push.vapid.public_key', ''));
        @endphp
        <script>
            window.__RP_INITIAL_CSS_ENTRY__ = @json($rpInitialCss);
            window.__RP_WEB_PUSH__ = {
                vapidPublicKey: @json($rpWebPushPublicKey !== '' ? $rpWebPushPublicKey : null),
            };
        </script>
        @vite([
            $useChatCss ? 'resources/css/chat.css' : 'resources/css/welcome.css',
            'resources/js/app.js',
        ])
    </head>
    <body class="rp-body antialiased">
        <a href="#main-content" class="rp-skip-link">Перейти до основного вмісту</a>
        <div id="app"></div>
    </body>
</html>
