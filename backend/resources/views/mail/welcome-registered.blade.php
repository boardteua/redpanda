@extends('mail.layouts.transactional')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;font-weight:600;">Вітаємо, {{ $userName }}!</h1>
    <p style="margin:0 0 16px;">Ваш обліковий запис у {{ $appName }} створено. Можете одразу заходити в чат.</p>
    <p style="margin:0 0 24px;">
        <a href="{{ $chatUrl }}" style="display:inline-block;padding:12px 20px;background:#ea580c;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Відкрити чат</a>
    </p>
    <p style="margin:0;font-size:14px;color:#4b5563;">Якщо ви не реєструвалися — зверніться до підтримки або проігноруйте цей лист.</p>
@endsection
