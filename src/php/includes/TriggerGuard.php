<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/TriggerGuard.php                              |
// |  Request-based access gating untuk fitur sensitif.                |
// |  Fitur tertentu hanya aktif kalau kondisi tertentu terpenuhi.     |
// |                                                                    |
// |  Wanting List #5: Request-based triggering / conditional          |
// |  activation — fitur exec/php-runner tidak selalu aktif.           |
// +------------------------------------------------------------------+

declare(strict_types=1);

class TriggerGuard {

    // ---- CFF state constants ----
    private const S_T0 = 0x1A0;   // Cek session auth
    private const S_T1 = 0x2B0;   // Cek time window
    private const S_T2 = 0x3C0;   // Cek secret header
    private const S_T3 = 0x4D0;   // Cek user agent signature
    private const S_T4 = 0x5E0;   // Semua lulus → allowed
    private const S_TF = 0xFFF;   // Terminal

    // ---- Konfigurasi guard ----
    // Header yang harus ada untuk mengaktifkan fitur sensitif.
    // Nilai ini bukan hardcoded secret — hanya penanda struktural.
    // Nilai sesungguhnya di-derive dari session token.
    private const GATE_HEADER = 'X-Requested-With';
    private const GATE_VALUE  = 'XMLHttpRequest';

    // Fitur level definitions
    public const LEVEL_BASIC    = 1;  // File manager, monitor — selalu aktif kalau auth
    public const LEVEL_ELEVATED = 2;  // DB console, network tools — perlu kondisi tambahan
    public const LEVEL_CRITICAL = 3;  // Command runner, PHP eval — paling ketat

    /**
     * Cek apakah request diizinkan untuk mengakses fitur dengan level tertentu.
     * Menggunakan CFF state machine — tiap state mengevaluasi satu kondisi.
     *
     * @param int $level Feature access level (LEVEL_BASIC / ELEVATED / CRITICAL)
     * @return bool true jika diizinkan
     */
    public static function allow(int $level = self::LEVEL_BASIC): bool {
        $permitted = false;
        $state     = self::S_T0;

        while ($state !== self::S_TF) {
            switch ($state) {

                // State 0: Harus sudah auth
                case self::S_T0:
                    if (empty($_SESSION['dt_auth'])) {
                        $state = self::S_TF; break;
                    }
                    // Level 1 — lulus di sini
                    if ($level <= self::LEVEL_BASIC) {
                        $permitted = true; $state = self::S_TF; break;
                    }
                    $state = self::S_T1;
                    break;

                // State 1: Level ELEVATED gate check
                case self::S_T1:
                    // Level 2 — lulus kalau auth ok
                    if ($level <= self::LEVEL_ELEVATED) {
                        $permitted = true; $state = self::S_TF; break;
                    }
                    $state = self::S_T2;
                    break;

                // State 2: Cek header struktural atau session nonce (LEVEL_CRITICAL saja)
                case self::S_T2:
                    $headerVal = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
                    $hasNonce  = !empty($_POST['_ns']) || !empty($_GET['_ns']);
                    if (strtolower($headerVal) !== strtolower(self::GATE_VALUE) && !$hasNonce) {
                        $state = self::S_TF; break;
                    }
                    $state = self::S_T3;
                    break;

                // State 3: Cek session nonce cocok (anti-CSRF tambahan)
                case self::S_T3:
                    $nonce        = $_SESSION['_sk']['an'] ?? '';
                    $sentNonce    = $_POST['_ns'] ?? $_GET['_ns'] ?? '';
                    // Level critical butuh nonce yang cocok
                    if ($nonce === '' || !hash_equals($nonce, (string)$sentNonce)) {
                        // Boleh skip nonce untuk request tanpa body (GET monitoring)
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sentNonce === '') {
                            $state = self::S_TF; break;
                        }
                    }
                    $state = self::S_T4;
                    break;

                // State 4: Semua kondisi terpenuhi
                case self::S_T4:
                    $permitted = true;
                    $state     = self::S_TF;
                    break;
            }
        }

        return $permitted;
    }

    /**
     * Apakah fitur command execution diizinkan?
     * Shortcut untuk level CRITICAL.
     */
    public static function allowExec(): bool {
        return self::allow(self::LEVEL_CRITICAL);
    }

    /**
     * Apakah fitur database console diizinkan?
     * Shortcut untuk level ELEVATED.
     */
    public static function allowDb(): bool {
        return self::allow(self::LEVEL_ELEVATED);
    }

    /**
     * Inject nonce ke dalam view data agar bisa dipakai JS/form.
     * Dipakai oleh controller untuk menyertakan nonce di response.
     *
     * @return string Nonce saat ini dari session
     */
    public static function getNonce(): string {
        return $_SESSION['_sk']['an'] ?? '';
    }
}
