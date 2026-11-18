export class TurtleError extends Error {
    constructor(line, message) {
        super('Ada masalah pada baris ' + line + '. ' + message);
        this.line = line;
    }
}
// A bounded Python subset: numeric variables, Turtle calls and for/range blocks.
// Student code is parsed as data and is never evaluated as JavaScript or Python.
export function parse(
    code,
    allowed = ['forward', 'backward', 'left', 'right', 'penup', 'pendown'],
) {
    if (code.length > 10000)
        throw new TurtleError(1, 'Kode terlalu panjang. Maksimal 10.000 karakter.');
    const rows = code
        .split(/\r\n|\n|\r/)
        .map((raw, i) => ({
            text: raw.split('#')[0].trim(),
            indent: raw.match(/^ */)[0].length,
            line: i + 1,
            tab: /^\s*\t/.test(raw),
        }))
        .filter((r) => r.text);
    const vars = new Map(),
        commands = [];
    commands.usedVariable = false;
    let work = 0;
    const reserved = new Set([
        'for',
        'in',
        'range',
        'import',
        'forward',
        'backward',
        'left',
        'right',
        'penup',
        'pendown',
        'turtle',
    ]);
    const numeric = /^-?(?:\d+(?:\.\d+)?|\.\d+)$/;
    const namePattern = /^[A-Za-z_]\w*$/;
    const value = (text, line) => {
        if (numeric.test(text)) return Number(text);
        if (namePattern.test(text) && vars.has(text)) return vars.get(text);
        throw new TurtleError(
            line,
            'Gunakan angka atau variabel yang sudah diisi. Contoh: langkah = 3 lalu forward(langkah).',
        );
    };
    function block(start, end, indent, depth = 0) {
        if (depth > 4)
            throw new TurtleError(rows[start].line, 'Maksimal empat tingkat perulangan.');
        for (let i = start; i < end; i++) {
            const r = rows[i];
            if (++work > 2000)
                throw new TurtleError(r.line, 'Program terlalu panjang. Ringkas perulanganmu.');
            if (r.tab || r.indent !== indent)
                throw new TurtleError(
                    r.line,
                    'Periksa indentasi. Gunakan spasi yang sama di dalam perulangan.',
                );
            const assignment = r.text.match(/^([A-Za-z_]\w*)\s*=\s*(.+)$/);
            if (assignment) {
                if (reserved.has(assignment[1]))
                    throw new TurtleError(
                        r.line,
                        'Nama variabel ini dipakai oleh perintah Python Turtle.',
                    );
                const n = value(assignment[2], r.line);
                if (!Number.isFinite(n) || Math.abs(n) > 1000)
                    throw new TurtleError(r.line, 'Nilai variabel harus antara -1000 dan 1000.');
                vars.set(assignment[1], n);
                continue;
            }
            const loop = r.text.match(/^for\s+([A-Za-z_]\w*)\s+in\s+range\(\s*([^(),]+)\s*\)\s*:$/);
            if (loop) {
                if (reserved.has(loop[1]))
                    throw new TurtleError(r.line, 'Pilih nama variabel perulangan lain.');
                const n = value(loop[2].trim(), r.line);
                if (!Number.isInteger(n) || n < 1 || n > 100)
                    throw new TurtleError(
                        r.line,
                        'range() membutuhkan bilangan bulat 1 sampai 100.',
                    );
                let stop = i + 1;
                while (stop < end && rows[stop].indent > indent) stop++;
                if (stop === i + 1)
                    throw new TurtleError(r.line, 'Isi perulangan harus menjorok dengan spasi.');
                for (let j = 0; j < n; j++) {
                    vars.set(loop[1], j);
                    block(i + 1, stop, rows[i + 1].indent, depth + 1);
                }
                i = stop - 1;
                continue;
            }
            const m = r.text.match(
                /^(?:turtle\.)?(forward|backward|left|right|penup|pendown)\s*\(\s*([^()]*)\s*\)$/,
            );
            if (!m)
                throw new TurtleError(
                    r.line,
                    'Gunakan perintah seperti forward(3), variabel, atau for i in range(3):. Import dan perintah lain tidak didukung.',
                );
            const [, name, raw] = m,
                arg = raw.trim(),
                pen = ['penup', 'pendown'].includes(name);
            if (!allowed.includes(name))
                throw new TurtleError(r.line, 'Perintah belum tersedia di level ini.');
            if (pen && arg)
                throw new TurtleError(r.line, 'penup() dan pendown() tidak menerima angka.');
            const n = pen ? 0 : value(arg, r.line);
            if (!Number.isFinite(n) || Math.abs(n) > 1000)
                throw new TurtleError(
                    r.line,
                    'Gerakan dan putaran membutuhkan angka antara -1000 dan 1000.',
                );
            if (!pen && namePattern.test(arg)) commands.usedVariable = true;
            commands.push({ name, value: n, line: r.line });
            if (commands.length > 100)
                throw new TurtleError(r.line, 'Batasi program sampai 100 perintah.');
        }
    }
    if (rows.length) block(0, rows.length, 0);
    if (!commands.length)
        throw new TurtleError(
            1,
            'Tulis satu perintah terlebih dahulu. Coba langkah = 3 lalu forward(langkah).',
        );
    return commands;
}
