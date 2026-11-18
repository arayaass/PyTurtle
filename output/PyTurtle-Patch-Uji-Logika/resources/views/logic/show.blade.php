@extends('layouts.app')
@section('title', 'Uji Logika · '.$definition['title'])
@section('content')
<div x-data="logicChallenge" x-init="boot()">
<div class="breadcrumb"><a href="/uji-logika">Uji Logika</a><span>/ Tantangan {{ $challenge }}</span></div>
<div class="page-heading"><div><span class="eyebrow">TANTANGAN {{ $challenge }} / 6</span><h1>{{ $definition['title'] }}</h1><p>Cari satu contoh yang membantah pernyataan, lalu jelaskan alasannya.</p></div><span class="tag" x-show="completed" x-cloak>✓ Sudah selesai</span></div>
<section class="card logic-claim"><span class="eyebrow">PERNYATAAN YANG DIUJI</span><blockquote>{{ $definition['claim'] }}</blockquote><p>Satu contoh yang bertentangan cukup untuk membantah kata “selalu”. Percobaan yang cocok belum membuktikan pernyataan berlaku untuk semua kondisi.</p></section>
<div class="logic-workspace"><section class="card logic-controls"><h2>1. Atur eksperimen</h2><p>Semua program mulai di (0, 0) dengan arah awal yang sama. Timur = X positif; utara = Y positif. Tidak ada dinding pada papan ini.</p>
<fieldset :disabled="busy"><label>Arah awal<select x-model.number="angle" @change="invalidate()"><option value="0">Timur →</option><option value="90">Utara ↑</option><option value="180">Barat ←</option><option value="270">Selatan ↓</option></select></label>
@if(collect($definition['a'])->contains(fn($command)=>$command[1]==='distance'))<label>Jarak maju (kotak)<select x-model.number="distance" @change="invalidate()">@for($n=1;$n<=4;$n++)<option value="{{ $n }}">{{ $n }} kotak</option>@endfor</select></label>@endif
<h3>Program A</h3><pre class="code-sample" x-text="program(definition.a)"></pre>
@if($definition['b'])<h3>Program B</h3><pre class="code-sample" x-text="program(definition.b)"></pre>@endif
<button class="primary full" @click="experiment()" x-text="busy ? 'Memeriksa…' : 'Uji pernyataan'">Uji pernyataan</button></fieldset>
<p class="small muted">Ubah arah atau jarak untuk percobaan baru. Eksperimen ini tidak mengurangi skor Maze.</p></section>
<section class="card logic-result"><h2>2. Amati hasil</h2><p x-show="!result">Jalankan eksperimen untuk melihat jejak, posisi, dan arah akhir.</p><canvas x-ref="board" width="640" height="480" aria-label="Papan eksperimen. Hasil posisi dan arah juga tersedia dalam tabel di bawah."></canvas>
<div role="status" aria-live="polite" class="notice" x-show="message" x-text="message"></div>
<div x-show="result" x-cloak><p class="small">● A: hijau · B: jingga · ○ titik awal. Panah menunjukkan arah akhir; angka pada garis adalah nomor instruksi.</p><div class="table-scroll"><table><thead><tr><th>Program</th><th>Posisi akhir</th><th>Arah akhir</th></tr></thead><tbody><template x-for="row in observations" :key="row.name"><tr><td x-text="row.name"></td><td x-text="'('+row.x+', '+row.y+')'"></td><td x-text="direction(row.angle)"></td></tr></template></tbody></table></div>
<details><summary>Lihat hasil setiap instruksi</summary><template x-for="row in observations" :key="row.name"><div><h3 x-text="'Program '+row.name"></h3><template x-for="(step,index) in row.steps" :key="index"><p class="small" x-text="step.code+' → ('+step.x+', '+step.y+'), '+direction(step.angle)"></p></template></div></template></details></div></section></div>
<section class="card logic-answer" x-show="result && result.counterexample" x-cloak><h2>3. Mengapa pernyataan terbantahkan?</h2><fieldset :disabled="busy || correct"><legend>Pilih alasan yang sesuai dengan hasil percobaanmu.</legend>@foreach($definition['options'] as $index=>$option)<label class="logic-option"><input type="radio" x-model="answer" value="{{ $index }}" name="reason">{{ $option }}</label>@endforeach</fieldset><button class="primary" @click="submit()" :disabled="busy || answer==='' || correct">Periksa alasan</button><p role="status" class="notice" x-show="answerMessage" x-text="answerMessage"></p><div x-show="correct"><a class="secondary" href="{{ $challenge<6 ? '/uji-logika/'.($challenge+1) : '/uji-logika' }}">{{ $challenge<6 ? 'Tantangan berikutnya →' : 'Lihat semua tantangan →' }}</a></div></section>
</div>
@endsection
@push('scripts')
<script type="application/json" id="logic-data">@json(['id'=>$challenge,'definition'=>$definition,'completed'=>$completed], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)</script>
@endpush
