import { parse } from './parser.js';
export class TurtleEngine {
    constructor(level) {
        this.level = level;
        this.reset();
    }
    reset() {
        this.state = { ...this.level.start, penDown: true, checkpoint: 0 };
    }
    inside(x, y) {
        return this.level.path.some(
            ([px, py, w, h]) => x >= px + 5 && x <= px + w - 5 && y >= py + 5 && y <= py + h - 5,
        );
    }
    simulate(code) {
        this.reset();
        const grid = this.level.movement_unit === 'grid';
        const stepSize = grid ? this.level.step_size : 1;
        const tolerance = grid ? 0.001 : 12;
        let commands;
        try {
            commands = parse(code, this.level.commands);
        } catch (error) {
            if (grid) error.message = error.message.replaceAll('forward(100)', 'forward(3)');
            throw error;
        }
        for (const command of commands) {
            const movement = ['forward', 'backward'].includes(command.name);
            if (grid && movement && !Number.isInteger(command.value)) {
                const error = new Error(
                    `Ada masalah pada baris ${command.line}. Jumlah langkah harus angka bulat. Contoh: forward(1), forward(2), atau forward(3).`,
                );
                error.line = command.line;
                throw error;
            }
            if (grid && ['left', 'right'].includes(command.name) && command.value % 90 !== 0) {
                const error = new Error(
                    `Ada masalah pada baris ${command.line}. Belokan pada grid memakai kelipatan 90°. Contoh: left(90) atau right(90).`,
                );
                error.line = command.line;
                throw error;
            }
        }
        const frames = [],
            checkpointPrograms = [],
            done = [];
        for (const command of commands) {
            const { name, value, line } = command;
            if (name === 'left' || name === 'right')
                this.state.angle =
                    (this.state.angle + (name === 'right' ? value : -value) + 3600) % 360;
            else if (name === 'penup' || name === 'pendown')
                this.state.penDown = name === 'pendown';
            else {
                const distance = (name === 'backward' ? -value : value) * stepSize,
                    steps = Math.max(1, Math.ceil(Math.abs(distance) / 2));
                for (let i = 0; i < steps; i++) {
                    const angle = (this.state.angle * Math.PI) / 180;
                    const x = this.state.x + (Math.cos(angle) * distance) / steps,
                        y = this.state.y + (Math.sin(angle) * distance) / steps;
                    if (!this.inside(x, y))
                        return {
                            result: 'PARTIAL',
                            state: { ...this.state },
                            frames,
                            checkpointPrograms,
                            line,
                            error_type: 'collision',
                            message:
                                'Shiha berhenti di tepi jalan. Hitung kembali kotak atau periksa arah belokan.',
                        };
                    this.state.x = x;
                    this.state.y = y;
                    const cp = this.level.checkpoints[this.state.checkpoint];
                    if (cp && Math.hypot(x - cp[0], y - cp[1]) <= tolerance) {
                        this.state.checkpoint++;
                        checkpointPrograms.push({
                            frame: frames.length,
                            code: [
                                ...done,
                                name + '(' + ((value * (i + 1)) / steps).toFixed(8) + ')',
                            ].join('\n'),
                        });
                    }
                    frames.push({ ...this.state, line, moving: true });
                }
            }
            frames.push({ ...this.state, line, moving: false });
            done.push(name + '(' + (['penup', 'pendown'].includes(name) ? '' : value) + ')');
        }
        const goal = this.level.checkpoints.at(-1);
        const success =
            this.state.checkpoint === this.level.checkpoints.length &&
            Math.hypot(this.state.x - goal[0], this.state.y - goal[1]) <= tolerance;
        if (success && this.level.require_variables && !commands.usedVariable) {
            return {
                result: 'PARTIAL',
                state: { ...this.state },
                frames,
                checkpointPrograms,
                error_type: 'variable_required',
                message:
                    'Jalurnya sudah benar! Sekarang simpan jarak atau sudut pada variabel dan gunakan variabel itu dalam perintah gerak.',
            };
        }
        return {
            result: success ? 'SUCCESS' : 'PARTIAL',
            state: { ...this.state },
            frames,
            checkpointPrograms,
            error_type: null,
            message: success
                ? 'Semua penanda terlewati. Hebat, kamu menemukan jalannya!'
                : 'Langkah bagus! Tambahkan perintah untuk mencapai semua penanda dan tujuan.',
        };
    }
}
