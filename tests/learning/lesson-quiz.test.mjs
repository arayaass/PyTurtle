import assert from 'node:assert/strict';
import lessonQuiz from '../../resources/js/learning/lesson-quiz.js';

const scrolls = [];
globalThis.window = {
    scrollX: 0,
    scrollY: 1850,
    scrollTo(position) {
        scrolls.push(position);
        this.scrollX = position.left;
        this.scrollY = position.top;
    },
};
let nextResponse,
    requests = [],
    pendingFetch = null;
globalThis.fetch = async (url, options) => {
    requests.push({ url, options });
    if (pendingFetch) return pendingFetch;
    if (nextResponse instanceof Error) throw nextResponse;
    return nextResponse;
};
globalThis.FormData = class {
    constructor(form) {
        this.answer = form.selected?.value;
        this.token = 'test-csrf-token';
    }
};
const response = (data, status = 200, redirected = false) => ({
    ok: status >= 200 && status < 300,
    status,
    redirected,
    json: async () => data,
});
function setup(initialCompleted = false) {
    const component = lessonQuiz(initialCompleted),
        events = [];
    const input = {
        value: '1',
        focus(options) {
            assert.equal(options.preventScroll, true);
            this.focused = true;
        },
    };
    const form = {
        action: 'http://127.0.0.1:8000/modules/7/complete',
        selected: input,
        querySelector(selector) {
            return selector.endsWith(':checked') ? this.selected : input;
        },
    };
    const dialog = {
        open: false,
        showModal() {
            this.open = true;
            window.scrollY = 0;
        }, // Simulate native focus scrolling.
        close() {
            this.open = false;
            component.popupClosed();
        },
    };
    component.$refs = {
        quizForm: form,
        resultDialog: dialog,
        closeResult: {
            focus(options) {
                assert.equal(options.preventScroll, true);
            },
        },
    };
    component.$nextTick = async () => {};
    component.$dispatch = (name, detail) => events.push({ name, detail });
    return { component, form, input, dialog, events };
}

let fixture = setup();
fixture.form.selected = null;
await fixture.component.checkAnswer(fixture.form);
assert.equal(requests.length, 0);
assert.equal(fixture.component.popupKind, 'info');
assert.equal(fixture.dialog.open, true);
assert.equal(window.scrollY, 1850);
fixture.component.closePopup();
assert.equal(window.scrollY, 1850);

