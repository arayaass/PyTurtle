@extends('layouts.auth')
@section('title','Pulihkan kata sandi')
@section('content')
<span class="eyebrow">KEMBALI KE JALUR</span><h1>Lupa kata sandi?</h1><p class="lead">Masukkan email akunmu. Kami akan mengirim tautan untuk membuat kata sandi baru.</p>
<form action="/forgot-password" method="POST" class="form-stack">@csrf<label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label><button class="primary">Kirim tautan pemulihan</button></form><a href="/login" class="back-link">← Kembali ke login</a>
@endsection
