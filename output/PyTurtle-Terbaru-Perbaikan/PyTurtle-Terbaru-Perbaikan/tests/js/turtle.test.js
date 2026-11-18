import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { parse } from '../../resources/js/game/turtle/parser.js';
import { TurtleEngine } from '../../resources/js/game/turtle/engine.js';
const levels = JSON.parse(
    fs.readFileSync(new URL('../../resources/data/levels.json', import.meta.url)),
);
test('All three routes, ordered checkpoints and partial saves are solvable', () => {
    for (const level of levels) {
        const simulation = new TurtleEngine(level).simulate(level.solution);
        assert.equal(simulation.result, 'SUCCESS');
        assert.equal(simulation.state.checkpoint, level.checkpoints.length);
        assert.equal(simulation.checkpointPrograms.length, level.checkpoints.length);
        for (const [i, checkpoint] of simulation.checkpointPrograms.entries())
            assert.ok(new TurtleEngine(level).simulate(checkpoint.code).state.checkpoint >= i + 1);
    }
});
test('Equivalent programs support prefix, decimals, negative distance and comments', () => {
    assert.equal(
        new TurtleEngine(levels[0]).simulate('turtle.forward( 1.0 ) # start\nbackward(-2)')
            .result,
        'SUCCESS',
    );
});
test('Reject arbitrary input, imports and excessive work before movement', () => {
    for (const code of [
        'import os',
        'eval(1)',
        'forward 100',
        'forward()',
        'forward(1001)',
        'penup(2)',
        'forward(Infinity)',
        'forward(1);right(90)',
        Array(102).fill('left(1)').join('\n'),
    ])
        assert.throws(() => parse(code));
});
test('Collision cannot be bypassed with a long move or penup', () => {
    const simulation = new TurtleEngine(levels[2]).simulate('penup()\nforward(1000)');
    assert.equal(simulation.result, 'PARTIAL');
    assert.equal(simulation.error_type, 'collision');
    assert.ok(simulation.state.x < 235);
});
test('Turning does not move the player; backward keeps heading', () => {
    const level = { ...levels[2], start: { x: 210, y: 350, angle: 0 } };
    const simulation = new TurtleEngine(level).simulate('left(90)\nbackward(-1)');
    assert.equal(simulation.state.angle, 270);
    assert.equal(simulation.state.x, 210);
    assert.equal(simulation.state.y, 290);
});
