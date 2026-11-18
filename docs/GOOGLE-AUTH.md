# Mengaktifkan login Google dengan Firebase

PyTurtle memakai **Firebase Authentication** untuk masuk dengan Google. Firebase menangani jendela pemilihan akun; server Laravel memeriksa token Firebase dan membuat sesi PyTurtle. Akun, modul, progres Maze, dan XP tetap disimpan di database PyTurtle.

## 1. Buat proyek Firebase

1. Buka [Firebase Console](https://console.firebase.google.com/), lalu masuk dengan akun Google pengelola.
2. Pilih **Create a project / Add project** dan beri nama, misalnya **PyTurtle**. Kamu juga bisa memakai proyek Firebase yang sudah ada.
3. Ikuti langkah pembuatan proyek. Google Analytics tidak diperlukan untuk fitur login ini.
4. Setelah selesai, buka proyek tersebut.

## 2. Daftarkan aplikasi Web

1. Buka **Project settings** melalui ikon roda gigi di samping Project Overview.
2. Di tab **General**, bagian **Your apps**, pilih ikon Web **</>**.
3. Isi nama aplikasi, misalnya **PyTurtle Web**, lalu klik **Register app**. Hosting Firebase tidak diperlukan untuk aplikasi Laravel ini.
4. Pada konfigurasi SDK, catat empat nilai dari objek `firebaseConfig`:

| Nilai di Firebase Console | Nama pengaturan di PyTurtle |
| --- | --- |
| `apiKey` | `FIREBASE_API_KEY` |
| `authDomain` | `FIREBASE_AUTH_DOMAIN` |
| `projectId` | `FIREBASE_PROJECT_ID` |
| `appId` | `FIREBASE_APP_ID` |

Tidak perlu menyalin seluruh kode contoh Firebase ke halaman aplikasi. Integrasinya sudah dipasang di proyek.

## 3. Aktifkan Google sebagai metode masuk

1. Di menu **Build**, buka **Authentication**.
2. Klik **Get started** jika belum pernah dipakai.
3. Buka **Sign-in method** dan pilih **Google**, atau gunakan **Add new provider → Google**.
4. Aktifkan **Enable**.
5. Pilih **Project support email** yang aktif, lalu klik **Save**.

Langkah penyedia Google dan login melalui pop-up mengikuti [panduan resmi Firebase](https://firebase.google.com/docs/auth/web/google-signin).

## 4. Izinkan domain aplikasi

Buka **Authentication → Settings → Authorized domains**, lalu tambahkan domain yang dipakai membuka PyTurtle:

```text
localhost
127.0.0.1
```

Masukkan hostname saja, tanpa `http://`, nomor port, atau `/login`. Saat dipasang di hosting, tambahkan hostname situsmu, misalnya `pyturtle.domainmu.com`.

Proyek yang dibuat setelah 28 April 2025 tidak otomatis memasukkan `localhost`, jadi periksa daftar tersebut secara manual. Lihat [FAQ Firebase Authentication](https://firebase.google.com/docs/auth/faq-and-troubleshooting).

## 5. Isi berkas .env

Di folder utama proyek, buka `.env`, lalu isi nilai dari aplikasi Web Firebase yang sama:

```dotenv
APP_URL=http://localhost:8000

FIREBASE_API_KEY=ISI_API_KEY_FIREBASE
FIREBASE_AUTH_DOMAIN=nama-proyekmu.firebaseapp.com
FIREBASE_PROJECT_ID=nama-proyekmu
FIREBASE_APP_ID=ISI_APP_ID_FIREBASE
```

Ganti seluruh nilai contoh dengan nilai asli dari Firebase Console. `FIREBASE_APP_ID` biasanya berbentuk `1:nomor:web:identitas`. Gunakan nilai `authDomain` yang benar dari konfigurasi, terutama bila kamu memakai domain autentikasi khusus.

Pengaturan `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, dan `GOOGLE_REDIRECT_URI` dari panduan lama tidak dipakai oleh integrasi ini. Verifikasi token dilakukan menggunakan sertifikat publik Firebase, sehingga tidak perlu menambahkan berkas service account/private key untuk alur login ini.

Keempat nilai Firebase Web di atas memang dikirim ke browser untuk inisialisasi SDK. Jangan memasukkan service account JSON atau private key ke nilai-nilai tersebut. Jangan mengubah `APP_KEY` maupun konfigurasi database yang sudah berfungsi.

## 6. Jalankan aplikasi

Jika menerapkan pembaruan proyek ke komputer lain, pasang dependensinya terlebih dahulu:

```powershell
composer install
npm install
npm run build
```

Kemudian bersihkan cache konfigurasi:

```powershell
php artisan config:clear
```

Pastikan migrasi proyek sudah diterapkan. Jika belum, jalankan:

```powershell
php artisan migrate
```

Kolom `google_id` dari migrasi proyek dipakai untuk menyimpan hubungan akun Google. Gunakan `migrate`, bukan `migrate:fresh`, agar data lama tetap tersimpan.

Jalankan aplikasi:

```powershell
php artisan serve --host=localhost --port=8000
```

Buka `http://localhost:8000/login` di Chrome, Edge, atau browser biasa. Izinkan pop-up dari aplikasi saat diminta. SDK Firebase dimuat dari CDN resmi Google dengan versi yang ditetapkan di kode; browser dan server membutuhkan koneksi internet. Rujukan: [pemasangan Firebase SDK melalui CDN](https://firebase.google.com/docs/web/alt-setup).

## 7. Coba login

**Untuk email baru:** klik **Lanjutkan dengan Google**, pilih akun, dan tunggu sampai diarahkan ke Home. Coba keluar lalu masuk kembali dengan akun yang sama.

**Untuk email yang sudah terdaftar di PyTurtle:**

1. Masuk memakai email dan kata sandi PyTurtle dahulu.
2. Buka **Profil saya → Hubungkan Google**.
3. Pilih akun Google dengan email yang sama.
4. Setelah konfirmasi berhasil, keluar lalu coba **Lanjutkan dengan Google** di halaman login.

Akun lama tidak digabung otomatis berdasarkan email. Progres dan XP tetap melekat pada akun yang dihubungkan. Identitas Google yang sudah ditautkan pada versi sebelumnya tetap digunakan oleh integrasi Firebase ini.

Pengguna yang berhasil login melalui Google juga akan terlihat di **Firebase Console → Authentication → Users**. Pengguna yang sudah masuk Firebase tetapi gagal masuk PyTurtle, misalnya karena akun lama belum dihubungkan, dapat tetap muncul di daftar Firebase tersebut.

## Jika muncul kendala

| Pesan / gejala | Solusi |
| --- | --- |
| Tombol Google nonaktif dan ada keterangan belum diaktifkan | Isi keempat pengaturan Firebase di `.env`, jalankan `php artisan config:clear`, lalu muat ulang halaman. |
| Tombol tetap nonaktif dengan pesan gagal dimuat | Periksa koneksi internet dan akses browser ke `www.gstatic.com`. Muat ulang halaman. |
| `auth/unauthorized-domain` | Tambahkan hostname yang sedang dipakai ke Authorized domains di Firebase. |
| `auth/operation-not-allowed` | Aktifkan Google pada Authentication → Sign-in method. |
| Pop-up diblokir | Izinkan pop-up untuk alamat PyTurtle, lalu klik tombol Google lagi. |
| Login dibatalkan | Klik tombol Google lagi dan selesaikan pemilihan akun. |
| Email sudah mempunyai akun PyTurtle | Masuk menggunakan kata sandi, lalu hubungkan Google dari Profil saya. |
| Login belum dapat diverifikasi | Pastikan semua nilai berasal dari proyek Firebase yang sama, waktu komputer/server benar, dan server bisa mengakses sertifikat Google melalui HTTPS. Mulai kembali dari tombol Google. |
| Kolom `google_id` tidak ditemukan | Terapkan migrasi pada database aplikasi yang benar. |

Jangan menonaktifkan verifikasi sertifikat HTTPS untuk mengatasi masalah jaringan. Perbaiki sertifikat CA PHP atau pengaturan waktu komputer jika koneksi HTTPS gagal.

## Cara kerja dan batas pengujian

Browser meminta Firebase ID token, mengirimkannya melalui formulir POST dengan CSRF, lalu server memeriksa tanda tangan RS256, proyek, issuer, masa berlaku, waktu autentikasi, penyedia Google, dan email terverifikasi. Alur perlu diselesaikan dalam 10 menit sejak autentikasi Google. Token tidak disimpan sebagai data akun; setelah pertukaran, aplikasi memakai sesi Laravel biasa. Rujukan verifikasi: [Firebase ID token verification](https://firebase.google.com/docs/auth/admin/verify-id-tokens).

Menghapus atau menonaktifkan akun di Firebase tidak otomatis mengakhiri sesi Laravel yang sudah dibuat. Pengelolaan sesi aplikasi tetap mengikuti Laravel.

Pengujian otomatis memakai token RSA dan respons sertifikat tiruan untuk memeriksa penerimaan serta penolakan identitas. Pengujian Google sungguhan baru dapat dilakukan setelah konfigurasi Firebase milikmu diisi. Panduan ini menggantikan panduan OAuth/Socialite sebelumnya.
