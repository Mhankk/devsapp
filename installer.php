<?php
// +------------------------------------------------------------------+
// |  devsapp — installer.php                                          |
// |  Standalone installer: download app dari GitHub Release,           |
// |  verifikasi SHA256 checksum, lalu extract ke folder target.       |
// |                                                                    |
// |  Usage: Taruh file ini di server, akses via browser.              |
// |  Requirement: PHP >= 8.0, ext-curl, ext-zip, folder writable.     |
// +------------------------------------------------------------------+

declare(strict_types=1);
set_time_limit(120);

// ============================================================
// KONFIGURASI — ubah sesuai repo lo
// ============================================================

const GITHUB_USER   = 'Mhankk';
const GITHUB_REPO   = 'devsapp';
const INSTALL_DIR   = __DIR__;                  // Target install (default: folder ini)
const APP_ZIP_NAME  = 'app.zip';
const CHECKSUM_FILE = 'checksums.txt';

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/** Output baris log ke browser */
function log_line(string $msg, string $type = 'info'): void {
    $icons = ['info' => '→', 'ok' => '✓', 'error' => '✗', 'warn' => '⚠'];
    $colors = [
        'info'  => '#888',
        'ok'    => '#00ff66',
        'error' => '#ff3333',
        'warn'  => '#ffb000',
    ];
    $icon  = $icons[$type]  ?? '→';
    $color = $colors[$type] ?? '#888';
    echo "<div style='color:{$color}; margin:2px 0;'>{$icon} " . htmlspecialchars($msg) . "</div>";
    ob_flush(); flush();
}

/** Cek requirement sebelum memulai */
function check_requirements(): array {
    $errors = [];
    if (PHP_MAJOR_VERSION < 8)          $errors[] = 'PHP >= 8.0 required (found ' . PHP_VERSION . ')';
    if (!extension_loaded('curl'))       $errors[] = 'ext-curl required';
    if (!extension_loaded('zip'))        $errors[] = 'ext-zip required';
    if (!is_writable(INSTALL_DIR))       $errors[] = 'Install directory is not writable: ' . INSTALL_DIR;
    return $errors;
}

/**
 * Hit GitHub API untuk resolve URL asset terbaru.
 * GitHub API membutuhkan User-Agent header.
 *
 * @return array{zip_url:string, checksum_url:string, tag:string}
 */
function resolve_latest_release(): array {
    $apiUrl = 'https://api.github.com/repos/' . GITHUB_USER . '/' . GITHUB_REPO . '/releases/latest';

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: ' . GITHUB_REPO . '-Installer/1.0',  // Wajib, GitHub API tolak tanpa ini
            'Accept: application/vnd.github+json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        throw new RuntimeException("GitHub API error (HTTP {$httpCode}). Rate limit mungkin tercapai, coba lagi nanti.");
    }

    $data = json_decode($response, true);
    if (empty($data['assets'])) {
        throw new RuntimeException("Tidak ada assets di release terbaru (tag: " . ($data['tag_name'] ?? 'unknown') . ")");
    }

    $zipUrl      = null;
    $checksumUrl = null;

    foreach ($data['assets'] as $asset) {
        if ($asset['name'] === APP_ZIP_NAME)  $zipUrl      = $asset['browser_download_url'];
        if ($asset['name'] === CHECKSUM_FILE) $checksumUrl = $asset['browser_download_url'];
    }

    if (!$zipUrl) {
        throw new RuntimeException("Asset '" . APP_ZIP_NAME . "' tidak ditemukan di release ini.");
    }
    if (!$checksumUrl) {
        throw new RuntimeException("Asset '" . CHECKSUM_FILE . "' tidak ditemukan. Wajib untuk verifikasi.");
    }

    return [
        'zip_url'      => $zipUrl,
        'checksum_url' => $checksumUrl,
        'tag'          => $data['tag_name'] ?? 'unknown',
    ];
}

/**
 * Download file dari URL ke path lokal.
 * Support redirect (GitHub assets redirect ke CDN).
 */
function download_file(string $url, string $targetPath): void {
    $fp = fopen($targetPath, 'wb');
    if (!$fp) throw new RuntimeException("Tidak bisa buka file untuk ditulis: {$targetPath}");

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE            => $fp,
        CURLOPT_TIMEOUT         => 120,
        CURLOPT_FOLLOWLOCATION  => true,   // Wajib untuk GitHub release assets (redirect ke CDN)
        CURLOPT_MAXREDIRS       => 5,
        CURLOPT_HTTPHEADER      => ['User-Agent: ' . GITHUB_REPO . '-Installer/1.0'],
    ]);
    $success  = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if (!$success || $httpCode !== 200) {
        @unlink($targetPath);
        throw new RuntimeException("Download gagal (HTTP {$httpCode}): {$url}");
    }
}

/**
 * Verifikasi SHA256 checksum file ZIP.
 * checksums.txt formatnya sama seperti output `sha256sum`.
 */
