@extends('mail.layouts.transactional')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;font-weight:600;">{{ $headline }}</h1>
    <p style="margin:0 0 16px;">Вітаємо, {{ $userName }}.</p>
    <p style="margin:0 0 16px;">{{ $bodyLine }}</p>
    <p style="margin:0;font-size:14px;color:#4b5563;">Якщо це були не ви — змініть пароль і зверніться до підтримки.</p>
@endsection
