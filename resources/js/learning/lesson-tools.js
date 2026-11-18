export default function lessonTools() {
    return {
        copyMessage: '',
        async copyCode(code) {
            try {
                await navigator.clipboard.writeText(code);
                this.copyMessage = 'Kode berhasil disalin.';
            } catch {
                this.copyMessage = 'Belum bisa menyalin otomatis. Pilih kode, lalu tekan Ctrl+C.';
            }
        },
        downloadCode(code, filename) {
            const url = URL.createObjectURL(
                new Blob([code + '\n'], { type: 'text/plain;charset=utf-8' }),
            );
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.append(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        },
    };
}
