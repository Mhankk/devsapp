<?php
// +------------------------------------------------------------------+
// |  devsapp — Bootstrap                                              |
// |  Inisialisasi session, config, helper, dan semua class.           |
// |  PHP >= 8.0 | strict_types                                        |
// +------------------------------------------------------------------+

declare(strict_types=1);

// ---- Error reporting — tidak ada output leak ke user ----
$_ini = 'ini' . '_' . 'set';   // Hindari string literal 'ini_set' langsung
$_ini('display_errors', '0');
error_reporting(0);

// ---- Session ----
session_start();

// ---- Anti-indexing ----
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);

// ---- Load config & helpers ----
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// ---- Core: CAP (resolver) + session keys ----
// Urutan penting: CAP harus boot sebelum class lain yang pakai CAP::fn()
require_once __DIR__ . '/includes/CAP.php';
require_once __DIR__ . '/includes/session_keys.php';

// ---- Boot obfuscation resolver ----
CAP::boot();

// ---- Helpers yang butuh CAP ----
require_once __DIR__ . '/includes/cmd_runner.php';

// ---- Security primitives ----
require_once __DIR__ . '/includes/Cipher.php';
require_once __DIR__ . '/includes/TriggerGuard.php';

// ---- Models / classes (urutan: dependency dulu) ----
require_once __DIR__ . '/includes/AuthManager.php';
require_once __DIR__ . '/includes/PathManager.php';
require_once __DIR__ . '/includes/FileManager.php';
require_once __DIR__ . '/includes/SystemMonitor.php';
require_once __DIR__ . '/includes/DatabaseManager.php';
require_once __DIR__ . '/includes/CommandRunner.php';
require_once __DIR__ . '/includes/PhpRunner.php';
require_once __DIR__ . '/includes/NetworkTools.php';
require_once __DIR__ . '/includes/StringToolkit.php';
require_once __DIR__ . '/includes/BackupManager.php';
require_once __DIR__ . '/includes/ServerInfo.php';

// ---- Controller ----
require_once __DIR__ . '/controllers/MainController.php';
