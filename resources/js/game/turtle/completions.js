const commands = {
    forward: {
        label: 'forward',
        apply: 'forward(1)',
        info: 'Maju sejumlah kotak mengikuti arah Shiha.',
    },
    left: { label: 'left', apply: 'left(90)', info: 'Putar kiri 90° tanpa berpindah kotak.' },
    right: { label: 'right', apply: 'right(90)', info: 'Putar kanan 90° tanpa berpindah kotak.' },
    variable: {
        label: 'variabel',
        apply: 'langkah = 3',
        type: 'variable',
        info: 'Simpan angka dengan nama pilihanmu, lalu gunakan: forward(langkah).',
    },
    for: {
        label: 'for',
        apply: 'for i in range(3):\n    forward(1)',
        type: 'keyword',
        info: 'Ulangi perintah dengan indentasi 4 spasi.',
    },
    range: {
        label: 'range',
        apply: 'range(jumlah)',
        type: 'function',
        info: 'Jumlah pengulangan dapat berupa angka atau variabel.',
    },
};

export function turtleCompletions(allowed) {
    const options = [...new Set([...(allowed ?? []), 'variable', 'for', 'range'])]
        .filter((name) => commands[name])
        .map((name) => ({
            type: 'function',
            ...commands[name],
        }));
    return (context) => {
        const word = context.matchBefore(/[a-zA-Z_]\w*/);
        if (!word && !context.explicit) return null;
        const source = context.state?.doc.sliceString(0, word?.from ?? context.pos) ?? '';
        const names = new Set(
            [
                ...source.matchAll(/^\s*(?:for\s+([a-zA-Z_]\w*)\s+in\b|([a-zA-Z_]\w*)\s*=(?!=))/gm),
            ].map((match) => match[1] ?? match[2]),
        );
        const variables = [...names]
            .filter((name) => !commands[name])
            .map((label) => ({ label, type: 'variable', info: 'Variabel dari kodemu.' }));
        return {
            from: word?.from ?? context.pos,
            options: [...options, ...variables],
            validFor: /^\w*$/,
        };
    };
}
