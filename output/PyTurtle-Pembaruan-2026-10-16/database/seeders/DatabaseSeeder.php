<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Level;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (json_decode(file_get_contents(resource_path('data/levels.json')), true) as $level) {
            Level::updateOrCreate(['id' => $level['id']], ['title' => $level['title'], 'definition' => $level]);
        }
        $lessons = [
            ['Langkah pertama', 'forward(distance)', 'Menggerakkan Turtle maju mengikuti arah yang sedang dihadapinya.', 'forward(100)', 'Jika menghadap timur, maju 100 unit berarti bergeser 100 unit ke kanan.', 1],
            ['Selangkah ke belakang', 'backward(distance)', 'Bergerak mundur tanpa mengubah arah hadap.', "forward(160)\nbackward(60)", 'Maju 160 lalu mundur 60 menghasilkan perpindahan 100 unit. Arah hadap tetap sama.', 1],
            ['Belok ke kiri', 'left(degrees)', 'Memutar arah berlawanan jarum jam. Putaran tidak mengubah posisi.', "forward(100)\nleft(90)\nforward(80)", 'Dari timur, putaran kiri 90 derajat membuat Turtle menghadap utara.', 2],
            ['Belok ke kanan', 'right(degrees)', 'Memutar arah searah jarum jam. Gunakan sudut dalam derajat.', "forward(80)\nright(90)\nforward(80)", 'Dari timur, putaran kanan 90 derajat membuat Turtle menghadap selatan.', 2],
            ['Jejak perjalanan', 'penup() / pendown()', 'Mengangkat atau menurunkan pena untuk mengatur garis jejak.', "penup()\nforward(60)\npendown()\nforward(60)", 'Gerakan pertama tidak menggambar; gerakan kedua meninggalkan jejak. Di Maze, tabrakan tetap berlaku.', 3],
            ['Gabungkan perintah', 'Urutan instruksi', 'Program menjalankan instruksi dari atas ke bawah. Pecah rute panjang menjadi langkah kecil.', "forward(120)\nleft(90)\nforward(120)", 'Baca arah awal, tentukan jarak, lalu putar. Uji satu bagian sebelum menambah instruksi berikutnya.', 3],
        ];
        foreach ($lessons as $i => $l) {
            Lesson::updateOrCreate(['id' => $i + 1], array_combine(['title', 'command', 'description', 'example', 'explanation', 'practice_level'], $l));
        }
        Lesson::findOrFail(4)->update(['matching_pairs' => json_decode(file_get_contents(resource_path('data/code-match.json')), true)]);
        $this->call(RunestoneTurtleSeeder::class);
    }
}
