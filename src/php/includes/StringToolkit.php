<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/StringToolkit.php                             |
// |  Transformasi string: encode/decode, hash, JSON, dan utilitas.    |
// +------------------------------------------------------------------+

declare(strict_types=1);

class StringToolkit {

    /**
     * Proses operasi transformasi string via dispatch table closure.
     * Setiap operasi adalah static closure yang menerima string input.
     *
     * @param string $operation Nama operasi (lihat getOperations())
     * @param string $input     Input string yang akan diproses
     * @return array{success:bool, output:string, operation?:string, error?:string}
     */
    public function process(string $operation, string $input): array {
        $ops = [
            // ---- Encoding ----
            'base64_encode' => static fn(string $s) => base64_encode($s),
            'base64_decode' => static fn(string $s) =>
                base64_decode($s) !== false ? base64_decode($s) : '[Invalid Base64]',
            'url_encode'    => static fn(string $s) => urlencode($s),
            'url_decode'    => static fn(string $s) => urldecode($s),
            'html_encode'   => static fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8'),
            'html_decode'   => static fn(string $s) => htmlspecialchars_decode($s, ENT_QUOTES),
            'hex_encode'    => static fn(string $s) => bin2hex($s),
            'hex_decode'    => static fn(string $s) => @hex2bin($s) ?: '[Invalid Hex]',

            // ---- Hashing ----
            'md5'    => static fn(string $s) => md5($s),
            'sha1'   => static fn(string $s) => sha1($s),
            'sha256' => static fn(string $s) => hash('sha256', $s),
            'sha512' => static fn(string $s) => hash('sha512', $s),

            // ---- JSON ----
            'json_prettify' => static function (string $s): string {
                $decoded = json_decode($s);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return '[Invalid JSON] ' . json_last_error_msg();
                }
                return (string)json_encode(
                    $decoded,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
            },
            'json_minify' => static function (string $s): string {
                $decoded = json_decode($s);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return '[Invalid JSON] ' . json_last_error_msg();
                }
                return (string)json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            },

            // ---- String operations ----
            'strlen'    => static fn(string $s) =>
                strlen($s) . ' bytes / ' . (function_exists('mb_strlen') ? mb_strlen($s) : strlen($s)) . ' chars',
            'strtoupper' => static fn(string $s) => strtoupper($s),
            'strtolower' => static fn(string $s) => strtolower($s),
            'reverse'    => static fn(string $s) => strrev($s),
            'rot13'      => static fn(string $s) => str_rot13($s),
            'wordcount'  => static fn(string $s) =>
                str_word_count($s) . ' words / ' . substr_count($s, "\n") . ' lines',
        ];

        if (!isset($ops[$operation])) {
            return [
                'success' => false,
                'error'   => 'Unknown operation: ' . $operation,
                'output'  => '',
            ];
        }

        try {
            $result = $ops[$operation]($input);
            return ['success' => true, 'output' => (string)$result, 'operation' => $operation];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'output' => ''];
        }
    }

    /**
     * Daftar semua operasi yang tersedia, dikelompokkan per kategori.
     *
     * @return array<string, list<string>>
     */
    public function getOperations(): array {
        return [
            'Encoding' => ['base64_encode', 'base64_decode', 'url_encode', 'url_decode', 'html_encode', 'html_decode', 'hex_encode', 'hex_decode'],
            'Hashing'  => ['md5', 'sha1', 'sha256', 'sha512'],
            'JSON'     => ['json_prettify', 'json_minify'],
            'String'   => ['strlen', 'strtoupper', 'strtolower', 'reverse', 'rot13', 'wordcount'],
        ];
    }
}
