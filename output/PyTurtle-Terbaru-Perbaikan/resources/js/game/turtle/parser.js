export class TurtleError extends Error {
    constructor(line, message) {
        super('Ada masalah pada baris ' + line + '. ' + message);
        this.line = line;
    }
}
export function parse(
    code,
    allowed = ['forward', 'backward', 'left', 'right', 'penup', 'pendown'],
) {
    if (code.length > 10000)
        throw new TurtleError(1, 'Kode terlalu panjang. Maksimal 10.000 karakter.');
    const commands = [];
    code.split(/\r\n|\n|\r/).forEach((raw, i) => {
        const line = raw.split('#')[0].trim();
        if (!line) return;
        const match = line.match(
            /^(?:turtle\.)?(forward|backward|left|right|penup|pendown)\s*\(\s*(-?(?:\d+(?:\.\d+)?|\.\d+))?\s*\)$/,
        );
        if (!match)
            throw new TurtleError(
                i + 1,
                'Gunakan tanda kurung, misalnya forward(100). Import, loop, variabel, dan perintah lain belum didukung.',
            );
        const [, name, number] = match;
        if (!allowed.includes(name))
            throw new TurtleError(
                i + 1,
                'Perintah belum tersedia di level ini. Lihat daftar perintah di atas editor.',
            );
        const pen = ['penup', 'pendown'].includes(name),
            value = Number(number);
        if (
            (pen && number !== undefined) ||
            (!pen && (number === undefined || !Number.isFinite(value) || Math.abs(value) > 1000))
        )
            throw new TurtleError(
                i + 1,
                'Gerakan dan putaran membutuhkan angka antara -1000 dan 1000. penup() dan pendown() tidak menerima angka.',
            );
        commands.push({ name, value: pen ? 0 : value, line: i + 1 });
        if (commands.length > 100)
            throw new TurtleError(i + 1, 'Batasi program sampai 100 perintah.');
    });
    if (!commands.length)
        throw new TurtleError(1, 'Tulis satu perintah terlebih dahulu. Coba forward(100).');
    return commands;
}
