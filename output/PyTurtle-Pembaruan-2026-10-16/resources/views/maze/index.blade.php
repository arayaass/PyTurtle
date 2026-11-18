@extends('layouts.app')
@section('title','Maze Quest')
@section('content')
<div class="page-heading"><div><span class="eyebrow">SHIHA’S TURTLE ADVENTURE</span><h1>Sebuah peta. Petualanganmu.</h1><p>Tulis kode, ikuti jejak Kakek, dan temukan harta yang sesungguhnya.</p></div><a class="primary" href="/game/continue">Lanjutkan petualangan →</a></div>
<div class="story-intro"><img src="/assets/characters/shiha/portrait.svg" alt="Shiha"><div><span class="eyebrow">SURAT DARI LOTENG</span><h2>“Kakek meninggalkan lebih dari sekadar peta.”</h2><p>Saat membuka kotak tua, Shiha menemukan sebuah peta menuju hutan tersembunyi. Kini, setiap baris kodemu akan membantunya mengikuti jejak perjalanan Kakek.</p></div></div>
<div class="level-grid">@foreach($levels as $level)<x-level-card :level="$level" :progress="$progress->get($level->id)" :unlocked="$level->id===1 || $progress->get($level->id-1)?->status==='completed'"/>@endforeach</div>
<div class="quiet-note"><span>Mulai dari langkah sederhana. Tidak perlu terburu-buru.</span><x-confirm-modal/></div>
@endsection
