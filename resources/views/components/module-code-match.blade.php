<section
    id="module-code-match"
    class="card module-code-match"
    aria-labelledby="module-match-heading"
    x-data="codeMatch(@js($lesson->id), @js($matchingPairs), @js(route('modules.code-match', $lesson)), @js($matchingResult?->only(['accuracy'])))"
    x-init="boot()"
>
    <div class="page-heading">
        <div>
            <span class="eyebrow">LATIHAN PASANGKAN KODE</span>
            <h2 id="module-match-heading">Pasangkan kode · {{ $lesson->title }}</h2>
            <p>Baca satu keterangan di kiri, lalu pilih kode di kanan yang menghasilkan hal tersebut.</p>
        </div>
        <div class="match-stats">
            <div><small>NILAI</small><b x-text="matched.length * 25">0</b></div>
            <div><small>KETEPATAN</small><b x-text="accuracy + '%'">0%</b></div>
            <button class="secondary" @click="reset()">↻ Ulangi</button>
        </div>
    </div>
    <p class="small module-requirements">
        <span x-text="matchingCompleted ? 'Pasangan kode: selesai' : 'Pasangan kode: belum selesai'"></span> &middot;
        <span x-text="quizPassed ? 'Pertanyaan: selesai' : 'Pertanyaan: belum selesai'"></span>
    </p>
    <p class="small muted">
        Cocokkan keempat pasangan dan jawab pertanyaan dengan benar untuk membuka modul berikutnya. Hasil latihan
        disimpan sebelum modul berikutnya terbuka.
    </p>
    <div class="match-instructions">
        <span class="step-circle">1</span><span>Pilih satu keterangan</span><span class="muted">→</span
        ><span class="step-circle">2</span><span>Pilih kode yang cocok</span
        ><span class="match-count" x-text="matched.length + ' / 4 pasangan cocok'"></span>
    </div>
    <div class="match-grid">
        <section>
            <div class="column-heading"><span>DESKRIPSI</span><span>A — D</span></div>
            <div class="match-column">
                <template x-for="pair in pairs" :key="pair.id"
                    ><button
                        class="match-card"
                        :class="{
                            selected: selected === pair.id,
                            matched: matched.includes(pair.id),
                            wrong: wrongId === pair.id,
                        }"
                        :disabled="matched.includes(pair.id)"
                        :aria-pressed="selected === pair.id"
                        @click="selected = pair.id"
                    >
                        <span
                            class="pair-letter"
                            x-text="matched.includes(pair.id) ? '✓' : pair.id.toUpperCase()"
                        ></span
                        ><span
                            ><b x-text="pair.title"></b><span class="match-desc" x-text="pair.description"></span
                            ><small x-show="matched.includes(pair.id)">Pasangan benar</small></span>
                    </button></template>
            </div>
        </section>
        <section>
            <div class="column-heading"><span>KODE PYTHON</span><span>01 — 04</span></div>
            <div class="match-column">
                <template x-for="(pair, i) in snippets" :key="pair.id"
                    ><button
                        class="match-card snippet"
                        :data-pair-id="pair.id"
                        :class="{ matched: matched.includes(pair.id), wrong: wrongSnippet === pair.id }"
                        :disabled="matched.includes(pair.id)"
                        @click="choose(pair.id)"
                    >
                        <span
                            class="pair-letter"
                            x-text="matched.includes(pair.id) ? '✓' : String(i + 1).padStart(2, '0')"
                        ></span>
                        <pre x-text="pair.code"></pre>
                        <span class="sr-only" x-show="matched.includes(pair.id)">Pasangan benar</span>
                    </button></template>
            </div>
        </section>
    </div>
    <div class="match-feedback" :class="matched.length === 4 ? 'success' : ''" role="status">
        <b x-text="feedback"></b><span x-text="saveStatus"></span>
    </div>
    <p class="small muted mt-6 text-center">
        Jika belum cocok, baca lagi keterangan dan kode, lalu coba pasangan lain.
    </p>
</section>
