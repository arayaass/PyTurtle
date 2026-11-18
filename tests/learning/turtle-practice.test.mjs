import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    simulateMovement,
    drawMovement,
    buildMovement,
    movementFrame,
} from '../../resources/js/learning/turtle-practice.js';
import turtlePractice from '../../resources/js/learning/turtle-practice.js';
const near = (actual, expected) =>
    assert.ok(Math.abs(actual - expected) < 1e-7, `${actual} != ${expected}`);
const rightAngle = simulateMovement('forward(95)\nleft(90)\nforward(65)');
near(rightAngle.state.x, 95);
near(rightAngle.state.y, 65);
near(rightAngle.state.heading, 90);
const backward = simulateMovement('left(90)\nbackward(30)');
near(backward.state.x, 0);
near(backward.state.y, -30);
near(backward.state.heading, 90);
const negative = simulateMovement('left(-90)\nforward(-20)');
near(negative.state.y, 20);
near(negative.state.heading, 270);
const pen = simulateMovement('penup()\nforward(85)\npendown()\nforward(55)');
assert.equal(pen.segments.length, 1);
near(pen.segments[0].from.x, 85);
near(pen.state.x, 140);
for (const input of [
    '',
    'import turtle',
    'for i in range(4):',
    'forward(1001)',
    'left()',
    'penup(2)',
    'eval("1")',
    'forward(1)\n'.repeat(101),
])
    assert.throws(() => simulateMovement(input));
const lessons = JSON.parse(
    readFileSync(new URL('../../resources/data/turtle-lessons.json', import.meta.url)),
);
assert.equal(lessons.length, 11);
assert.equal(new Set(lessons.map((l) => l.key)).size, 11);
for (const [i, lesson] of lessons.entries()) {
    assert.equal(lesson.order, i + 1);
    assert.equal(lesson.content.source_section, `4.${i + 1}`);
    assert.ok(
        lesson.content.source_url.startsWith(
            'https://runestone.academy/ns/books/published/thinkcspy/PythonTurtle/',
        ),
    );
    assert.ok(lesson.content.quiz.options[lesson.content.quiz.answer]);
    if (lesson.content.practice) {
        const result = simulateMovement(lesson.content.practice.code);
        assert.ok(Number.isFinite(result.state.x) && Number.isFinite(result.state.y));
        if ([6, 11].includes(lesson.order)) {
            near(result.state.x, 0);
            near(result.state.y, 0);
            near(result.state.heading, 0);
        }
    }
}
const calls = [];
const ctx = new Proxy(
    {},
    {
        get:
            (_, name) =>
            (...args) =>
                calls.push([name, ...args]),
        set: () => true,
    },
);
const canvas = { width: 640, height: 420, getContext: () => ctx };
drawMovement(canvas, rightAngle);
assert.ok(calls.some((c) => c[0] === 'rotate' && c[1] === -Math.PI / 2));

// Deterministic frame and timer clock: no real waiting needed.
let nextId = 1,
    time = 0;
const frames = new Map(),
    timers = new Map();
