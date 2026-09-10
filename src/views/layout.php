<?php
// +------------------------------------------------------------------+
// |  devsapp — views/layout.php                                       |
// |  HTML layout utama. Include partial views per-tab.                |
// |  Menerima: $viewData (dari MainController), $_sk (session keys)   |
// +------------------------------------------------------------------+
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<title><?= h($viewData['config']['appName']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="src/assets/css/app.css">
</head>
<body>
<div class="wrap">

    <?php require __DIR__ . '/partials/header.php'; ?>
    <?php require __DIR__ . '/partials/tabs.php'; ?>
    <?php require __DIR__ . '/partials/notice.php'; ?>

    <?php
    // ---- Route ke partial view yang sesuai dengan tab aktif ----
    $tabViewMap = [
        'fm'  => __DIR__ . '/tabs/tab_fm.php',
        'mon' => __DIR__ . '/tabs/tab_mon.php',
        'db'  => __DIR__ . '/tabs/tab_db.php',
        'dev' => __DIR__ . '/tabs/tab_dev.php',
        'net' => __DIR__ . '/tabs/tab_net.php',
        'kit' => __DIR__ . '/tabs/tab_kit.php',
        'nfo' => __DIR__ . '/tabs/tab_nfo.php',
    ];

    $activeTab = $viewData['m'];
    if (isset($tabViewMap[$activeTab])) {
        require $tabViewMap[$activeTab];
    }
    ?>

    <?php require __DIR__ . '/partials/footer.php'; ?>

</div>

<!-- Config data dari PHP — dikirim sekali saja ke JS via JSON -->
<script>
const APP_CONFIG = <?= json_encode([
    'refreshMs'   => $viewData['config']['refreshMs'],
    'currentPath' => $viewData['currentPath'],
    'm'           => $viewData['m'],
    '_sk'         => $_SESSION['_sk'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="src/assets/js/app.js"></script>
</body>
</html>
