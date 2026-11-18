@extends('layouts.app')
@section('title', $lesson->title)
@section('content')
    <a class="back-link" href="/modules">← Kembali ke modules</a>
    <div class="page-heading">
        <div>
            <span class="eyebrow">MODULE 01 / LESSON 0{{ $lesson->id }}</span>
            <h1>{{ $lesson->title }}</h1>
            <p>{{ $lesson->description }}</p>
        </div>
        <span class="tag">+20 XP saat selesai</span>
    </div>
    <div class="learning-layout">
        <x-card class="lesson-detail"
            ><span class="eyebrow">KENALI PERINTAHNYA</span>
            <h2><code>{{ $lesson->command }}</code></h2>
            <p>{{ $lesson->explanation }}</p>
            <h3>Coba baca dari atas ke bawah</h3>
            <pre class="code-sample">{{ $lesson->example }}</pre>
            <div class="notice">
                Jarak menggunakan unit. Sudut menggunakan derajat. Gerakan mengikuti arah hadap saat ini.
            </div>
            <h3>Langkah belajarmu</h3>
            <ol class="steps">
                <li>Baca arah awal Shiha: menghadap timur (kanan).</li>
                <li>Perkirakan hasil setiap baris kode.</li>
                <li>Jalankan di Maze dan bandingkan hasilnya.</li>
            </ol>
            <form method="POST" action="/modules/{{ $lesson->id }}/complete">
                @csrf
                <button class="primary">
                    {{ auth()->user()->lessons()->where('lessons.id',$lesson->id)->exists()?'✓ Materi sudah selesai':'Saya sudah memahami materi' }}
                </button>
            </form></x-card>
        <div>
            <x-card class="visual-lesson"
                ><span class="eyebrow">BAYANGKAN GERAKANNYA</span>
                <svg viewBox="0 0 360 200" role="img" aria-label="Diagram gerakan: {{ $lesson->explanation }}">
                    <defs>
                        <marker id="arrow" markerWidth="8" markerHeight="8" refX="5" refY="3" orient="auto">
                            <path d="M0 0 6 3 0 6" fill="none" stroke="#28755a" />
                        </marker>
                    </defs>
                    <path
                        d="{{ [1=>'M60 130H290',2=>'M60 100H290M290 140H190',3=>'M60 150H220V50',4=>'M60 60H220V160',5=>'M60 120H170',6=>'M60 150H220V50'][$lesson->id] }}"
                        fill="none"
                        stroke="#28755a"
                        stroke-width="4"
                        marker-end="url(#arrow)"
                    />
                    @if ($lesson->id === 5)
                        <path
                            d="M170 120H290"
                            stroke="#28755a"
                            stroke-width="4"
                            stroke-dasharray="7"
                            marker-end="url(#arrow)"
                        />
                    @endif
                    <circle
                        cx="60"
                        cy="{{ $lesson->id===4?60:($lesson->id===3||$lesson->id===6?150:($lesson->id===2?100:($lesson->id===5?120:130))) }}"
                        r="9"
                        fill="#df9a58"
                    />
                    <text x="40" y="190" fill="#547264" font-size="12">MULAI</text>
                </svg>
                <p>{{ $lesson->explanation }}</p></x-card
            ><a href="/maze/{{ $lesson->practice_level }}" class="primary full mt-4"
                >Latihan di Chapter {{ $lesson->practice_level }} →</a>
            <p class="small muted mt-4">Chapter berikutnya terbuka setelah chapter sebelumnya selesai.</p>
        </div>
    </div>
@endsection
