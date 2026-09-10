<?php
// views/partials/tabs.php — Navigation tab bar
// varnaming.md: field 'nv' (navigate), 'm'/'md' (mode), 'loc'/'lc' (location) → $_sk
$tabDaftar = [
    'fm'  => ['fa-folder',        'FILES'],
    'mon' => ['fa-chart-line',    'MONITOR'],
    'db'  => ['fa-database',      'DATABASE'],
    'dev' => ['fa-code',          'DEVTOOLS'],
    'net' => ['fa-network-wired', 'NETWORK'],
    'kit' => ['fa-wrench',        'TOOLKIT'],
    'nfo' => ['fa-info-circle',   'INFO'],
];
?>
<nav class="tabs">
    <?php foreach ($tabDaftar as $tabId => [$ikon, $label]): ?>
    <form method="post" style="display:inline;">
        <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="<?= $tabId ?>">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <button type="submit" class="<?= $viewData['m'] === $tabId ? 'active' : '' ?>">
            <i class="fa-solid <?= $ikon ?>"></i> <?= $label ?>
        </button>
    </form>
    <?php endforeach; ?>
</nav>
