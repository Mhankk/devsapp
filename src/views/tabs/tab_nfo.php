<?php
// +------------------------------------------------------------------+
// |  devsapp — views/tabs/tab_nfo.php                                 |
// |  Tab Server Info: server details, PHP extensions, cron jobs.      |
// +------------------------------------------------------------------+
?>

<!-- ============================================================ -->
<!-- TAB 7: SERVER INFO                                           -->
<!-- ============================================================ -->

<!-- Server Information Table -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; SERVER INFORMATION</h3>
    <table>
        <tbody>
            <?php foreach ($viewData['nfo']['serverInfo'] as $key => $val): ?>
            <tr>
                <td style="width:200px;">
                    <b><?= h(strtoupper(str_replace('_', ' ', $key))) ?></b>
                </td>
                <td class="mono" style="word-break:break-all;"><?= h($val) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- PHP Extensions -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <h3 style="margin:0; font-size:14px; color:var(--accent); text-transform:uppercase;">
            &gt; PHP EXTENSIONS (<?= count($viewData['nfo']['extensions']) ?>)
        </h3>
        <a href="?rpc=sysreport" target="_blank" class="btn-sm btn-good" style="padding:4px 10px;">
            <i class="fa-solid fa-circle-info"></i> FULL SYSREPORT
        </a>
    </div>
    <div class="ext-grid">
        <?php foreach ($viewData['nfo']['extensions'] as $ext): ?>
            <span><?= h($ext) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<!-- Cron Jobs -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">
        &gt; CRON JOBS (Current User)
    </h3>
    <pre class="mono" style="background:#000; color:var(--text); padding:12px; border:1px solid var(--line); overflow-x:auto; max-height:300px; font-size:12px; line-height:1.4; margin:0;"><?= h($viewData['nfo']['cronJobs']) ?></pre>
</div>
