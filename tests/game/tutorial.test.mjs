import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { parse } from '../../resources/js/game/turtle/parser.js';
import { TurtleEngine } from '../../resources/js/game/turtle/engine.js';
const levels = JSON.parse(fs.readFileSync('resources/data/levels.json', 'utf8'));
test('variables and nested loops compile with bounded work and source lines', () => {
    const commands = parse('n = 2\nfor i in range(n):\n    for j in range(2):\n        forward(n)');
    assert.equal(commands.length, 4);
    assert.equal(commands[0].value, 2);
    assert.equal(commands[0].line, 4);
    for (const code of [
        'for i in range(1000):\n    forward(1)',
        'for i in range(3):\nforward(1)',
        'forward(missing)',
        'x = __import__(1)',
        'for i in range(100):\n    for j in range(100):\n        x = 1',
        'for i in range(100):\n    forward(1)\n    forward(1)',
    ])
        assert.throws(() => parse(code));
});
test('all tutorial stages require a variable and late levels accept loops', () => {
    for (const stage of levels[0].tutorial_stages) {
        const l = { ...levels[0], ...stage };
        const engine = new TurtleEngine(l);
        assert.equal(engine.simulate(stage.solution).result, 'SUCCESS');
        assert.equal(
            engine.simulate(
                stage.solution
                    .replaceAll('forward(langkah)', 'forward(3)')
                    .replaceAll('left(sudut)', 'left(90)')
                    .replaceAll('right(sudut)', 'right(90)'),
            ).error_type,
            'variable_required',
        );
    }
    for (const l of levels.slice(3)) {
        assert.match(l.solution, /for i in range/);
        assert.equal(new TurtleEngine(l).simulate(l.solution).result, 'SUCCESS');
    }
});
