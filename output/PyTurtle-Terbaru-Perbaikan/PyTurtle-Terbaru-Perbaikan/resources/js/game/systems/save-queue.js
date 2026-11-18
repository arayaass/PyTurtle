const identity = () => document.body.dataset.user || 'guest';
const version = () => Number(document.body.dataset.version || 1);
const queueKey = () => 'pyturtle:queue:' + identity();
function read() {
    try {
        return JSON.parse(localStorage.getItem(queueKey()) || '[]');
    } catch {
        return [];
    }
}
function write(queue) {
    localStorage.setItem(queueKey(), JSON.stringify(queue));
}
let busy = false;
export async function post(url, data) {
    const controller = new AbortController(),
        timer = setTimeout(() => controller.abort(), 12000);
    try {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(data),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(
                result.message || 'Penyimpanan belum berhasil. Silakan coba lagi.',
            );
            error.status = response.status;
            throw error;
        }
        return result;
    } finally {
        clearTimeout(timer);
    }
}
export function enqueue(url, payload) {
    const entry = { id: crypto.randomUUID(), url, payload, version: version() };
    const queue = read();
    queue.push(entry);
    try {
        write(queue);
    } catch {
        window.dispatchEvent(
            new CustomEvent('save-status', {
                detail: {
                    message: 'Penyimpanan perangkat penuh. Tetap di halaman ini dan coba lagi.',
                    failed: true,
                },
            }),
        );
        throw new Error('Penyimpanan perangkat tidak tersedia.');
    }
    window.dispatchEvent(new CustomEvent('save-status', { detail: { message: 'Saving…' } }));
    void flush();
    return entry.id;
}
async function flushQueue() {
    if (busy || !document.querySelector('meta[name="csrf-token"]')) return;
    busy = true;
    try {
        while (read().length) {
            const entry = read()[0];
            if (entry.version !== version()) {
                write(read().filter((e) => e.id !== entry.id));
                continue;
            }
            let result;
            try {
                result = await post(entry.url, entry.payload);
            } catch (error) {
                if ([403, 409, 422].includes(error.status)) {
                    write(read().filter((e) => e.id !== entry.id));
                    window.dispatchEvent(
                        new CustomEvent('save-status', {
                            detail: {
                                message:
                                    error.status === 409
                                        ? 'Petualangan berubah. Muat ulang halaman.'
                                        : error.message,
                                failed: true,
                                id: entry.id,
                            },
                        }),
                    );
                    continue;
                }
                window.dispatchEvent(
                    new CustomEvent('save-status', {
                        detail: {
                            message: [401, 419].includes(error.status)
                                ? 'Sesi berakhir. Masuk kembali untuk menyinkronkan progres.'
                                : 'Progress tersimpan sementara di perangkat. Sinkronisasi akan dicoba kembali.',
                            offline: true,
                        },
                    }),
                );
                break;
            }
            write(read().filter((e) => e.id !== entry.id));
            window.dispatchEvent(
                new CustomEvent('save-status', {
                    detail: {
                        message: read().length ? 'Saving…' : 'Progress tersimpan',
                        id: entry.id,
                        result,
                    },
                }),
            );
        }
    } finally {
        busy = false;
    }
}
export async function flush() {
    if (navigator.locks)
        return navigator.locks.request(queueKey(), { ifAvailable: true }, (lock) =>
            lock ? flushQueue() : undefined,
        );
    return flushQueue();
}
export function bootQueue() {
    window.addEventListener('online', flush);
    window.addEventListener('focus', flush);
    setInterval(() => {
        if (navigator.onLine) void flush();
    }, 15000);
    void flush();
}
export function localKey(suffix) {
    return 'pyturtle:' + identity() + ':' + version() + ':' + suffix;
}
