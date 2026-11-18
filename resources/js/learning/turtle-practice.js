import { parse } from '../game/turtle/parser.js';

const initialState = () => ({ x: 0, y: 0, heading: 0, penDown: true });
const normalize = (angle) => ((angle % 360) + 360) % 360;
const number = (value) => Number(value.toFixed(2));

// Build the complete route once so the canvas stays still during animation.
export function buildMovement(code) {
    const commands = parse(code);
    const state = initialState();
    const points = [{ x: 0, y: 0 }];
    const segments = [];
    const steps = [];
    let duration = 0;
    for (const command of commands) {
        const { name, value } = command;
        const before = { ...state };
        let turn = 0,
            segment = null,
            milliseconds = 280;
        if (name === 'left' || name === 'right') {
            turn = name === 'left' ? value : -value;
            state.heading = normalize(state.heading + turn);
            milliseconds = Math.max(240, (Math.abs(turn) / 140) * 1000);
        } else if (name === 'penup' || name === 'pendown') {
            state.penDown = name === 'pendown';
        } else {
            const distance = name === 'backward' ? -value : value;
            const radians = (state.heading * Math.PI) / 180;
            state.x += Math.cos(radians) * distance;
            state.y += Math.sin(radians) * distance;
            const next = { x: state.x, y: state.y };
            if (state.penDown) {
                segment = { from: { x: before.x, y: before.y }, to: next };
                segments.push(segment);
            }
            points.push(next);
            milliseconds = Math.max(240, (Math.abs(distance) / 85) * 1000);
        }
        steps.push({
            ...command,
            before,
            after: { ...state },
            turn,
            segment,
            start: duration,
            duration: milliseconds,
        });
        duration += milliseconds;
    }
    return { state: { ...state }, points, segments, count: commands.length, steps, duration };
}

export function simulateMovement(code) {
    return buildMovement(code);
}

// Sample a route at a given time: movement and turns both happen gradually.
export function movementFrame(plan, elapsed) {
    const time = Math.max(0, Math.min(elapsed, plan.duration));
    const segments = [];
    let state = initialState(),
        completed = 0,
        current = null;
    for (const step of plan.steps) {
        if (time >= step.start + step.duration) {
            state = { ...step.after };
            if (step.segment) segments.push(step.segment);
            completed++;
            continue;
        }
        current = step;
        const fraction = (time - step.start) / step.duration;
        state = {
            x: step.before.x + (step.after.x - step.before.x) * fraction,
            y: step.before.y + (step.after.y - step.before.y) * fraction,
            heading: normalize(step.before.heading + step.turn * fraction),
            penDown: step.before.penDown,
        };
        if (step.name === 'penup' || step.name === 'pendown') state.penDown = step.after.penDown;
        if (step.segment && fraction > 0)
            segments.push({ from: step.segment.from, to: { x: state.x, y: state.y } });
        break;
    }
    return {
        state,
        points: plan.points,
        segments,
        completed,
        current,
        done: time >= plan.duration,
    };
}

