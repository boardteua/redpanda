<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;line-height:1.5;color:#1f2937;background:#f3f4f6;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;padding:24px 28px;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
        <p style="margin:0 0 20px;font-size:13px;color:#6b7280;">{{ $appName }}</p>
        @yield('content')
        <p style="margin:28px 0 0;font-size:12px;color:#9ca3af;">Лист надіслано автоматично з {{ $appName }}.</p>
    </div>
</body>
</html>
