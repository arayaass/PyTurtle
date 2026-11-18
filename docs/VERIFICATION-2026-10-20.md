# Verifikasi pembaruan PyTurtle - 20 Oktober 2026

## Media rujukan

PDF `C:\Users\Raya\Downloads\Ka Roy.pdf` terdiri dari 145 halaman. Lampiran pada halaman PDF 114 (halaman tercetak 99) memuat dua QR:

- Materi ajar: https://qr.me-qr.com/4zTTO56F
- Media pembelajaran: https://qr.me-qr.com/P59BGHer → https://bidawang-geometry-app.vercel.app/

QR dipindai dari gambar asli lampiran. Bagian perancangan/implementasi yang relevan serta aktivitas latihan dan umpan balik pada halaman tercetak 73-81 dibaca. Login media berhasil dengan akun yang diberikan setelah alamat email dikoreksi oleh pengguna. Tidak dibuat akun baru; kredensial tidak dimasukkan ke sumber proyek.

Game rujukan menggunakan editor di kiri, peta di kanan, tahapan tantangan berurutan, dan pop-up Petunjuk. PyTurtle mengikuti pola tata letak dan tahapan itu sambil mempertahankan tokoh, cerita, serta peta sendiri. Petunjuk rute muncul otomatis setiap tiga Run yang belum berhasil; misi yang selalu terlihat berupa cerita.

## Perubahan

- Area konten diperlebar menjadi maksimal 1540 px, dengan margin/padding lebih lega dan font lebih besar. Tampilan ponsel tetap satu kolom tanpa overflow.
- Level 1: tiga sublevel persisten. Level 2 terkunci sampai ketiga sublevel selesai. Kiriman dari sublevel lama atau upaya melompati tahapan ditolak server.
- Jalur level 2 dan 3 dipertahankan. Cerita level 3 mengarah ke perjalanan lanjutan.
- Level 4 memakai tujuh penanda dan pola tangga berulang. Level 5 memakai 16 penanda dengan labirin lebih panjang. Peti, hadiah final, replay penilaian, dan badge pindah ke level 5.
- Semua level harus menggunakan variabel dalam argumen perintah gerak/putar. Assignment saja tidak mencukupi. Loop `for ... in range(...)` dapat digunakan dan dianjurkan pada dua level terakhir.
- Interpreter JS/PHP menerima subset yang sama, dengan batas ekspansi 100 perintah, 2.000 langkah interpreter, dan empat tingkat loop. Input tidak dieksekusi sebagai Python/PHP/JS.
- Kode lengkap disimpan pada akhir Run. Editor tetap kosong saat halaman dibuka; sublevel dan skor terbaik tersimpan. Penyelesaian sublevel tidak dihitung sebagai kesalahan/penalti skor.

## Validasi

- PHPUnit: 49 tes, 573 assertion lulus.
- Node: 10 tes lulus, mencakup solusi seluruh level/sublevel, variabel wajib, nested loop, batas kerja, tabrakan, dan penanda.
- Browser Chrome: perjalanan lengkap lulus melalui kernel HTTP Laravel asli dengan SQLite khusus pengujian, tanpa socket server. Termasuk tiga kegagalan sebelum pop-up, reload sublevel, lock level 2, kelima level, badge final, dan progres 100%.
- Tampilan desktop 1440 px dan ponsel 390 px diperiksa dari screenshot. Ponsel tidak overflow horizontal. Kompas pada peta lanjut dipindah agar penanda tetap terlihat.
- Build produksi berhasil. Jalankan `node scripts/verify-adventure.mjs` untuk mengulangi uji perjalanan menggunakan `storage/testing/kernel.sqlite`.

Server HTTP pratinjau tidak dapat dijangkau lewat socket localhost di lingkungan pengujian ini; adapter kernel hanya dipakai pengujian dan tidak menambah endpoint publik.

Setelah MySQL diaktifkan, pembaruan database utama berhasil diterapkan dengan `php artisan db:seed --class=MazeGridSeeder --force`. Kelima level sudah tersedia. Seeder ini mempertahankan akun dan progres lama.

