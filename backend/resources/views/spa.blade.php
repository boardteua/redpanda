<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{-- interactive-widget: layout viewport під віртуальну клавіатуру (MDN viewport meta); без JS --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, interactive-widget=resizes-content">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Redpanda') }}</title>

        {{-- Паритет з legacy board.te.ua: favicon з https://www.board.te.ua/favicon.ico (збережено як /board-te-ua-favicon.ico) --}}
        <link rel="icon" href="{{ url('/board-te-ua-favicon.ico') }}" type="image/x-icon">
        <link rel="alternate" type="text/markdown" href="{{ url('/llms.txt') }}" title="LLM context (Chat v2)">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="rp-body antialiased">
        <a href="#main-content" class="rp-skip-link">Перейти до основного вмісту</a>
        <div id="app"></div>
    </body>
</html>
