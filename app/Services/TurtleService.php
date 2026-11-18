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
        try {
            $program = (new TurtleProgram)->parse($code, $level['commands']);
        } catch (\InvalidArgumentException $error) {
            [$line, $message] = explode('|', $error->getMessage(), 2);

            return $this->error($state, (int) $line, $message);
        }
        $commands = $program['commands'];
        foreach ($commands as [$name, $value, $line]) {
            if ($grid && in_array($name, ['forward', 'backward'], true) && floor($value) !== $value) {
                return $this->error($state, $line, 'Jumlah langkah harus angka bulat.');
            }
            if ($grid && in_array($name, ['left', 'right'], true) && fmod($value, 90) != 0) {
                return $this->error($state, $line, 'Belokan pada grid memakai kelipatan 90°.');
            }
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

        if ($success && ($level['require_variables'] ?? false) && ! $program['used_variable']) {
            return ['result' => 'PARTIAL', 'state' => $state, 'message' => 'Jalurnya sudah benar! Sekarang simpan jarak atau sudut pada variabel dan gunakan variabel itu dalam perintah gerak.', 'error_type' => 'variable_required'];
        }

        return ['result' => $success ? 'SUCCESS' : 'PARTIAL', 'state' => $state, 'message' => $success ? 'Challenge berhasil! Perjalananmu tersimpan.' : 'Langkah bagus! Tambahkan perintah untuk mencapai semua penanda dan tujuan.', 'error_type' => null];
    }

    private function error(array $state, int $line, string $message): array
    {
        return ['result' => 'ERROR', 'state' => $state, 'line' => $line, 'message' => "Ada masalah pada baris $line. $message", 'error_type' => 'syntax'];
    }
}
