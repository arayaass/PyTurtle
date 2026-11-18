@extends('layouts.auth')
@section('title','Kata sandi baru')
@section('content')
<h1>Awal yang baru.</h1><p class="lead">Buat kata sandi baru untuk melanjutkan petualangan.</p>
<form action="/reset-password" method="POST" class="form-stack">@csrf<input type="hidden" name="token" value="{{ $token }}"><label>Email<input type="email" name="email" value="{{ old('email',request('email')) }}" required></label><label>Kata sandi baru<input type="password" name="password" autocomplete="new-password" minlength="8" required></label><label>Ulangi kata sandi<input type="password" name="password_confirmation" autocomplete="new-password" required></label><button class="primary">Simpan kata sandi</button></form>
@endsection
