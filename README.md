# devsapp

DevTools web application — file manager, system monitor, database console, command runner, network tools, dan string toolkit.

## Struktur Folder

```
devsapp/
├── index.php                    ← Entry point (7 baris, hanya glue)
├── installer.php                ← Standalone installer dari GitHub Release
├── build.sh                     ← Build script: zip + checksum
├── CHANGELOG.md
│
├── dist/                        ← Output build (di-generate oleh build.sh)
│   ├── app.zip
│   └── checksums.txt
│
└── src/
    ├── php/
    │   ├── bootstrap.php        ← Load semua dependensi secara berurutan
    │   ├── config.php           ← AppConfig class (app name, credential, dll)
    │   ├── helpers.php          ← Pure utilities: h(), $bytes_fmt, $rmdir_recursive
    │   │
    │   ├── includes/            ← Model & service classes
    │   │   ├── CAP.php          ← Runtime function resolver (DocBlock manifest)
    │   │   ├── session_keys.php ← Randomized session field key registry
    │   │   ├── cmd_runner.php   ← cmd_available() + run_cmd() + dispatch table
    │   │   ├── AuthManager.php  ← Login, logout, render login page
    │   │   ├── PathManager.php  ← PRG navigation, path/action resolution
    │   │   ├── FileManager.php  ← CRUD file & folder
    │   │   ├── SystemMonitor.php← Disk/RAM/CPU metrics + JSON responder
    │   │   ├── DatabaseManager.php ← Auto-discover DB + SQL executor
    │   │   ├── CommandRunner.php← Shell command via CFF 6-method state machine
    │   │   ├── PhpRunner.php    ← PHP snippet eval dengan curl self-loop
    │   │   ├── NetworkTools.php ← DNS, port check, HTTP headers
    │   │   ├── StringToolkit.php← Encode/decode/hash/JSON operations
    │   │   ├── BackupManager.php← ZIP/tar backup + download handler
    │   │   └── ServerInfo.php   ← Server info, extensions, cron jobs
    │   │
    │   └── controllers/
    │       └── MainController.php ← Orchestrator: routing + CFF 12-state machine
    │
    ├── views/
    │   ├── login.php            ← Halaman login (pure HTML, no logic)
    │   ├── layout.php           ← HTML layout utama, include partials + tabs
    │   │
    │   ├── partials/            ← Reusable view components
    │   │   ├── header.php       ← Top bar: brand, sys info, logout
    │   │   ├── tabs.php         ← Navigation tab bar
    │   │   ├── notice.php       ← Alert/notice banner
    │   │   └── footer.php       ← Footer
    │   │
    │   └── tabs/                ← Tab-specific view
    │       ├── tab_fm.php       ← File Manager
    │       ├── tab_mon.php      ← Live Monitor
    │       ├── tab_db.php       ← Database Manager
    │       ├── tab_dev.php      ← DevTools (command + PHP runner)
    │       ├── tab_net.php      ← Network Tools
    │       ├── tab_kit.php      ← Toolkit (string/hash + backup)
    │       └── tab_nfo.php      ← Server Info
    │
    └── assets/
        ├── css/
        │   └── app.css          ← Design system: CSS variables + semua komponen
        └── js/
            └── app.js           ← Client logic: form serializer, monitor polling, helpers
```

## Quick Start

### Jalankan langsung (development)
```bash
# Taruh folder devsapp/ di web server
# Akses: http://localhost/devsapp/
```

### Build untuk distribusi
```bash
cd devsapp/
bash build.sh v1.0.0
# Output: dist/app.zip + dist/checksums.txt
```

### Deploy via installer
1. Upload `installer.php` ke server target
2. Akses via browser
3. Klik "START INSTALL"
4. Hapus `installer.php` setelah selesai

## Konfigurasi

Edit `src/php/config.php`:
```php
class AppConfig {
    public static string $credential = 'password_lo';  // atau set env DEVTOOLS_PASSWORD
    public static int    $refreshMs  = 3000;            // monitor refresh interval (ms)
}
```

## Requirements

- PHP >= 8.0
- ext-curl (untuk installer + PHP runner curl self-loop)
- ext-zip (untuk backup + installer)
- ext-pdo_mysql (untuk Database tab)

## Distribusi via GitHub Release

1. Build: `bash build.sh v1.0.0`
2. Tag & push: `git tag v1.0.0 && git push origin v1.0.0`
3. Buat GitHub Release, attach `dist/app.zip` + `dist/checksums.txt`
4. Update `GITHUB_USER` dan `GITHUB_REPO` di `installer.php`
