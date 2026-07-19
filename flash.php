<?php $flash = getFlash(); if ($flash): ?>
<div class="alert <?= e($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '&#10003;' : ($flash['type'] === 'danger' ? '&#9888;' : '&#8505;') ?>
    <span><?= e($flash['message']) ?></span>
</div>
<?php endif; ?>
