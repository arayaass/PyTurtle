@extends('layouts.app')
@section('title', $lesson->title)
@section('content')
    @php($content = $lesson->learning_content)
    @php($practice = $content['practice'] ?? ['code' => "forward(80)\nleft(90)\nforward(60)", 'instruction' => 'Ubah jarak atau arah pada contoh, lalu jalankan untuk melihat pengaruhnya.'])
    <div
        x-data="{ lessonCompleted: @js($isCompleted), quizPassed: @js(in_array($lesson->id, $quizDone, true)), matchingCompleted: @js(in_array($lesson->id, $matchingDone, true)), completedLessons: @js(count($done)), unlockedLessons: @js($unlocked) }"
        @lesson-completed.window="if ($event.detail.lessonId === {{ $lesson->id }}) { lessonCompleted = $event.detail.completed === true; quizPassed = $event.detail.quizPassed === true; matchingCompleted = $event.detail.matchingCompleted === true; completedLessons = $event.detail.completedLessons; if (Array.isArray($event.detail.unlockedLessons)) unlockedLessons = $event.detail.unlockedLessons; }"
    >
        @if (session('lesson_locked'))
            <div class="notice turtle-lock-notice" role="status">{{ session('lesson_locked') }}</div>
        @endif
        <div class="page-heading turtle-lesson-heading">
            <div>
                <span class="eyebrow">MODUL 01 · BAGIAN {{ $content['source_section'] }}</span>
                <h1>{{ $lesson->title }}</h1>
                <p>{{ $lesson->description }}</p>
            </div>
            <div class="turtle-lesson-meta">
                <span class="tag">{{ $content['minutes'] }} menit · +20 XP</span
                ><span class="tag turtle-completed-tag" x-show="lessonCompleted" x-cloak>✓ Materi selesai</span>
            </div>
        </div>
        <div class="turtle-course-layout">
            <aside class="card turtle-course-nav" aria-label="Daftar materi Python Turtle Basics">
                <div class="turtle-nav-title">
                    <span class="eyebrow">PERJALANAN BELAJAR</span
                    ><b
                        ><span x-text="completedLessons">{{ count($done) }}</span>
                        dari {{ $lessons->count() }} selesai</b>
                </div>
                <nav>
                    <ol>
                        @foreach ($lessons as $item)
                            <li>
                                <a
                                    role="link"
                                    @if (in_array($item->id, $unlocked, true)) href="{{ route('modules.show', $item) }}" @endif
                                    :href="unlockedLessons.includes({{ $item->id }}) ? @js(route('modules.show', $item)) : null"
                                    :class="{ 'is-locked': ! unlockedLessons.includes({{ $item->id }}) }"
                                    :aria-disabled="! unlockedLessons.includes({{ $item->id }})"
                                    :tabindex="unlockedLessons.includes({{ $item->id }}) ? 0 : -1"
                                    @click="if (! unlockedLessons.includes({{ $item->id }})) $event.preventDefault()"
                                    @if (! in_array($item->id, $unlocked, true)) class="is-locked" aria-disabled="true" tabindex="-1" @endif
                                    @if ($item->id === $lesson->id) aria-current="page" @endif
                                >
                                    <span
                                        class="turtle-nav-lock"
                                        x-show="! unlockedLessons.includes({{ $item->id }})"
                                        @if (in_array($item->id, $unlocked, true)) style="display: none" @endif
                                        aria-label="Terkunci"
                                    >🔒</span>
                                    <span class="turtle-nav-number">{{ sprintf('%02d', $loop->iteration) }}</span
                                    ><span>{{ $item->title }}</span>
                                    @if ($item->id === $lesson->id)
                                        <span
                                            class="turtle-nav-check"
                                            aria-label="Selesai"
                                            x-show="lessonCompleted"
                                            x-cloak
                                        >✓</span>
                                    @endif
                                    @if ($item->id !== $lesson->id && in_array($item->id, $done, true))
                                        <span class="turtle-nav-check" aria-label="Selesai">✓</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
                <p class="turtle-nav-lock-help">
                    Pasangkan empat keterangan dengan kode dan jawab pertanyaan dengan benar untuk membuka materi
                    berikutnya.
                </p>
            </aside>
            <article class="turtle-lesson-body">
                <section class="card turtle-reading-card">
                    <span class="eyebrow">SETELAH BELAJAR, KAMU BISA</span>
                    <ul class="turtle-goals">
                        @foreach ($content['goals'] as $goal)
                            <li>{{ $goal }}</li>
                        @endforeach
                    </ul>
                    @foreach ($content['sections'] as $section)
                        <section class="turtle-text-section">
                            <h2>{{ $section['title'] }}</h2>
                            @foreach ($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                            @if (isset($section['bullets']))
                                <ul class="turtle-bullets">
                                    @foreach ($section['bullets'] as $bullet)
                                        <li>{{ $bullet }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (isset($section['table']))
                                <div class="table-scroll">
                                    <table class="turtle-reference-table">
                                        <caption class="sr-only">
                                            {{ $section['title'] }}
                                        </caption>
                                        <thead>
                                            <tr>
                                                @foreach ($section['table']['headers'] as $header)
                                                    <th scope="col">{{ $header }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($section['table']['rows'] as $row)
                                                <tr>
                                                    @foreach ($row as $cell)
                                                        <td>{{ $cell }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </section>
                @foreach ($content['examples'] as $example)
                    <section class="card turtle-example-card" x-data="lessonTools">
                        <div class="turtle-example-heading">
                            <div>
                                <span class="eyebrow">CONTOH PYTHON LENGKAP</span>
                                <h2>{{ $example['title'] }}</h2>
                            </div>
                            <div class="turtle-small-actions">
                                <button
                                    class="secondary"
                                    type="button"
                                    @click="copyCode($refs.exampleCode.textContent)"
                                >
                                    Salin kode
                                </button>
                                <button
                                    class="secondary"
                                    type="button"
                                    @click="downloadCode($refs.exampleCode.textContent, 'pyturtle-{{ $content['source_section'] }}-{{ $loop->iteration }}.py')"
                                >
                                    Unduh .py
                                </button>
                            </div>
                        </div>
                        <pre class="code-sample turtle-python-example"><code x-ref="exampleCode">{{ $example['code'] }}</code></pre>
                        <p>{{ $example['explanation'] }}</p>
                        <p
                            class="small accent turtle-copy-message"
                            role="status"
                            aria-live="polite"
                            x-text="copyMessage"
                        ></p>
                    </section>
                @endforeach
                <div class="turtle-run-help">
                    <p>
                        Untuk menjalankan contoh lengkap, buka bagian sumber di Runestone dan gunakan kotak kode yang
                        tersedia. Kamu juga bisa mengunduh file .py untuk Python di komputer.
                    </p>
                    <a class="text-link" href="{{ $content['source_url'] }}" target="_blank" rel="noopener noreferrer"
                        >Coba di Runestone ↗</a>
                </div>
                <section class="card turtle-practice-card" x-data="turtlePractice(@js($practice['code']))">
                    <div class="turtle-animation-heading">
                        <div>
                            <span class="eyebrow">COBA GERAKANNYA DI SINI</span>
                            <h2>Lihat Turtle bergerak mengikuti kodemu.</h2>
                        </div>
                        <span
                            class="turtle-animation-badge"
                            :class="{ 'is-running': running }"
                            x-text="
                                running ? '● Bergerak' : paused ? 'Ⅱ Dijeda' : error ? 'Periksa kode' : 'Siap mencoba'
                            "
                        >Siap mencoba</span>
                    </div>
                    <p>{{ $practice['instruction'] }}</p>
                    <p class="turtle-practice-help">
                        Coba forward(), backward(), left(), right(), penup(), dan pendown(). Editor ini juga mendukung
                        variabel angka serta for dengan range(jumlah). Untuk mencoba print(), daftar nama, beberapa
                        objek Turtle, warna, atau metode lain, jalankan contoh Python lengkap di Runestone.
                    </p>
                    <div class="turtle-animation-options">
                        <label class="turtle-auto-option"
                            ><input type="checkbox" x-model="autoRun" @change="autoRunChanged($event.target.checked)" />
                            Jalankan otomatis setelah kode diedit</label>
                        <label class="turtle-speed-option" for="turtle-speed"
                            >Kecepatan
                            <select id="turtle-speed" x-model.number="speed">
                                <option value="0.5">Pelan · 0,5×</option>
                                <option value="1">Normal · 1×</option>
                                <option value="2">Cepat · 2×</option>
                                <option value="4">Sangat cepat · 4×</option>
                            </select>
                        </label>
                    </div>
                    <div class="turtle-practice-grid">
                        <div class="turtle-practice-editor">
                            <label for="turtle-practice-code">Kode gerakan <span class="turtle-shortcut">Ctrl + Enter untuk menjalankan</span></label>
                            <textarea
                                id="turtle-practice-code"
                                x-ref="code"
                                x-model="code"
                                @input="codeEdited($event.target.value)"
                                spellcheck="false"
                                maxlength="10000"
                                rows="10"
                                aria-describedby="turtle-practice-feedback"
                                @keydown.ctrl.enter.prevent="run()"
                            ></textarea>
                            <div class="turtle-command-focus">
                                <span x-text="activeLine ? 'BARIS ' + activeLine : 'PERINTAH AKTIF'"
                                    >PERINTAH AKTIF</span
                                ><code x-text="activeCommand || 'Menunggu gerakan…'">Menunggu gerakan…</code>
                            </div>
                            <div class="button-row turtle-animation-buttons">
                                <button
                                    type="button"
                                    class="primary"
                                    @click="run()"
                                    x-text="running || paused ? '↻ Jalankan ulang' : '▶ Jalankan'"
                                >
                                    ▶ Jalankan
                                </button>
                                <button
                                    type="button"
                                    class="secondary"
                                    @click="pauseOrResume()"
                                    :disabled="! running && ! paused"
                                    x-text="paused ? '▶ Lanjutkan' : 'Ⅱ Jeda'"
                                >
                                    Ⅱ Jeda
                                </button>
                                <button type="button" class="secondary" @click="stop()" :disabled="! running && ! paused">
                                    ■ Hentikan
                                </button>
                                <button type="button" class="secondary" @click="reset()">Ulangi contoh</button>
                            </div>
                        </div>
                        <div class="turtle-practice-result">
                            <canvas
                                x-ref="canvas"
                                width="640"
                                height="420"
                                role="img"
                                aria-label="Animasi Turtle mengikuti kode. Posisi awal 0, 0, menghadap timur."
                            ></canvas>
                            <div class="turtle-live-stats">
                                <span>Posisi <b x-text="position">(0, 0)</b></span
                                ><span>Arah <b x-text="heading + '°'">0°</b></span
                                ><span>Pena <b x-text="penDown ? 'Turun' : 'Terangkat'">Turun</b></span>
                            </div>
                            <div class="turtle-animation-progress">
                                <label for="turtle-progress"
                                    >Perintah selesai <span x-text="completed + ' / ' + total">0 / 0</span></label
                                ><progress id="turtle-progress" max="100" :value="progress" value="0">
                                    Progres gerakan
                                </progress>
                            </div>
                            <p class="small muted">
                                Titik emas adalah awal perjalanan. Turtle hijau menggambar sambil bergerak. Saat pena
                                terangkat, Turtle bergerak tanpa membuat garis.
                            </p>
                        </div>
                    </div>
                    <p
                        id="turtle-practice-feedback"
                        class="notice"
                        :class="error ? 'error' : 'success'"
                        role="status"
                        aria-live="polite"
                        x-text="feedback"
                    >
                        Jalankan kode untuk melihat Turtle bergerak.
                    </p>
                    <section
                        class="turtle-exercise-card turtle-inline-exercise"
                        aria-labelledby="turtle-exercise-heading"
                    >
                        <div>
                            <span class="eyebrow">LATIHAN MANDIRI</span>
                            <h2 id="turtle-exercise-heading">Coba langsung di editor.</h2>
                            <p>{{ $content['exercise']['task'] }}</p>
                        </div>
                        <button
                            type="button"
                            class="primary turtle-experiment-button"
                            @click="
                                $refs.code.focus();
                                $refs.code.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            "
                        >
                            Mulai eksperimen
                        </button>
                        <details>
                            <summary>Lihat petunjuk</summary>
                            <p>{{ $content['exercise']['hint'] }}</p>
                        </details>
                        @if (isset($content['exercise']['solution']))
                            <details>
                                <summary>Lihat contoh jawaban</summary>
                                <pre class="code-sample">{{ $content['exercise']['solution'] }}</pre>
                            </details>
                        @endif
                    </section>
                </section>
                @if (count($matchingPairs) === 4)
                    @include('components.module-code-match')
                @endif
                <section
                    id="turtle-quiz"
                    class="card turtle-quiz-card"
                    aria-labelledby="turtle-quiz-heading"
                    x-data="lessonQuiz(@js($isCompleted))"
                    @lesson-completed.window="if ($event.detail.lessonId === {{ $lesson->id }}) completed = $event.detail.completed === true"
                    @matching-finished.window="if ($event.detail.lesson_id === {{ $lesson->id }} && $event.detail.completed) showCompletion($event.detail)"
                >
                    <span class="eyebrow">CEK PEMAHAMAN</span>
                    <h2 id="turtle-quiz-heading">Satu pertanyaan sebelum lanjut.</h2>
                    <form
                        x-ref="quizForm"
                        method="POST"
                        action="/modules/{{ $lesson->id }}/complete"
                        @submit.prevent="checkAnswer($event.target)"
                        novalidate
                        :aria-busy="submitting"
                    >
                        @csrf
                        <fieldset :disabled="submitting">
                            <legend>{{ $content['quiz']['question'] }}</legend>
                            @foreach ($content['quiz']['options'] as $index => $option)
                                <label class="turtle-answer"
                                    ><input
                                        type="radio"
                                        name="answer"
                                        value="{{ $index }}"
                                        required
                                        @checked(old('answer') !== null && (int) old('answer') === $index)
                                    /><span>{{ $option }}</span></label>
                            @endforeach
                        </fieldset>
                        @error('answer')
                            <p class="notice error" role="alert">{{ $message }}</p>
                        @enderror
                        <button
                            class="primary"
                            type="submit"
                            :disabled="submitting"
                            x-text="submitting ? 'Memeriksa jawaban…' : 'Periksa jawaban'"
                        >
                            Periksa jawaban
                        </button>
                        <p class="turtle-quiz-completion" :class="{ 'is-completed': completed }">
                            <span
                                :style="{ visibility: completed ? 'hidden' : 'visible' }"
                                @if ($isCompleted) style="visibility: hidden" @endif
                                >Jawaban tepat memberi +20 XP satu kali. Pasangkan kode dan jawab pertanyaan dengan
                                benar untuk membuka materi berikutnya.</span>
                            <span
                                :style="{ visibility: completed ? 'visible' : 'hidden' }"
                                @if ($isCompleted === false) style="visibility: hidden" @endif
                                >✓ Kamu sudah menyelesaikan materi ini. Kamu bisa mencoba lagi; XP diberikan satu
                                kali.</span>
                        </p>
                    </form>
                    <dialog
                        x-ref="resultDialog"
                        class="turtle-result-dialog"
                        :class="'result-' + popupKind"
                        aria-labelledby="turtle-result-title"
                        aria-describedby="turtle-result-message"
                        @close="popupClosed()"
                    >
                        <div
                            class="turtle-result-icon"
                            aria-hidden="true"
                            x-text="popupKind === 'correct' ? '✓' : popupKind === 'wrong' ? '↻' : '!'"
                        >
                            !
                        </div>
                        <span class="eyebrow">HASIL EVALUASI</span>
                        <h2 id="turtle-result-title" x-text="popupTitle">Hasil jawaban</h2>
                        <p id="turtle-result-message" x-text="popupMessage"></p>
                        <p class="turtle-result-reward" x-text="popupReward"></p>
                        <button
                            x-ref="closeResult"
                            class="primary"
                            type="button"
                            @click="closePopup()"
                            x-text="popupButton"
                        >
                            Tutup
                        </button>
                    </dialog>
                </section>
                <section
                    class="notice module-completion-status"
                    aria-label="Syarat melanjutkan modul"
                    aria-live="polite"
                >
                    <b>Syarat untuk lanjut</b>
                    <p
                        x-text="
                            matchingCompleted
                                ? 'Pasangkan kode: selesai dan tersimpan'
                                : 'Pasangkan kode: cocokkan keempat keterangan dan tunggu hingga tersimpan'
                        "
                    >
                        {{ in_array($lesson->id, $matchingDone, true) ? 'Pasangkan kode: selesai dan tersimpan' : 'Pasangkan kode: cocokkan keempat keterangan dan tunggu hingga tersimpan' }}
                    </p>
                    <p
                        x-text="
                            quizPassed
                                ? 'Evaluasi: jawaban benar dan tersimpan'
                                : 'Evaluasi: jawab pertanyaan dengan benar'
                        "
                    >
                        {{ in_array($lesson->id, $quizDone, true) ? 'Evaluasi: jawaban benar dan tersimpan' : 'Evaluasi: jawab pertanyaan dengan benar' }}
                    </p>
                </section>
                <nav class="turtle-lesson-pagination" aria-label="Materi sebelum dan sesudah">
                    @if ($previous)
                        <a class="secondary" href="{{ route('modules.show', $previous) }}">← {{ $previous->title }}</a>
                    @else
                        <a class="secondary" href="{{ route('modules') }}">← Daftar materi</a>
                    @endif
                    @if ($next)
                        <a
                            class="primary"
                            role="link"
                            @if (in_array($next->id, $unlocked, true)) href="{{ route('modules.show', $next) }}" @endif
                            :href="unlockedLessons.includes({{ $next->id }}) ? @js(route('modules.show', $next)) : null"
                            :class="{ 'is-locked': ! unlockedLessons.includes({{ $next->id }}) }"
                            :aria-disabled="! unlockedLessons.includes({{ $next->id }})"
                            :tabindex="unlockedLessons.includes({{ $next->id }}) ? 0 : -1"
                            @click="if (! unlockedLessons.includes({{ $next->id }})) $event.preventDefault()"
                            @if (! in_array($next->id, $unlocked, true)) aria-disabled="true" tabindex="-1" @endif
                        >
                            <span
                                x-show="! unlockedLessons.includes({{ $next->id }})"
                                @if (in_array($next->id, $unlocked, true)) style="display: none" @endif
                                >🔒 Cocokkan kode dan jawab pertanyaan<br /></span
                            >{{ $next->title }} →
                        </a>
                    @else
                        <a
                            class="primary"
                            role="link"
                            @if ($isCompleted) href="{{ route('maze') }}" @else aria-disabled="true" tabindex="-1" @endif
                            :href="lessonCompleted && matchingCompleted && quizPassed ? @js(route('maze')) : null"
                            :class="{ 'is-locked': ! (lessonCompleted && matchingCompleted && quizPassed) }"
                            :aria-disabled="! (lessonCompleted && matchingCompleted && quizPassed)"
                            :tabindex="lessonCompleted && matchingCompleted && quizPassed ? 0 : -1"
                            @click="if (! (lessonCompleted && matchingCompleted && quizPassed)) $event.preventDefault();"
                        >
                            <span x-show="! lessonCompleted" @if ($isCompleted) style="display: none" @endif
                                >Cocokkan kode dan jawab pertanyaan<br /></span
                            >Mulai petualangan Maze →
                        </a>
                    @endif
                </nav>
                <div class="reference-note">
                    <b>Sumber materi · bagian {{ $content['source_section'] }}</b>
                    <p>
                        Materi ini disusun ulang dalam bahasa Indonesia dari Bab 4 Python Turtle Graphics karya Brad
                        Miller dan David Ranum di Runestone Academy. Contoh dan latihan disiapkan untuk PyTurtle.
                    </p>
                    <a href="{{ $content['source_url'] }}" target="_blank" rel="noopener noreferrer"
                        >Buka materi sumber ↗</a>
                </div>
            </article>
        </div>
    </div>
@endsection
