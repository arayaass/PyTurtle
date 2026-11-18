import assert from 'node:assert/strict';
import { turtleCompletions } from '../../resources/js/game/turtle/completions.js';
const context = { explicit: true, pos: 0, matchBefore: () => null };
assert.deepEqual(
    turtleCompletions(['forward', 'left', 'right', 'backward', 'print'])(context).options.map(
        (o) => o.label,
    ),
    ['forward', 'left', 'right'],
);
assert.deepEqual(
    turtleCompletions(['forward'])(context).options.map((o) => o.apply),
    ['forward(1)'],
);
assert.equal(turtleCompletions(['forward'])({ ...context, explicit: false }), null);