globalThis.requestAnimationFrame = (callback) => {
    const id = nextId++;
    frames.set(id, callback);
    return id;
};
globalThis.cancelAnimationFrame = (id) => frames.delete(id);
globalThis.setTimeout = (callback, delay) => {
    const id = nextId++;
    timers.set(id, { callback, at: time + delay });
    return id;
};
globalThis.clearTimeout = (id) => timers.delete(id);
function tick(delta = 16) {
    time += delta;
    for (const [id, timer] of [...timers])
        if (timer.at <= time) {
            timers.delete(id);
            timer.callback();
        }
    const pending = [...frames];
    frames.clear();
    for (const [, callback] of pending) callback(time);
}
const component = turtlePractice('forward(95)');
component.$refs = { canvas };
component.run();
assert.equal(component.error, false);
assert.equal(component.running, true);
assert.equal(component.position, '(0, 0)');
tick();
tick(100);
assert.ok(Number(component.position.slice(1).split(',')[0]) > 0);
assert.ok(Number(component.position.slice(1).split(',')[0]) < 95);
component.pauseOrResume();
const pausedPosition = component.position;
assert.equal(component.paused, true);
assert.equal(frames.size, 0);
tick(100);
assert.equal(component.position, pausedPosition);
component.pauseOrResume();
tick();
tick(100);
assert.notEqual(component.position, pausedPosition);
for (let i = 0; i < 30 && component.running; i++) tick(100);
assert.equal(component.position, '(95, 0)');
assert.equal(component.running, false);
assert.equal(component.progress, 100);
assert.equal(component.completed, 1);
assert.match(component.feedback, /1 perintah selesai/);
component.run();
tick();
tick(100);
component.stop();
const stoppedPosition = component.position;
tick(100);
assert.equal(component.position, stoppedPosition);
assert.equal(frames.size, 0);
component.reset();
assert.equal(component.code, 'forward(95)');
assert.equal(component.error, false);
component.codeEdited('left(90)');
assert.equal(frames.size, 0);
assert.equal(timers.size, 1);
component.codeEdited('forward(50)');
assert.equal(timers.size, 1);
tick(649);
assert.equal(component.running, false);
tick(1);
assert.equal(component.running, true);
assert.equal(component.total, 1);
assert.equal(component.position, '(0, 0)');
for (let i = 0; i < 20 && component.running; i++) tick(100);
assert.equal(component.position, '(50, 0)');
component.codeEdited('right(90)');
component.autoRunChanged(false);
assert.equal(timers.size, 0);
tick(1000);
assert.equal(component.running, false);
component.code = 'for';
component.run();
assert.equal(component.error, true);
assert.equal(frames.size, 0);
assert.equal(component.activeLine, 1);
component.code = 'forward(85)';
component.speed = 2;
component.run();
tick();
tick(100);
assert.equal(component.position, '(17, 0)');
component.run();
assert.equal(frames.size, 1); // Rerun cancels the earlier frame.
component.autoRun = true;
component.codeEdited('forward(60)');
component.destroy();
assert.equal(frames.size, 0);
assert.equal(timers.size, 0);

const animated = buildMovement('# Heading east\n\nforward(85)\nleft(90)\nforward(85)');
const halfMove = movementFrame(animated, 500);
near(halfMove.state.x, 42.5);
near(halfMove.segments[0].to.x, 42.5);
assert.equal(halfMove.current.line, 3);
assert.equal(halfMove.completed, 0);
assert.equal(halfMove.points, animated.points); // Fixed bounds for every frame.
const turnStep = animated.steps[1];
const halfTurn = movementFrame(animated, turnStep.start + turnStep.duration / 2);
near(halfTurn.state.heading, 45);
near(halfTurn.state.x, 85);
assert.equal(halfTurn.segments.length, 1);
const finished = movementFrame(animated, animated.duration + 1000);
near(finished.state.x, 85);
near(finished.state.y, 85);
assert.equal(finished.done, true);
assert.equal(finished.completed, 3);
for (const [code, midway, final] of [
    ['right(270)', 225, 90],
    ['left(-90)', 315, 270],
    ['left(720)', 0, 0],
]) {
    const route = buildMovement(code);
    near(movementFrame(route, route.duration / 2).state.heading, midway);
    near(movementFrame(route, route.duration).state.heading, final);
}
const penRoute = buildMovement('penup()\nforward(85)\npendown()\nbackward(20)');
const liftedMove = movementFrame(penRoute, penRoute.steps[1].start + 500);
near(liftedMove.state.x, 42.5);
assert.equal(liftedMove.state.penDown, false);
assert.equal(liftedMove.segments.length, 0);
const finalPen = movementFrame(penRoute, penRoute.duration);
assert.equal(finalPen.segments.length, 1);
near(finalPen.state.x, 65);
for (const lesson of lessons.filter((l) => l.content.practice)) {
    const route = buildMovement(lesson.content.practice.code);
    const end = movementFrame(route, route.duration);
    near(end.state.x, route.state.x);
    near(end.state.y, route.state.y);
    assert.deepEqual(end.segments, route.segments);
}
console.log(
    'PASS: gradual movement, signed rotations, pen paths, stable bounds, pause/resume, stop, rerun, edit debounce, speed, errors, destroy, and all curriculum examples.',
);
