@extends('layouts.auth')
@section('title','Mulai petualangan')
@section('content')
<span class="eyebrow">HALO, PENJELAJAH BARU</span><h1>Perjalananmu<br>dimulai di sini.</h1><p class="lead">Tak perlu pengalaman coding. Cukup rasa ingin tahu.</p>
<form method="POST" action="/register" class="form-stack">@csrf
<label>Nama penjelajah<input name="name" value="{{ old('name') }}" maxlength="40" autocomplete="name" placeholder="Siapa namamu?" required autofocus></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required></label>
<label>Kata sandi <small>Minimal 8 karakter</small><input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
<label>Ulangi kata sandi<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
<button class="primary full">Buat akun & mulai jelajah <span>→</span></button></form>
<p class="auth-switch">Sudah punya akun? <a href="/login">Masuk</a></p>
@endsection
