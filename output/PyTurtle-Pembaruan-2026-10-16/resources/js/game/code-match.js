import { enqueue, localKey } from './systems/save-queue';
export default function codeMatch() {
    let saveId, wrongTimer;
    return {
        pairs: [],
        snippets: [],
        selected: null,
        matched: [],
        attempts: [],
        wrongId: null,
        wrongSnippet: null,
        feedback: 'Pilih satu deskripsi di sebelah kiri untuk memulai.',
        saveStatus: '',
        get accuracy() {
            return this.attempts.length
                ? Math.round((this.matched.length / this.attempts.length) * 100)
                : 0;
        },
        boot() {
            this.pairs = JSON.parse(document.getElementById('match-data').textContent);
            this.shuffle();
            try {
                const saved = JSON.parse(localStorage.getItem(localKey('matching')) || 'null');
                if (saved) {
                    this.attempts = saved.attempts;
                    this.matched = saved.matched;
                    this.feedback =
                        this.matched.length === 4
                            ? '✓ Semua pasangan benar. Hebat!'
                            : 'Latihan sebelumnya dipulihkan. Lanjutkan memasangkan.';
                }
            } catch {}
            window.addEventListener('save-status', (e) => {
                this.saveStatus = e.detail.message;
                if (saveId && e.detail.id === saveId && !e.detail.failed)
                    this.feedback = '✓ Semua pasangan benar! Hasil tersimpan.';
            });
        },
        shuffle() {
            this.snippets = [...this.pairs].sort(() => Math.random() - 0.5);
        },
        choose(id) {
            if (!this.selected) {
                this.feedback = 'Pilih deskripsi gerakan terlebih dahulu.';
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
                this.feedback = '✓ Cocok! Lanjutkan ke pasangan berikutnya.';
                if (this.matched.length === 4) {
                    this.feedback = '✓ Semua pasangan benar. Kamu sudah memahami urutan gerakan!';
                    try {
                        saveId = enqueue('/code-match/submit', {
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
                this.feedback = 'Belum cocok. Periksa jarak, arah putaran, dan urutan perintah.';
                clearTimeout(wrongTimer);
                wrongTimer = setTimeout(() => {
                    this.wrongId = null;
                    this.wrongSnippet = null;
                }, 900);
            }
            try {
                localStorage.setItem(
                    localKey('matching'),
                    JSON.stringify({ attempts: this.attempts, matched: this.matched }),
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
            this.wrongId = null;
            this.wrongSnippet = null;
            this.feedback = 'Latihan baru. Pilih deskripsi untuk mulai.';
            this.saveStatus = '';
            this.shuffle();
            try {
                localStorage.removeItem(localKey('matching'));
            } catch {}
        },
    };
}
