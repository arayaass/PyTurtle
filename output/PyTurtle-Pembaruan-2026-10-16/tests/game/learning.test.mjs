import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    traceProgram,
    describeState,
    comparePrediction,
} from '../../resources/js/game/turtle/learning.js';
const levels = JSON.parse(
    readFileSync(new URL('../../resources/data/levels.json', import.meta.url)),
);
const level = levels[1];
const trace = traceProgram(level, '# Mulai\nforward(3)\n\nleft(90)\nforward(1)');
assert.equal(trace.steps.length, 3);
assert.deepEqual(
    trace.steps.map((step) => step.line),
    [2, 4, 5],
);
assert.deepEqual(describeState(level, trace.steps[0]), {
    x: 3,
    y: 0,
    angle: 0,
    direction: 'Timur →',
});
assert.deepEqual(describeState(level, trace.steps[1]), {
    x: 3,
    y: 0,
    angle: 270,
    direction: 'Utara ↑',
});
assert.ok(comparePrediction(level, trace.state, { x: 3, y: -1, angle: 270 }));
assert.ok(!comparePrediction(level, trace.state, { x: 3, y: -1, angle: 90 }));
const collision = traceProgram(levels[0], 'forward(30)\nforward(1)');
assert.equal(collision.error_type, 'collision');
assert.equal(collision.steps.length, 1);
assert.equal(collision.steps[0].line, 1);
assert.deepEqual(collision.steps[0].x, collision.state.x);
assert.throws(() => traceProgram(level, 'forward(3)\nbad()'));
for (const entry of levels) {
    const solution = traceProgram(entry, entry.solution);
    assert.equal(solution.result, 'SUCCESS');
    assert.equal(solution.steps.length, entry.solution.split('\n').length);
    assert.equal(solution.steps.at(-1).checkpoint, entry.checkpoints.length);
}
