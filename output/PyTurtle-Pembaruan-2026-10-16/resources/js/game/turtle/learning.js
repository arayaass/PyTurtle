import { TurtleEngine } from './engine.js';

export function traceProgram(level, code) {
    const simulation = new TurtleEngine(level).simulate(code);
    const steps = simulation.frames.filter((frame) => !frame.moving);
    if (simulation.error_type === 'collision') {
        steps.push({ ...simulation.state, line: simulation.line, moving: false });
    }
    return { ...simulation, steps };
}

export function describeState(level, state) {
    const size = level.step_size || 1;
    const round = (number) => Math.round(number * 100) / 100;
    const angle = ((state.angle % 360) + 360) % 360;
    return {
        x: round((state.x - level.start.x) / size),
        y: round((state.y - level.start.y) / size),
        angle,
        direction:
            { 0: 'Timur →', 90: 'Selatan ↓', 180: 'Barat ←', 270: 'Utara ↑' }[angle] || `${angle}°`,
    };
}

export function comparePrediction(level, state, prediction) {
    const actual = describeState(level, state);
    return (
        Math.abs(actual.x - Number(prediction.x)) < 0.01 &&
        Math.abs(actual.y - Number(prediction.y)) < 0.01 &&
        actual.angle === Number(prediction.angle)
    );
}
