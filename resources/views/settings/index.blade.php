@extends('layouts.app')
@section('title', 'Settings')
@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">RUANG UNTUK NYAMAN</span>
            <h1>Atur ritme petualanganmu.</h1>
            <p>Belajar paling baik saat kamu merasa nyaman.</p>
        </div>
    </div>
    <x-card class="settings-card"
        ><form method="POST" action="/settings">
            @csrf
            @foreach (['music_enabled' => ['Musik suasana', 'Nada ambient lembut saat bermain. Audio dimulai setelah interaksi.'], 'sound_enabled' => ['Efek suara', 'Nada pendek untuk checkpoint dan keberhasilan.'], 'reduced_motion' => ['Kurangi animasi', 'Matikan gerakan ambient dan tampilkan perpindahan secara langsung.']] as $key => [$title,$desc])
                <label class="setting-row"
                    ><span><b>{{ $title }}</b><small>{{ $desc }}</small></span
                    ><input
                        type="checkbox"
                        name="{{ $key }}"
                        value="1"
                        @checked($settings?->$key ?? ($key === 'sound_enabled'))
                /></label>
            @endforeach
            <button class="primary mt-6">Simpan pengaturan</button>
        </form></x-card>
    <div class="quiet-note">
        <span>Preferensi reduced motion dari perangkat juga dihormati.</span><x-confirm-modal />
    </div>
@endsection
