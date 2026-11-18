@extends('layouts.auth')
@section('title','Mulai petualangan')
@section('content')
<span class="eyebrow">HALO, PENJELAJAH BARU</span><h1>Perjalananmu<br>dimulai di sini.</h1><p class="lead">Tak perlu pengalaman coding. Cukup rasa ingin tahu.</p>
<x-google-button/><div class="auth-divider"><span>atau gunakan email</span></div>
<form method="POST" action="/register" class="form-stack">@csrf
<label>Nama penjelajah<input name="name" value="{{ old('name') }}" maxlength="40" autocomplete="name" placeholder="Siapa namamu?" required autofocus></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required></label>
<x-password-input hint="Minimal 8 karakter" minlength="8" autocomplete="new-password" required/>
<x-password-input name="password_confirmation" label="Ulangi kata sandi" minlength="8" autocomplete="new-password" required/>
<button class="primary full">Buat akun & mulai jelajah <span>→</span></button></form>
<p class="auth-switch">Sudah punya akun? <a href="/login">Masuk</a></p>
@endsection
