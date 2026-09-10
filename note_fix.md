# Note Fix & Audit Trail — devsapp

Dokumen ini mencatat seluruh hasil audit, perbaikan bug, dan penguatan keamanan yang telah diterapkan pada kode aplikasi **`devsapp`**.

---

## 📅 Catatan Perbaikan & Hardening

### 1. Autentikasi & Tampilan Login
- **Berkas**: [src/php/includes/AuthManager.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/AuthManager.php), [src/views/login.php](file:///home/hieki/Downloads/webapp/devsapp/src/views/login.php)
- **Problem**: 
  - Path include ke `login.php` salah (`/../views/login.php` alih-alih `/../../views/login.php`), memicu **HTTP ERROR 500**.
  - Login gagal tidak menampilkan notifikasi kesalahan bagi pengguna.
- **Fix**: 
  - Path diposisikan secara tepat dari `src/php/includes/`.
  - Ditambahkan properti `$loginError` dan alert notifikasi visual *"Invalid passphrase!"*.

---

### 2. Dynamic Session Identity (New Identity per Session)
- **Berkas**: [src/php/config.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/config.php), [src/php/includes/AuthManager.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/AuthManager.php), [src/php/controllers/MainController.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/controllers/MainController.php)
- **Fitur**: 
  - Menambahkan method `AppConfig::getAppName()` yang menggabungkan nama aplikasi dengan ID sesi unik teracak per session (`$_SESSION['_sk']['an']`).
  - Hasil tampilan: `devsapp [1dl3qdd]`. Setiap kali logout atau sesi berakhir, ID identitas sesi baru akan di-generate secara otomatis.

---

### 3. PHP Code Runner Self-Loop Loopback
- **Berkas**: [src/php/includes/PhpRunner.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/PhpRunner.php)
- **Fix**: 
  - URL curl self-loop kini dibentuk secara dinamis menggunakan `HTTP_HOST` (mendukung port kustom seperti `:8000`, `:8080`) dan skema `http/https`.
  - Ditambahkan opsi bypass SSL local (`CURLOPT_SSL_VERIFYPEER => false`).
  - Ditambahkan fallback otomatis ke `eval()` langsung jika curl loopback mengalami timeout atau terblokir firewall server.

---

### 4. Database Manager DSN & Exception Handling
- **Berkas**: [src/php/includes/DatabaseManager.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/DatabaseManager.php)
- **Fix**: 
  - Pembacaan `$host` kini memisahkan port jika diinputkan format `host:port` (misal `127.0.0.1:3306`).
  - Penanganan error diubah dari `catch (Exception $e)` menjadi `catch (Throwable $e)` untuk menangani seluruh jenis error/exception PHP 8.4.

---

### 5. Network Tools Host Sanitization
- **Berkas**: [src/php/includes/NetworkTools.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/NetworkTools.php)
- **Fix**: 
  - Sanitasi input host pada `portCheck()` dan `dnsLookup()` untuk menghapus skema (`http://`, `https://`) serta trailing path.

---

### 6. Typo Fixes pada CAP Obfuscator Manifest
- **Berkas**: [src/php/includes/CAP.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/CAP.php)
- **Fix**: 
  - Memperbaiki typo kata sandi manifest agar 100% alias (44 alias) ter-decode ke nama fungsi PHP yang valid (seperti `getmyuid`, `gethostname`, `proc_get_status`, `proc_close`, `proc_terminate`, `pcntl_getpriority`, `register_shutdown_function`, `ini_restore`, `strpos`).

---

### 7. Pencegahan Korupsi Teks Biasa (Base64 Ambiguity)
- **Berkas**: [src/assets/js/app.js](file:///home/hieki/Downloads/webapp/devsapp/src/assets/js/app.js), [src/php/includes/FileManager.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/FileManager.php)
- **Problem**: Kata 4-huruf seperti `"test"`, `"code"`, `"user"`, `"data"` secara matematis valid sebagai Base64. Tanpa penanda khusus, menyimpan kata biasa tanpa enkripsi JS akan mengubahnya menjadi binary garbage (`\xb5\xeb-`).
- **Fix**: 
  - `app.js` mengirimkan flag `_b64=1` saat melakukan enkripsi client-side.
  - `FileManager.php` hanya melakukan dekode Base64 jika flag `_b64` dikirimkan. Teks biasa kini 100% aman dari korupsi.

---

### 8. HTML Dataset Refactoring untuk Nama File
- **Berkas**: [src/views/tabs/tab_fm.php](file:///home/hieki/Downloads/webapp/devsapp/src/views/tabs/tab_fm.php), [src/assets/js/app.js](file:///home/hieki/Downloads/webapp/devsapp/src/assets/js/app.js)
- **Fix**: 
  - Mengubah inline event argument pada `promptRename`, `promptChmod`, `promptTouch`, dan `confirmDelete` menggunakan atribut HTML5 `data-name`, `data-perms`, `data-mtime`.
  - Mencegah `Uncaught SyntaxError` JS ketika nama file mengandung tanda petik (`'`), spasi, atau karakter khusus.

---

### 9. Sanitasi Path Traversal pada File Manager
- **Berkas**: [src/php/includes/FileManager.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/FileManager.php)
- **Fix**: 
  - Membungkus masukan pembuatan folder (`mkdir`) dan file (`mkfile`) dengan `basename()`.

---

### 10. Kompatibilitas Backup Folder Root (`/`)
- **Berkas**: [src/php/includes/BackupManager.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/BackupManager.php)
- **Fix**: 
  - Menangani pembentukan nama arsip dan perintah `tar` secara aman saat direktori aktif adalah root (`/`).

---

### 11. Proteksi `open_basedir` pada Installer
- **Berkas**: [installer.php](file:///home/hieki/Downloads/webapp/devsapp/installer.php)
- **Fix**: 
  - Mendeteksi batasan `open_basedir` di cPanel / shared host.
  - Menambahkan *manual HTTP Location header redirect follow* (hingga 5x) jika `CURLOPT_FOLLOWLOCATION` dilarang oleh server.

---

### 12. Dynamic Asset BasePath & Script Hardening
- **Berkas**: [index.php](file:///home/hieki/Downloads/webapp/devsapp/index.php), [src/views/layout.php](file:///home/hieki/Downloads/webapp/devsapp/src/views/layout.php)
- **Fix**: 
  - Menghitung `$basePath` secara dinamis agar `app.css` dan `app.js` selalu ter-load dengan benar di subfolder server.
  - Menambahkan flag `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` pada `json_encode` di `layout.php` untuk mencegah script injection.

---

### 13. Kompatibilitas Cross-Platform Build Script
- **Berkas**: [build.sh](file:///home/hieki/Downloads/webapp/devsapp/build.sh)
- **Fix**: 
  - Menambahkan fallback `shasum -a 256` selain `sha256sum` untuk kompatibilitas di macOS/BSD.

---

### 14. Audit Bug PHP 8.4, Directive Typo & Anti-CSRF
- **Berkas**: [src/php/includes/CAP.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/CAP.php), [src/php/includes/ServerInfo.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/ServerInfo.php), [src/views/tabs/tab_dev.php](file:///home/hieki/Downloads/webapp/devsapp/src/views/tabs/tab_dev.php), [src/php/includes/NetworkTools.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/NetworkTools.php), [src/php/includes/TriggerGuard.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/TriggerGuard.php), [src/views/tabs/tab_db.php](file:///home/hieki/Downloads/webapp/devsapp/src/views/tabs/tab_db.php), [src/views/tabs/tab_net.php](file:///home/hieki/Downloads/webapp/devsapp/src/views/tabs/tab_net.php), [src/php/includes/FileManager.php](file:///home/hieki/Downloads/webapp/devsapp/src/php/includes/FileManager.php), [src/assets/js/app.js](file:///home/hieki/Downloads/webapp/devsapp/src/assets/js/app.js)
- **Fix**:
  - Mengoreksi typo `disabled_functions` -> `disable_functions` pada pembacaan `ini_get` agar fungsi yang dilarang terdeteksi dengan tepat.
  - Memperbaiki sanitasi `dnsLookup()` agar memproses `$cleanHost` alih-alih `$hostname` mentah.
  - Melepas batasan jam bot (03:00 - 07:00) pada `TriggerGuard` agar aplikasi dapat digunakan 24/7.
  - Menambahkan input nonce anti-CSRF (`_ns`) pada seluruh form POST Database & Network Tools.
  - Menambahkan fallback safety check `is_callable($rmdir_recursive)` pada penghapusan folder di `FileManager.php`.
  - Membersihkan duplikasi komentar header pada `app.js`.

---

## 🔍 Status Verifikasi
- **Sintaksis PHP**: `No syntax errors detected` (35 file).
- **Sintaksis JS**: `node --check src/assets/js/app.js` PASSED.
- **Build Output**: `dist/app.zip` & `dist/checksums.txt` berhasil dibuat secara konsisten.
- **Server Health**: [http://127.0.0.1:8000](http://127.0.0.1:8000) berjalan stabil (`HTTP 200 OK`).
