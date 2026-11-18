@extends('layouts.app')
@section('title','Profil')
@section('content')
<div class="page-heading"><div><span class="eyebrow">IDENTITAS PENJELAJAH</span><h1>Profil saya.</h1><p>Nama ini tampil pada leaderboard.</p></div></div>
<x-card class="settings-card"><form method="POST" action="/profile" class="form-stack">@csrf<label>Nama tampilan<input name="display_name" maxlength="40" value="{{ old('display_name',auth()->user()->display_name??auth()->user()->name) }}" required></label><label>Email akun<input value="{{ auth()->user()->email }}" readonly></label><p class="small muted">Email hanya terlihat olehmu.</p><button class="primary">Simpan profil</button></form></x-card>
@endsection
