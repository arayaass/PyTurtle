/** Original procedural forest art. Coordinates remain logical 600 × 460. */
export class ForestGame {
    constructor(canvas, level, reducedMotion = false) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.level = level;
        this.reducedMotion = reducedMotion;
        this.player = { ...level.start, penDown: true, checkpoint: 0 };
        this.trail = [];
        this.moving = false;
        this.complete = false;
        this.lastFrame = 0;
        this.frameId = requestAnimationFrame((t) => this.loop(t));
    }
    reset(state = null) {
        this.player = state ? { ...state } : { ...this.level.start, penDown: true, checkpoint: 0 };
        this.trail = [];
        this.moving = false;
        this.complete = false;
    }
    setPlayer(state) {
        const old = this.player;
        if (state.penDown && Math.hypot(old.x - state.x, old.y - state.y) > 0)
            this.trail.push([old.x, old.y, state.x, state.y]);
        this.player = { ...state };
        this.moving = Boolean(state.moving);
    }
    loop(time) {
        this.frameId = requestAnimationFrame((t) => this.loop(t));
        if (document.hidden || time - this.lastFrame < 1000 / (this.reducedMotion ? 12 : 30))
            return;
        this.lastFrame = time;
        this.draw(this.reducedMotion ? 0 : time / 1000);
    }
    dispose() {
        cancelAnimationFrame(this.frameId);
    }
    ellipse(x, y, rx, ry, color) {
        const c = this.ctx;
        c.fillStyle = color;
        c.beginPath();
        c.ellipse(x, y, rx, ry, 0, 0, Math.PI * 2);
        c.fill();
    }
    rect(x, y, w, h, color, r = 0) {
        const c = this.ctx;
        c.fillStyle = color;
        c.beginPath();
        c.roundRect(x, y, w, h, r);
        c.fill();
    }
    line(points, color, width = 1) {
        const c = this.ctx;
        c.strokeStyle = color;
        c.lineWidth = width;
        c.lineCap = 'round';
        c.beginPath();
        points.forEach(([x, y], i) => (i ? c.lineTo(x, y) : c.moveTo(x, y)));
        c.stroke();
    }
    onPath(x, y, padding = 0) {
        return this.level.path.some(
            ([px, py, w, h]) =>
                x >= px - padding &&
                x <= px + w + padding &&
                y >= py - padding &&
                y <= py + h + padding,
        );
    }
    tree(x, y, size, time, index) {
        const c = this.ctx;
        c.save();
        c.translate(x, y);
        c.scale(size, size);
        const sway = Math.sin(time * 0.65 + index) * 1.2;
        this.ellipse(4, 13, 26, 10, '#426d4933');
        this.rect(-4, -12, 8, 30, '#8d7652', 2);
        this.ellipse(sway, -14, 26, 23, '#367456');
        this.ellipse(-10 + sway, -23, 19, 20, '#4e8a5c');
        this.ellipse(11 + sway, -27, 20, 18, '#69a264');
        this.ellipse(sway, -37, 15, 13, '#83b372');
        this.line(
            [
                [-13, -26],
                [-17, -21],
            ],
            '#93be7822',
            3,
        );
        c.restore();
    }
    shiha(time) {
        const c = this.ctx,
            p = this.player,
            walking = this.moving && !this.reducedMotion,
            bob = walking ? Math.sin(time * 15) * 1.1 : 0;
        c.save();
        c.translate(p.x, p.y + bob);
        this.ellipse(0, 7, 12, 5, '#324d3930');
        const step = walking ? Math.sin(time * 15) * 3 : 0,
            angle = (p.angle + 360) % 360;
        const up = angle > 225 && angle < 315,
            left = angle >= 135 && angle <= 225,
            right = angle < 45 || angle >= 315;
        // Draw a real side silhouette, rather than hiding one eye on a front-facing body.
        // Mirroring the entire pose keeps the backpack, shoulder, feet and face aligned.
        if (left || right) {
            if (left) c.scale(-1, 1);
            const stride = walking ? Math.sin(time * 15) * 4 : 0;
            // Far leg and arm are behind the torso; feet point along the path.
            this.line(
                [
                    [-2, 0],
                    [-2 - stride, 8],
                ],
                '#344852',
                5,
            );
            this.rect(-4 - stride, 7, 10, 4, '#584a3e', 2);
            this.line(
                [
                    [1, -12],
                    [2 + stride, -2],
                ],
                '#d4a478',
                4,
            );
            // Narrow torso in profile, with a backpack projecting behind the shoulder.
            this.rect(-7, -16, 13, 21, '#df8d48', 4);
            this.rect(-14, -15, 8, 18, '#9e7843', 3);
            this.rect(-14, -12, 4, 10, '#b78f50', 2);
            this.line(
                [
                    [-5, -14],
                    [-3, 0],
                ],
                '#f1ce83',
                2,
            );
            this.line(
                [
                    [1, 1],
                    [1 + stride, 9],
                ],
                '#3d5560',
                5,
            );
            this.rect(-1 + stride, 8, 11, 4, '#655245', 2);
            this.rect(-2, -13, 7, 7, '#eea25c', 3);
            this.line(
                [
                    [1, -8],
                    [1 - stride, 0],
                ],
                '#f3c494',
                5,
            );
            // Side of the head: hair at the back, nose and one eye toward the route.
            this.rect(-8, -33, 17, 20, '#f3c494', 6);
            this.rect(-9, -34, 8, 17, '#695139', 4);
            this.rect(-6, -35, 16, 8, '#695139', 4);
            this.rect(7, -23, 4, 5, '#f3c494', 2);
            this.rect(5, -25, 2, 3, '#433d32', 1);
            this.ellipse(-1, -23, 2, 3, '#dfaf7e');
            this.line(
                [
                    [5, -17],
                    [8, -17],
                ],
                '#a76c49',
                1,
            );
            this.ellipse(1, -34, 14, 4, '#a09360');
            this.rect(-9, -44, 19, 11, '#c8b47c', 6);
            this.rect(-9, -36, 19, 4, '#657a47', 1);
            c.restore();
            return;
        }
        this.rect(-7, 0 + step, 5, 9, '#3d5560', 2);
        this.rect(2, 0 - step, 5, 9, '#3d5560', 2);
        this.rect(-8, 7 + step, 7, 4, '#655245', 1);
        this.rect(2, 7 - step, 7, 4, '#655245', 1);
        this.rect(-10, -16, 20, 21, '#e59856', 5);
        if (!up) this.rect(-13, -13, 7, 15, '#b4874c', 3);
        this.rect(-12, -12, 5, 13, '#f3c494', 2);
        this.rect(8, -12, 5, 13, '#f3c494', 2);
        this.rect(-10, -33, 20, 20, '#f3c494', 7);
        this.rect(-11, -35, 22, 9, '#695139', 5);
        if (up) {
            this.rect(-10, -28, 20, 13, '#705638', 3);
            // Back-facing shoulders and a full backpack hide the front of the shirt.
            this.line(
                [
                    [-8, -14],
                    [-7, 1],
                ],
                '#caa36a',
                3,
            );
            this.line(
                [
                    [8, -14],
                    [7, 1],
                ],
                '#caa36a',
                3,
            );
            this.rect(-8, -16, 16, 20, '#a27b43', 4);
            this.rect(-6, -6, 12, 8, '#b58d50', 2);
            this.line(
                [
                    [-5, -11],
                    [5, -11],
                ],
                '#d8b070',
                2,
            );
        } else {
            if (!left) this.rect(4, -23, 2, 3, '#433d32');
            if (!right) this.rect(-6, -23, 2, 3, '#433d32');
            this.line(
                [
                    [-2, -17],
                    [2, -17],
                ],
                '#a76c49',
                1,
            );
            this.line(
                [
                    [-7, -12],
                    [0, -7],
                    [7, -12],
                ],
                '#f5df9c',
                3,
            );
        }
        this.ellipse(0, -34, 14, 5, '#a09360');
        this.rect(-10, -44, 20, 11, '#c8b47c', 7);
        this.rect(-10, -36, 20, 4, '#657a47', 1);
        c.restore();
    }
    drawGrid() {
        if (this.level.movement_unit !== 'grid') return;
        const c = this.ctx,
            size = this.level.step_size;
        const origin = this.level.start;
        c.save();
        for (
            let row = -Math.ceil(origin.y / size);
            row <= Math.ceil((460 - origin.y) / size);
            row++
        ) {
            for (
                let column = -Math.ceil(origin.x / size);
                column <= Math.ceil((600 - origin.x) / size);
                column++
            ) {
                const x = origin.x + column * size,
                    y = origin.y + row * size;
                if (!this.onPath(x, y, -5)) continue;
                const tile = size - 8;
                c.fillStyle = (row + column) % 2 === 0 ? '#fff9e044' : '#b79b6733';
                c.strokeStyle = '#806b4199';
                c.lineWidth = 1.7;
                c.beginPath();
                c.roundRect(x - tile / 2, y - tile / 2, tile, tile, 3);
                c.fill();
                c.stroke();
            }
        }
        c.restore();
    }
    draw(time) {
        const c = this.ctx;
        c.clearRect(0, 0, 600, 460);
        this.rect(0, 0, 600, 460, '#aac98c');
        for (let i = 0; i < 100; i++) {
            const x = (i * 137 + 39) % 600,
                y = (i * 83 + 26) % 460;
            this.ellipse(x, y, 14 + (i % 4) * 5, 7, '#a0c08244');
            if (!this.onPath(x, y, 15)) {
                const sway = Math.sin(time + i) * 1.4;
                this.line(
                    [
                        [x - 3, y],
                        [x - 4 + sway, y - 5],
                        [x, y],
                        [x + 2 + sway, y - 7],
                        [x + 3, y],
                    ],
                    '#7fa76888',
                    1,
                );
            }
        }
        // Shallow river: the bridge overlays it only where the walkable path crosses.
        if (this.level.id >= 2) {
            c.fillStyle = '#77b8ba';
            c.beginPath();
            c.moveTo(0, 225);
            c.bezierCurveTo(180, 193, 360, 249, 600, 214);
            c.lineTo(600, 264);
            c.bezierCurveTo(350, 303, 180, 241, 0, 274);
            c.fill();
            for (let i = 0; i < 24; i++) {
                const x = ((i * 81 + time * 9) % 620) - 10,
                    y = 232 + ((i * 19) % 35);
                this.line(
                    [
                        [x, y],
                        [x + 12, y - 1],
                    ],
                    '#b1d7c988',
                    2,
                );
            }
            const fx = 135 + Math.sin(time * 0.5) * 40;
            this.ellipse(fx, 246, 5, 2, '#528f9255');
        }
        // Forest roads remain underneath the visible step grid.
        for (const [x, y, w, h] of this.level.path)
            this.rect(x - 4, y - 4, w + 8, h + 8, '#91a774', 14);
        for (const [x, y, w, h] of this.level.path) this.rect(x, y, w, h, '#d5be8b', 10);
        for (const [x, y, w, h] of this.level.path)
            this.rect(x + 5, y + 5, w - 10, h - 10, '#e2cf9e', 6);
        for (let i = 0; i < 70; i++) {
            const x = (i * 137 + 17) % 600,
                y = (i * 61 + 9) % 460;
            if (this.onPath(x, y, -7)) this.ellipse(x, y, 1.2, 1, '#bba57266');
        }
        if (this.level.id === 2) {
            this.rect(242, 216, 56, 70, '#806f4d', 2);
            for (let y = 220; y < 281; y += 9) this.rect(247, y, 46, 7, '#d7b784', 1);
            this.line(
                [
                    [242, 213],
                    [242, 288],
                ],
                '#675e43',
                4,
            );
            this.line(
                [
                    [298, 213],
                    [298, 288],
                ],
                '#675e43',
                4,
            );
        }
        this.drawGrid();
        // Flowers, rocks, bushes and a fallen log stay away from the path.
        for (let i = 0; i < 37; i++) {
            const x = ((i * 149 + 41) % 570) + 10,
                y = ((i * 73 + 18) % 430) + 10;
            if (this.onPath(x, y, 24) || (this.level.id >= 2 && y > 210 && y < 285)) continue;
            if (i % 4 === 0) {
                this.ellipse(x, y, 12, 7, '#8caa76');
                this.ellipse(x - 3, y - 3, 9, 6, '#abb79a');
            } else if (i % 4 === 1) {
                this.ellipse(x, y, 13, 9, '#6e9a62');
                this.ellipse(x - 6, y - 3, 7, 7, '#83aa67');
            } else {
                this.line(
                    [
                        [x, y],
                        [x, y - 5],
                    ],
                    '#6f9252',
                );
                this.ellipse(x - 2, y - 6, 3, 2, i % 2 ? '#f2d99b' : '#e8b5a1');
                this.ellipse(x + 2, y - 6, 3, 2, i % 2 ? '#f2d99b' : '#e8b5a1');
            }
        }
        this.rect(38, 393, 48, 11, '#8f7650', 5);
        this.ellipse(83, 398, 5, 5, '#cdb583');
        if (this.level.id === 3) {
            for (const [x, y] of [
                [438, 90],
                [550, 100],
                [460, 145],
            ]) {
                this.rect(x, y - 19, 20, 28, '#a1ad8c', 3);
                this.rect(x - 3, y - 21, 26, 7, '#bcc4a5', 2);
                this.line(
                    [
                        [x + 4, y - 12],
                        [x + 12, y - 12],
                    ],
                    '#8b9a7b',
                );
            }
        }
        // Ordered checkpoints with labels, color is not the only cue.
        this.level.checkpoints.forEach(([x, y], i) => {
            const passed = i < this.player.checkpoint,
                final = i === this.level.checkpoints.length - 1;
            this.ellipse(x, y, 15, 8, passed ? '#8aa66e66' : '#fff8d977');
            if (final && this.level.id < 5) {
                this.rect(x - 3, y - 33, 5, 37, '#87734d', 1);
                this.line(
                    [
                        [x + 1, y - 31],
                        [x + 20, y - 27],
                        [x + 1, y - 17],
                    ],
                    passed ? '#74955a' : '#eee6b1',
                    8,
                );
                c.font = 'bold 8px Segoe UI';
                c.fillStyle = '#526645';
                c.textAlign = 'center';
                c.fillText('TUJUAN', x, y + 18);
            } else if (final) {
                this.rect(x - 12, y - 14, 24, 18, '#a98148', 3);
                this.rect(x - 12, y - 17, 24, 10, this.complete ? '#d8bd69' : '#c49b53', 4);
                if (this.complete) {
                    this.rect(x - 12, y - 27, 24, 10, '#d8bd69', 3);
                    this.rect(x - 9, y - 11, 18, 9, '#f7dd8a', 1);
                }
                this.rect(x - 2, y - 13, 4, 14, '#f0d484', 1);
                this.rect(x - 4, y - 7, 8, 5, '#786e41', 1);
                for (let k = 0; k < 4; k++) {
                    const a = time * 0.8 + (k * Math.PI) / 2,
                        xx = x + Math.cos(a) * 21,
                        yy = y - 12 + Math.sin(a) * 17;
                    this.line(
                        [
                            [xx - 2, yy],
                            [xx + 2, yy],
                        ],
                        '#fff2bb',
                        1.5,
                    );
                    this.line(
                        [
                            [xx, yy - 2],
                            [xx, yy + 2],
                        ],
                        '#fff2bb',
                        1.5,
                    );
                }
            } else {
                this.ellipse(x, y, 9, 9, passed ? '#6c9060' : '#fff7d6');
                c.fillStyle = passed ? '#fff' : '#8b8e60';
                c.font = 'bold 10px Segoe UI';
                c.textAlign = 'center';
                c.fillText(passed ? '✓' : String(i + 1), x, y + 3);
            }
        });
        // Trail.
        for (const [x, y, nx, ny] of this.trail)
            this.line(
                [
                    [x, y],
                    [nx, ny],
                ],
                '#5e8f7980',
                2,
            );
        // Sign at the start.
        const s = this.level.start;
        this.rect(s.x - 29, s.y - 32, 4, 22, '#8e7550');
        this.rect(s.x - 40, s.y - 36, 26, 12, '#c3a36e', 2);
        c.font = '8px Segoe UI';
        c.fillStyle = '#5d6745';
        c.textAlign = 'center';
        c.fillText('→', s.x - 27, s.y - 27);
        if (this.level.movement_unit === 'grid') {
            c.font = 'bold 8px Segoe UI';
            c.fillText('MULAI', s.x, s.y + 21);
        }
        const trees = [];
        for (let i = 0; i < 42; i++) {
            const x = (i * 113 + 17) % 600,
                y = (i * 67 + 15) % 460;
            if (!this.onPath(x, y, 42) && !(this.level.id >= 2 && y > 198 && y < 294))
                trees.push([x, y, 0.7 + (i % 4) * 0.12, i]);
        }
        trees
            .filter(([, y]) => y < this.player.y)
            .sort((a, b) => a[1] - b[1])
            .forEach(([x, y, s, i]) => this.tree(x, y, s, time, i));
        this.shiha(time);
        trees
            .filter(([, y]) => y >= this.player.y)
            .sort((a, b) => a[1] - b[1])
            .forEach(([x, y, s, i]) => this.tree(x, y, s, time, i));
        // Ambient rabbit, butterfly, bird and squirrel.
        const rabbitX = 148 + Math.max(0, Math.sin(time * 0.3)) * 18;
        this.ellipse(rabbitX, 407, 7, 4, '#efead8');
        this.ellipse(rabbitX + 5, 403, 4, 4, '#f5f0df');
        this.ellipse(rabbitX + 3, 397, 1.8, 5, '#f4efdf');
        this.ellipse(rabbitX + 7, 397, 1.8, 5, '#f4efdf');
        this.ellipse(rabbitX + 7, 402, 1, 1, '#777057');
        const bx = 420 + Math.sin(time * 0.7) * 22,
            by = 54 + Math.cos(time) * 12,
            wing = 2 + Math.abs(Math.sin(time * 5)) * 3;
        this.ellipse(bx - wing, by, wing, 3, '#e6d494');
        this.ellipse(bx + wing, by, wing, 3, '#e6d494');
        const birdX = ((time * 10 + 130) % 720) - 60;
        this.line(
            [
                [birdX - 6, 48],
                [birdX, 51 + Math.sin(time * 5) * 2],
                [birdX + 6, 48],
            ],
            '#5c7c65',
            2,
        );
        this.ellipse(562, 400, 6, 4, '#b68a5e');
        this.ellipse(569, 396, 3, 3, '#b68a5e');
        this.line(
            [
                [557, 400],
                [551, 392],
                [553, 387],
            ],
            '#ab7f54',
            5,
        );
        if (!this.reducedMotion)
            for (let i = 0; i < 5; i++) {
                const x = (i * 133 + time * 4) % 600,
                    y = (i * 93 + time * 8) % 460;
                this.ellipse(x, y, 2, 1, '#edf1bd88');
            }
        // Quiet world border and compass.
        c.strokeStyle = '#6c8a5222';
        c.lineWidth = 12;
        c.strokeRect(0, 0, 600, 460);
        c.save();
        if (this.level.id >= 4) c.translate(-240, 360);
        this.ellipse(562, 39, 18, 18, '#f7f3d8c9');
        c.font = '9px Segoe UI';
        c.textAlign = 'center';
        c.fillStyle = '#6d805c';
        c.fillText('N', 562, 30);
        this.line(
            [
                [562, 34],
                [562, 48],
            ],
            '#6d805c',
            1,
        );
        this.line(
            [
                [557, 40],
                [567, 40],
            ],
            '#6d805c',
            1,
        );
        this.line(
            [
                [559, 37],
                [562, 34],
                [565, 37],
            ],
            '#6d805c',
            1,
        );
        c.restore();
    }
}
