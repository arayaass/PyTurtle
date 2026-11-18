# PyTurtle — Shiha's Turtle Adventure

Aplikasi kuliah Sistem Interaksi untuk belajar Python Turtle melalui materi singkat, Code Matching, dan RPG hutan tiga chapter. Laravel adalah aplikasi utama: backend, autentikasi, Blade, database, dan endpoint AJAX berada dalam satu proyek.

## Fitur

- Register, login, logout, remember me, forgot/reset password menggunakan session dan password broker Laravel.
- Home personal: level penjelajah, XP, progres, lanjut petualangan, dan materi berikutnya.
- Enam materi berbahasa Indonesia dengan syntax, contoh, diagram, penjelasan, latihan, dan progres belajar.
- Code Match: empat pasangan, pilihan keyboard/mouse, feedback benar/salah, penguncian pasangan benar, reset, score, accuracy, penyimpanan hasil.
- Tiga level RPG dengan Shiha original, cerita intro/transisi/ending, animasi jalan empat arah, collision, checkpoint berurutan, dan treasure.
- CodeMirror 6 bertema terang, Python syntax highlighting, nomor baris, active line, Ctrl/⌘+Enter, Run, Reset, Hint, kecepatan 1×/2×/4×.
- Autosave checkpoint, submission, cerita, skor; antrean localStorage dengan retry saat online, fokus tab, dan tiap 15 detik.
- Progress, best score, pembukaan level berurutan, leaderboard tanpa email, profil, musik/suara/reduced motion, dan restart dengan dialog konfirmasi.

## Persyaratan

- PHP 8.2 atau lebih baru; ekstensi PDO MySQL, mbstring, openssl, tokenizer, XML, ctype, fileinfo, curl. PDO SQLite digunakan untuk tes terisolasi.
- Composer 2.
- Node.js 22.12+ (pengembangan ini menggunakan Node 24), npm.
- MySQL 8 atau MariaDB yang kompatibel dengan Laravel. Koneksi default aplikasi adalah MySQL.
- Browser modern dengan Canvas, localStorage, dan Web Audio. Audio baru dimulai sesudah interaksi pengguna.

## Instalasi dan menjalankan

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
```

Di Linux/macOS, gunakan `cp .env.example .env`. **Jangan menimpa .env yang sudah berisi konfigurasi.** Di workspace hasil implementasi, .env dan application key sudah disiapkan.

Buat database kosong menggunakan akun MySQL milikmu:

```sql
CREATE DATABASE pyturtle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Isi koneksi di `.env`:

```dotenv
APP_NAME=PyTurtle
APP_URL=http://127.0.0.1:8000
APP_LOCALE=id
APP_TIMEZONE=Asia/Makassar
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pyturtle
DB_USERNAME=nama_user_mysql
DB_PASSWORD="kata_sandi_mysql"
```

Lalu:

```powershell
php artisan config:clear
php artisan migrate --seed
npm run build
php artisan serve
```

Buka **http://127.0.0.1:8000**, buat akun sendiri, lalu mulai dari Modules atau Maze Quest. Seeder tidak membuat akun atau leaderboard palsu. Untuk pengembangan dengan hot reload, jalankan `npm run dev` di terminal kedua; alternatif `composer run dev` menjalankan Laravel dan Vite bersama. Pada PowerShell yang memblokir npm.ps1, gunakan `npm.cmd`.

### Pemulihan kata sandi

Default `MAIL_MAILER=log` cocok untuk pengembangan. Email reset disimpan di `storage/logs/laravel.log`, bukan dikirim ke inbox. Untuk pengiriman sungguhan, isi konfigurasi SMTP (`MAIL_MAILER=smtp`, host, port, username, password, from address) lalu jalankan `php artisan config:clear`. Tautan reset memakai `APP_URL`. Tidak ada social login atau admin dashboard pada scope ini.

### Preview dan tes tanpa menyentuh MySQL

```powershell
node scripts/test-server.mjs --preview
```

Menyediakan preview pada **http://127.0.0.1:8011**, memakai `storage/testing/preview.sqlite`. Ini lingkungan demo terpisah, bukan pengganti konfigurasi MySQL aplikasi. Script tidak mengubah .env. Hentikan dengan Ctrl+C. Pengujian browser otomatis memakai port 8010 dan `storage/testing/browser.sqlite` secara terpisah.

## Pengujian

```powershell
php artisan test
npm test
npm run build
npm run test:e2e
```

