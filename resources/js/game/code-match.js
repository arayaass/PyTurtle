import { enqueue, localKey } from './systems/save-queue';
export default function codeMatch(lessonId, pairs, submitUrl, initialResult = null) {
    let saveId, wrongTimer;
    return {
        pairs: [],
        snippets: [],
        selected: null,
        matched: [],
        attempts: [],
        wrongId: null,
        wrongSnippet: null,
        feedback: 'Pilih satu keterangan di sebelah kiri untuk mulai.',
        saveStatus: '',
        savedAccuracy: null,
        get accuracy() {
            if (this.savedAccuracy !== null) return Math.round(Number(this.savedAccuracy));
            return this.attempts.length
                ? Math.round((this.matched.length / this.attempts.length) * 100)
                : 0;
        },
        boot() {
            this.pairs = pairs;
            this.shuffle();
            try {
                const saved = JSON.parse(
                    localStorage.getItem(localKey('matching:' + lessonId)) || 'null',
                );
                if (saved) {
                    this.attempts = saved.attempts;
                    this.matched = saved.matched;
                    saveId = saved.saveId;
                    this.feedback =
                        this.matched.length === 4
                            ? '✓ Semua pasangan sudah cocok. Hebat!'
                            : 'Latihan sebelumnya dibuka kembali. Lanjutkan mencocokkan keterangan dengan kode.';
                }
            } catch {}
            if (initialResult) {
                this.matched = this.pairs.map((pair) => pair.id);
                this.attempts = [];
                this.savedAccuracy = initialResult.accuracy;
                this.feedback = '✓ Semua kode sudah cocok. Hasil latihan tersimpan.';
                this.saveStatus = 'Kemajuan tersimpan';
                saveId = null;
            }
            window.addEventListener('save-status', (e) => {
                if (!saveId || (e.detail.id !== saveId && !e.detail.offline)) return;
                this.saveStatus = e.detail.message;
                if (e.detail.id === saveId && !e.detail.failed && !e.detail.offline) {
                    this.feedback =
                        '✓ Semua keterangan sudah cocok dengan kodenya! Hasil tersimpan.';
                    const result = e.detail.result;
                    if (result) {
                        this.$dispatch('lesson-completed', {
                            lessonId: result.lesson_id,
                            completedLessons: result.completed_lessons,
                            unlockedLessons: result.unlocked_lessons,
                            completed: result.completed === true,
                            quizPassed: result.quiz_passed === true,
                            matchingCompleted: result.matching_completed === true,
                        });
                        if (result.unlocked_features?.length)
                            this.feedback += ` Fitur baru terbuka: ${result.unlocked_features.join(', ')}.`;
                        else if (result.completed)
                            this.feedback +=
                                ' Materi selesai! Semua syarat untuk melanjutkan sudah terpenuhi.';
                        else
                            this.feedback +=
                                ' Jawab evaluasi dengan benar untuk membuka modul berikutnya.';
                        if (result.completed) this.$dispatch('matching-finished', result);
                    }
                }
            });
        },
        shuffle() {
            this.snippets = [...this.pairs].sort(() => Math.random() - 0.5);
        },
        choose(id) {
            if (!this.selected) {
                this.feedback = 'Pilih keterangan terlebih dahulu.';
                return;
            }
            if (this.matched.includes(id)) return;
            if (this.attempts.length >= 200) {
                this.feedback = 'Batas latihan tercapai. Tekan Reset untuk mencoba kembali.';
                return;
            }
            this.attempts.push({ description: this.selected, snippet: id });
            if (this.selected === id) {
                clearTimeout(wrongTimer);
                this.wrongId = null;
                this.wrongSnippet = null;
                this.matched.push(id);
                this.selected = null;
                this.feedback = '✓ Kode cocok. Lanjutkan ke keterangan berikutnya.';
                if (this.matched.length === 4) {
                    this.feedback = '✓ Semua keterangan cocok dengan kodenya. Latihan ini selesai!';
                    try {
                        saveId = enqueue(submitUrl, {
                            request_id: crypto.randomUUID(),
                            attempts: JSON.parse(JSON.stringify(this.attempts)),
                        });
                    } catch (e) {
                        this.saveStatus = e.message;
                    }
                }
            } else {
                this.wrongId = this.selected;
                this.wrongSnippet = id;
                this.feedback = 'Belum cocok. Baca kembali keterangan dan periksa hasil kode.';
                clearTimeout(wrongTimer);
                wrongTimer = setTimeout(() => {
                    this.wrongId = null;
                    this.wrongSnippet = null;
                }, 900);
            }
            try {
                localStorage.setItem(
                    localKey('matching:' + lessonId),
                    JSON.stringify({ attempts: this.attempts, matched: this.matched, saveId }),
                );
            } catch {
                this.saveStatus = 'Penyimpanan perangkat tidak tersedia.';
            }
        },
        reset() {
            saveId = null;
            clearTimeout(wrongTimer);
            this.selected = null;
            this.matched = [];
            this.attempts = [];
            this.savedAccuracy = null;
            this.wrongId = null;
            this.wrongSnippet = null;
            this.feedback = 'Latihan baru. Pilih satu keterangan, lalu cari kode yang cocok.';
            this.saveStatus = '';
            this.shuffle();
            try {
                localStorage.removeItem(localKey('matching:' + lessonId));
            } catch {}
        },
    };
}
