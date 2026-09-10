<?php
// +------------------------------------------------------------------+
// |  devsapp — index.php                                              |
// |  Entry point aplikasi. Hanya melakukan:                           |
// |  1. Load bootstrap (semua dependensi)                             |
// |  2. Jalankan controller → $viewData                               |
// |  3. Include view layout                                           |
// +------------------------------------------------------------------+

require_once __DIR__ . '/src/php/bootstrap.php';

// Jalankan controller, kumpulkan data untuk view
$app      = new MainController();
$viewData = $app->handleRequest();

// Base URL path untuk assets (CSS/JS) — kompatibel subfolder server
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

// Render view
require __DIR__ . '/src/views/layout.php';
