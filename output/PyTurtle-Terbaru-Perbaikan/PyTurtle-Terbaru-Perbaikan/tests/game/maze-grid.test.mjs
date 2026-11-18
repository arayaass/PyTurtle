import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { TurtleEngine } from '../../resources/js/game/turtle/engine.js';
import { ForestGame } from '../../resources/js/game/Game.js';
const levels = JSON.parse(readFileSync(new URL('../../resources/data/levels.json', import.meta.url)));
const near = (actual, expected) => assert.ok(Math.abs(actual - expected) < 0.001, `${actual} != ${expected}`);
for (const level of levels) {
    assert.equal(level.starter, ''); assert.equal(level.movement_unit, 'grid');
    const engine = new TurtleEngine(level), result = engine.simulate(level.solution);
    assert.equal(result.result, 'SUCCESS', `chapter ${level.id}`);
    assert.equal(result.state.checkpoint, level.checkpoints.length);
    near(result.state.x, level.checkpoints.at(-1)[0]); near(result.state.y, level.checkpoints.at(-1)[1]);
    let previous = [level.start.x, level.start.y];
    for (const checkpoint of level.checkpoints) {
        const count = Math.hypot(checkpoint[0] - previous[0], checkpoint[1] - previous[1]) / level.step_size;
        assert.ok([1, 2, 3].includes(count), `long leg in chapter ${level.id}`);
        previous = checkpoint;
    }
    for (const checkpoint of result.checkpointPrograms) {
        assert.doesNotThrow(() => engine.simulate(checkpoint.code));
        assert.ok(engine.simulate(checkpoint.code).state.checkpoint > 0);
    }
    for (const command of level.solution.split('\n').filter(line => line.startsWith('forward'))) assert.match(command, /^forward\([123]\)$/);
    assert.throws(() => engine.simulate('forward(1.5)'), /angka bulat/);
}
const first = new TurtleEngine(levels[0]);
let result = first.simulate('forward(1)'); near(result.state.x - levels[0].start.x, 60);
assert.equal(result.state.checkpoint, 1); assert.equal(result.result, 'PARTIAL');
result = first.simulate('forward(2)'); near(result.state.x, 210); assert.equal(result.result, 'PARTIAL');
assert.equal(first.simulate('forward(3)').result, 'SUCCESS');
assert.equal(first.simulate('forward(1)\nforward(2)').result, 'SUCCESS');
assert.equal(first.simulate('forward(4)').error_type, 'collision');
assert.equal(first.simulate('forward(180)').result, 'PARTIAL');
assert.equal(first.simulate('forward(3)\nbackward(1)').result, 'PARTIAL');
result = first.simulate('forward(2)\nbackward(1)'); near(result.state.x, 150); near(result.state.angle, 0);
assert.throws(() => first.simulate(''), /forward\(3\)/);
assert.throws(() => first.simulate('wrong'), /forward\(3\)/);
const second = new TurtleEngine(levels[1]);
assert.throws(() => second.simulate('left(45)'), /kelipatan 90/);
result = second.simulate('forward(3)\nleft(90)\nforward(1)');
near(result.state.x, 270); near(result.state.y, 290); near(result.state.angle, 270);
const third = new TurtleEngine(levels[2]);
result = third.simulate('penup()\nforward(2)\npendown()'); near(result.state.x, 210); assert.equal(result.state.penDown, true);
const legacy = { ...levels[0] }; delete legacy.movement_unit; delete legacy.step_size;
near(new TurtleEngine(legacy).simulate('forward(60)').state.x, 150);

// Render the actual grid method with a recording canvas context.
const tileCounts = [4, 10, 15];
for (const [i, level] of levels.entries()) {
    const tiles = [];
    const ctx = new Proxy({}, { get: (_, name) => (...args) => { if (name === 'roundRect') tiles.push(args); }, set: () => true });
    const world = Object.create(ForestGame.prototype); world.level = level; world.ctx = ctx;
    world.drawGrid(); assert.equal(tiles.length, tileCounts[i]);
    assert.ok(tiles.every(([, , width, height]) => width === 52 && height === 52));
}
console.log('PASS: 3 chapters solved with whole 1–3 step legs; 1 step = 60 map units; checkpoints, collisions, turns, backward, pen states, checkpoint saves, and visible grid tiles.');
