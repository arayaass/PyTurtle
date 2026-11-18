@extends('layouts.app')
@section('title','Uji Logika')
@section('content')
<div class="page-heading"><div><span class="eyebrow">COBA · AMATI · BUKTIKAN</span><h1>Uji Logika</h1><p>Apakah sebuah pernyataan selalu benar? Temukan satu kondisi yang membuatnya salah.</p></div><span class="tag">{{ count($done) }} / {{ count($challenges) }} selesai</span></div>
<div class="notice">Pilih tantangan → ubah kondisi awal → jalankan eksperimen → jelaskan bantahanmu. Percobaan bebas diulang tanpa penalti. Progress disimpan terpisah dari XP Maze.</div>
<div class="logic-cards">@foreach($challenges as $id=>$item)<a class="card logic-card" href="/uji-logika/{{ $id }}"><span class="eyebrow">TANTANGAN {{ $id }}</span><h2>{{ $item['title'] }}</h2><p>{{ $item['claim'] }}</p><span class="tag">{{ in_array($id,$done) ? '✓ Selesai · coba lagi' : 'Mulai eksperimen →' }}</span></a>@endforeach</div>
@endsection
