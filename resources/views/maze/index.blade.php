@extends('layouts.app')
@section('title', 'Maze Quest')
@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">SHIHA’S TURTLE ADVENTURE</span>
            <h1>Sebuah peta. Petualanganmu.</h1>
            <p>Tulis kode, ikuti jejak Kakek, dan temukan harta yang sesungguhnya.</p>
        </div>
        <a class="primary" href="/game/continue">Lanjutkan petualangan →</a>
    </div>
    <div class="story-intro">
        <img src="/assets/characters/shiha/portrait.svg" alt="Shiha" />
        <div>
            <span class="eyebrow">SURAT DARI LOTENG</span>
            <h2>“Kakek meninggalkan lebih dari sekadar peta.”</h2>
            <p>
                Saat membuka kotak tua, Shiha menemukan sebuah peta menuju hutan tersembunyi. Kini, setiap baris kodemu
                akan membantunya mengikuti jejak perjalanan Kakek.
            </p>
        </div>
    </div>
    <div class="notice">
        <b>Bekal materi: {{ $completedModules }}/11 modul selesai.</b>
        <p>
            Selesaikan Code Matching dan evaluasi modul 1–4 untuk membuka Maze level 1–2; sampai modul 8 untuk level 3;
            sampai modul 11 untuk level 4–5. Level Maze dimainkan berurutan.
        </p>
        <a class="text-link" href="/modules">Lanjut membaca modul →</a>
    </div>
    <div class="level-grid">
        @foreach ($levels as $level)
            <x-level-card
                :level="$level"
                :progress="$progress->get($level->id)"
                :unlocked="$service->unlocked(auth()->user(), $level->id)"
                :lock-message="$completedModules < $access->requiredForMaze($level->id) ? 'Selesaikan sampai modul '.$access->requiredForMaze($level->id) : 'Selesaikan level '.($level->id - 1)"
            />
        @endforeach
    </div>
    <div class="quiet-note">
        <span>Mulai dari langkah sederhana. Tidak perlu terburu-buru.</span><x-confirm-modal />
    </div>
@endsection
