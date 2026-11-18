@extends('layouts.app')
@section('title', $level->title)
@section('content')
    <div id="maze-app" x-data="mazeGame" x-init="boot()">
        <div class="breadcrumb"><a href="/maze">Maze Quest</a><span>/</span><span>Chapter 0{{ $level->id }}</span></div>
        <div class="page-heading compact">
            <div>
                <span class="eyebrow">CHAPTER 0{{ $level->id }} / 05</span>
                <h1>{{ $level->title }}</h1>
                <p>{{ $level->definition['subtitle'] }}</p>
            </div>
            <div class="save-status" role="status" x-text="saveStatus"></div>
        </div>
        <section class="card maze-mission" aria-label="Misi level">
            <div class="objective">
                <span class="eyebrow">MISIMU HARI INI</span>
                <h3>Bantu Shiha mencapai tujuan</h3>
                <p>{{ $definition['objective'] }}</p>
            </div>
        </section>
        <details class="card maze-instructions">
            <summary>Instruksi &amp; contoh kode <span>Buka untuk melihat tutorial dan panduan</span></summary>
            <div class="maze-grid-guide">
                <b>Bekal penjelajah: variabel</b
                ><span>Simpan jarak atau sudut dalam variabel, lalu gunakan dalam perintah gerak.</span
                ><code>langkah = 3 &nbsp; → &nbsp; forward(langkah)</code
                ><span>1 kotak = 1 langkah · Awal menghadap timur →</span>
                @if ($definition['recommend_loop'] ?? false)
                    <b>Tantangan pola</b
                    ><span
                        >Coba gunakan <code>for i in range(jumlah):</code> untuk merangkum bagian jalan yang
                        berulang.</span>
                @endif
            </div>
            <div class="command-list">
                @foreach ($definition['editor_commands'] as $command)
                    <x-command-pill :command="$command" />
                @endforeach
            </div>
            <details class="maze-library" open>
                <summary>Pustaka kode · variabel dan perulangan</summary>
                <p>
                    Pilih contoh untuk menyisipkannya di posisi kursor. Ganti nama dan nilainya sesuai rute. Editor juga
                    menyarankan nama variabel yang kamu tulis.
                </p>
                <div class="library-grid">
                    @foreach (['Variabel jarak' => "langkah = 3\nforward(langkah)", 'Variabel sudut' => "sudut = 90\nleft(sudut)", 'Perulangan for' => "for i in range(3):\n    forward(1)", 'Perulangan dengan variabel' => "jumlah = 3\nfor i in range(jumlah):\n    forward(1)"] as $label => $snippet)
                        <button
                            class="secondary library-example"
                            type="button"
                            @click="insertCode(@js($snippet))"
                            :disabled="running || syncing || runCompleted || nextTutorial"
                        >
                            <b>{{ $label }}</b>
                            <pre>{{ $snippet }}</pre>
                        </button>
                    @endforeach
                </div>
                <p class="small muted">
                    Isi blok perulangan harus menjorok 4 spasi. Tekan Ctrl + Space untuk membuka saran kode.
                </p>
            </details>
            @if ($level->id === 1)
                <div class="tutorial-progress">
                    <span class="eyebrow">TUTORIAL {{ $definition['tutorial_stage']+1 }} / 3</span>
                    <h3>{{ $definition['tutorial_title'] }}</h3>
                    <div class="tutorial-steps">
                        @foreach (['Maju', 'Maju + kiri', 'Maju + kiri + kanan'] as $i => $label)
                            <span class="{{ $i===$definition['tutorial_stage']?'active':'' }}">{{ $i+1 }}. {{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </details>
        <div class="maze-layout">
            <section class="card editor-panel">
                <x-code-editor />
                <div class="editor-toolbar">
                    <button
                        class="primary"
                        @click="run()"
                        :disabled="running || syncing || runCompleted || nextTutorial"
                    >
                        <span aria-hidden="true">▷</span>
                        <span x-text="running ? 'Menjalankan…' : 'Run Code'"></span></button
                    ><button class="secondary" @click="reset()" :disabled="running || syncing || nextTutorial">
                        Reset Code
                    </button>
                </div>
                <div class="editor-foot">
                    <span>Ctrl / ⌘ + Enter untuk menjalankan</span
                    ><label
                        >Kecepatan
                        <select x-model.number="speed" :disabled="running">
                            <option value="1">1×</option>
                            <option value="2">2×</option>
                            <option value="4">4×</option>
                        </select></label>
                </div>
                <p class="small muted hint-note">
                    Petunjuk bantuan muncul setelah tiga percobaan yang belum berhasil. Setiap Run dimulai dari titik
                    awal. Skor terbaik tetap tersimpan.
                </p>
            </section>
            <section class="card world-panel">
                <div class="world-top">
                    <span
                        ><span class="live-dot"></span> Hutan Kenangan
                        <span class="maze-grid-badge">1 kotak = 1 langkah</span></span
                    ><span x-text="checkpoint+' / {{ count($definition['checkpoints']) }} checkpoint'"></span>
                </div>
                <canvas
                    id="game-canvas"
                    width="600"
                    height="460"
                    aria-label="Peta hutan. {{ $definition['objective'] }}"
                >Bantu Shiha mengikuti penanda pada peta secara berurutan.</canvas>
                <div class="world-legend">
                    <span><i class="legend-dot start"></i> Shiha</span
                    ><span><i class="legend-dot check"></i> Checkpoint</span
                    ><span><i class="legend-dot goal"></i> Tujuan</span><span>Awal: timur →</span>
                </div>
                <div class="feedback" :class="result.toLowerCase()" role="status" aria-live="polite">
                    <b
                        x-text="
                            result === 'SUCCESS'
                                ? '✓ Challenge berhasil!'
                                : result === 'ERROR'
                                  ? 'Periksa kodemu'
                                  : result === 'PARTIAL'
                                    ? 'Terus mencoba'
                                    : 'Siap menjelajah?'
                        "
                    ></b>
                    <p x-text="message"></p>
                    <span x-show="result === 'SUCCESS' && ! nextTutorial" x-text="'Skor: ' + score + ' / 100'"></span>
                </div>
                @if ($level->id === 5)
                    <div class="notice" x-show="saved && result === 'SUCCESS'" x-cloak>
                        <p x-text="rewardMessage"></p>
                        <span x-show="hasBadge"><x-turtle-badge /></span>
                    </div>
                @endif
                @if ($level->id === 5)
                    <x-final-reward />
                @endif
                <div class="completion-actions" x-show="result === 'SUCCESS'" x-cloak>
                    <button class="primary" x-show="nextTutorial && saved" @click="nextStage()">
                        Sublevel berikutnya →</button
                    ><button class="secondary" x-show="! nextTutorial" @click="showOutro()">Baca cerita →</button>
                    @if ($level->id < 5)
                        <a class="primary" href="/game/continue" x-show="saved && ! nextTutorial"
                            >Chapter berikutnya →</a>
                    @else
                        <a class="primary" href="/progress" x-show="saved">Lihat pencapaian →</a>
                    @endif
                </div>
            </section>
        </div>
        <div
            class="hint-modal"
            x-show="hintOpen"
            x-cloak
            role="dialog"
            aria-modal="true"
            aria-label="Bantuan perjalanan"
            x-trap.inert.noscroll="hintOpen"
            @keydown.escape.window="hintOpen = false"
        >
            <section class="card">
                <span class="eyebrow">AYO COBA LAGI</span>
                <h2>Shiha punya petunjuk untukmu</h2>
                <p>Tak apa jika belum berhasil. Perhatikan pola berikut, lalu perbaiki kodemu.</p>
                <pre x-text="hintText"></pre>
                <button class="primary" @click="hintOpen = false">Mengerti, coba lagi</button>
            </section>
        </div>
        <x-dialog />
    </div>
@endsection
@push('scripts')
    @php($mazeData=['level'=>$definition,'progress'=>$progress,'story'=>$story,'settings'=>$settings])
    <script type="application/json" id="maze-data">
        @json($mazeData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    </script>
@endpush