function verify_checksum(string $zipPath, string $checksumContent): void {
    preg_match('/([a-f0-9]{64})\s+' . preg_quote(APP_ZIP_NAME, '/') . '/', $checksumContent, $m);

    if (empty($m[1])) {
        throw new RuntimeException("Checksum untuk '" . APP_ZIP_NAME . "' tidak ditemukan di checksums.txt");
    }

    $expected = $m[1];
    $actual   = hash_file('sha256', $zipPath);

    if (!hash_equals($expected, $actual)) {
        throw new RuntimeException("Checksum MISMATCH!\n  Expected: {$expected}\n  Actual:   {$actual}");
    }
}

/**
 * Extract ZIP ke folder target.
 */
function extract_zip(string $zipPath, string $targetDir): void {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException("Tidak bisa buka ZIP: {$zipPath}");
    }
    $zip->extractTo($targetDir);
    $zip->close();
}

/**
 * Generate site key unik dari fingerprint server ini.
 * Key = hash dari hostname + document_root + server_addr + microtime.
 * Setiap install di server berbeda menghasilkan key yang berbeda.
 *
 * Wanting List #2: Encryption dengan key unik per-target Sites.
 *
 * @return string Site key dalam hex (64 chars)
 */
function generate_site_key(): string {
    $sidikJari = implode('|', [
        php_uname('n'),                                 // hostname
        $_SERVER['DOCUMENT_ROOT']    ?? getcwd(),       // document root
        $_SERVER['SERVER_ADDR']      ?? gethostbyname((string)gethostname()),  // IP
        PHP_SAPI,
        microtime(true),                                // Random agar tiap install unik
    ]);
    return hash('sha256', $sidikJari);
}

/**
 * Embed site key ke config.php yang sudah di-extract.
 * Tambahkan sebagai konstanta AppConfig::$siteKey.
 *
 * @param string $configPath Path ke config.php
 * @param string $siteKey    Key yang akan di-embed
 */
function embed_site_key(string $configPath, string $siteKey): void {
    if (!is_file($configPath)) {
        throw new RuntimeException("config.php tidak ditemukan: {$configPath}");
    }

    $konten = file_get_contents($configPath);
    if ($konten === false) {
        throw new RuntimeException("Tidak bisa baca config.php");
    }

    // Cek apakah sudah ada siteKey
    if (str_contains($konten, 'siteKey')) return;

    // Sisipkan setelah baris class AppConfig {
    $tanda     = 'public static string $credential';
    $penyisipan = '    public static string $siteKey = \'' . $siteKey . "'; // auto-generated\n    ";
    $kontenBaru = str_replace($tanda, $penyisipan . $tanda, $konten);

    if (@file_put_contents($configPath, $kontenBaru) === false) {
        throw new RuntimeException("Tidak bisa tulis ke config.php");
    }
}

// ============================================================
// MAIN — hanya jalankan jika ada POST confirm
// ============================================================

