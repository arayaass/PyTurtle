<?php

namespace App\Services;

/** Deterministic interpreter: never executes Python, PHP or shell input. */
class TurtleService
{
    public function run(string $code, array $level): array
    {
        $state = $level['start'] + ['penDown' => true, 'checkpoint' => 0];
        $grid = ($level['movement_unit'] ?? null) === 'grid';
        $stepSize = $grid ? $level['step_size'] : 1;
        $tolerance = $grid ? 0.001 : 12;
        $example = $grid ? 'forward(3)' : 'forward(100)';
        $commands = [];
        foreach (preg_split('/\r\n|\n|\r/', $code) as $i => $raw) {
            $line = trim(explode('#', $raw, 2)[0]);
            if ($line === '') {
                continue;
            }
            if (! preg_match('/^(?:turtle\.)?(forward|backward|left|right|penup|pendown)\s*\(\s*(-?(?:\d+(?:\.\d+)?|\.\d+))?\s*\)$/D', $line, $m)) {
                return $this->error($state, $i + 1, "Gunakan tanda kurung, misalnya $example. Import, loop, variabel, dan perintah lain belum didukung.");
            }
            $name = $m[1];
            $value = $m[2] ?? null;
            if (! in_array($name, $level['commands'], true)) {
                return $this->error($state, $i + 1, 'Perintah belum tersedia di level ini. Lihat daftar perintah di atas editor.');
            }
            $pen = in_array($name, ['penup', 'pendown']);
            if (($pen && $value !== null) || (! $pen && ($value === null || abs((float) $value) > 1000))) {
                return $this->error($state, $i + 1, 'Gerakan dan putaran membutuhkan angka antara -1000 dan 1000. penup() dan pendown() tidak menerima angka.');
            }
            if ($grid && in_array($name, ['forward', 'backward'], true) && floor((float) $value) !== (float) $value) {
                return $this->error($state, $i + 1, 'Jumlah langkah harus angka bulat. Contoh: forward(1), forward(2), atau forward(3).');
            }
            if ($grid && in_array($name, ['left', 'right'], true) && fmod((float) $value, 90) != 0) {
                return $this->error($state, $i + 1, 'Belokan pada grid memakai kelipatan 90°. Contoh: left(90) atau right(90).');
            }
            $commands[] = [$name, (float) $value, $i + 1];
            if (count($commands) > 100) {
                return $this->error($state, $i + 1, 'Batasi program sampai 100 perintah.');
            }
        }
        if (! $commands) {
            return $this->error($state, 1, "Tulis satu perintah terlebih dahulu. Coba $example.");
        }
        foreach ($commands as [$name,$value,$line]) {
            if ($name === 'left' || $name === 'right') {
                $state['angle'] = fmod($state['angle'] + ($name === 'right' ? $value : -$value) + 3600, 360);

                continue;
            }
            if ($name === 'penup' || $name === 'pendown') {
                $state['penDown'] = $name === 'pendown';

                continue;
            }
            $distance = ($name === 'backward' ? -$value : $value) * $stepSize;
            $steps = max(1, (int) ceil(abs($distance) / 2));
            for ($step = 0; $step < $steps; $step++) {
                $x = $state['x'] + cos(deg2rad($state['angle'])) * $distance / $steps;
                $y = $state['y'] + sin(deg2rad($state['angle'])) * $distance / $steps;
                $inside = false;
                foreach ($level['path'] as [$px,$py,$w,$h]) {
                    if ($x >= $px + 5 && $x <= $px + $w - 5 && $y >= $py + 5 && $y <= $py + $h - 5) {
                        $inside = true;
                    }
                }
                if (! $inside) {
                    return ['result' => 'PARTIAL', 'state' => $state, 'line' => $line, 'message' => 'Shiha berhenti di tepi jalan. Hitung kembali kotak atau periksa arah belokan.', 'error_type' => 'collision'];
                }
                $state['x'] = $x;
                $state['y'] = $y;
                $cp = $level['checkpoints'][$state['checkpoint']] ?? null;
                if ($cp && hypot($x - $cp[0], $y - $cp[1]) <= $tolerance) {
                    $state['checkpoint']++;
                }
            }
        }
        $goal = end($level['checkpoints']);
        $success = $state['checkpoint'] === count($level['checkpoints']) && hypot($state['x'] - $goal[0], $state['y'] - $goal[1]) <= $tolerance;

        return ['result' => $success ? 'SUCCESS' : 'PARTIAL', 'state' => $state, 'message' => $success ? 'Challenge berhasil! Perjalananmu tersimpan.' : 'Langkah bagus! Tambahkan perintah untuk mencapai semua penanda dan tujuan.', 'error_type' => null];
    }

    private function error(array $state, int $line, string $message): array
    {
        return ['result' => 'ERROR', 'state' => $state, 'line' => $line, 'message' => "Ada masalah pada baris $line. $message", 'error_type' => 'syntax'];
    }
}
