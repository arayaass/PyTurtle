<section class="maze-learning" aria-labelledby="learning-title">
    <span class="eyebrow">LABORATORIUM LOGIKA</span>
    <h3 id="learning-title">Prediksi → Telusuri → Perbaiki</h3>
    <p>Tulis kode di editor, lalu perkirakan hasilnya sebelum melihat perjalanan Shiha.</p>
    <details><summary>Cara membaca posisi</summary><p>Titik awal adalah (0, 0). X bertambah ke timur dan berkurang ke barat; Y bertambah ke selatan dan berkurang ke utara. Satu angka berarti satu kotak. Contoh: maju 2 kotak dari arah awal berarti (2, 0), menghadap timur.</p></details>
    <fieldset :disabled="learningBusy || tracing || running || syncing">
        <legend>1. Prediksi hasil akhir kodemu</legend>
        <div class="prediction-fields">
            <label>Posisi X<input type="number" step="1" min="-1000" max="1000" x-model="predictionX" placeholder="Contoh: 2"></label>
            <label>Posisi Y<input type="number" step="1" min="-1000" max="1000" x-model="predictionY" placeholder="Contoh: 0"></label>
            <label>Arah akhir<select x-model="predictionAngle"><option value="">Pilih arah</option><option value="0">Timur →</option><option value="90">Selatan ↓</option><option value="180">Barat ←</option><option value="270">Utara ↑</option></select></label>
        </div>
    </fieldset>
    <button class="secondary" @click="startLearning()" :disabled="running || syncing || runCompleted || tracing || learningBusy" x-text="learningBusy ? 'Menyiapkan…' : 'Kunci prediksi & telusuri'">Kunci prediksi & telusuri</button>
    <p class="small muted">Mode terpandu memakai bantuan: mengurangi 5 poin sekali per penilaian, sama seperti Hint. Tidak memberi XP atau membuka chapter. Gunakan Run Code untuk penilaian. Untuk mencoba hadiah maksimal, mulai penilaian baru.</p>
    <div x-show="learningMessage" class="notice" role="status" x-text="learningMessage" x-cloak></div>
    <div x-show="tracing || learningDone" x-cloak>
        <h4>2. Telusuri satu instruksi</h4>
        <p x-text="'Instruksi ' + traceIndex + ' / ' + traceTotal + ' · ' + tracePosition"></p>
        <pre class="trace-command" x-text="traceCommand"></pre>
        <div class="trace-controls" x-show="tracing">
            <button class="secondary" @click="traceStep(-1)" :disabled="traceIndex===0">← Sebelumnya</button>
            <button class="primary" @click="traceStep(1)" :disabled="traceIndex===traceTotal">Instruksi berikutnya →</button>
            <button class="text-link" @click="finishLearning()">Perbaiki kode</button>
        </div>
        <div x-show="learningDone">
            <h4>3. Bandingkan dan perbaiki</h4>
            <p x-text="predictionFeedback"></p>
            <label>Apa yang perlu kamu ubah atau pertahankan?<textarea x-model="reflection" maxlength="1000" rows="2" placeholder="Contoh: belokan di baris 2 perlu ke kiri karena tujuan ada di utara."></textarea></label>
            <p class="small muted">Catatan refleksi hanya untuk latihan ini; tidak disimpan setelah halaman ditutup atau prediksi baru dimulai.</p>
            <button class="secondary" @click="finishLearning()">Kembali ke editor</button>
        </div>
    </div>
</section>
