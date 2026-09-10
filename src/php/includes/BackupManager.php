<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/BackupManager.php                             |
// |  Buat arsip ZIP/TAR dari direktori, dan handle download.           |
// +------------------------------------------------------------------+

declare(strict_types=1);

class BackupManager {

    /**
     * Buat arsip dari direktori yang diberikan.
     * Coba ZipArchive dulu, fallback ke tar jika ZipArchive tidak tersedia.
     *
     * @param string $dirPath Path direktori yang akan diarsip
     * @return array{success:bool, file?:string, filename?:string, method?:string, error?:string}
     */
    public function createArchive(string $dirPath): array {
        if (!is_dir($dirPath)) {
            return ['success' => false, 'error' => 'Directory not found: ' . $dirPath];
        }

        $basename = basename($dirPath) ?: 'root';
        $tmpDir   = sys_get_temp_dir();
        $ts       = date('Ymd_His');

        // ---- Coba ZipArchive (ekstensi bawaan PHP) ----
        if (class_exists('ZipArchive')) {
            $zipFile = "{$tmpDir}/{$basename}_backup_{$ts}.zip";
            $zip     = new ZipArchive();

            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                // Closure rekursif: tambah semua file ke ZIP via use (&$self)
                $addDir = null;
                $addDir = function (string $path, string $prefix) use ($zip, &$addDir): void {
                    $items = @scandir($path) ?: [];
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $fullPath = $path . DIRECTORY_SEPARATOR . $item;
                        $zipPath  = $prefix . '/' . $item;
                        if (is_dir($fullPath)) {
                            $zip->addEmptyDir($zipPath);
                            $addDir($fullPath, $zipPath);
                        } elseif (is_file($fullPath) && is_readable($fullPath)) {
                            $zip->addFile($fullPath, $zipPath);
                        }
                    }
                };

                $addDir($dirPath, $basename);
                $zip->close();

                if (is_file($zipFile)) {
                    return [
                        'success'  => true,
                        'file'     => $zipFile,
                        'filename' => basename($zipFile),
                        'method'   => 'ZipArchive',
                    ];
                }
            }
        }

        // ---- Fallback ke tar via command runner ----
        if (cmd_available()) {
            $tarFile = "{$tmpDir}/{$basename}_backup_{$ts}.tar.gz";
            run_cmd(
                'cd ' . escapeshellarg(dirname($dirPath)) .
                ' && tar -czf ' . escapeshellarg($tarFile) . ' ' . escapeshellarg($basename)
            );
            if (is_file($tarFile)) {
                return [
                    'success'  => true,
                    'file'     => $tarFile,
                    'filename' => basename($tarFile),
                    'method'   => 'tar',
                ];
            }
        }

        return [
            'success' => false,
            'error'   => 'No archive method available (ZipArchive or tar required).',
        ];
    }

    /**
     * Stream file backup ke browser sebagai download, lalu hapus file tmp.
     * Dipanggil jika ada GET parameter 'bk'. Security: hanya file dari sys_get_temp_dir().
     */
    public function handleBackupDownload(): void {
        if (!isset($_GET['bk']) || !is_string($_GET['bk'])) return;

        $file = $_GET['bk'];

        // Security: hanya izinkan download dari tmp dir
        if (!str_starts_with($file, sys_get_temp_dir())) return;
        if (!is_file($file) || !is_readable($file)) return;

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);

        @unlink($file); // Hapus file tmp setelah di-download
        exit;
    }
}
