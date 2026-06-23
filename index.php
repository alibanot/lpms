<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$period = ($_GET['period'] ?? 'today') === 'month' ? 'month' : 'today';
$periodStart = $period === 'month' ? $monthStart : $today;
$periodEnd = $period === 'month' ? $monthEnd : $today;
$periodLabel = $period === 'month' ? 'This Month' : 'Today';
$trendType = ($_GET['trend'] ?? 'gerai') === 'all' ? 'all' : 'gerai';

$stmt = db()->prepare("
    SELECT COALESCE(SUM(total),0) total, COALESCE(SUM(qty),0) qty
    FROM (
        SELECT total, qty FROM sales WHERE sale_type = 'Gerai' AND sale_date BETWEEN ? AND ?
        UNION ALL
        SELECT total, qty FROM orders WHERE status = 'Completed' AND pickup_date BETWEEN ? AND ?
        UNION ALL
        SELECT deposit_paid AS total, 0 AS qty FROM events WHERE deposit_paid > 0 AND deposit_date BETWEEN ? AND ?
        UNION ALL
        SELECT balance_paid AS total, 0 AS qty FROM events WHERE balance_paid > 0 AND balance_paid_date BETWEEN ? AND ?
    ) dashboard_sales
");
$stmt->execute([$periodStart, $periodEnd, $periodStart, $periodEnd, $periodStart, $periodEnd, $periodStart, $periodEnd]);
$periodSales = $stmt->fetch();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE expense_date BETWEEN ? AND ?');
$stmt->execute([$periodStart, $periodEnd]);
$periodExpenses = $stmt->fetchColumn();

$stmt = db()->prepare('SELECT event_date, event_place FROM events WHERE event_date >= ? ORDER BY event_date ASC, id ASC LIMIT 1');
$stmt->execute([$today]);
$nextCatering = $stmt->fetch();

$trendLabels = [];
$trendValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $trendLabels[] = date('d M', strtotime($date));
    if ($trendType === 'gerai') {
        $stmt = db()->prepare("SELECT COALESCE(SUM(total),0) FROM sales WHERE sale_type = 'Gerai' AND sale_date = ?");
        $stmt->execute([$date]);
    } else {
        $stmt = db()->prepare("
            SELECT COALESCE(SUM(total),0)
            FROM (
                SELECT total FROM sales WHERE sale_type = 'Gerai' AND sale_date = ?
                UNION ALL
                SELECT total FROM orders WHERE status = 'Completed' AND pickup_date = ?
                UNION ALL
                SELECT deposit_paid AS total FROM events WHERE deposit_paid > 0 AND deposit_date = ?
                UNION ALL
                SELECT balance_paid AS total FROM events WHERE balance_paid > 0 AND balance_paid_date = ?
            ) dashboard_sales
        ");
        $stmt->execute([$date, $date, $date, $date]);
    }
    $trendValues[] = (float) $stmt->fetchColumn();
}

$stmt = db()->prepare("
    SELECT sale_type, COALESCE(SUM(total),0) total
    FROM (
        SELECT 'Gerai' AS sale_type, total FROM sales WHERE sale_type = 'Gerai' AND sale_date BETWEEN ? AND ?
        UNION ALL
        SELECT COALESCE(order_type, 'Tempahan') AS sale_type, total FROM orders WHERE status = 'Completed' AND pickup_date BETWEEN ? AND ?
        UNION ALL
        SELECT 'Catering' AS sale_type, deposit_paid AS total FROM events WHERE deposit_paid > 0 AND deposit_date BETWEEN ? AND ?
        UNION ALL
        SELECT 'Catering' AS sale_type, balance_paid AS total FROM events WHERE balance_paid > 0 AND balance_paid_date BETWEEN ? AND ?
    ) dashboard_sales
    GROUP BY sale_type
");
$stmt->execute([$periodStart, $periodEnd, $periodStart, $periodEnd, $periodStart, $periodEnd, $periodStart, $periodEnd]);
$categoryRows = $stmt->fetchAll();
$categoryLabels = array_column($categoryRows, 'sale_type');
$categoryValues = array_map('floatval', array_column($categoryRows, 'total'));

$metrics = [
    ['Sales', money($periodSales['total']), 'bi-cash-stack', 'bg-success-subtle text-success'],
    ['Pieces', number_plain($periodSales['qty']), 'bi-box-seam', 'bg-primary-subtle text-primary'],
    ['Expenses', money($periodExpenses), 'bi-wallet2', 'bg-danger-subtle text-danger'],
];

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <h1 class="h5 mb-1">Dashboard</h1>
        <div class="text-muted small"><?= h($periodLabel) ?> summary</div>
    </div>
    <div class="btn-group no-print" role="group" aria-label="Dashboard period">
        <a class="btn btn-sm <?= $period === 'today' ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?period=today&trend=<?= h($trendType) ?>">Today</a>
        <a class="btn btn-sm <?= $period === 'month' ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?period=month&trend=<?= h($trendType) ?>">This Month</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <?php foreach ($metrics as [$label, $value, $icon, $tone]): ?>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card metric-card h-100">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="text-muted small"><?= h($label) ?></div>
                        <div class="h4 mb-0"><?= h($value) ?></div>
                    </div>
                    <span class="icon <?= h($tone) ?>"><i class="bi <?= h($icon) ?>"></i></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="col-12">
        <div class="card metric-card">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="text-muted small">Next Catering Reminder</div>
                    <?php if ($nextCatering): ?>
                        <div class="h5 mb-1"><?= h(date('d/m/Y', strtotime($nextCatering['event_date']))) ?> - <?= h($nextCatering['event_place']) ?></div>
                    <?php else: ?>
                        <div class="h5 mb-0">No upcoming catering</div>
                    <?php endif; ?>
                </div>
                <span class="icon bg-info-subtle text-info"><i class="bi bi-calendar-event"></i></span>
            </div>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="chart-panel">
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-3">
                <h2 class="h6 mb-0">Sales Trend - Last 7 Days</h2>
                <div class="btn-group no-print" role="group" aria-label="Sales trend type">
                    <a class="btn btn-sm <?= $trendType === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?period=<?= h($period) ?>&trend=all">All</a>
                    <a class="btn btn-sm <?= $trendType === 'gerai' ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?period=<?= h($period) ?>&trend=gerai">Gerai</a>
                </div>
            </div>
            <canvas id="salesTrendChart" data-labels='<?= h(json_encode($trendLabels)) ?>' data-values='<?= h(json_encode($trendValues)) ?>'></canvas>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="chart-panel">
            <h2 class="h6 mb-3">Sales by Category</h2>
            <canvas id="salesCategoryChart" data-labels='<?= h(json_encode($categoryLabels)) ?>' data-values='<?= h(json_encode($categoryValues)) ?>'></canvas>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trend = document.getElementById('salesTrendChart');
    if (trend) {
        new Chart(trend, {
            type: 'line',
            data: {
                labels: JSON.parse(trend.dataset.labels || '[]'),
                datasets: [{ label: <?= json_encode($trendType === 'gerai' ? 'Gerai' : 'All Sales') ?>, data: JSON.parse(trend.dataset.values || '[]'), borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.12)', fill: true, tension: .35 }]
            }
        });
    }
    const category = document.getElementById('salesCategoryChart');
    if (category) {
        new Chart(category, {
            type: 'doughnut',
            data: {
                labels: JSON.parse(category.dataset.labels || '[]'),
                datasets: [{ data: JSON.parse(category.dataset.values || '[]'), backgroundColor: ['#0f766e', '#2563eb', '#f59e0b', '#dc2626'] }]
            }
        });
    }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
