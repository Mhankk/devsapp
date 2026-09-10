<?php
// +------------------------------------------------------------------+
// |  devsapp — helpers.php                                            |
// |  Pure utility functions — tidak ada side effect, tidak ada global |
// +------------------------------------------------------------------+

declare(strict_types=1);

/**
 * HTML-escape nilai apapun untuk output yang aman di view.
 *
 * @param mixed $v Nilai yang akan di-escape
 */
function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/**
 * Format bytes ke unit yang mudah dibaca (B / KB / MB / GB / TB).
 * Disimpan sebagai static closure supaya bisa dipakai sebagai callback.
 */
$bytes_fmt = static function (float $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return ($i === 0
        ? number_format($bytes, 0)
        : number_format($bytes, 1)
    ) . ' ' . $units[$i];
};

/**
 * Ambil permission file/folder dalam format octal (misal: 0755).
 * Static closure — tidak butuh $this, hemat memori.
 */
$get_perms_octal = static function (string $path): string {
    return substr(sprintf('%o', @fileperms($path) ?: 0), -4);
};

/**
 * Hapus direktori beserta seluruh isinya secara rekursif.
 * Closure rekursif — memanggil dirinya sendiri via `use (&$self)`.
 *
 * @return bool true jika berhasil dihapus
 */
$rmdir_recursive = null;
$rmdir_recursive = function (string $dirPath) use (&$rmdir_recursive): bool {
    $entries = array_diff((array)@scandir($dirPath), ['.', '..']);
    foreach ($entries as $entry) {
        $fullPath = "$dirPath/$entry";
        is_dir($fullPath)
            ? $rmdir_recursive($fullPath)
            : @unlink($fullPath);
    }
    return (bool)@rmdir($dirPath);
};