$isInstalling = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_install']));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>devsapp Installer</title>
<style>
* { box-sizing: border-box; }
body {
    margin:0; background:#000; color:#00ff66;
    font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
    display:grid; place-items:center; min-height:100vh;
}
.box {
    width:min(640px, 95vw); background:#0a0a0a;
    border:2px solid #00ff66; padding:28px;
    box-shadow:0 0 20px rgba(0,255,102,0.15);
}
h1 { margin:0 0 4px; font-size:20px; color:#00ff66; text-transform:uppercase; letter-spacing:2px; }
.subtitle { color:#008833; font-size:12px; margin-bottom:20px; }
.req-list { list-style:none; padding:0; margin:0 0 20px; }
.req-list li { padding:4px 0; font-size:12px; }
.req-ok   { color:#00ff66; } .req-ok::before { content:'✓  '; }
.req-fail { color:#ff3333; } .req-fail::before { content:'✗  '; }
.log-box {
    background:#000; border:1px solid #222; padding:14px;
    font-size:12px; max-height:320px; overflow-y:auto;
    margin-bottom:16px; line-height:1.6;
}
button {
    width:100%; padding:12px; background:#00ff66; color:#000;
    border:none; font-family:inherit; font-size:14px; font-weight:bold;
    text-transform:uppercase; letter-spacing:1px; cursor:pointer;
}
button:hover { background:#ffb000; }
button:disabled { background:#333; color:#666; cursor:not-allowed; }
.warn { color:#ffb000; font-size:12px; margin-bottom:16px; padding:10px; border:1px solid #ffb000; }
hr { border-color:#222; margin:20px 0; }
</style>
</head>
<body>
<div class="box">
    <h1>⬡ devsapp Installer</h1>
    <div class="subtitle">Pulls latest release from GitHub → verifies checksum → extracts</div>
    <hr>

    <?php
    // ---- Cek requirements selalu ----
    $errors = check_requirements();
    ?>

    <div style="margin-bottom:16px;">
        <strong style="font-size:12px; color:#ffb000;">REQUIREMENT CHECK:</strong>
        <ul class="req-list">
            <li class="<?= PHP_MAJOR_VERSION >= 8 ? 'req-ok' : 'req-fail' ?>">PHP >= 8.0 (found <?= PHP_VERSION ?>)</li>
            <li class="<?= extension_loaded('curl') ? 'req-ok' : 'req-fail' ?>">ext-curl</li>
            <li class="<?= extension_loaded('zip')  ? 'req-ok' : 'req-fail' ?>">ext-zip</li>
            <li class="<?= is_writable(INSTALL_DIR) ? 'req-ok' : 'req-fail' ?>">Install dir writable: <?= INSTALL_DIR ?></li>
        </ul>
    </div>

    <?php if (!$isInstalling): ?>
    <!-- ---- Form konfirmasi install ---- -->
    <?php if (!empty($errors)): ?>
        <div class="warn">
            ⚠ Requirements tidak terpenuhi. Perbaiki dulu sebelum install.
        </div>
    <?php endif; ?>

    <div class="warn">
        ⚠ Installer akan download dan extract file ke: <b><?= htmlspecialchars(INSTALL_DIR) ?></b><br>
        File yang sudah ada mungkin ditimpa.
    </div>

    <form method="post">
        <input type="hidden" name="confirm_install" value="1">
        <button type="submit" <?= !empty($errors) ? 'disabled' : '' ?>>
            <?= !empty($errors) ? 'REQUIREMENTS NOT MET' : '⬡ START INSTALL' ?>
        </button>
    </form>

    <?php else: ?>
    <!-- ---- Proses install ---- -->
    <div class="log-box" id="log">
    <?php
    ob_implicit_flush(true);

    $tmpZip      = sys_get_temp_dir() . '/' . APP_ZIP_NAME;
    $tmpChecksum = sys_get_temp_dir() . '/checksums.txt';
    $success     = false;

    try {
        // Step 1: Resolve release
        log_line("Menghubungi GitHub API...");
        $release = resolve_latest_release();
        log_line("Release ditemukan: " . $release['tag'], 'ok');
        log_line("ZIP URL: " . $release['zip_url']);

        // Step 2: Download checksum dulu
        log_line("Mendownload checksums.txt...");
        download_file($release['checksum_url'], $tmpChecksum);
        $checksumContent = file_get_contents($tmpChecksum);
        log_line("Checksum file didapat", 'ok');

        // Step 3: Download app.zip
        log_line("Mendownload app.zip... (ini bisa butuh beberapa detik)");
        download_file($release['zip_url'], $tmpZip);
        $sizeMb = round(filesize($tmpZip) / 1048576, 2);
        log_line("Download selesai ({$sizeMb} MB)", 'ok');

        // Step 4: Verifikasi checksum
        log_line("Memverifikasi SHA256 checksum...");
        verify_checksum($tmpZip, $checksumContent);
        log_line("Checksum OK ✓", 'ok');

        // Step 5: Extract
        log_line("Mengextract ke " . INSTALL_DIR . "...");
        extract_zip($tmpZip, INSTALL_DIR);
        log_line("Extract selesai", 'ok');

        // Step 6: Generate & embed site key (Wanting List #2)
        log_line("Generating site key unik untuk server ini...");
        $kunciFinger = generate_site_key();
        $konfig = INSTALL_DIR . '/src/php/config.php';
        try {
            embed_site_key($konfig, $kunciFinger);
            log_line("Site key berhasil di-embed ke config.php ✓", 'ok');
            log_line("Site key: " . substr($kunciFinger, 0, 16) . '...' . substr($kunciFinger, -8), 'info');
        } catch (Throwable $ke) {
            log_line("⚠ Site key gagal di-embed: " . $ke->getMessage() . " (tidak fatal)", 'warn');
        }

        // Step 7: Cleanup
        @unlink($tmpZip);
        @unlink($tmpChecksum);
        log_line("Temporary files dibersihkan", 'ok');

        $success = true;
        log_line("INSTALL SELESAI! Akses aplikasi di folder target.", 'ok');

    } catch (Throwable $e) {
        log_line("ERROR: " . $e->getMessage(), 'error');
        log_line("Install dibatalkan. Tidak ada file yang berubah.", 'warn');
        // Cleanup partial download
        @unlink($tmpZip);
        @unlink($tmpChecksum);
    }
    ?>
    </div>

    <?php if ($success): ?>
    <div style="color:#00ff66; padding:12px; border:1px solid #00ff66; font-size:13px; text-align:center;">
        ✓ Instalasi berhasil! Hapus file installer ini setelah selesai.
    </div>
    <?php else: ?>
    <div style="color:#ff3333; padding:12px; border:1px solid #ff3333; font-size:13px; text-align:center;">
        ✗ Instalasi gagal. Lihat log di atas untuk detail.
    </div>
    <form method="post" style="margin-top:12px;">
        <input type="hidden" name="confirm_install" value="1">
        <button type="submit">↺ COBA LAGI</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>

</div>
</body>
</html>
