# Mengaktifkan Google Login

Login dan register menyediakan tombol Google. Akun yang sudah ada dapat ditautkan melalui **Profil saya → Hubungkan Google**, menggunakan akun Google dengan email yang sama. Progress dan XP tetap milik akun yang sama. Login Google tidak menggabungkan akun berdasarkan email secara otomatis; masuk dahulu dengan kata sandi untuk menghubungkannya.

1. Di Google Cloud Console, pilih proyek kalian dan atur Google Auth Platform (branding, audience, dan consent screen). Untuk aplikasi berstatus Testing, tambahkan email penguji sebagai test users.
2. Buat OAuth client dengan tipe **Web application**.
3. Tambahkan Authorized redirect URI yang sama persis dengan alamat aplikasi, misalnya:
   - Aplikasi lokal: `http://127.0.0.1:8000/auth/google/callback`
   - Preview proyek: `http://127.0.0.1:8011/auth/google/callback`
   - Production: `https://domain-kalian/auth/google/callback`
4. Isi `.env` tanpa mengubah konfigurasi database atau APP_KEY yang sudah ada:

```dotenv
GOOGLE_CLIENT_ID=client-id-dari-google
GOOGLE_CLIENT_SECRET=client-secret-dari-google
GOOGLE_REDIRECT_URI=http://127.0.0.1:8011/auth/google/callback
```

5. Jalankan `php artisan config:clear`. Saat memasang pembaruan proyek, jalankan juga `composer install`, `php artisan migrate --force`, dan `npm run build`.
6. Buka login, klik **Lanjutkan dengan Google**, pilih akun penguji, dan selesaikan consent. Untuk akun PyTurtle lama, masuk dengan kata sandi lalu hubungkan dari Profil saya.

Port, hostname (`localhost` berbeda dari `127.0.0.1`), protokol, dan path callback harus sama persis. Jangan mencampur tab aplikasi port 8000 dengan callback port 8011 karena sesi OAuth harus kembali ke aplikasi yang sama. Gunakan HTTPS untuk production. Jangan commit Client Secret.

Jika kredensial belum diisi, tombol Google dinonaktifkan dengan keterangan; login email tetap tersedia. Jika pengguna Google perlu kata sandi lokal, fitur lupa kata sandi dapat digunakan setelah SMTP aplikasi dikonfigurasi.

Implementasi menggunakan Laravel Socialite, validasi OAuth state, batas sesi 10 menit, dan email Google terverifikasi. Scope hanya `openid`, `email`, `profile`. Token Google tidak disimpan. Tes otomatis mencakup redirect/state, callback, pendaftaran, login ulang, penautan, konflik email, dan pembatalan. Login nyata ke Google perlu diuji dengan kredensial proyek kalian.

Referensi: [Laravel Socialite](https://laravel.com/docs/12.x/socialite) dan [Google OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect).
