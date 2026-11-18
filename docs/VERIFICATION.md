# Laporan verifikasi — 30 September 2026

## Hasil

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test --compact` | 21 tes lulus, 135 assertion |
| `npm test` | 5 tes parser/engine lulus |
| `npm run build` | Berhasil; bundle aplikasi dan CodeMirror terpisah |
| `npm run test:e2e` | 2 skenario browser lulus; tanpa page error pada alur petualangan penuh |
| `php artisan view:cache` | Semua template Blade berhasil dikompilasi |
| `composer validate --no-check-publish` | composer.json dan lock valid |
| Preview `http://127.0.0.1:8011/login` | HTTP 200 |

Pengujian menggunakan PHP 8.2.12, Laravel 12.69.3, Node 24.15.0, Vite 7.3.6, dan Chrome melalui Playwright.

## Alur browser yang benar-benar diuji

1. Halaman auth siang hari, register, redirect Home.
2. Materi selesai dan feedback XP.
3. Level 2 ditolak sebelum level 1.
4. Editor putih, kode tidak valid dengan petunjuk baris, reset kode.
5. Shiha bergerak ke checkpoint; status simpan menunggu server.
6. Refresh mempertahankan kode dan checkpoint.
7. Ketiga chapter diselesaikan dengan kode, checkpoint, cerita, dan pembukaan level berikutnya.
8. Progress menampilkan 100%.
9. Code Match: jawaban salah, recovery, semua pasangan benar, akurasi 80%, skor 100, status tersimpan setelah respons server.
10. Pengaturan reduced motion tersimpan.
11. Dialog restart bisa dibatalkan dan dikonfirmasi; chapter kembali terkunci.
12. Simulasi internet putus: antrean lokal dipertahankan, tersinkron saat online, dan checkpoint tetap ada setelah refresh.
13. Halaman Home, Modules, Maze, Code Match, Progress, dan Settings tidak meluber pada viewport 390 px.

Screenshot yang dihasilkan dan diperiksa: `storage/testing/login.png`, `home.png`, `maze.png`, `code-match.png`, dan `mobile.png`.

## Batas verifikasi

- Pengguna memilih mengisi kredensial MySQL sendiri di .env. Pengujian database memakai SQLite in-memory untuk PHPUnit serta file database terpisah untuk browser/demo. Koneksi, migrasi, dan runtime pada MySQL pengguna belum diklaim terverifikasi.
- Default mailer adalah log; reset password diuji melalui notification/password broker Laravel. Pengiriman ke inbox SMTP memerlukan konfigurasi pengguna.
- Lampiran berisi brief teks, tanpa screenshot Figma. Kesesuaian piksel dengan Figma belum dapat diperiksa.
- Asset hutan dan Shiha original berupa SVG/Canvas procedural; audio berupa nada sintetis. Belum memakai sprite sheet final atau rekaman audio.
- Preview menggunakan `storage/testing/preview.sqlite` pada port 8011, terpisah dari database browser test port 8010 dan dari database MySQL utama.

Laporan implementasi terperinci, route/controller/model, migration/schema, komponen Blade, engine, autosave, scoring, keamanan, dan instalasi berada di [README](../README.md).
