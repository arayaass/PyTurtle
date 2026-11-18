<?php

namespace App\Services;

/** Parses a bounded Python subset without executing user code. */
class TurtleProgram
{
    public function parse(string $code, array $allowed): array
    {
        if (strlen($code) > 10000) {
            throw new \InvalidArgumentException('1|Kode terlalu panjang.');
        }
        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', $code) as $i => $raw) {
            $text = trim(explode('#', $raw, 2)[0]);
            if ($text === '') {
                continue;
            }
            preg_match('/^ */', $raw, $m);
            $rows[] = ['text' => $text, 'indent' => strlen($m[0]), 'line' => $i + 1, 'tab' => preg_match('/^\s*\t/', $raw)];
        }
        $vars = [];
        $commands = [];
        $used = false;
        $work = 0;
        $reserved = ['for', 'in', 'range', 'import', 'forward', 'backward', 'left', 'right', 'penup', 'pendown', 'turtle'];
        $fail = function ($line, $message) {
            throw new \InvalidArgumentException($line.'|'.$message);
        };
        $value = function ($text, $line) use (&$vars, $fail) {
            if (preg_match('/^-?(?:\d+(?:\.\d+)?|\.\d+)$/D', $text)) {
                return (float) $text;
            }
            if (preg_match('/^[A-Za-z_]\w*$/D', $text) && array_key_exists($text, $vars)) {
                return $vars[$text];
            }
            $fail($line, 'Gunakan angka atau variabel yang sudah diisi. Contoh: langkah = 3 lalu forward(langkah).');
        };
        $block = function ($start, $end, $indent, $depth = 0) use (&$block, &$vars, &$commands, &$used, &$work, $rows, $allowed, $reserved, $value, $fail) {
            if ($depth > 4) {
                $fail($rows[$start]['line'], 'Maksimal empat tingkat perulangan.');
            }
            for ($i = $start; $i < $end; $i++) {
                $r = $rows[$i];
                $line = $r['line'];
                if (++$work > 2000) {
                    $fail($line, 'Program terlalu panjang. Ringkas perulanganmu.');
                }
                if ($r['tab'] || $r['indent'] !== $indent) {
                    $fail($line, 'Periksa indentasi. Gunakan spasi yang sama di dalam perulangan.');
                }
                if (preg_match('/^([A-Za-z_]\w*)\s*=\s*(.+)$/D', $r['text'], $m)) {
                    if (in_array($m[1], $reserved, true)) {
                        $fail($line, 'Nama variabel ini dipakai oleh perintah Python Turtle.');
                    }
                    $n = $value($m[2], $line);
                    if (! is_finite((float) $n) || abs($n) > 1000) {
                        $fail($line, 'Nilai variabel harus antara -1000 dan 1000.');
                    }
                    $vars[$m[1]] = $n;

                    continue;
                }
                if (preg_match('/^for\s+([A-Za-z_]\w*)\s+in\s+range\(\s*([^(),]+)\s*\)\s*:$/D', $r['text'], $m)) {
                    if (in_array($m[1], $reserved, true)) {
                        $fail($line, 'Pilih nama variabel perulangan lain.');
                    }
                    $n = $value(trim($m[2]), $line);
                    if (floor((float) $n) != (float) $n || $n < 1 || $n > 100) {
                        $fail($line, 'range() membutuhkan bilangan bulat 1 sampai 100.');
                    }
                    $stop = $i + 1;
                    while ($stop < $end && $rows[$stop]['indent'] > $indent) {
                        $stop++;
                    }
                    if ($stop === $i + 1) {
                        $fail($line, 'Isi perulangan harus menjorok dengan spasi.');
                    }
                    for ($j = 0; $j < $n; $j++) {
                        $vars[$m[1]] = $j;
                        $block($i + 1, $stop, $rows[$i + 1]['indent'], $depth + 1);
                    }
                    $i = $stop - 1;

                    continue;
                }
                if (! preg_match('/^(?:turtle\.)?(forward|backward|left|right|penup|pendown)\s*\(\s*([^()]*)\s*\)$/D', $r['text'], $m)) {
                    $fail($line, 'Gunakan perintah seperti forward(3), variabel, atau for i in range(3):. Import dan perintah lain tidak didukung.');
                }
                $name = $m[1];
                $arg = trim($m[2]);
                $pen = in_array($name, ['penup', 'pendown'], true);
                if (! in_array($name, $allowed, true)) {
                    $fail($line, 'Perintah belum tersedia di level ini.');
                }
                if ($pen && $arg !== '') {
                    $fail($line, 'penup() dan pendown() tidak menerima angka.');
                }
                $n = $pen ? 0 : $value($arg, $line);
                if (! is_finite((float) $n) || abs($n) > 1000) {
                    $fail($line, 'Gerakan dan putaran membutuhkan angka antara -1000 dan 1000.');
                }
                if (! $pen && preg_match('/^[A-Za-z_]\w*$/D', $arg)) {
                    $used = true;
                }
                $commands[] = [$name, (float) $n, $line];
                if (count($commands) > 100) {
                    $fail($line, 'Batasi program sampai 100 perintah.');
                }
            }
        };
        if ($rows) {
            $block(0, count($rows), 0);
        }
        if (! $commands) {
            $fail(1, 'Tulis satu perintah terlebih dahulu. Coba langkah = 3 lalu forward(langkah).');
        }

        return ['commands' => $commands, 'used_variable' => $used];
    }
}
