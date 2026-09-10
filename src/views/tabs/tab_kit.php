<?php
// +------------------------------------------------------------------+
// |  devsapp — views/tabs/tab_kit.php                                 |
// |  Tab Toolkit: string transformer & backup manager.                |
// |  varnaming.md: field 'encode','decode','key','value' → $_sk       |
// +------------------------------------------------------------------+
?>

<!-- String Transformer -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; STRING TRANSFORMER &amp; HASH TOOLS</h3>
    <form method="post">
        <input type="hidden" name="<?= h($_sk['ko']) ?>" value="transform">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="kit">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">

        <!-- Operation selector grouped -->
        <div style="margin-bottom:10px;">
            <select name="<?= h($_sk['kt']) ?>" style="width:100%; padding:8px;">
                <?php foreach ($viewData['kit']['operations'] as $group => $ops): ?>
                    <optgroup label="<?= h($group) ?>">
                        <?php foreach ($ops as $op): ?>
                            <option value="<?= h($op) ?>"
                                    <?= ($viewData['kit']['lastOp'] === $op) ? 'selected' : '' ?>>
                                <?= h($op) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>

        <textarea name="<?= h($_sk['kv']) ?>"
                  placeholder="Enter text to transform..."
                  style="height:120px;"><?= h($viewData['kit']['lastInput']) ?></textarea>
        <br><br>
        <button type="submit" class="btn-good" style="padding:8px 16px;">
            <i class="fa-solid fa-wand-magic-sparkles"></i> TRANSFORM
        </button>
    </form>

    <!-- Transform Result -->
    <?php if ($viewData['kit']['result']): ?>
    <?php $kr = $viewData['kit']['result']; ?>
    <hr style="border-color:var(--line); margin:15px 0;">
    <?php if (!$kr['success']): ?>
        <div class="alert alert-error"><?= h($kr['error']) ?></div>
    <?php else: ?>
        <div style="display:flex; gap:8px; align-items:center; margin-bottom:6px;">
            <h4 style="margin:0; color:var(--accent); text-transform:uppercase;">RESULT:</h4>
            <span class="badge badge-success"><?= h($kr['operation']) ?></span>
        </div>
        <pre class="mono" style="background:#000; color:var(--good); padding:12px; border:1px solid var(--good); overflow-x:auto; max-height:300px; font-size:12px; line-height:1.4; margin:0; word-wrap:break-word; white-space:pre-wrap;"><?= h($kr['output']) ?></pre>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Backup Manager -->
<div class="card">
    <h3 style="margin-top:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; BACKUP MANAGER</h3>
    <div style="margin-bottom:10px;">
        <span style="color:var(--muted); font-size:11px;">Current path: </span>
        <span class="mono" style="color:var(--good);"><?= h($viewData['currentPath']) ?></span>
    </div>
    <form method="post">
        <input type="hidden" name="<?= h($_sk['ko']) ?>" value="backup">
        <input type="hidden" name="<?= h($_sk['md']) ?>" value="kit">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <button type="submit" style="padding:8px 16px;"
                onclick="return confirm('Create backup archive of current directory?')">
            <i class="fa-solid fa-box-archive"></i> CREATE BACKUP
        </button>
        <span style="font-size:11px; color:var(--muted); margin-left:8px;">Uses ZipArchive or tar (fallback)</span>
    </form>

    <!-- Backup Result -->
    <?php if ($viewData['kit']['backupResult']): ?>
    <?php $br = $viewData['kit']['backupResult']; ?>
    <hr style="border-color:var(--line); margin:12px 0;">
    <?php if (!$br['success']): ?>
        <div class="alert alert-error"><?= h($br['error']) ?></div>
    <?php else: ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check"></i>
            Archive created: <b><?= h($br['filename']) ?></b> (via <?= h($br['method']) ?>)
            &nbsp;—&nbsp;
            <a href="?bk=<?= urlencode($br['file']) ?>" style="color:var(--good); font-weight:bold;">
                <i class="fa-solid fa-download"></i> DOWNLOAD
            </a>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
