<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$filter = $_GET['filter'] ?? 'today';
[$startDate, $endDate] = report_date_range($filter, $_GET['start_date'] ?? null, $_GET['end_date'] ?? null);

$stmt = db()->prepare("
    SELECT COALESCE(SUM(total),0) total, COALESCE(SUM(qty),0) qty
    FROM (
        SELECT total, qty FROM sales WHERE sale_date BETWEEN ? AND ?
        UNION ALL
        SELECT total, qty FROM orders WHERE status = 'Completed' AND pickup_date BETWEEN ? AND ?
    ) report_sales
");
$stmt->execute([$startDate, $endDate, $startDate, $endDate]);
$salesTotals = $stmt->fetch();

$stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?');
$stmt->execute([$startDate, $endDate]);
$expensesTotal = (float) $stmt->fetchColumn();

$stmt = db()->prepare("
    SELECT sale_type, COALESCE(SUM(total),0) total, COALESCE(SUM(qty),0) qty
    FROM (
        SELECT sale_type, total, qty FROM sales WHERE sale_date BETWEEN ? AND ?
        UNION ALL
        SELECT 'Tempahan' AS sale_type, total, qty FROM orders WHERE status = 'Completed' AND pickup_date BETWEEN ? AND ?
    ) report_sales
    GROUP BY sale_type
");
$stmt->execute([$startDate, $endDate, $startDate, $endDate]);
$breakdowns = $stmt->fetchAll();

$breakdownMap = [];
foreach ($breakdowns as $row) {
    $breakdownMap[$row['sale_type']] = $row;
}

$saleTypes = ['Gerai', 'Tempahan', 'Frozen', 'Catering'];
$query = http_build_query(['filter' => $filter, 'start_date' => $startDate, 'end_date' => $endDate]);

$pageTitle = 'Reports';
include __DIR__ . '/includes/header.php';
?>
<div class="form-card mb-3 no-print">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-12 col-md-3">
            <label class="form-label">Filter</label>
            <select class="form-select" name="filter" id="reportFilter">
                <?php foreach (['today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This Week', 'this_month' => 'This Month', 'custom' => 'Custom Date Range'] as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= $filter === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Start Date</label>
            <input class="form-control" type="date" name="start_date" value="<?= h($startDate) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">End Date</label>
            <input class="form-control" type="date" name="end_date" value="<?= h($endDate) ?>">
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-fill" type="submit">Apply</button>
            <a class="btn btn-outline-success" href="export_report.php?<?= h($query) ?>">CSV</a>
            <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print</button>
        </div>
    </form>
</div>
<div class="row g-3 mb-3">
    <div class="col-12 col-md-3"><div class="card metric-card"><div class="card-body"><div class="text-muted small">Total Sales</div><div class="h4"><?= money($salesTotals['total']) ?></div></div></div></div>
    <div class="col-12 col-md-3"><div class="card metric-card"><div class="card-body"><div class="text-muted small">Total Expenses</div><div class="h4"><?= money($expensesTotal) ?></div></div></div></div>
    <div class="col-12 col-md-3"><div class="card metric-card"><div class="card-body"><div class="text-muted small">Net Profit</div><div class="h4"><?= money((float) $salesTotals['total'] - $expensesTotal) ?></div></div></div></div>
    <div class="col-12 col-md-3"><div class="card metric-card"><div class="card-body"><div class="text-muted small">Total Quantity Sold</div><div class="h4"><?= number_plain($salesTotals['qty']) ?></div></div></div></div>
</div>
<div class="table-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0">Breakdown: <?= h($startDate) ?> to <?= h($endDate) ?></h1>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead><tr><th>Sale Type</th><th>Quantity</th><th>Total Sales</th></tr></thead>
            <tbody>
            <?php foreach ($saleTypes as $type): $row = $breakdownMap[$type] ?? ['qty' => 0, 'total' => 0]; ?>
                <tr><td><?= h($type) ?></td><td><?= number_plain($row['qty']) ?></td><td><?= money($row['total']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
