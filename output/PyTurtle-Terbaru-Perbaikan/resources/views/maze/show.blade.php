@extends('layouts.app')
@section('title',$level->title)
@section('content')
<div id="maze-app" x-data="mazeGame" x-init="boot()">
<div class="breadcrumb"><a href="/maze">Maze Quest</a><span>/</span><span>Chapter 0{{ $level->id }}</span></div>
<div class="page-heading compact"><div><span class="eyebrow">CHAPTER 0{{ $level->id }} / 03</span><h1>{{ $level->title }}</h1><p>{{ $level->definition['subtitle'] }}</p></div><div class="save-status" role="status" x-text="saveStatus"></div></div>
<div class="maze-layout"><section class="card editor-panel"><div class="objective"><span class="eyebrow">MISIMU HARI INI</span><h3>Bantu Shiha mencapai tujuan</h3><p>{{ $level->definition['objective'] }}</p><div class="maze-grid-guide"><b>1 kotak = 1 langkah</b><span><code>forward(3)</code> maju 3 kotak · <code>backward(1)</code> mundur 1 kotak</span><span>Mulai menghadap timur →. Belokan memakai kelipatan 90°.</span></div></div><div class="command-list">@foreach($level->definition['commands'] as $command)<x-command-pill :command="$command"/>@endforeach</div>
<x-code-editor/>
<div class="editor-toolbar"><button class="primary" @click="run()" :disabled="running || syncing"><span aria-hidden="true">▷</span> <span x-text="running?'Menjalankan…':'Run Code'"></span></button><button class="secondary" @click="reset()" :disabled="running || syncing">Reset Code</button><button class="text-link" @click="hint()" :disabled="running">Hint</button></div>
<div class="editor-foot"><span>Ctrl / ⌘ + Enter untuk menjalankan</span><label>Kecepatan <select x-model.number="speed" :disabled="running"><option value="1">1×</option><option value="2">2×</option><option value="4">4×</option></select></label></div>
<div class="notice" x-show="hintText" x-text="hintText" x-cloak></div><p class="small muted hint-note">Hint mengurangi 5 poin sekali per level. Run selalu dimulai dari titik awal; editor selalu kosong saat chapter dibuka. Skor terbaik tetap tersimpan.</p>
</section>
<section class="card world-panel"><div class="world-top"><span><span class="live-dot"></span> Hutan Kenangan <span class="maze-grid-badge">1 kotak = 1 langkah</span></span><span x-text="checkpoint+' / {{ count($level->definition['checkpoints']) }} checkpoint'"></span></div><canvas id="game-canvas" width="600" height="460" aria-label="Peta hutan. {{ $level->definition['objective'] }}">Gunakan instruksi teks di samping untuk mengikuti rute.</canvas><div class="world-legend"><span><i class="legend-dot start"></i> Shiha</span><span><i class="legend-dot check"></i> Checkpoint</span><span><i class="legend-dot goal"></i> Tujuan</span><span>Awal: timur →</span></div>
<div class="feedback" :class="result.toLowerCase()" role="status" aria-live="polite"><b x-text="result==='SUCCESS'?'✓ Challenge berhasil!':result==='ERROR'?'Periksa kodemu':result==='PARTIAL'?'Terus mencoba':'Siap menjelajah?'"></b><p x-text="message"></p><span x-show="result==='SUCCESS'" x-text="'Skor: '+score+' / 100'"></span></div>
<div class="completion-actions" x-show="result==='SUCCESS'" x-cloak><button class="secondary" @click="showOutro()">Baca cerita →</button>@if($level->id<3)<a class="primary" href="/maze/{{ $level->id+1 }}" x-show="saved">Chapter berikutnya →</a>@else<a class="primary" href="/progress" x-show="saved">Lihat pencapaian →</a>@endif</div>
</section></div>
<x-dialog/>
</div>
@endsection
@push('scripts')
@php($mazeData=['level'=>$definition,'progress'=>$progress,'story'=>$story,'settings'=>$settings])
<script type="application/json" id="maze-data">@json($mazeData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)</script>
@endpush
