import { post } from './systems/save-queue';

export default function logicChallenge() {
    return {
        id: 0,
        definition: {},
        completed: false,
        angle: 0,
        distance: 2,
        result: null,
        busy: false,
        answer: '',
        correct: false,
        message: '',
        answerMessage: '',
        boot() {
            Object.assign(this, JSON.parse(document.getElementById('logic-data').textContent));
            this.draw();
        },
        direction(angle) {
            return { 0: 'kanan → (Timur)', 90: 'atas ↑ (Utara)', 180: 'kiri ← (Barat)', 270: 'bawah ↓ (Selatan)' }[angle];
        },
        position(state) {
            const parts = [];
            if (state.x) parts.push(`${Math.abs(state.x)} kotak ke ${state.x > 0 ? 'kanan' : 'kiri'}`);
            if (state.y) parts.push(`${Math.abs(state.y)} kotak ke ${state.y > 0 ? 'atas' : 'bawah'}`);
            return parts.length ? parts.join(' dan ') + ' dari titik awal' : 'Tetap di titik awal';
        },
        program(commands) {
            return (commands || [])
                .map(([name, value]) => `${name}(${value === 'distance' ? this.distance : value})`)
                .join('\n');
        },
        get observations() {
            return this.result
                ? [
                      { name: 'A', ...this.result.a },
                      ...(this.result.b ? [{ name: 'B', ...this.result.b }] : []),
                  ]
                : [];
        },
        invalidate() {
            this.result = null;
            this.answer = '';
            this.correct = false;
            this.message = 'Kondisi berubah. Jalankan eksperimen lagi.';
            this.answerMessage = '';
            this.draw();
        },
        async experiment() {
            if (this.busy) return;
            this.busy = true;
            this.result = null;
            this.answer = '';
            this.correct = false;
            this.answerMessage = '';
            this.message = '';
            this.draw();
            try {
                this.result = await post(`/uji-logika/${this.id}/experiment`, {
                    angle: this.angle,
                    distance: this.distance,
                    request_id: crypto.randomUUID(),
                });
                this.message = this.result.counterexample
                    ? 'Ketemu! Hasil percobaan ini tidak cocok dengan kalimat di atas. Artinya, kalimat itu tidak selalu benar. Pilih alasannya di bagian 3.'
                    : 'Hasilnya masih cocok dengan kalimat di atas. Sekarang coba arah atau jarak lain. Apakah hasilnya tetap cocok?';
                this.draw();
            } catch (error) {
                this.message = 'Eksperimen belum tersimpan. ' + error.message;
            } finally {
                this.busy = false;
            }
        },
        async submit() {
            if (this.busy || !this.result?.counterexample || this.answer === '' || this.correct)
                return;
            this.busy = true;
            try {
                const response = await post(`/uji-logika/${this.id}/answer`, {
                    experiment_id: this.result.experiment_id,
                    answer: Number(this.answer),
                });
                this.correct = response.correct;
                this.answerMessage = response.message;
                if (response.correct) {
                    this.completed = true;
                    this.answerMessage =
                        '✓ Tantangan selesai dan progress tersimpan. ' + response.message;
                }
            } catch (error) {
                this.answerMessage = 'Jawaban belum tersimpan. ' + error.message;
            } finally {
                this.busy = false;
            }
        },
        draw() {
            const canvas = this.$refs.board,
                ctx = canvas.getContext('2d'),
                unit = 24,
                cx = 320,
                cy = 240;
            ctx.clearRect(0, 0, 640, 480);
            ctx.fillStyle = '#f5f8ef';
            ctx.fillRect(0, 0, 640, 480);
            ctx.strokeStyle = '#dbe4d2';
            ctx.lineWidth = 1;
            for (let x = 8; x < 640; x += unit) {
                ctx.beginPath();
                ctx.moveTo(x, 0);
                ctx.lineTo(x, 480);
                ctx.stroke();
            }
            for (let y = 0; y < 480; y += unit) {
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(640, y);
                ctx.stroke();
            }
            ctx.fillStyle = '#405c50';
            ctx.font = '14px sans-serif';
            ctx.fillText('Utara (+Y) ↑', 12, 24);
            ctx.fillText('Timur (+X) →', 510, 465);
            for (const row of this.observations) {
                const color = row.name === 'A' ? '#246c51' : '#b35a15';
                ctx.strokeStyle = color;
                ctx.fillStyle = color;
                ctx.lineWidth = 3;
                ctx.setLineDash(row.name === 'B' ? [7, 5] : []);
                ctx.beginPath();
                row.steps.forEach((s, i) => {
                    const x = cx + s.x * unit,
                        y = cy - s.y * unit;
                    i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
                });
                ctx.stroke();
                ctx.setLineDash([]);
                row.steps.slice(1).forEach((s, i) => {
                    ctx.fillText(
                        String(i + 1),
                        cx + s.x * unit + 6,
                        cy - s.y * unit + (row.name === 'A' ? -8 : 18),
                    );
                });
                ctx.save();
                ctx.translate(cx + row.x * unit, cy - row.y * unit);
                ctx.rotate((-row.angle * Math.PI) / 180);
                ctx.beginPath();
                ctx.moveTo(12, 0);
                ctx.lineTo(-8, -7);
                ctx.lineTo(-8, 7);
                ctx.closePath();
                ctx.fill();
                ctx.restore();
                ctx.fillText(
                    row.name,
                    cx + row.x * unit - 18,
                    cy - row.y * unit + (row.name === 'A' ? -12 : 24),
                );
            }
            ctx.strokeStyle = '#405c50';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.arc(cx, cy, 5, 0, 2 * Math.PI);
            ctx.stroke();
            ctx.fillStyle = '#405c50';
            ctx.fillText('(0, 0)', cx + 8, cy + 34);
        },
    };
}