fixture = setup();
nextResponse = response({
    correct: false,
    title: 'Jawaban belum tepat',
    message: 'forward() menerima jarak.',
    completed: false,
    xp_awarded: 0,
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.popupKind, 'wrong');
assert.equal(fixture.component.completed, false);
assert.equal(fixture.component.popupButton, 'Coba lagi');
assert.equal(fixture.form.selected.value, '1');
assert.equal(fixture.events.length, 0);
assert.equal(window.scrollY, 1850);
assert.equal(requests.at(-1).options.headers.Accept, 'application/json');
assert.equal(requests.at(-1).options.credentials, 'same-origin');
assert.equal(requests.at(-1).options.body.token, 'test-csrf-token');
fixture.component.closePopup();
assert.equal(fixture.input.focused, true);
assert.equal(window.scrollY, 1850);

nextResponse = response({
    correct: true,
    title: 'Jawaban benar!',
    message: 'Benar karena jarak.',
    completed: true,
    xp_awarded: 20,
    lesson_id: 7,
    completed_lessons: 1,
    unlocked_lessons: [7, 8],
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.completed, true);
assert.equal(fixture.component.popupKind, 'correct');
assert.match(fixture.component.popupReward, /\+20 XP/);
assert.deepEqual(fixture.events[0], {
    name: 'lesson-completed',
    detail: {
        lessonId: 7,
        completedLessons: 1,
        unlockedLessons: [7, 8],
        completed: true,
        quizPassed: false,
        matchingCompleted: false,
    },
});
fixture.component.closePopup();
nextResponse = response({
    correct: true,
    title: 'Jawaban benar!',
    message: 'Benar.',
    completed: true,
    xp_awarded: 0,
    lesson_id: 7,
    completed_lessons: 1,
    unlocked_lessons: [7, 8],
});
await fixture.component.checkAnswer(fixture.form);
assert.match(fixture.component.popupReward, /satu kali/);
fixture.component.closePopup();
nextResponse = response({
    correct: false,
    title: 'Jawaban belum tepat',
    message: 'Coba lagi.',
    completed: true,
    xp_awarded: 0,
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.completed, true);
fixture.component.closePopup();

for (const [result, pattern] of [
    [response({ errors: { answer: ['Pilih jawaban tersedia.'] } }, 422), /Pilih jawaban tersedia/],
    [response(null, 419), /Sesi login/],
    [response(null, 200, true), /Sesi login/],
    [
        response({ message: 'Materi masih terkunci. Selesaikan materi 01 dahulu.' }, 403),
        /Materi masih terkunci/,
    ],
    [response(null, 500), /Coba lagi/],
    [response({ unexpected: true }), /Hasil pemeriksaan/],
    [new TypeError('Failed to fetch'), /Koneksi terganggu/],
]) {
    fixture = setup();
    nextResponse = result;
    await fixture.component.checkAnswer(fixture.form);
    assert.equal(fixture.component.popupKind, 'info');
    assert.match(fixture.component.popupMessage, pattern);
    assert.equal(fixture.component.submitting, false);
    assert.equal(fixture.component.completed, false);
    assert.equal(window.scrollY, 1850);
    fixture.component.closePopup();
}
fixture = setup();
let finish;
pendingFetch = new Promise((resolve) => {
    finish = resolve;
});
const before = requests.length;
const first = fixture.component.checkAnswer(fixture.form);
await fixture.component.checkAnswer(fixture.form);
assert.equal(requests.length, before + 1);
finish(response({ correct: false, title: 'Coba lagi', message: 'Belum tepat.', completed: false }));
await first;
pendingFetch = null;
assert.equal(fixture.component.submitting, false);
fixture.component.closePopup();
fixture = setup();
pendingFetch = new Promise((resolve) => {
    finish = resolve;
});
const destroyedRequest = fixture.component.checkAnswer(fixture.form);
fixture.component.destroy();
assert.equal(requests.at(-1).options.signal.aborted, true);
finish(response({ correct: true, completed: true }));
await destroyedRequest;
pendingFetch = null;
assert.equal(fixture.dialog.open, false);
assert.equal(fixture.events.length, 0);
assert.ok(scrolls.every((s) => s.top === 1850 && s.behavior === 'instant'));
console.log(
    'PASS: correct/wrong/empty answer dialogs, no navigation, scroll restoration, selected answer, CSRF, JSON, repeated attempts, duplicate-submit guard, errors, and request cancellation.',
);

fixture = setup();
nextResponse = response({
    correct: true,
    title: 'Jawaban benar!',
    message: 'Selesai.',
    completed: true,
    xp_awarded: 20,
    unlocked_features: ['Code Matching', 'Maze Quest level 1–2'],
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.popupTitle, 'Fitur baru terbuka!');
assert.match(fixture.component.popupMessage, /Code Matching, Maze Quest level 1–2/);
assert.equal(fixture.dialog.open, true);

fixture = setup();
nextResponse = response({
    correct: true,
    title: 'Jawaban benar!',
    message: 'Selesaikan Code Matching.',
    completed: false,
    quiz_passed: true,
    matching_completed: false,
    xp_awarded: 20,
    lesson_id: 7,
    completed_lessons: 0,
    unlocked_lessons: [7],
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.completed, false);
assert.equal(fixture.events[0].detail.completed, false);
assert.equal(fixture.events[0].detail.quizPassed, true);
assert.equal(fixture.events[0].detail.matchingCompleted, false);
assert.match(fixture.component.popupReward, /Evaluasi selesai/);
assert.doesNotMatch(fixture.component.popupReward, /Materi selesai/);

const destinations = [];
window.location = {
    assign(url) {
        destinations.push(url);
    },
};
fixture = setup();
nextResponse = response({
    correct: true,
    completed: true,
    title: 'Materi selesai!',
    message: 'Selesai.',
    xp_awarded: 20,
    continue_url: '/modules/8',
    continue_label: 'Lanjut ke modul berikutnya',
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.popupButton, 'Lanjut ke modul berikutnya');
assert.deepEqual(destinations, []);
fixture.component.closePopup();
assert.deepEqual(destinations, ['/modules/8']);
nextResponse = response({
    correct: false,
    completed: true,
    title: 'Coba lagi',
    message: 'Belum tepat.',
    continue_url: '/modules/8',
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.popupUrl, null);
fixture.component.closePopup();
assert.equal(destinations.length, 1);
nextResponse = response({
    correct: true,
    completed: false,
    message: 'Lanjutkan Code Matching.',
    continue_url: '/modules/8',
});
await fixture.component.checkAnswer(fixture.form);
assert.equal(fixture.component.popupUrl, null);
fixture.component.closePopup();
assert.equal(destinations.length, 1);
await fixture.component.showCompletion({
    completed: true,
    unlocked_features: ['Maze Quest level 1-2'],
    continue_url: '/maze',
    continue_label: 'Buka Maze Quest',
});
assert.equal(fixture.component.popupTitle, 'Fitur baru terbuka!');
assert.equal(fixture.component.popupButton, 'Buka Maze Quest');
assert.equal(destinations.length, 1);
fixture.component.closePopup();
assert.deepEqual(destinations, ['/modules/8', '/maze']);
