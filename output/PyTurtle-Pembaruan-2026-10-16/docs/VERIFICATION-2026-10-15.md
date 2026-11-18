# Verifikasi pembaruan 15 Oktober 2026

- PHPUnit: 44 tes, 482 assertion lulus. Meliputi reward final, hint, checkpoint, replay, XP tidak menumpuk, badge, kiriman dari run lama, Google state, identitas terverifikasi, login ulang, penautan akun, dan penolakan konflik akun.
- Node: 8 tes lulus, termasuk whitelist autocomplete dan perilaku editor/grid.
- Vite production build berhasil.
- Browser Chrome: alur tiga chapter, autocomplete hanya forward/left/right, kesalahan → 95 XP tanpa badge → ulang bersih → 100 XP dengan badge, leaderboard, profil, Code Match, settings, restart, offline/retry, dan layout mobile.
- Pemeriksaan visual: login, hasil reward final, dan leaderboard. Screenshot tersedia di `storage/testing/`.
- Audit npm setelah pembaruan patch dependensi: 0 kerentanan terlapor. Composer melaporkan tidak ada advisori kerentanan.

Database SQLite preview port 8011 sudah dimigrasikan. Migrasi MySQL utama belum dapat dijalankan karena koneksi `127.0.0.1:3309` ditolak. Setelah layanan database tersedia, jalankan `php artisan migrate --force` pada konfigurasi utama.

Google OAuth diuji melalui redirect nyata ke URL Google dan callback dengan identitas provider yang dimock. Login ke akun Google nyata belum diuji karena Client ID/Client Secret proyek belum tersedia; ikuti `docs/GOOGLE-AUTH.md`. Tidak ada kredensial palsu yang digunakan untuk mengaktifkan tombol di preview.
