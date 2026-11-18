@extends('layouts.app')
@section('title', 'Home')
@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">SELAMAT DATANG KEMBALI</span>
            <h1>Hello, {{ $user->display_name ?? $user->name }}<span class="accent">.</span></h1>
            <p>Sedikit belajar hari ini, selangkah lebih jauh esok hari.</p>
        </div>
        <span class="date-label">Jurnal penjelajah / {{ now()->format('d.m.Y') }}</span>
    </div>
    <section class="adventure-hero">
        <div class="hero-copy">
            <span class="tag">PERJALANAN BERSAMA SHIHA</span>
            <h2>Hutan menyimpan cerita.<br />Kodemu membuka jalan.</h2>
            <p>
                Peta Kakek membawa Shiha ke tempat yang belum pernah ia jelajahi. Bantu ia menemukan jalan, satu
                perintah Python pada satu waktu.
            </p>
            <a class="primary" href="/game/continue"
                >{{ $completed ? 'Lanjutkan petualangan' : 'Mulai petualangan' }} <span>→</span></a
            ><span class="hero-note">5 chapter • Belajar sambil bermain</span>
        </div>
        <div class="hero-landscape">
            <img
                src="/assets/nature/forest-daylight.svg"
                alt="Shiha menjelajahi hutan cerah dengan sungai dan jembatan"
            />
        </div>
    </section>
    <section class="stats-strip">
        <div>
            <span class="stat-label">LEVEL PENJELAJAH</span
            ><strong
                >{{ 1+intdiv($user->xp,100) }} <small>Penjelajah {{ $user->xp>=300?'andal':'pemula' }}</small></strong>
        </div>
        <div>
            <span class="stat-label">TOTAL EXPERIENCE</span><strong>{{ $user->xp }} <small>XP terkumpul</small></strong>
        </div>
        <div class="stat-progress">
            <div class="spread">
                <span class="stat-label">PROGRES PETUALANGAN</span><b>{{ $completed }}/5 chapter</b>
            </div>
            <x-progress-bar :value="$completed / 5 * 100" />
        </div>
    </section>
    <div class="section-heading">
        <div>
            <span class="eyebrow">RUANG BELAJARMU</span>
            <h2>Mau menjelajah ke mana?</h2>
        </div>
        <span class="muted">Pilih langkah kecilmu hari ini.</span>
    </div>
    @php($completedModules = app(\App\Services\LessonAccessService::class)->completedCount($user))
    <div class="notice">
        <b>{{ $completedModules }}/11 materi modul selesai</b>
        <p>
            Code Matching tersedia di setiap modul. Modul 4: Maze level 1–2 · Modul 8: Maze level 3 · Modul 11: Maze
            level 4–5. Selesaikan Code Matching dan evaluasi untuk membuka modul berikutnya.
        </p>
    </div>
    <div class="feature-grid">
        @foreach ([['01', 'Python Turtle Basics', 'Kenali perintah, pahami gerakan, lalu coba sendiri.', $lessons->count().' materi singkat', '/modules', 'mint'], ['02', 'Live Code Maze', 'Ubah baris kode menjadi langkah petualangan Shiha.', '5 chapter cerita', '/maze', 'sand'], ['03', 'Leaderboard', 'Lihat perjalananmu di antara penjelajah lainnya.', 'Tumbuh bersama', '/leaderboard', 'blue']] as [$no,$title,$desc,$meta,$href,$color])
            <a href="{{ $href }}" class="card feature-card"
                ><div class="feature-top">
                    <span class="feature-number {{ $color }}">{{ $no }}</span><span aria-hidden="true">↗</span>
                </div>
                <h3>{{ $title }}</h3>
                <p>{{ $desc }}</p>
                <span
                    class="small muted"
                    >{{ $href === '/maze' && $completedModules < 4 ? '🔒 Selesaikan 4 materi pertama' : $meta }}</span
                ></a>
        @endforeach
    </div>
    <div class="section-heading">
        <div>
            <span class="eyebrow">SATU LANGKAH LAGI</span>
            <h2>Continue learning</h2>
        </div>
        <a href="/modules" class="text-link">Semua materi →</a>
    </div>
    @php($next=app(\App\Services\LessonAccessService::class)->forUser($user)['requiredLesson'] ?? $lessons->first())
    @if ($next)
        <a class="card continue-card" href="/modules/{{ $next->id }}"
            ><span class="lesson-index">{{ sprintf('%02d', $next->sort_order ?? $next->id) }}</span>
            <div>
                <span class="eyebrow">PYTHON TURTLE BASICS</span>
                <h3>{{ $next->title }}</h3>
                <p>{{ $next->command }} · {{ $next->learning_content['minutes'] ?? 5 }} menit belajar</p>
            </div>
            <span class="primary">Buka materi →</span></a>
    @endif
@endsection
