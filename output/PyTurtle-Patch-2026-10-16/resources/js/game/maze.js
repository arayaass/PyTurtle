import {
    EditorView,
    keymap,
    lineNumbers,
    highlightActiveLine,
    highlightActiveLineGutter,
} from '@codemirror/view';
import { EditorState, Compartment } from '@codemirror/state';
import { python } from '@codemirror/lang-python';
import { autocompletion } from '@codemirror/autocomplete';
import { turtleCompletions } from './turtle/completions';
import { basicSetup } from 'codemirror';
import { TurtleEngine } from './turtle/engine';
import { traceProgram, describeState, comparePrediction } from './turtle/learning';
import { ForestGame } from './Game';
import { ForestAudio } from './systems/audio';
import { enqueue, post } from './systems/save-queue';

export default function mazeGame() {
    // Keep CodeMirror and Canvas objects outside Alpine's reactive proxy.
    let editor,
        world,
        audio,
        data,
        typing,
        playback,
        learningLock = new Compartment(),
        trace = null,
        traceCode = '',
        lockedPrediction = null,
        finalId = null;
    return {
        running: false,
        syncing: false,
        saved: false,
        runVersion: 1,
        runFailures: 0,
        runCompleted: false,
        hasBadge: false,
        rewardMessage: '',
        predictionX: '',
        predictionY: '',
        predictionAngle: '',
        learningBusy: false,
        tracing: false,
        learningDone: false,
        learningMessage: '',
        predictionFeedback: '',
        reflection: '',
        traceIndex: 0,
        traceTotal: 0,
        tracePosition: '',
        traceCommand: '',
        speed: 1,
        checkpoint: 0,
        score: 0,
        result: 'READY',
        message: 'Rencanakan langkahmu, tulis perintahnya, lalu tekan Run Code.',
        saveStatus: 'Progress tersimpan',
        hintText: '',
        dialogOpen: false,
        dialogIndex: 0,
        dialogText: '',
        dialogLines: [],
        outro: false,
        boot() {
            data = JSON.parse(document.getElementById('maze-data').textContent);
            this.runVersion = data.progress?.run_version ?? 1;
            this.runFailures = data.progress?.run_failures ?? 0;
            this.runCompleted = data.level.id === 3 && Boolean(data.progress?.run_completed);
            this.hasBadge = Boolean(data.progress?.perfect_completed_at);
            if (this.runCompleted)
                this.rewardMessage = `XP terbaik chapter ini: ${data.progress.best_score}. ${this.hasBadge ? 'Badge Turtle Master sudah diperoleh.' : 'Ulangi penilaian tanpa kesalahan dan hint untuk mendapat badge.'}`;
            const reduced =
                Boolean(data.settings?.reduced_motion) ||
                matchMedia('(prefers-reduced-motion: reduce)').matches;
            world = new ForestGame(document.getElementById('game-canvas'), data.level, reduced);
            audio = new ForestAudio(data.settings || { sound_enabled: true });
            // Every visit starts with an empty editor, including saved chapters.
            editor = new EditorView({
                state: EditorState.create({
                    doc: '',
                    extensions: [
                        basicSetup,
                        learningLock.of(EditorState.readOnly.of(false)),
                        python(),
                        autocompletion({
                            override: [turtleCompletions(data.level.editor_commands)],
                        }),
                        lineNumbers(),
                        highlightActiveLine(),
                        highlightActiveLineGutter(),
                        EditorView.lineWrapping,
                        EditorView.contentAttributes.of({
                            'aria-label': 'Editor kode Python',
                            spellcheck: 'false',
                        }),
                        keymap.of([
                            {
                                key: 'Mod-Enter',
                                run: () => {
                                    this.run();
                                    return true;
                                },
                            },
                        ]),
                    ],
                }),
                parent: document.getElementById('code-editor'),
            });
            if (
                data.progress?.status === 'completed' &&
                (data.level.id !== 3 || this.runCompleted)
            ) {
                this.score = data.progress.best_score;
                this.result = 'SUCCESS';
                this.saved = true;
                this.message = 'Chapter sudah selesai. Kamu boleh mencoba rute lain untuk belajar.';
                world.complete = true;
            }
            window.addEventListener('save-status', (event) => {
                this.saveStatus = event.detail.message;
                if (event.detail.offline) this.syncing = false;
                if (event.detail.id === finalId) {
                    this.syncing = false;
                    if (event.detail.failed) {
                        this.saved = false;
                        return;
                    }
                    this.saved = true;
                    this.score = event.detail.result.score;
                    this.result = event.detail.result.result;
                    this.runFailures = event.detail.result.run_failures;
                    this.runCompleted = data.level.id === 3 && event.detail.result.run_completed;
                    this.hasBadge = event.detail.result.has_badge;
                    this.rewardMessage = event.detail.result.badge_earned
                        ? 'Pengerjaan sempurna! 100 XP dan badge Turtle Master.'
                        : `Reward percobaan: ${this.score} XP. XP terbaik: ${event.detail.result.best_score}. Ulangi penilaian tanpa kesalahan dan hint untuk mendapat badge.`;
                    if (this.result === 'SUCCESS') {
                        world.complete = true;
                        audio.complete();
                        this.showOutro();
                    }
                }
            });
            if (data.story === 'outro:' + data.level.id) this.showOutro();
            else if (
                !data.progress?.attempts &&
                data.story !== 'playing:' + data.level.id &&
                data.story !== 'finished:' + data.level.id
            )
                this.openDialogue(data.level.dialogue, false);
            window.addEventListener('pagehide', () => {
                world.dispose();
                cancelAnimationFrame(playback);
                clearInterval(typing);
            });
        },
        async startLearning() {
            if (
                this.running ||
                this.syncing ||
                this.runCompleted ||
                this.tracing ||
                this.learningBusy
            )
                return;
            const prediction = {
                x: this.predictionX,
                y: this.predictionY,
                angle: this.predictionAngle,
            };
            if (
                [prediction.x, prediction.y, prediction.angle].some(
                    (value) => String(value).trim() === '',
                ) ||
                ![prediction.x, prediction.y].every(
                    (value) => Number.isInteger(Number(value)) && Math.abs(Number(value)) <= 1000,
                ) ||
                ![0, 90, 180, 270].includes(Number(prediction.angle))
            ) {
                this.learningMessage =
                    'Isi prediksi X dan Y dengan bilangan bulat, lalu pilih arah akhir.';
                return;
            }
            traceCode = editor.state.doc.toString();
            if (!traceCode.trim()) {
                this.learningMessage = 'Tulis kode di editor sebelum mengunci prediksi.';
                editor.focus();
                return;
            }
            this.learningBusy = true;
            editor.dispatch({ effects: learningLock.reconfigure(EditorState.readOnly.of(true)) });
            try {
                // The server records assistance before any execution details are revealed.
                await post('/game/' + data.level.id + '/hint', {
                    version: Number(document.body.dataset.version),
                    run_version: this.runVersion,
                });
                this.learningDone = false;
                this.predictionFeedback = '';
                this.reflection = '';
                this.saved = false;
                this.result = 'READY';
                lockedPrediction = { ...prediction };
                trace = traceProgram(data.level, traceCode);
                this.traceTotal = trace.steps.length;
                this.traceIndex = 0;
                this.tracing = true;
                this.learningMessage = `Prediksi terkunci: (${prediction.x}, ${prediction.y}), ${describeState(data.level, { ...data.level.start, angle: Number(prediction.angle) }).direction}.`;
                this.message =
                    'Mode belajar: lihat perubahan posisi dan arah pada setiap instruksi.';
                this.renderTrace();
            } catch (error) {
                this.learningMessage = error.message;
                this.tracing = false;
                editor.dispatch({
                    effects: learningLock.reconfigure(EditorState.readOnly.of(false)),
                });
                if (error.line) {
                    const line = editor.state.doc.line(error.line);
                    editor.dispatch({
                        selection: { anchor: line.from, head: line.to },
                        scrollIntoView: true,
                    });
                    this.learningMessage +=
                        ' Perbaiki baris ini, kemudian buat prediksi lagi. Kode belum dijalankan.';
                    editor.focus();
                }
            } finally {
                this.learningBusy = false;
            }
        },
        renderTrace() {
            const state =
                this.traceIndex === 0
                    ? { ...data.level.start, checkpoint: 0, penDown: true }
                    : trace.steps[this.traceIndex - 1];
            world.reset();
            for (const step of trace.steps.slice(0, this.traceIndex)) world.setPlayer(step);
            world.moving = false;
            this.checkpoint = state.checkpoint;
            const position = describeState(data.level, state);
            this.tracePosition = `Posisi (${position.x}, ${position.y}) · ${position.direction}`;
            this.traceCommand = state.line
                ? `Baris ${state.line}: ${traceCode.split(/\r\n|\n|\r/)[state.line - 1]}`
                : 'Titik awal · belum ada instruksi dijalankan';
            if (state.line) {
                const line = editor.state.doc.line(state.line);
                editor.dispatch({
                    selection: { anchor: line.from, head: line.to },
                    scrollIntoView: true,
                });
            }
        },
        traceStep(delta) {
            if (!this.tracing || !trace) return;
            this.traceIndex = Math.max(0, Math.min(this.traceTotal, this.traceIndex + delta));
            this.renderTrace();
            this.learningDone = this.traceIndex === this.traceTotal;
            if (this.learningDone) {
                const actual = describeState(data.level, trace.state);
                this.predictionFeedback =
                    (comparePrediction(data.level, trace.state, lockedPrediction)
                        ? 'Prediksimu tepat! '
                        : 'Prediksi dan hasil berbeda. ') +
                    `Hasil: (${actual.x}, ${actual.y}), ${actual.direction}. ` +
                    trace.message +
                    (trace.error_type === 'collision'
                        ? ` Periksa jarak atau arah pada baris ${trace.line}; posisi bisa berhenti di antara kotak.`
                        : '');
            }
        },
        finishLearning() {
            if (this.learningBusy) return;
            this.tracing = false;
            editor.dispatch({ effects: learningLock.reconfigure(EditorState.readOnly.of(false)) });
            this.learningMessage = this.learningDone
                ? 'Hasil penelusuran ini untuk kode sebelumnya. Perbaiki kode, lalu buat prediksi baru atau gunakan Run Code.'
                : 'Penelusuran dihentikan. Perbaiki kode lalu kunci prediksi baru.';
            editor.focus();
        },
        async run() {
            if (
                this.running ||
                this.syncing ||
                this.runCompleted ||
                this.tracing ||
                this.learningBusy
            )
                return;
            audio.start();
            this.running = true;
            this.saved = false;
            this.result = 'READY';
            this.message = 'Menjalankan kode…';
            this.checkpoint = 0;
            const code = editor.state.doc.toString();
            let simulation;
            world.reset();
            if (!code.trim()) {
                this.running = false;
                this.result = 'ERROR';
                this.message = 'Editor masih kosong. Tulis langkahmu dahulu, misalnya forward(3).';
                editor.focus();
                return;
            }
            try {
                simulation = new TurtleEngine(data.level).simulate(code);
            } catch (error) {
                this.running = false;
                this.result = 'ERROR';
                this.message = error.message;
                const line = editor.state.doc.line(
                    Math.min(error.line || 1, editor.state.doc.lines),
                );
                editor.dispatch({
                    selection: { anchor: line.from, head: line.to },
                    scrollIntoView: true,
                });
                editor.focus();
                this.send(code);
                return;
            }
            let frame = 0,
                lastTime = 0,
                cpIndex = 0;
            const animate = (time) => {
                if (!lastTime) lastTime = time;
                const count = world.reducedMotion
                    ? simulation.frames.length
                    : Math.max(1, Math.floor((time - lastTime) / 16) * this.speed);
                lastTime = time;
                for (let i = 0; i < count && frame < simulation.frames.length; i++, frame++) {
                    const s = simulation.frames[frame];
                    world.setPlayer(s);
                    if (s.checkpoint > this.checkpoint) {
                        this.checkpoint = s.checkpoint;
                        audio.checkpoint();
                    }
                    if (simulation.checkpointPrograms[cpIndex]?.frame === frame) {
                        const partial = simulation.checkpointPrograms[cpIndex++].code;
                        try {
                            enqueue('/game/progress/save', {
                                level_id: data.level.id,
                                code: partial,
                                phase: 'checkpoint',
                                run_version: this.runVersion,
                                request_id: crypto.randomUUID(),
                                version: Number(document.body.dataset.version),
                            });
                        } catch (error) {
                            this.saveStatus = error.message;
                        }
                    }
                }
                if (frame < simulation.frames.length) playback = requestAnimationFrame(animate);
                else {
                    world.moving = false;
                    this.running = false;
                    this.result = simulation.result;
                    this.message = simulation.message;
                    this.send(code);
                }
            };
            playback = requestAnimationFrame(animate);
        },
        send(code) {
            this.syncing = true;
            try {
                finalId = enqueue('/game/progress/save', {
                    level_id: data.level.id,
                    code,
                    phase: 'final',
                    run_version: this.runVersion,
                    request_id: crypto.randomUUID(),
                    version: Number(document.body.dataset.version),
                });
            } catch (error) {
                this.syncing = false;
                this.saveStatus = error.message;
            }
        },
        reset() {
            if (this.running || this.syncing || this.tracing || this.learningBusy) return;
            this.learningDone = false;
            this.learningMessage = '';
            this.predictionX = this.predictionY = this.predictionAngle = '';
            this.reflection = '';
            finalId = null;
            editor.dispatch({
                changes: { from: 0, to: editor.state.doc.length, insert: '' },
            });
            world.reset();
            this.checkpoint = 0;
            this.result = 'READY';
            this.message =
                'Editor dikosongkan dan Shiha kembali ke awal. Skor terbaik tetap tersimpan.';
            this.saved = false;
        },
        async replay() {
            if (this.running || this.syncing || this.tracing || this.learningBusy) return;
            this.syncing = true;
            try {
                const response = await post('/game/' + data.level.id + '/replay', {
                    version: Number(document.body.dataset.version),
                    run_version: this.runVersion,
                });
                this.runVersion = response.run_version;
                this.runFailures = 0;
                this.runCompleted = false;
                this.hintText = '';
                this.rewardMessage = '';
                this.syncing = false;
                this.reset();
                this.message =
                    'Penilaian baru dimulai. Selesaikan dalam satu Run tanpa hint untuk 100 XP dan badge.';
            } catch (error) {
                this.saveStatus = error.message;
            } finally {
                this.syncing = false;
            }
        },
        async hint() {
            if (
                this.running ||
                this.syncing ||
                this.runCompleted ||
                this.tracing ||
                this.learningBusy
            )
                return;
            audio.start();
            try {
                const response = await post('/game/' + data.level.id + '/hint', {
                    run_version: this.runVersion,
                    version: Number(document.body.dataset.version),
                });
                this.hintText = response.hint;
            } catch {
                this.hintText =
                    'Hint memerlukan koneksi. Periksa kembali petunjuk jarak di bagian misimu, lalu coba lagi saat terhubung.';
            }
        },
        showOutro() {
            this.openDialogue(data.level.outro, true);
        },
        openDialogue(lines, outro) {
            this.dialogLines = lines;
            this.outro = outro;
            this.dialogIndex = 0;
            this.dialogOpen = true;
            this.typeDialogue();
        },
        typeDialogue() {
            clearInterval(typing);
            this.dialogText = '';
            let i = 0;
            const line = this.dialogLines[this.dialogIndex];
            if (world.reducedMotion) {
                this.dialogText = line;
                return;
            }
            typing = setInterval(() => {
                this.dialogText = line.slice(0, ++i);
                if (i >= line.length) clearInterval(typing);
            }, 24);
        },
        skipTyping() {
            clearInterval(typing);
            this.dialogText = this.dialogLines[this.dialogIndex];
        },
        nextDialogue() {
            audio.start();
            if (this.dialogText !== this.dialogLines[this.dialogIndex]) {
                this.skipTyping();
                return;
            }
            if (this.dialogIndex < this.dialogLines.length - 1) {
                this.dialogIndex++;
                this.typeDialogue();
            } else {
                this.dialogOpen = false;
                clearInterval(typing);
                try {
                    enqueue('/game/story', {
                        level_id: data.level.id,
                        version: Number(document.body.dataset.version),
                        story_state: this.outro ? 'finished' : 'playing',
                    });
                } catch (error) {
                    this.saveStatus = error.message;
                }
                editor.focus();
            }
        },
    };
}
