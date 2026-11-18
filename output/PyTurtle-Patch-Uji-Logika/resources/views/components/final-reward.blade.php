<section class="final-reward" aria-label="Penilaian chapter terakhir">
<b>Hadiah akhir: hingga 100 XP + badge Turtle Master</b>
<p>Selesaikan rute dalam satu Run tanpa kesalahan dan tanpa hint untuk hadiah maksimal. Run yang error atau belum mencapai tujuan mengurangi 5 XP; hint mengurangi 5 XP sekali. Reward minimal 60 XP.</p>
<p>Kesalahan pada penilaian ini: <b x-text="runFailures"></b></p>
<button class="secondary" @click="replay()" :disabled="running || syncing">Ulangi penilaian</button>
<p class="small muted">Mulai ulang penilaian untuk mencoba hadiah maksimal. XP terbaik diperbarui, tidak ditumpuk. Skor terbaik dan badge yang sudah didapat tetap tersimpan.</p>
</section>
