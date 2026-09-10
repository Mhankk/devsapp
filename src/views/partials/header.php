<?php // views/partials/header.php — Top bar: brand, sys info, logout ?>
<header class="top-header">
    <div class="brand">
        <div class="logo"><i class="fa-solid fa-code"></i></div>
        <div>
            <div class="title">
                <?= h($viewData['config']['appName']) ?>
                <?= h($viewData['config']['version']) ?>
            </div>
            <div style="color:var(--muted); font-size:11px;">
                [HOST: <?= h($viewData['sysInfo']['hostname']) ?>]
                [PHP: <?= h($viewData['sysInfo']['phpVer']) ?>]
                [OS: <?= h($viewData['sysInfo']['os']) ?>]
            </div>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:10px;">
        <span class="badge badge-success"><i class="fa-solid fa-signal"></i> ONLINE</span>
        <a href="?bye=1" class="btn-danger btn-sm" style="padding:5px 10px; border:1px solid var(--bad);">
            <i class="fa-solid fa-power-off"></i> LOGOUT
        </a>
    </div>
</header>
