export default function lessonQuiz(initialCompleted = false) {
    let request = null,
        destroyed = false,
        savedPosition = null;
    return {
        completed: initialCompleted,
        submitting: false,
        popupKind: 'info',
        popupTitle: '',
        popupMessage: '',
        popupReward: '',
        popupButton: 'Tutup',
        popupUrl: null,
        init() {},
        destroy() {
            destroyed = true;
            request?.abort();
        },
        restorePosition() {
            if (savedPosition) window.scrollTo({ ...savedPosition, behavior: 'instant' });
        },
        async showPopup(kind, title, message, reward = '', destination = null, button = null) {
            if (destroyed) return;
            this.popupKind = kind;
            this.popupTitle = title;
            this.popupMessage = message;
            this.popupReward = reward;
            this.popupUrl = destination;
            this.popupButton = button ?? (kind === 'wrong' ? 'Coba lagi' : 'Tutup');
            savedPosition = { left: window.scrollX, top: window.scrollY };
            await this.$nextTick();
            if (destroyed) return;
            if (!this.$refs.resultDialog.open) this.$refs.resultDialog.showModal();
            this.$refs.closeResult.focus({ preventScroll: true });
            this.restorePosition();
        },
        closePopup() {
            const destination = this.popupUrl;
            this.$refs.resultDialog.close();
            if (destination) window.location.assign(destination);
        },
        async showCompletion(result) {
            if (destroyed) return;
            this.completed = result.completed === true;
            const destination = this.completed ? (result.continue_url ?? null) : null;
            const reward = !this.completed
                ? result.xp_awarded > 0
                    ? `+${result.xp_awarded} XP · Pertanyaan selesai. Sekarang cocokkan kode.`
                    : 'Pertanyaan sudah dijawab. Sekarang cocokkan kode.'
                : result.xp_awarded > 0
                  ? `+${result.xp_awarded} XP · Materi selesai!`
                  : result.xp_awarded === 0
                    ? 'Materi sudah selesai. XP diberikan satu kali.'
                    : 'Materi selesai! Progres tersimpan.';
            await this.showPopup(
                'correct',
                result.unlocked_features?.length
                    ? 'Fitur baru terbuka!'
                    : (result.title ?? 'Materi selesai!'),
                result.unlocked_features?.length
                    ? `${result.message ?? 'Latihan memasangkan kode dan pertanyaan sudah selesai.'} Kini terbuka: ${result.unlocked_features.join(', ')}. Untuk membuka Maze, selesaikan level sebelumnya secara berurutan.`
                    : (result.message ??
                          'Latihan memasangkan kode dan pertanyaan sudah selesai. Kamu bisa melanjutkan perjalanan.'),
                reward,
                destination,
                destination
                    ? (result.continue_label ?? 'Lanjut belajar')
                    : this.completed
                      ? 'Tutup'
                      : 'Lanjutkan memasangkan kode',
            );
        },
        popupClosed() {
            // Return to the chosen answer without the browser scrolling to a button.
            const choice =
                this.$refs.quizForm.querySelector('input[name="answer"]:checked') ??
                this.$refs.quizForm.querySelector('input[name="answer"]');
            choice?.focus({ preventScroll: true });
            this.restorePosition();
        },
        async checkAnswer(form) {
            if (this.submitting || destroyed) return;
            const selected = form.querySelector('input[name="answer"]:checked');
            if (!selected) {
                await this.showPopup(
                    'info',
                    'Pilih jawaban dahulu',
                    'Klik salah satu pilihan jawaban, lalu tekan Periksa jawaban.',
                );
                return;
            }
            // Capture FormData before the fieldset is disabled while submitting.
            const data = new FormData(form);
            this.submitting = true;
            request = new AbortController();
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: request.signal,
                });
                if (destroyed) return;
                if (response.status === 401 || response.status === 419 || response.redirected) {
                    throw new Error(
                        'Sesi login sudah berakhir. Muat ulang halaman dan masuk kembali, lalu coba lagi.',
                    );
                }
                const result = await response.json().catch(() => null);
                if (!response.ok) {
                    const message =
                        response.status === 422
                            ? (result?.errors?.answer?.[0] ??
                              'Pilih salah satu jawaban yang tersedia.')
                            : response.status === 403
                              ? (result?.message ??
                                'Materi masih terkunci. Selesaikan materi sebelumnya terlebih dahulu.')
                              : 'Jawaban belum bisa diperiksa. Coba lagi beberapa saat.';
                    throw new Error(message);
                }
                if (typeof result?.correct !== 'boolean')
                    throw new Error('Hasil pemeriksaan belum tersedia. Coba lagi.');
                this.completed = result.completed === true;
                if (result.correct) {
                    this.$dispatch('lesson-completed', {
                        lessonId: result.lesson_id,
                        completedLessons: result.completed_lessons,
                        unlockedLessons: result.unlocked_lessons,
                        completed: result.completed === true,
                        quizPassed: result.quiz_passed === true,
                        matchingCompleted: result.matching_completed === true,
                    });
                }
                if (result.correct) await this.showCompletion(result);
                else await this.showPopup('wrong', result.title, result.message);
            } catch (error) {
                if (!destroyed && error.name !== 'AbortError') {
                    await this.showPopup(
                        'info',
                        'Belum bisa memeriksa jawaban',
                        error instanceof TypeError
                            ? 'Koneksi terganggu. Periksa koneksi dan coba lagi.'
                            : error.message,
                    );
                }
            } finally {
                this.submitting = false;
                request = null;
            }
        },
    };
}
