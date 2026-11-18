<?php

namespace App\Services;

class LogicChallengeService
{
    public function challenges(): array
    {
        return [
            1 => ['title' => 'Maju selalu ke kanan?', 'claim' => 'forward(jarak) selalu membuat Shiha bergerak ke kanan layar.', 'a' => [['forward', 'distance']], 'b' => null, 'rule' => 'east', 'options' => ['Gerak maju mengikuti arah hadap awal.', 'Gerak maju selalu mengikuti kanan layar.', 'Jarak mengubah arah hadap.'], 'answer' => 0, 'explanation' => 'Forward berarti maju ke arah wajah Shiha. Kalau wajahnya ke atas, ia maju ke atas. Kalau wajahnya ke kiri, ia maju ke kiri—itu tetap maju, bukan mundur. Kanan layar hanya menjadi arah maju saat Shiha menghadap kanan.'],
            2 => ['title' => 'Urutan tidak berpengaruh?', 'claim' => 'Dua program dengan perintah yang sama selalu berakhir pada posisi yang sama, walaupun urutannya ditukar.', 'a' => [['forward', 'distance'], ['left', 90]], 'b' => [['left', 90], ['forward', 'distance']], 'rule' => 'same_position', 'options' => ['Belok selalu mengubah posisi.', 'Urutan menentukan arah saat gerak dilakukan.', 'Jumlah baris menentukan posisi akhir.'], 'answer' => 1, 'explanation' => 'Belok sebelum maju mengubah arah perjalanan. Belok setelah maju hanya mengubah arah hadap pada posisi yang sudah dicapai.'],
            3 => ['title' => 'Belok kanan berarti selatan?', 'claim' => 'right(90) selalu membuat Shiha menghadap selatan.', 'a' => [['right', 90]], 'b' => null, 'rule' => 'south', 'options' => ['Right memindahkan karakter ke selatan.', 'Arah awal tidak memengaruhi belokan.', 'Arah akhir bergantung pada arah awal.'], 'answer' => 2, 'explanation' => 'Right memutar 90° searah jarum jam dari arah awal. Dari utara hasilnya timur; hanya dari timur hasilnya selatan.'],
            4 => ['title' => 'Dua kali maju selalu setara?', 'claim' => 'Maju dua kali sejauh jarak pilihanmu selalu sama dengan forward(2).', 'a' => [['forward', 'distance'], ['forward', 'distance']], 'b' => [['forward', 2]], 'rule' => 'same_position', 'options' => ['Total jarak program A adalah dua kali jarak pilihan.', 'Dua perintah selalu berarti dua kotak.', 'Forward kedua membatalkan forward pertama.'], 'answer' => 0, 'explanation' => 'Program A bergerak 2 × jarak pilihan. Program B selalu dua kotak. Keduanya sama hanya jika jarak pilihan satu kotak.'],
            5 => ['title' => 'Empat putaran membalik arah?', 'claim' => 'Empat kali right(90) membuat arah akhir berlawanan dengan arah awal.', 'a' => [['right', 90], ['right', 90], ['right', 90], ['right', 90]], 'b' => null, 'rule' => 'opposite', 'options' => ['Empat putaran menggerakkan karakter empat kotak.', 'Total 360° kembali ke arah semula.', 'Setiap belokan selalu menghasilkan arah yang sama.'], 'answer' => 1, 'explanation' => 'Empat putaran 90° berjumlah 360°, sehingga karakter kembali menghadap arah awal tanpa berpindah posisi.'],
            6 => ['title' => 'Maju lalu mundur pasti kembali?', 'claim' => 'forward(jarak) lalu backward(2) selalu mengembalikan Shiha ke titik awal.', 'a' => [['forward', 'distance'], ['backward', 2]], 'b' => null, 'rule' => 'origin', 'options' => ['Backward selalu mengembalikan karakter ke titik awal.', 'Backward memutar karakter 180°.', 'Kembali ke awal hanya jika jarak maju dan mundur sama.'], 'answer' => 2, 'explanation' => 'Backward bergerak mundur tanpa membalik arah hadap. Jika maju dan mundur berbeda jarak, posisi akhirnya tidak kembali ke titik awal.'],
        ];
    }

    public function get(int $id): array
    {
        abort_unless(isset($this->challenges()[$id]), 404);

        return $this->challenges()[$id];
    }

    public function simulate(array $program, int $angle, int $distance): array
    {
        $x = 0;
        $y = 0;
        $steps = [['x' => 0, 'y' => 0, 'angle' => $angle, 'code' => 'Awal']];
        foreach ($program as [$command, $value]) {
            $value = $value === 'distance' ? $distance : $value;
            if ($command === 'left' || $command === 'right') {
                $angle = ($angle + ($command === 'left' ? $value : -$value) + 360) % 360;
            } else {
                $move = $command === 'backward' ? -$value : $value;
                $x += (int) round(cos(deg2rad($angle))) * $move;
                $y += (int) round(sin(deg2rad($angle))) * $move;
            }
            $steps[] = ['x' => $x, 'y' => $y, 'angle' => $angle, 'code' => "$command($value)"];
        }

        return ['x' => $x, 'y' => $y, 'angle' => $angle, 'steps' => $steps];
    }

    public function experiment(int $id, int $angle, int $distance): array
    {
        $c = $this->get($id);
        $a = $this->simulate($c['a'], $angle, $distance);
        $b = $c['b'] ? $this->simulate($c['b'], $angle, $distance) : null;
        $holds = match ($c['rule']) {
            'east' => $a['x'] > 0 && $a['y'] === 0,
            'south' => $a['angle'] === 270,
            'same_position' => $a['x'] === $b['x'] && $a['y'] === $b['y'],
            'opposite' => $a['angle'] === ($angle + 180) % 360,
            'origin' => $a['x'] === 0 && $a['y'] === 0,
        };

        return ['a' => $a, 'b' => $b, 'counterexample' => ! $holds];
    }
}
