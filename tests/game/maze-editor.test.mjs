import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { TurtleEngine } from '../../resources/js/game/turtle/engine.js';
const levels = JSON.parse(
    readFileSync(new URL('../../resources/data/levels.json', import.meta.url)),
);
let data,
    latestEditor,
    latestWorld,
    draftReads = 0;
const saved = [];
const doc = (value) => ({ length: value.length, toString: () => value });
class EditorView {
    constructor(options) {
        this.state = { doc: doc(options.state.doc) };
        latestEditor = this;
    }
    dispatch({ changes }) {
        if (changes) this.state.doc = doc(changes.insert);
    }
    focus() {}
    static lineWrapping = {};
    static contentAttributes = { of: () => ({}) };
}
class ForestGame {
    constructor(canvas, level) {
        this.level = level;
        this.player = { ...level.start };
        latestWorld = this;
    }
    reset() {
        this.player = { ...this.level.start };
        this.complete = false;
    }
    dispose() {}
}
class ForestAudio {
    start() {}
    complete() {}
}
const context = vm.createContext({
    console,
    setInterval: () => 1,
    clearInterval() {},
    cancelAnimationFrame() {},
    requestAnimationFrame: () => 1,
    matchMedia: () => ({ matches: false }),
    document: {
        getElementById: (id) => (id === 'maze-data' ? { textContent: JSON.stringify(data) } : {}),
        body: { dataset: { version: '1' } },
    },
    window: { addEventListener() {} },
    localStorage: {
        getItem() {
            draftReads++;
            return 'forward(180)';
        },
    },
});
const dependencies = {
    '@codemirror/view': {
        EditorView,
        keymap: { of: () => ({}) },
        lineNumbers: () => ({}),
        highlightActiveLine: () => ({}),
        highlightActiveLineGutter: () => ({}),
    },
    '@codemirror/state': {
        EditorState: { create: (value) => value, readOnly: { of: (value) => value } },
        Compartment: class {
            of(value) {
                return value;
            }
            reconfigure(value) {
                return value;
            }
        },
    },
    '@codemirror/autocomplete': { autocompletion: () => ({}) },
    './turtle/completions': { turtleCompletions: () => () => null },
    '@codemirror/lang-python': { python: () => ({}) },
    codemirror: { basicSetup: {} },
    './turtle/engine': { TurtleEngine },
    './Game': { ForestGame },
    './systems/audio': { ForestAudio },
    './systems/save-queue': {
        enqueue: (...args) => {
            saved.push(args);
            return 'id';
        },
        post: async () => ({}),
    },
};
const source = readFileSync(new URL('../../resources/js/game/maze.js', import.meta.url), 'utf8');
const module = new vm.SourceTextModule(source, { context });
await module.link(async (name) => {
    const values = dependencies[name];
    assert.ok(values, name);
    return new vm.SyntheticModule(
        Object.keys(values),
        function () {
            for (const [key, value] of Object.entries(values)) this.setExport(key, value);
        },
        { context },
    );
});
await module.evaluate();
for (const level of levels) {
    data = {
        level,
        settings: {},
        story: `playing:${level.id}`,
        progress: {
            code: 'forward(180)',
            state: { x: 450, y: 270, checkpoint: 2 },
            attempts: 1,
            status: 'partial',
        },
    };
    const game = module.namespace.default();
    game.boot();
    assert.equal(latestEditor.state.doc.toString(), '');
    assert.equal(latestWorld.player.x, level.start.x);
    assert.equal(latestWorld.player.y, level.start.y);
    assert.equal(game.checkpoint, 0);
    await game.run();
    assert.equal(game.result, 'ERROR');
    assert.match(game.message, /Editor masih kosong/);
    assert.equal(saved.length, 0);
    latestEditor.dispatch({ changes: { insert: 'forward(3)' } });
    game.reset();
    assert.equal(latestEditor.state.doc.toString(), '');
    assert.equal(game.result, 'READY');
}
assert.equal(draftReads, 0);
data = {
    level: levels[0],
    settings: {},
    story: 'playing:1',
    progress: { attempts: 1, code: 'forward(180)', status: 'completed', best_score: 95 },
};
const completedGame = module.namespace.default();
completedGame.boot();
assert.equal(latestEditor.state.doc.toString(), '');
assert.equal(completedGame.score, 95);
console.log(
    'PASS: all Maze editors open/reset empty, saved/local drafts are not loaded, player starts at origin, blank runs are not queued, and best scores are retained.',
);
