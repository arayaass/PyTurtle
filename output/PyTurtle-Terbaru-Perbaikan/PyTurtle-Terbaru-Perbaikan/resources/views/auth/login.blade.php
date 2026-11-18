@extends('layouts.auth')
@section('title','Selamat datang kembali')
@section('content')
<span class="eyebrow">PETUALANGANMU MENUNGGU</span><h1>Selamat datang<br>kembali.</h1><p class="lead">Siap melanjutkan perjalanan bersama Shiha?</p>
<form method="POST" action="/login" class="form-stack">@csrf
<label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" required autofocus></label>
<label>Kata sandi<input type="password" name="password" autocomplete="current-password" placeholder="Masukkan kata sandimu" required></label>
<div class="form-between"><label class="check-label"><input type="checkbox" name="remember" value="1"> Ingat saya</label><a href="/forgot-password">Lupa kata sandi?</a></div>
<button class="primary full" type="submit">Masuk & lanjutkan petualangan <span>→</span></button>
</form><p class="auth-switch">Baru di PyTurtle? <a href="/register">Buat akun</a></p>
@endsection
