<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/ServerInfo.php                                |
// |  Informasi server, ekstensi PHP, dan cron jobs.                   |
// +------------------------------------------------------------------+

declare(strict_types=1);

class ServerInfo {

    /**
     * Kumpulkan informasi server secara lengkap.
     * Bump exec time karena beberapa call (uptime) bisa lambat.
     *
     * @return array<string, string>
     */
    public function getFullInfo(): array {
        @set_time_limit(15);

        // Uptime: coba /proc/uptime dulu, fallback ke command
        $uptime = 'N/A';
        if (@is_readable('/proc/uptime')) {
            $raw  = @file_get_contents('/proc/uptime');
            if ($raw) {
                $secs   = (int)$raw;
                $days   = floor($secs / 86400);
                $hours  = floor(($secs % 86400) / 3600);
                $mins   = floor(($secs % 3600) / 60);
                $uptime = "{$days}d {$hours}h {$mins}m";
            }
        } elseif (cmd_available()) {
            $uptime = trim(run_cmd('uptime -p 2>/dev/null')) ?: 'N/A';
        }

        // Server IP — skip DNS lookup (bisa hang 30s di restricted server)
        $serverIp = $_SERVER['SERVER_ADDR']
            ?? $_SERVER['LOCAL_ADDR']
            ?? '(N/A)';

        return [
            'server_ip'          => $serverIp,
            'client_ip'          => $_SERVER['REMOTE_ADDR']      ?? 'N/A',
            'server_port'        => $_SERVER['SERVER_PORT']       ?? 'N/A',
            'server_sw'          => $_SERVER['SERVER_SOFTWARE']   ?? 'N/A',
            'document_root'      => $_SERVER['DOCUMENT_ROOT']     ?? 'N/A',
            'script_path'        => $_SERVER['SCRIPT_FILENAME']   ?? 'N/A',
            'php_version'        => PHP_VERSION,
            'php_sapi'           => PHP_SAPI,
            'php_os'             => PHP_OS_FAMILY . ' (' . PHP_OS . ')',
            'php_ini'            => php_ini_loaded_file() ?: 'N/A',
            'php_user'           => CAP::call('gu') ?: 'N/A',
            'uptime'             => $uptime,
            'memory_limit'       => CAP::call('ig', 'memory_limit')        ?: 'N/A',
            'max_exec_time'      => CAP::call('ig', 'max_execution_time') . 's',
            'upload_max'         => CAP::call('ig', 'upload_max_filesize') ?: 'N/A',
            'post_max'           => CAP::call('ig', 'post_max_size')       ?: 'N/A',
            'open_basedir'       => CAP::call('ig', 'open_basedir')        ?: 'None (Unrestricted)',
            'disabled_functions' => CAP::call('ig', 'disable_functions')   ?: 'None (Full Access)',
            'temp_dir'           => sys_get_temp_dir(),
            'timezone'           => date_default_timezone_get(),
        ];
    }

    /**
     * Ambil daftar ekstensi PHP yang terload, diurutkan alphabetically.
     *
     * @return list<string>
     */
    public function getExtensions(): array {
        $exts = get_loaded_extensions();
        natcasesort($exts);
        return array_values($exts);
    }

    /**
     * Ambil cron jobs untuk current user via 'crontab -l'.
     * Return pesan informatif jika tidak tersedia.
     */
    public function getCronJobs(): string {
        if (!cmd_available()) {
            return 'Command execution not available.';
        }

        $cron = trim(run_cmd('crontab -l'));
        if ($cron === '' || str_contains($cron, 'no crontab')) {
            return 'No crontab entries found for current user.';
        }

        return $cron;
    }
}
