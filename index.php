<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$stmt = db()->prepare("
    SELECT COALESCE(SUM(total),0) total, COALESCE(SUM(qty),0) qty
    FROM (
        SELECT total, qty FROM sales WHERE sale_date = ?
        UNION ALL
        SELECT total, qty FROM orders WHERE status = 'Completed' AND pickup_date = ?
    ) dashboard_sales
");
$stmt->execute([$today, $today]);
$todaySales = $stmt->fetch();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE expense_date = ?');
$stmt->execute([$today]);
$todayExpenses = $stmt->fetchColumn();

$stmt = db()->prepare("
    SELECT COALESCE(SUM(total),0) total, COALESCE(SUM(qty),0) qty
    FROM (
        SELECT total, qty FROM sales WHERE sale_date BETWEEN ? AND ?
        UNION ALL
        SELECT total, qty FROM orders WHERE status = 'Completed' AND pickup_date BETWEEN ? AND ?
    ) dashboard_sales
");
$stmt->execute([$monthStart, $monthEnd, $monthStart, $monthEnd]);
$monthSales = $stmt->fetch();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) total FROM expenses WHERE expense_date BETWEEN ? AND ?');
$stmt->execute([$monthStart, $monthEnd]);
$monthExpenses = $stmt->fetchColumn();

$pendingOrders = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

$trendLabels = [];
$trendValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $trendLabels[] = date('d M', strtotime($date));
    $stmt = db()->prepare("
        SELECT COALESCE(SUM(total),0)
        FROM (
            SELECT total FROM sales WHERE sale_date = ?
            UNION ALL
            SELECT total FROM orders WHERE status = 'Completed' AND pickup_date = ?
        ) dashboard_sales
    ");
    $stmt->execute([$date, $date]);
    $trendValues[] = (float) $stmt->fetchColumn();
}

$categoryRows = db()->query("
    SELECT sale_type, COALESCE(SUM(total),0) total
    FROM (
        SELECT sale_type, total FROM sales
        UNION ALL
        SELECT 'Tempahan' AS sale_type, total FROM orders WHERE status = 'Completed'
    ) dashboard_sales
    GROUP BY sale_type
")->fetchAll();
$categoryLabels = array_column($categoryRows, 'sale_type');
$categoryValues = array_map('floatval', array_column($categoryRows, 'total'));

$metrics = [
    ['Today\'s Sales', money($todaySales['total']), 'bi-cash-stack', 'bg-success-subtle text-success'],
    ['Today\'s Expenses', money($todayExpenses), 'bi-wallet2', 'bg-danger-subtle text-danger'],
    ['Today\'s Profit', money((float) $todaySales['total'] - (float) $todayExpenses), 'bi-graph-up-arrow', 'bg-primary-subtle text-primary'],
    ['Monthly Sales', money($monthSales['total']), 'bi-calendar-check', 'bg-info-subtle text-info'],
    ['Monthly Expenses', money($monthExpenses), 'bi-calendar-minus', 'bg-warning-subtle text-warning'],
    ['Monthly Profit', money((float) $monthSales['total'] - (float) $monthExpenses), 'bi-trophy', 'bg-success-subtle text-success'],
    ['Pieces Sold Today', number_plain($todaySales['qty']), 'bi-box-seam', 'bg-secondary-subtle text-secondary'],
    ['Pieces Sold This Month', number_plain($monthSales['qty']), 'bi-boxes', 'bg-primary-subtle text-primary'],
    ['Pending Orders', (string) $pendingOrders, 'bi-hourglass-split', 'bg-warning-subtle text-warning'],
];

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
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
</div>
<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="chart-panel">
            <h2 class="h6 mb-3">Sales Trend - Last 7 Days</h2>
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
                datasets: [{ label: 'Sales', data: JSON.parse(trend.dataset.values || '[]'), borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.12)', fill: true, tension: .35 }]
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