- PHPUnit: autentikasi/reset password/rate limit, middleware auth, render halaman, validasi server, progres/checkpoint, isolasi user, larangan score palsu, unlock, idempotensi, replay/best score, restart/version, Code Match, leaderboard, materi/settings.
- Node test runner: parser, solusi ketiga level, checkpoint, program ekuivalen, batas command, collision, putaran dan gerak mundur.
- Playwright: browser Chrome terpasang; register → belajar → kode invalid → checkpoint → refresh → tiga level → Code Match salah/benar → settings → restart; offline/retry dan lebar mobile.
- Untuk Chromium Playwright alih-alih Chrome lokal, hapus `channel: 'chrome'` dari konfigurasi, lalu jalankan `npx playwright install chromium`.
- Screenshot QA berada di `storage/testing/`; screenshot/trace gagal di `test-results/`. Folder ini tidak perlu di-commit.
- PHPUnit memakai SQLite `:memory:`; browser memakai database SQLite QA. Migrasi MySQL nyata perlu dijalankan setelah kredensial .env diisi.

## Route dan controller

| Route | Controller / fungsi |
| --- | --- |
| GET / | Redirect ke Home atau login |
| GET/POST /register, /login | AuthController / formulir, validasi, session |
| POST /logout | AuthController / invalidasi session |
| GET/POST /forgot-password | AuthController / password broker |
| GET /reset-password/{token}, POST /reset-password | AuthController / reset password |
| GET /home | HomeController |
| GET /modules, /modules/{module} | ModuleController |
| POST /modules/{module}/complete | ModuleController / penyelesaian idempotent |
| GET /maze, /maze/{level} | MazeController / otorisasi unlock |
| GET /game/continue | MazeController / lanjut level terakhir |
| POST /game/progress/save | GameProgressController / checkpoint dan final submission |
| POST /game/level/complete | Alias validasi submission yang sama, bukan endpoint untuk memaksakan status selesai |
| POST /game/{level}/hint | GameProgressController / catat penggunaan hint |
| POST /game/story | GameProgressController / status cerita |
| POST /game/restart | GameProgressController / reset progres petualangan |
| GET /code-match, /code-match/4 | CodeMatchController / latihan lesson 4 |
| POST /code-match/submit | CodeMatchController / nilai dan simpan riwayat pasangan |
| GET /leaderboard | LeaderboardController / paginasi XP |
| GET /progress | ProgressController |
| GET/POST /settings, /profile | SettingsController |

Semua fitur belajar/game dilindungi middleware `auth`. Form memakai CSRF. AJAX memakai `X-CSRF-TOKEN` dan cookie session. Tidak ada backend terpisah atau arbitrary Python di server.

## Struktur dan komponen

```text
app/Http/Controllers/          controller per fitur
app/Models/                    Eloquent models
app/Services/                  TurtleService, GameProgressService, CodeMatchService
database/migrations/           skema dan constraint
database/seeders/               tiga level, enam materi, empat pasangan Code Match
resources/data/                definisi level dan pasangan (sumber seeder)
resources/views/               layout, halaman, reusable Blade components
resources/js/game/turtle/      parser dan engine deterministik
resources/js/game/systems/     antrean penyimpanan dan audio
resources/js/game/Game.js      dunia Canvas dan karakter original
resources/js/game/maze.js      CodeMirror, animasi, dialog, interaksi halaman
resources/js/game/code-match.js state Alpine Code Match
public/assets/                 SVG original dan folder penggantian asset
tests/                         PHPUnit, Node, Playwright
```

Blade components: `navbar`, `card`, `progress-bar`, `level-card`, `command-pill`, `code-editor`, `dialog`, dan `confirm-modal`. Layout `app` dan `auth` menangani struktur umum; Alpine menangani menu, modal, focus trap, cerita, dan feedback. CSS menggunakan Tailwind 4 melalui Vite, ditambah stylesheet komponen untuk tampilan konsisten.

## Database, model, migration

Migration bawaan Laravel: `users/password_reset_tokens/sessions`, `cache/cache_locks`, `jobs/job_batches/failed_jobs`.

Migration `2026_09_30_000001_create_learning_tables` menambahkan profil/XP ke user dan tabel belajar/game. Migration `2026_09_30_000002_add_matching_material_to_lessons` menambahkan JSON pasangan Code Match.

