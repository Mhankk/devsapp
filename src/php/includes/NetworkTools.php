<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/NetworkTools.php                              |
// |  DNS lookup, port check, dan HTTP header inspector.               |
// +------------------------------------------------------------------+

declare(strict_types=1);

class NetworkTools {

    // ---- CFF state constants untuk dnsLookup ----
    private const S_D0 = 0x100;   // Resolve IP via gethostbyname
    private const S_D1 = 0x200;   // Ambil semua DNS records via dns_get_record
    private const S_DF = 0xFFF;   // Terminal

    /**
     * DNS Lookup: resolve hostname ke IP + semua DNS records yang tersedia.
     *
     * @return array{success:bool, host?:string, ip?:string, records?:array, error?:string}
     */
    public function dnsLookup(string $hostname): array {
        $cleanHost = preg_replace('#^https?://#i', '', trim($hostname));
        $cleanHost = explode('/', $cleanHost)[0];
        $cleanHost = explode(':', $cleanHost)[0];

        if ($cleanHost === '') {
            return ['success' => false, 'error' => 'Hostname required.'];
        }

        $result = ['success' => true, 'host' => $cleanHost, 'records' => []];
        $state  = self::S_D0;

        while ($state !== self::S_DF) {
            switch ($state) {

                // State 0: Resolve IP utama
                case self::S_D0:
                    $ip = @gethostbyname($cleanHost);
                    $result['ip'] = ($ip !== $cleanHost) ? $ip : 'N/A';
                    $state = self::S_D1;
                    break;

                // State 1: Ambil semua DNS records
                case self::S_D1:
                    $dnsGetRecord = CAP::fn('dg');
                    if (CAP::ok('dg')) {
                        $records = @$dnsGetRecord($cleanHost, DNS_ALL);
                        if ($records) {
                            // Format records jadi array yang bersih
                            $result['records'] = array_map(static fn(array $r) => [
                                'type'   => $r['type']   ?? 'N/A',
                                'target' => $r['ip'] ?? $r['target'] ?? $r['txt'] ?? 'N/A',
                                'ttl'    => $r['ttl']    ?? 0,
                            ], $records);
                        }
                    }
                    $state = self::S_DF;
                    break;
            }
        }

        return $result;
    }

    /**
     * Port Check: tes apakah port tertentu terbuka di host yang diberikan.
     *
     * @param string $host    Hostname atau IP
     * @param int    $port    Nomor port
     * @param int    $timeout Timeout dalam detik (default: 3)
     * @return array{success:bool, host?:string, port?:int, open?:bool, ms?:float, error?:string}
     */
    public function portCheck(string $host, int $port, int $timeout = 3): array {
        // Sanitasi $host: hapus http://, https://, dan path
        $cleanHost = preg_replace('#^https?://#i', '', trim($host));
        $cleanHost = explode('/', $cleanHost)[0];
        $cleanHost = explode(':', $cleanHost)[0];

        if ($cleanHost === '' || $port <= 0) {
            return ['success' => false, 'error' => 'Invalid host or port.'];
        }

        $fsockopen = CAP::fn('fs');
        $start     = microtime(true);
        $conn      = @$fsockopen($cleanHost, $port, $errno, $errstr, $timeout);
        $elapsed   = round((microtime(true) - $start) * 1000, 1);

        if ($conn) {
            fclose($conn);
            return [
                'success' => true,
                'host'    => $host,
                'port'    => $port,
                'open'    => true,
                'ms'      => $elapsed,
            ];
        }

        return [
            'success' => true,
            'host'    => $host,
            'port'    => $port,
            'open'    => false,
            'error'   => "$errstr ($errno)",
            'ms'      => $elapsed,
        ];
    }

    /**
     * HTTP Header Inspector: ambil response headers via HEAD request.
     *
     * @return array{success:bool, url?:string, headers?:array, error?:string}
     */
    public function httpHeaders(string $url): array {
        if (trim($url) === '') {
            return ['success' => false, 'error' => 'URL required.'];
        }

        // Pastikan ada protokol
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'http://' . $url;
        }

        $ctx = stream_context_create([
            'http' => [
                'method'          => 'HEAD',
                'timeout'         => 5,
                'follow_location' => 0,
                'ignore_errors'   => true,
            ],
        ]);

        $headers = @get_headers($url, true, $ctx);
        if ($headers === false) {
            return ['success' => false, 'error' => 'Failed to connect to ' . $url];
        }

        return ['success' => true, 'url' => $url, 'headers' => $headers];
    }
}
