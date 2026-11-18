const commands = {
    forward: {
        label: 'forward',
        apply: 'forward(1)',
        info: 'Maju sejumlah kotak mengikuti arah Shiha.',
    },
    left: { label: 'left', apply: 'left(90)', info: 'Putar kiri 90° tanpa berpindah kotak.' },
    right: { label: 'right', apply: 'right(90)', info: 'Putar kanan 90° tanpa berpindah kotak.' },
};

export function turtleCompletions(allowed) {
    const options = allowed
        .filter((name) => commands[name])
        .map((name) => ({
            ...commands[name],
            type: 'function',
        }));
    return (context) => {
        const word = context.matchBefore(/[a-zA-Z_]\w*/);
        if (!word && !context.explicit) return null;
        return { from: word?.from ?? context.pos, options, validFor: /^\w*$/ };
    };
}