export function drawMovement(canvas, result) {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const { width, height } = canvas;
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#fbfcf8';
    ctx.fillRect(0, 0, width, height);
    ctx.strokeStyle = '#edf1e8';
    ctx.lineWidth = 1;
    ctx.beginPath();
    for (let x = 0; x < width; x += 32) {
        ctx.moveTo(x, 0);
        ctx.lineTo(x, height);
    }
    for (let y = 0; y < height; y += 32) {
        ctx.moveTo(0, y);
        ctx.lineTo(width, y);
    }
    ctx.stroke();
    const xs = result.points.map((p) => p.x),
        ys = result.points.map((p) => p.y);
    const minX = Math.min(...xs),
        maxX = Math.max(...xs);
    const minY = Math.min(...ys),
        maxY = Math.max(...ys);
    const scale = Math.min(
        1.5,
        (width - 110) / Math.max(1, maxX - minX),
        (height - 110) / Math.max(1, maxY - minY),
    );
    const middleX = (minX + maxX) / 2,
        middleY = (minY + maxY) / 2;
    const project = (p) => ({
        x: width / 2 + (p.x - middleX) * scale,
        y: height / 2 - (p.y - middleY) * scale,
    });
    const start = project({ x: 0, y: 0 });
    ctx.strokeStyle = '#d8e1d1';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(start.x, 38);
    ctx.lineTo(start.x, height - 25);
    ctx.moveTo(25, start.y);
    ctx.lineTo(width - 25, start.y);
    ctx.stroke();
    ctx.strokeStyle = '#28755a';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.beginPath();
    for (const segment of result.segments) {
        const a = project(segment.from),
            b = project(segment.to);
        ctx.moveTo(a.x, a.y);
        ctx.lineTo(b.x, b.y);
    }
    ctx.stroke();
    ctx.fillStyle = '#d49a48';
    ctx.beginPath();
    ctx.arc(start.x, start.y, 5, 0, Math.PI * 2);
    ctx.fill();
    const turtle = project(result.state);
    ctx.save();
    ctx.translate(turtle.x, turtle.y);
    ctx.rotate((-result.state.heading * Math.PI) / 180);
    // A small Turtle facing east; its shell, head, and feet rotate together.
    ctx.fillStyle = '#376e50';
    for (const y of [-8, 8])
        for (const x of [-7, 6]) {
            ctx.beginPath();
            ctx.ellipse(x, y, 5, 3, x > 0 ? 0.6 : -0.6, 0, Math.PI * 2);
            ctx.fill();
        }
    ctx.beginPath();
    ctx.ellipse(15, 0, 6, 5, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.beginPath();
    ctx.moveTo(-10, -3);
    ctx.lineTo(-17, 0);
    ctx.lineTo(-10, 3);
    ctx.fill();
    ctx.fillStyle = result.state.penDown ? '#73aa67' : '#c8d2c1';
    ctx.strokeStyle = '#28593f';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.ellipse(0, 0, 12, 9, 0, 0, Math.PI * 2);
    ctx.fill();
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(-7, 0);
    ctx.lineTo(7, 0);
    ctx.moveTo(0, -7);
    ctx.lineTo(0, 7);
    ctx.stroke();
    ctx.fillStyle = '#fff';
    ctx.beginPath();
    ctx.arc(17, -2, 1.5, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
    ctx.fillStyle = '#64755a';
    ctx.font = '14px Segoe UI, sans-serif';
    ctx.fillText('UTARA ↑', 20, 27);
}

export default function turtlePractice(initialCode = '') {
    // Keep frame handles outside Alpine's reactive state.
    let frameId = null,
        editTimer = null,
        plan = null,
        elapsed = 0,
        previousTime = null,
        destroyed = false;
    const cancelFrame = () => {
        if (frameId !== null) cancelAnimationFrame(frameId);
        frameId = null;
        previousTime = null;
    };
    const cancelEdit = () => {
        if (editTimer !== null) clearTimeout(editTimer);
        editTimer = null;
    };
    return {
        code: initialCode,
        error: false,
        feedback: 'Jalankan kode untuk melihat Turtle bergerak.',
        running: false,
        paused: false,
        autoRun: true,
        speed: 1,
        progress: 0,
        completed: 0,
        total: 0,
        activeLine: null,
        activeCommand: '',
        position: '(0, 0)',
        heading: 0,
        penDown: true,
        init() {
            this.$nextTick(() => {
                if (!destroyed) this.run();
            });
        },
        destroy() {
            destroyed = true;
            cancelFrame();
            cancelEdit();
        },
        paint(result) {
            drawMovement(this.$refs.canvas, result);
            const state = result.state;
            this.position = `(${number(state.x)}, ${number(state.y)})`;
            this.heading = number(state.heading);
            this.penDown = state.penDown;
            this.completed = result.completed ?? 0;
            this.progress = plan ? Math.min(100, (elapsed / plan.duration) * 100) : 0;
            const current = result.current;
            const line = current?.line ?? null;
            if (line !== this.activeLine) {
                this.activeLine = line;
                this.activeCommand = current
                    ? `${current.name}(${['penup', 'pendown'].includes(current.name) ? '' : current.value})`
                    : '';
                if (current && this.running) this.feedback = `Baris ${line}: ${this.activeCommand}`;
            }
        },
        schedule() {
            frameId = requestAnimationFrame((timestamp) => {
                frameId = null;
                if (!this.running || !plan || destroyed) return;
                if (previousTime !== null)
                    elapsed = Math.min(
                        plan.duration,
                        elapsed +
                            Math.min(100, Math.max(0, timestamp - previousTime)) *
                                Number(this.speed),
                    );
                previousTime = timestamp;
                const result = movementFrame(plan, elapsed);
                this.paint(result);
                if (result.done) {
                    this.running = false;
                    this.paused = false;
                    previousTime = null;
                    this.feedback = `${plan.count} perintah selesai. Posisi ${this.position}, arah ${this.heading}°. Pena ${this.penDown ? 'turun' : 'terangkat'}.`;
                } else this.schedule();
            });
        },
        run() {
            cancelFrame();
            cancelEdit();
            this.running = false;
            this.paused = false;
            this.activeLine = null;
            this.activeCommand = '';
            this.error = false;
            elapsed = 0;
            try {
                plan = buildMovement(this.code);
                this.total = plan.count;
                this.running = true;
                this.paint(movementFrame(plan, 0));
                this.schedule();
            } catch (error) {
                plan = null;
                this.total = 0;
                this.error = true;
                this.paint({ state: initialState(), points: [{ x: 0, y: 0 }], segments: [] });
                this.activeLine = error.line ?? null;
                this.feedback = error.message;
            }
        },
        pauseOrResume() {
            if (this.running) {
                cancelFrame();
                this.running = false;
                this.paused = true;
                this.feedback = `Dijeda pada baris ${this.activeLine}. Klik Lanjutkan untuk bergerak lagi.`;
            } else if (this.paused && plan) {
                this.paused = false;
                this.running = true;
                this.feedback = `Baris ${this.activeLine}: ${this.activeCommand}`;
                this.schedule();
            }
        },
        stop() {
            cancelFrame();
            cancelEdit();
            this.running = false;
            this.paused = false;
            this.feedback = 'Gerakan dihentikan. Klik Jalankan untuk mengulang dari awal.';
        },
        codeEdited(value) {
            this.code = value;
            cancelFrame();
            cancelEdit();
            this.running = false;
            this.paused = false;
            this.error = false;
            this.activeLine = null;
            this.activeCommand = '';
            this.feedback = this.autoRun
                ? 'Kode berubah. Gerakan dimulai setelah kamu selesai mengetik.'
                : 'Kode berubah. Klik Jalankan untuk mencoba.';
            if (this.autoRun)
                editTimer = setTimeout(() => {
                    editTimer = null;
                    if (!destroyed) this.run();
                }, 650);
        },
        autoRunChanged(value) {
            this.autoRun = value;
            if (!value) cancelEdit();
        },
        reset() {
            this.code = initialCode;
            this.run();
        },
    };
}
