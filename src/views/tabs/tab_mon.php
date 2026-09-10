<?php
// +------------------------------------------------------------------+
// |  devsapp — views/tabs/tab_mon.php                                 |
// |  Tab Monitor: live metric cards + host info table.                |
// |  Data di-update via AJAX polling (APP_CONFIG.refreshMs).          |
// +------------------------------------------------------------------+
?>

<!-- ============================================================ -->
<!-- TAB 2: LIVE MONITOR                                          -->
<!-- ============================================================ -->

<!-- Metric Cards -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="margin:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; SYSTEM METRICS MONITOR</h3>
        <span id="mon-status" class="badge badge-success">
            POLLING (<?= (int)$viewData['config']['refreshMs'] / 1000 ?>s)
        </span>
    </div>

    <div class="grid">
        <!-- Disk -->
        <div class="card" style="margin:0;">
            <div style="color:var(--muted); font-size:11px; text-transform:uppercase; font-weight:bold;">[DISK USAGE]</div>
            <div class="metric-val" id="mon-disk-used">--</div>
            <div class="bar"><div class="fill" id="mon-disk-bar"></div></div>
            <div style="font-size:11px; color:var(--muted);" id="mon-disk-sub">-- free / -- total</div>
        </div>

        <!-- RAM -->
        <div class="card" style="margin:0;">
            <div style="color:var(--muted); font-size:11px; text-transform:uppercase; font-weight:bold;">[RAM USAGE]</div>
            <div class="metric-val" id="mon-ram-used">--</div>
            <div class="bar"><div class="fill" id="mon-ram-bar"></div></div>
            <div style="font-size:11px; color:var(--muted);" id="mon-ram-sub">-- free / -- total</div>
        </div>

        <!-- CPU -->
        <div class="card" style="margin:0;">
            <div style="color:var(--muted); font-size:11px; text-transform:uppercase; font-weight:bold;">[CPU LOAD]</div>
            <div class="metric-val" id="mon-cpu-load">--</div>
            <div class="bar"><div class="fill" id="mon-cpu-bar"></div></div>
            <div style="font-size:11px; color:var(--muted);" id="mon-cpu-sub">1 min avg load</div>
        </div>

        <!-- Processes -->
        <div class="card" style="margin:0;">
            <div style="color:var(--muted); font-size:11px; text-transform:uppercase; font-weight:bold;">[PROCESSES]</div>
            <div class="metric-val" id="mon-proc-count">--</div>
            <div style="font-size:11px; color:var(--muted);" id="mon-php-mem">PHP Alloc: --</div>
        </div>
    </div>
</div>

<!-- Realtime Host Info Table -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; HOST REALTIME INFORMATION</h3>
    <table>
        <tbody>
            <tr><td><b>HOSTNAME</b></td>   <td id="mon-host"   class="mono">--</td></tr>
            <tr><td><b>PHP VERSION</b></td><td id="mon-phpver" class="mono">--</td></tr>
            <tr><td><b>SYSTEM OS</b></td>  <td id="mon-os"     class="mono">--</td></tr>
            <tr><td><b>LAST SYNC</b></td>  <td id="mon-time"   class="mono">--</td></tr>
        </tbody>
    </table>
</div>
