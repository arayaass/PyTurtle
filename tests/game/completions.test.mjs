import assert from 'node:assert/strict';
import { turtleCompletions } from '../../resources/js/game/turtle/completions.js';
const context = { explicit: true, pos: 0, matchBefore: () => null };
assert.deepEqual(
    turtleCompletions(['forward', 'left', 'right', 'backward', 'print'])(context).options.map(
        (o) => o.label,
    ),
    ['forward', 'left', 'right', 'variabel', 'for', 'range'],
);
assert.deepEqual(
    turtleCompletions(['forward'])(context).options.map((o) => o.apply),
    ['forward(1)', 'langkah = 3', 'for i in range(3):\n    forward(1)', 'range(jumlah)'],
);
assert.equal(turtleCompletions(['forward'])({ ...context, explicit: false }), null);

const docContext = {
    ...context,
    pos: 100,
    state: {
        doc: {
            sliceString: () =>
                'jarak = 3\nputaran = 2\nfor ulang in range(putaran):\n    forward(jarak)',
        },
    },
};
const labels = turtleCompletions(['forward'])(docContext).options.map((option) => option.label);
assert.ok(labels.includes('jarak'));
assert.ok(labels.includes('putaran'));
assert.ok(labels.includes('ulang'));
assert.ok(!labels.includes('backward'));
