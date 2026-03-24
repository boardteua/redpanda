@extends('mail.layouts.transactional')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;font-weight:600;">Скидання пароля</h1>
    <p style="margin:0 0 16px;">Ви отримали цей лист, бо для вашого облікового запису запитали скидання пароля.</p>
    <p style="margin:0 0 24px;">
        <a href="{{ $resetUrl }}" style="display:inline-block;padding:12px 20px;background:#ea580c;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Скинути пароль</a>
    </p>
    <p style="margin:0 0 8px;font-size:14px;color:#4b5563;">Посилання діє <strong>{{ $expireMinutes }}</strong> хвилин.</p>
    <p style="margin:0;font-size:14px;color:#4b5563;">Якщо ви цього не робили — проігноруйте лист; пароль не зміниться.</p>
@endsection