| Tabel / model | Isi utama dan relasi |
| --- | --- |
| users / User | name, display_name, email, password hash, xp, current_level, adventure_version |
| levels / Level | id, title, definition JSON: start/path/checkpoints/commands/story |
| lessons / Lesson | command, description, example, explanation, practice_level, matching_pairs JSON |
| game_progress / GameProgress | user_id unik, current_level/checkpoint, player_x/y/direction, story_state |
| level_progress / LevelProgress | user_id + level_id unik, status, score, best_score, attempts, hints_used, code, state JSON, completed_at |
| code_submissions / CodeSubmission | user_id, level_id, request_id, code, result, score, error_type, feedback JSON |
| code_match_results / CodeMatchResult | user_id, lesson_id, request_id, correct_answers, wrong_answers, accuracy, score, completed_at |
| user_settings / UserSetting | user_id unik, music_enabled, sound_enabled, reduced_motion |
| lesson_user | user_id + lesson_id unik; materi yang selesai |

User hasOne GameProgress dan UserSetting (`setting`), hasMany LevelProgress, CodeSubmission, CodeMatchResult, dan belongsToMany Lesson. Foreign key mengikat record ke user/level/lesson; progres user dihapus dengan cascade saat akun dihapus. Proyek tidak menyediakan penghapusan akun.

## Arsitektur game dan parser

Canvas dipilih agar ringan dan mudah dirawat. Dunia memakai koordinat logis 600 × 460, tetap proporsional di perangkat berbeda. `ForestGame` merender hutan, animasi Shiha idle/jalan atas/bawah/kiri/kanan, air, pepohonan, hewan, jejak pena, checkpoint, dan treasure. Render dibatasi sekitar 30 FPS, berhenti menggambar pada tab tersembunyi; reduced motion menghilangkan animasi ambient dan langsung menampilkan perpindahan.

`parser.js` hanya menerima satu pemanggilan per baris, komentar `#`, baris kosong, whitespace, angka desimal/negatif, dan prefix opsional `turtle.`. Command: `forward`, `backward`, `left`, `right`, `penup`, `pendown`, sesuai daftar per level. Maksimal 100 command, 10.000 karakter, nilai absolut 1.000 per command. Tidak menerima import, loop, assignment, variabel, semicolon, eval, filesystem, network, goto, atau circle.

Semua sintaks divalidasi sebelum eksekusi. `TurtleEngine` menyimpan `x, y, angle, penDown, checkpoint`, menghitung gerak trigonometri, dan memeriksa setiap langkah maksimum dua unit agar tidak menembus tembok. Collision menggunakan gabungan koridor jalan dengan inset lima unit; checkpoint harus dilalui berurutan dalam radius 12 unit. Sukses memerlukan semua checkpoint dan berhenti di tujuan. `penup()` tidak meniadakan collision.

Layar memakai y positif ke bawah dan sudut internal searah jarum jam; pengguna tetap melihat perilaku Turtle yang benar: left berlawanan jarum jam, right searah jarum jam, arah awal timur. Parser dan simulator PHP (`TurtleService`) memvalidasi ulang kode di server. Tidak ada exact-string matching atau fungsi eksekusi shell untuk kode pengguna.

Hasil: SUCCESS mencapai tujuan, PARTIAL kode valid tetapi belum mencapai tujuan/terhenti collision, ERROR sintaks tidak valid. Pesan menyebut nomor baris dan contoh perbaikan; editor memilih baris bermasalah.

## Autosave, offline, dan melanjutkan

1. Saat checkpoint tercapai, frontend membuat program prefix yang mereproduksi perjalanan sampai checkpoint dan mengirim `phase=checkpoint`. Server memvalidasi ulang prefix; ini menyimpan posisi tanpa menambah percobaan atau memberikan reward.
2. Pada akhir Run, kode utuh dikirim dengan `phase=final`. Server menghitung hasil, percobaan, skor, best score, XP, dan unlock dalam transaksi dengan lock per user.
3. Cerita disimpan saat dialog selesai. Draft kode disimpan lokal setiap perubahan, kode submission juga disimpan di database.
4. Antrean localStorage terpisah menurut user; draft menurut user + adventure_version + level. Retry otomatis saat online/fokus/15 detik, timeout request 12 detik. UUID unik mencegah reward/attempt ganda jika response hilang.
5. Web Locks, jika tersedia, mencegah dua tab mengirim antrean bersamaan. Server tetap mengunci user dan memakai unique request_id. Pengguna sebaiknya bermain satu level per tab.
6. Jika offline, game tetap berjalan pada halaman yang sudah dimuat. Full navigation tanpa internet bukan fitur PWA. Status menunjukkan progres tersimpan sementara; unlock resmi menunggu server.
7. Reload memulihkan kode lokal terbaru serta posisi/checkpoint server terakhir. Continue menuju chapter terakhir; jika sudah selesai, menuju chapter berikutnya.
8. Restart menghapus game_progress, level_progress, code_submissions dan menaikkan adventure_version. Antrean lama ditolak; akun, materi, Code Match, dan pengaturan tetap ada.

