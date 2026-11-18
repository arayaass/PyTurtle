<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        class="danger-link"
        @click="
            open = true;
            $nextTick(() => $refs.cancel.focus());
        "
    >
        Mulai Ulang Petualangan
    </button>
    <div class="modal-backdrop" x-show="open" x-cloak @click.self="open = false">
        <section
            class="modal card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="restart-title"
            x-trap.inert.noscroll="open"
        >
            <span class="eyebrow">AWAL YANG BARU</span>
            <h2 id="restart-title">Mulai ulang petualangan?</h2>
            <p>
                Progress level akan dihapus dan Shiha kembali ke awal cerita. Akun, materi, dan hasil Code Match tetap
                tersimpan.
            </p>
            <form action="/game/restart" method="POST" class="button-row">
                @csrf
                <input type="hidden" name="confirm" value="1" /><button
                    type="button"
                    class="secondary"
                    @click="open = false"
                    x-ref="cancel"
                >
                    Batal</button
                ><button class="danger" type="submit">Mulai Ulang</button>
            </form>
        </section>
    </div>
</div>
