<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/SystemMonitor.php                             |
// |  Ambil metrik sistem: disk, RAM, CPU, proses, PHP memory.         |
// +------------------------------------------------------------------+

declare(strict_types=1);

class SystemMonitor {

    /**
     * Kumpulkan semua metrik sistem yang tersedia.
     *
     * @param string $path Path aktif untuk cek disk usage
     * @return array Metrik lengkap: disk, ram, cpu, proc_count, dll
     */
    public function getMetrics(string $path): array {
        $diskTotal = @disk_total_space($path);
        $diskFree  = @disk_free_space($path);
        $diskUsed  = ($diskTotal !== false && $diskFree !== false)
            ? $diskTotal - $diskFree
            : 0;

        // CPU load via sys_getloadavg (via CAP alias 'sg')
        $sysLoad = CAP::ok('sg') ? CAP::call('sg') : [0, 0, 0];
        $cpuLoad = is_array($sysLoad) && isset($sysLoad[0])
            ? round($sysLoad[0], 2)
            : 0.0;

        // RAM via /proc/meminfo (closure dengan referensi)
        $ramTotal = 0.0;
        $ramFree  = 0.0;
        $parseMeminfo = function () use (&$ramTotal, &$ramFree): void {
            if (!@is_readable('/proc/meminfo')) return;
            $content = @file_get_contents('/proc/meminfo');
            if (!$content) return;

            preg_match('/MemTotal:\s+(\d+)/',     $content, $mt);
            preg_match('/MemAvailable:\s+(\d+)/', $content, $ma);
            preg_match('/MemFree:\s+(\d+)/',      $content, $mf);

            if (isset($mt[1])) $ramTotal = (float)$mt[1] * 1024;
            if (isset($ma[1]))     $ramFree = (float)$ma[1] * 1024;
            elseif (isset($mf[1])) $ramFree = (float)$mf[1] * 1024;
        };
        $parseMeminfo();

        $ramUsed = max(0, $ramTotal - $ramFree);

        // Jumlah proses via 'ps aux'
        $procCount = 0;
        if (cmd_available()) {
            $raw = trim(run_cmd('ps aux | wc -l'));
            if (is_numeric($raw)) $procCount = max(0, (int)$raw - 1);
        }

        return [
            'status'        => 'ok',
            'timestamp'     => date('Y-m-d H:i:s'),
            'hostname'      => gethostname() ?: 'localhost',
            'os'            => PHP_OS_FAMILY,
            'php_ver'       => PHP_VERSION,
            'disk' => [
                'total' => $diskTotal ?: 0,
                'free'  => $diskFree  ?: 0,
                'used'  => $diskUsed,
                'pct'   => ($diskTotal && $diskTotal > 0)
                    ? round(($diskUsed / $diskTotal) * 100, 1)
                    : 0,
            ],
            'ram' => [
                'total' => $ramTotal,
                'free'  => $ramFree,
                'used'  => $ramUsed,
                'pct'   => ($ramTotal && $ramTotal > 0)
                    ? round(($ramUsed / $ramTotal) * 100, 1)
                    : 0,
            ],
            'cpu' => [
                'load'  => $cpuLoad,
                'cores' => (int)run_cmd('nproc') ?: 1,
            ],
            'proc_count'    => $procCount,
            'php_mem_usage' => memory_get_usage(true),
        ];
    }

    /**
     * Kirim response JSON dan exit.
     * Menggunakan closure yang di-bind ke $this (auto-binding closure).
     */
    public function respondJson(array $data): void {
        $respond = function (array $data): void {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('X-Powered-By: ' . get_class($this));
            echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        };
        $respond($data);
    }
}
