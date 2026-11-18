@extends('layouts.app')
@section('title','Progress')
@section('content')
@php($completed=$progress->where('status','completed')->count())
<div class="page-heading"><div><span class="eyebrow">JURNAL PENJELAJAH</span><h1>Lihat seberapa jauh kamu melangkah.</h1><p>Setiap percobaan adalah bagian dari belajar.</p></div><a class="primary" href="/game/continue">Lanjutkan petualangan →</a></div>
<div class="stats-strip"><div><span class="stat-label">SKOR PETUALANGAN</span><strong>{{ $progress->sum('best_score') }} <small>/ 300</small></strong></div><div><span class="stat-label">MATERI SELESAI</span><strong>{{ $lessonCount }} <small>/ {{ $lessonTotal }} materi</small></strong></div><div class="stat-progress"><div class="spread"><span>Petualangan selesai</span><b>{{ round($completed/3*100) }}%</b></div><x-progress-bar :value="$completed/3*100"/></div></div>
<div class="level-grid">@foreach($levels as $level)<x-level-card :level="$level" :progress="$progress->get($level->id)" :unlocked="$level->id===1 || $progress->get($level->id-1)?->status==='completed'"/>@endforeach</div>
<x-card class="progress-table"><h2>Catatan perjalanan</h2><div class="table-scroll"><table><thead><tr><th>Chapter</th><th>Status</th><th>Percobaan</th><th>Hint</th><th>Skor terbaik</th></tr></thead><tbody>@foreach($levels as $level)@php($p=$progress->get($level->id))<tr><td>{{ $level->title }}</td><td>{{ $p?->status==='completed'?'✓ Selesai':($p?'Sedang dimainkan':($level->id===1||$progress->get($level->id-1)?->status==='completed'?'Siap dimainkan':'Terkunci')) }}</td><td>{{ $p?->attempts??0 }}</td><td>{{ $p?->hints_used??0 }}</td><td>{{ $p?->best_score??0 }} / 100</td></tr>@endforeach</tbody></table></div></x-card>
<div class="quiet-note"><p>Skor terbaik tetap tersimpan ketika kamu mengulang level.</p><x-confirm-modal/></div>
@endsection
