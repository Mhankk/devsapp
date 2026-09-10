# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning mengikuti [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

---

## [v1.0.0] — 2024-12-01

### Added
- Initial release dari refactor AIO file UltraFMD-dev.php
- Struktur folder terpisah: `php/`, `views/`, `assets/`
- **AuthManager** — session auth dengan CFF state machine
- **PathManager** — navigasi PRG (Post-Redirect-Get)
- **FileManager** — CRUD: mkdir, mkfile, upload, rename, chmod, touch, rm, edit
- **SystemMonitor** — metrik disk/RAM/CPU/proses via JSON polling endpoint
- **DatabaseManager** — auto-discovery credentials + SQL console via PDO
- **CommandRunner** — adaptive execution (6 metode: shell_exec, exec, system, passthru, proc_open, popen)
- **PhpRunner** — eval PHP snippet dengan curl self-loop sandbox
- **NetworkTools** — DNS lookup, port check, HTTP header inspector
- **StringToolkit** — encode/decode/hash/JSON/string transformations
- **BackupManager** — ZIP dan tar backup dengan auto-cleanup
- **ServerInfo** — server info, PHP extensions, cron jobs
- **MainController** — orchestrator dengan CFF 12-state machine
- CSS design system dengan CSS variables (design tokens)
- Modular JavaScript dengan per-module comments
- Build script (`build.sh`) dengan SHA256 checksum generation
- Installer (`installer.php`) dengan GitHub API + checksum verification

### Architecture
- Semua PHP logic terpisah dari HTML/template
- Views dipecah jadi partials dan tab-views
- Semua CSS di `assets/css/app.css` (tidak ada inline style yang hardcode warna)
- Semua JS di `assets/js/app.js` (tidak ada inline `<script>` selain config injection)
- Entry point `index.php` hanya 7 baris
