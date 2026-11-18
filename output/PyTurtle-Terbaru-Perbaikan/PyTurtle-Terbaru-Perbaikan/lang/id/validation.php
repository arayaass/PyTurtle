<?php
return [
    'required'=>':attribute perlu diisi.',
    'email'=>'Masukkan alamat email yang valid.',
    'unique'=>':attribute sudah digunakan. Coba masuk atau gunakan email lain.',
    'confirmed'=>'Konfirmasi :attribute belum sama.',
    'min'=>['string'=>':attribute minimal :min karakter.','array'=>':attribute minimal :min item.'],
    'max'=>['string'=>':attribute maksimal :max karakter.','array'=>':attribute maksimal :max item.'],
    'string'=>':attribute harus berupa teks.',
    'integer'=>':attribute harus berupa angka bulat.',
    'in'=>'Pilihan :attribute tidak tersedia.',
    'exists'=>'Pilihan :attribute tidak ditemukan.',
    'accepted'=>'Konfirmasi terlebih dahulu sebelum melanjutkan.',
    'prohibited'=>':attribute tidak boleh dikirim.',
    'uuid'=>'Identitas penyimpanan tidak valid. Muat ulang lalu coba lagi.',
    'boolean'=>'Pilihan :attribute harus aktif atau nonaktif.',
    'attributes'=>['name'=>'Nama','display_name'=>'Nama tampilan','email'=>'Email','password'=>'Kata sandi','code'=>'Kode','level_id'=>'Level','attempts'=>'Percobaan'],
];
