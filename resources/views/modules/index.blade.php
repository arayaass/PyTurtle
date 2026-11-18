@extends('layouts.app')
@section('title', 'Python Turtle Basics')
@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">BELAJAR DARI DASAR</span>
            <h1>Mulai dari satu langkah.</h1>
            <p>Kenali Turtle, pahami polanya, lalu bantu Shiha menjelajah.</p>
        </div>
        <span class="tag">{{ count($done) }} / {{ $lessons->count() }} materi selesai</span>
    </div>
    <section class="module-banner turtle-module-banner">
        <div>
            <span class="eyebrow">MODULE 01 · RUNESTONE BAB 4</span>
            <h2>Python Turtle Basics</h2>
            <p>{{ $lessons->count() }} materi: dari program pertama hingga pengulangan dan latihan menggambar.</p>
        </div>
        <a
            class="secondary"
            href="https://runestone.academy/ns/books/published/thinkcspy/PythonTurtle/toctree.html"
            target="_blank"
            rel="noopener noreferrer"
        >Buka referensi ↗</a>
    </section>
    @if ($lessons->isNotEmpty())
        <div class="turtle-course-intro">
            <p>
                Pasangkan empat keterangan dengan kode, lalu jawab pertanyaan untuk membuka materi berikutnya. Kamu
                tetap bisa membaca materi yang sudah terbuka.
            </p>
            <p>
                <b>Contoh Python lengkap</b> dapat dicoba di Runestone atau di Python pada komputer.
                <b>Editor gerak PyTurtle</b> dapat menjalankan perintah dasar Turtle, variabel angka, dan pengulangan
                for dengan range(jumlah).
            </p>
        </div>
        <div class="lesson-grid">
            @foreach ($lessons as $lesson)
                @php($content = $lesson->learning_content)
                @php($completed = in_array($lesson->id, $done, true))
                @php($canOpen = in_array($lesson->id, $unlocked, true))
                @if ($canOpen)
                    <a class="card lesson-card turtle-lesson-card" href="{{ route('modules.show', $lesson) }}">
                @else
                    <div class="card lesson-card turtle-lesson-card is-locked" aria-disabled="true">
                @endif
                <div class="spread">
                    <span class="lesson-index">{{ sprintf('%02d', $loop->iteration) }}</span>
                    <span class="small {{ $completed ? 'accent' : 'muted' }}">
                        @if ($canOpen)
                            {{ $completed ? '✓ Selesai' : '' }}
                        @else
                            🔒 Terkunci
                        @endif
                    </span>
                </div>
                <h3>{{ $lesson->title }}</h3>
                <code>{{ $lesson->command }}</code>
                <p>{{ $lesson->description }}</p>
                <div class="spread">
                    <span class="text-link">
                        @if ($canOpen)
                            {{ $completed ? 'Baca kembali →' : 'Pelajari materi →' }}
                        @else
                            Selesaikan materi sebelumnya
                        @endif
                    </span>
                    <span class="small muted">Bagian {{ $content['source_section'] }}</span>
                </div>
                @if ($canOpen)
                </a>
    @else
        </div>
    @endif
    @endforeach
    </div>
    @else
    <div class="card turtle-empty">
        <h2>Materi sedang disiapkan</h2>
        <p>Materi belum tersedia. Hubungi pengelola untuk mengaktifkan paket pembelajaran.</p>
    </div>
    @endif
    <div class="reference-note">
        <b>Sumber pembelajaran</b>
        <p>
            <i>How to Think Like a Computer Scientist: Interactive Edition</i>, Brad Miller dan David Ranum — Bab 4,
            Python Turtle Graphics, Runestone Academy. Penjelasan, contoh, dan latihan dalam PyTurtle disusun ulang
            dalam bahasa Indonesia; tautan bagian sumber tersedia di setiap materi.
        </p>
        <p>
            <a
                href="https://runestone.academy/ns/books/published/thinkcspy/PythonTurtle/toctree.html"
                target="_blank"
                rel="noopener noreferrer"
            >Baca bab sumber ↗</a>
        </p>
    </div>
@endsection