localStorage dapat dibatasi browser atau penuh. UI memberi tahu jika penyimpanan lokal gagal; jangan menutup halaman sebelum sinkronisasi selesai. HTTP 401/419 meminta login ulang; 403/409/422 menampilkan penolakan yang relevan.

## Scoring dan alur level

- Skor level = `max(60, 100 - hints_used*5 - min(20, (attempts-1)*2))` pada SUCCESS. Hint hanya dihitung sekali per level. Checkpoint autosave tidak dihitung sebagai percobaan. ERROR/PARTIAL tidak memperoleh skor penyelesaian.
- Skor petualangan = jumlah **best_score** ketiga chapter, maksimal 300. Replay tidak menurunkan best score.
- Code Match: 25 poin per pasangan benar, maksimal 100; accuracy = correct / (correct + wrong) × 100. Server menghitung ulang pasangan, bukan menerima skor browser.
- XP = total best score level + skor Code Match terbaik per lesson + 20 × jumlah materi selesai. Maksimum seed awal 520 XP; belajar/main ulang tidak menggandakan XP.
- Level penjelajah = `1 + floor(XP / 100)`; berbeda dari chapter cerita.

| Chapter | Materi | Cerita |
| --- | --- | --- |
| 1 — Memasuki Hutan | forward, backward | Menemukan dan mengikuti jalan di peta Kakek |
| 2 — Jembatan Sungai | forward, backward, left, right | Menyeberangi sungai dengan mengatur belokan |
| 3 — Jejak Terakhir | kombinasi movement, penup, pendown | Reruntuhan, peti, kompas, catatan, dan pesan Kakek |

Level 2 mensyaratkan penyelesaian level 1; level 3 mensyaratkan level 2. Pengamanan berlaku pada GET halaman maupun POST progres/hint/cerita.

## Keamanan dan deployment

Password hashing, session regeneration, logout invalidation, CSRF, validasi allowlist, mass-assignment protection, prepared queries Eloquent, auth middleware, login/reset/register throttling, transaksi, dan isolasi user diterapkan. Endpoint progres tidak menerima user_id, score, atau status dari browser. Leaderboard hanya memilih nama, XP, dan ID untuk menandai pengguna sendiri.

Jangan commit .env atau log berisi tautan reset. Untuk production: HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, konfigurasi SMTP, `SESSION_SECURE_COOKIE=true`, document root ke `public/`, `npm run build`, `php artisan migrate --force`, `php artisan optimize`. Gunakan akun database khusus dengan hak sesuai kebutuhan. Seeder bersifat upsert dan memperbarui definisi materi; tinjau sebelum menjalankannya pada konten yang sudah dikustomisasi.

## Desain interaksi dan asset

Instruksi bertahap, command pills, bantuan hint, feedback status, error recovery, target berlabel, focus ring, keyboard shortcut, focus trap modal, skip navigation, semantic HTML, reduced motion, layout bertumpuk, dan konfirmasi restart mendukung pemula. Warna bukan satu-satunya pembeda: ada teks ✓, status, dan nomor checkpoint.

Asset berupa SVG dan Canvas **original placeholder**, bukan asset game berlisensi pihak lain. Lihat [panduan asset](public/assets/README.md). Belum ada sprite sheet final dari ilustrator atau audio rekaman; idle/jalan empat arah dan suara saat ini dibuat secara procedural.

Screenshot Figma tidak terdapat pada lampiran yang diterima saat implementasi. UI mengikuti spesifikasi teks (hutan siang, hijau lembut, Home bersih, editor putih, Code Match dua kolom); kesamaan piksel terhadap Figma belum dapat diverifikasi.

## Referensi pembelajaran

Materi berbahasa Indonesia ditulis untuk aplikasi ini, tanpa kutipan panjang:

- [Dokumentasi resmi Python Turtle](https://docs.python.org/3/library/turtle.html): perilaku gerak, rotasi, dan kontrol pena. Interpreter aplikasi sengaja mendukung subset yang lebih sempit.
- [Automate the Boring Stuff with Python — Python Basics](https://automatetheboringstuff.com/3e/chapter1.html), Al Sweigart: konteks pemrograman dasar. Tidak dijadikan sumber palsu untuk semua fungsi Turtle.
- [Python Programming in Context — informasi penerbit](https://samples.jblearning.com/9781284175554/9781284176520_FMXx_Secured.pdf), Bradley N. Miller, David L. Ranum (edisi ketiga juga mencantumkan Julie Anderson): rujukan pendekatan belajar kontekstual dan Turtle. Tidak mengklaim kutipan halaman atau mengambil teks buku yang tidak diperiksa.
