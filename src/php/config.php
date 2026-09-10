<?php
// +------------------------------------------------------------------+
// |  devsapp — config.php                                             |
// |  Konfigurasi terpusat aplikasi.                                   |
// |                                                                    |
// |  $siteKey di-inject otomatis oleh installer.php saat install.     |
// |  Jika kosong, Cipher akan pakai fingerprint server saja.          |
// +------------------------------------------------------------------+

declare(strict_types=1);

class AppConfig {

    /** Nama aplikasi — ditampilkan di header & title */
    public static string $appName = 'devsapp';

    /** Versi */
    public static string $version = 'v1.0';

    /**
     * Password akses aplikasi.
     * Kosongkan untuk nonaktifkan auth (tidak disarankan).
     * Atau set environment variable DEVTOOLS_PASSWORD untuk override.
     */
    public static string $credential = '';

    /**
     * Site key unik per-target — di-inject oleh installer.php.
     * Dipakai oleh Cipher::encrypt/decrypt sebagai key material tambahan.
     * Kosong = hanya pakai fingerprint server (masih aman).
     */
    public static string $siteKey = '';

    /**
     * Interval refresh live monitor dalam milidetik.
     * Default: 3 detik. Naikkan ke 5000+ di server yang sibuk.
     */
    public static int $refreshMs = 3000;
}
