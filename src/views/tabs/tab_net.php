<?php
// +------------------------------------------------------------------+
// |  devsapp — views/tabs/tab_net.php                                 |
// |  Tab Network: DNS, port check, HTTP header inspector.             |
// |  varnaming.md: field 'host','port','url','ip' → dynamic $_sk      |
// +------------------------------------------------------------------+
?>

<!-- DNS Lookup -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; DNS LOOKUP</h3>
    <?php if ($viewData['guardDenied'] && ($viewData['net']['lastAction'] ?? '') === 'ns'): ?>
    <div class="alert alert-error"><i class="fa-solid fa-ban"></i> Feature tidak tersedia saat ini.</div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="<?= h($_sk['no']) ?>" value="ns">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="net">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <div style="display:flex; gap:8px;">
            <input type="text" name="<?= h($_sk['nh']) ?>" placeholder="example.com" style="flex:1;"
                   value="<?= h($viewData['net']['lastAction'] === 'ns' ? $viewData['net']['lastHost'] : '') ?>"
                   required>
            <button type="submit" style="padding:8px 16px;">
                <i class="fa-solid fa-magnifying-glass"></i> LOOKUP
            </button>
        </div>
    </form>

    <?php if ($viewData['net']['result'] && ($viewData['net']['lastAction'] ?? '') === 'ns'): ?>
    <?php $nr = $viewData['net']['result']; ?>
    <hr style="border-color:var(--line); margin:12px 0;">
    <?php if (!$nr['success']): ?>
        <div class="alert alert-error"><?= h($nr['error']) ?></div>
    <?php else: ?>
        <div style="margin-bottom:8px;">
            <b style="color:var(--good);">IP:</b> <span class="mono"><?= h($nr['ip']) ?></span>
        </div>
        <?php if (!empty($nr['records'])): ?>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>TYPE</th><th>TARGET</th><th>TTL</th></tr></thead>
            <tbody>
                <?php foreach ($nr['records'] as $rec): ?>
                <tr>
                    <td><span class="badge badge-success"><?= h($rec['type']) ?></span></td>
                    <td class="mono"><?= h($rec['target']) ?></td>
                    <td class="mono"><?= (int)$rec['ttl'] ?>s</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Port Checker -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; PORT CHECKER</h3>
    <form method="post">
        <input type="hidden" name="<?= h($_sk['no']) ?>" value="pt">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="net">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="text"   name="<?= h($_sk['nh']) ?>"  placeholder="example.com"       style="flex:2;"
                   value="<?= h($viewData['net']['lastAction'] === 'pt' ? $viewData['net']['lastHost'] : '') ?>"
                   required>
            <input type="number" name="<?= h($_sk['np']) ?>"  placeholder="Port (80, 443...)"  style="flex:1; min-width:120px;"
                   value="<?= h($viewData['net']['lastPort']) ?>" required>
            <input type="number" name="<?= h($_sk['nto']) ?>" placeholder="Timeout (s)"        style="flex:1; min-width:100px;"
                   value="3" min="1" max="30">
            <button type="submit" style="padding:8px 16px;">
                <i class="fa-solid fa-plug"></i> CHECK
            </button>
        </div>
    </form>

    <?php if ($viewData['net']['result'] && ($viewData['net']['lastAction'] ?? '') === 'pt'): ?>
    <?php $portResult = $viewData['net']['result']; ?>
    <hr style="border-color:var(--line); margin:12px 0;">
    <?php if (!$portResult['success']): ?>
        <div class="alert alert-error"><?= h($portResult['error']) ?></div>
    <?php else: ?>
        <div style="display:flex; gap:12px; align-items:center;">
            <span class="badge <?= $portResult['open'] ? 'badge-success' : 'badge-danger' ?>"
                  style="font-size:12px; padding:4px 10px;">
                <i class="fa-solid <?= $portResult['open'] ? 'fa-check' : 'fa-xmark' ?>"></i>
                <?= h($portResult['host']) ?>:<?= (int)$portResult['port'] ?>
                — <?= $portResult['open'] ? 'OPEN' : 'CLOSED' ?>
            </span>
            <span style="color:var(--muted); font-size:11px;">(<?= $portResult['ms'] ?>ms)</span>
        </div>
        <?php if (!$portResult['open'] && isset($portResult['error'])): ?>
            <div style="color:var(--muted); font-size:11px; margin-top:6px;"><?= h($portResult['error']) ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- HTTP Header Inspector -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; HTTP HEADER INSPECTOR</h3>
    <form method="post">
        <input type="hidden" name="<?= h($_sk['no']) ?>" value="hdr">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="net">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <div style="display:flex; gap:8px;">
            <input type="text" name="<?= h($_sk['nu']) ?>" placeholder="https://example.com" style="flex:1;"
                   value="<?= h($viewData['net']['lastUrl']) ?>" required>
            <button type="submit" style="padding:8px 16px;">
                <i class="fa-solid fa-globe"></i> FETCH
            </button>
        </div>
    </form>

    <?php if ($viewData['net']['result'] && ($viewData['net']['lastAction'] ?? '') === 'hdr'): ?>
    <?php $hr = $viewData['net']['result']; ?>
    <hr style="border-color:var(--line); margin:12px 0;">
    <?php if (!$hr['success']): ?>
        <div class="alert alert-error"><?= h($hr['error']) ?></div>
    <?php else: ?>
        <table>
            <thead><tr><th>HEADER</th><th>VALUE</th></tr></thead>
            <tbody>
                <?php foreach ($hr['headers'] as $idx => $val): ?>
                <tr>
                    <td><b><?= is_int($idx) ? 'Status' : h($idx) ?></b></td>
                    <td class="mono" style="word-break:break-all;">
                        <?= h(is_array($val) ? implode(', ', $val) : (string)$val) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php endif; ?>
</div>
