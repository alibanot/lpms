<?php
$items = [
    ['index.php', 'bi-speedometer2', 'Dashboard'],
    ['sales.php', 'bi-receipt', 'Daily Sales'],
    ['expenses.php', 'bi-wallet2', 'Expenses'],
    ['orders.php', 'bi-bag-check', 'Tempahan'],
    ['events.php', 'bi-calendar-event', 'Catering'],
    ['reports.php', 'bi-bar-chart-line', 'Reports'],
    ['settings.php', 'bi-gear', 'Settings'],
];
?>
<aside class="sidebar d-none d-lg-flex">
    <div class="brand">
        <span class="brand-mark">LP</span>
        <span><?= h(APP_NAME) ?></span>
    </div>
    <nav class="nav flex-column gap-1">
        <?php foreach ($items as [$href, $icon, $label]): ?>
            <a class="nav-link <?= active_class($href) ?>" href="<?= h($href) ?>">
                <i class="bi <?= h($icon) ?>"></i><span><?= h($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><?= h(APP_NAME) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column gap-1 mobile-nav">
            <?php foreach ($items as [$href, $icon, $label]): ?>
                <a class="nav-link <?= active_class($href) ?>" href="<?= h($href) ?>">
                    <i class="bi <?= h($icon) ?>"></i><span><?= h($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
