<?php // views/partials/notice.php — Alert/notice banner (success atau error) ?>
<?php if ($viewData['notice']): ?>
<div class="alert alert-<?= $viewData['notice']['type'] === 'success' ? 'success' : 'error' ?>">
    <i class="fa-solid <?= $viewData['notice']['type'] === 'success' ? 'fa-check' : 'fa-triangle-exclamation' ?>"></i>
    <?= h($viewData['notice']['msg']) ?>
</div>
<?php endif; ?>
