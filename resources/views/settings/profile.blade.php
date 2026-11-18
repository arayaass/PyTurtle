@extends('layouts.app')
@section('title', 'Profil')
@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">IDENTITAS PENJELAJAH</span>
            <h1>Profil saya.</h1>
            <p>Nama ini tampil pada leaderboard.</p>
        </div>
    </div>
    <x-card class="settings-card"
        ><form method="POST" action="/profile" class="form-stack">
            @csrf
            <label
                >Nama tampilan<input
                    name="display_name"
                    maxlength="40"
                    value="{{ old('display_name',auth()->user()->display_name??auth()->user()->name) }}"
                    required /></label
            ><label>Email akun<input value="{{ auth()->user()->email }}" readonly /></label>
            <p class="small muted">Email hanya terlihat olehmu.</p>
            <button class="primary">Simpan profil</button>
        </form></x-card>
    <x-card class="settings-card"
        ><h2>Akun Google</h2>
        <p>Hubungkan Google dengan alamat email yang sama untuk masuk ke akun dan progress ini.</p>
        @if (auth()->user()->google_id)
            <p class="notice">Akun Google sudah terhubung.</p>
        @else
            <x-google-button :link="true" />
        @endif
    </x-card>
@endsection
