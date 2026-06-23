<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$filter = $_GET['filter'] ?? 'today';
[$startDate, $endDate] = report_date_range($filter, $_GET['start_date'] ?? null, $_GET['end_date'] ?? null);

$stmt = db()->prepare('SELECT sale_type, COALESCE(SUM(qty),0) qty, COALESCE(SUM(total),0) total FROM sales WHERE sale_date BETWEEN ? AND ? GROUP BY sale_type');
$stmt->execute([$startDate, $endDate]);
$rows = $stmt->fetchAll();

$stmt = db()->prepare('SELECT COALESCE(SUM(total),0), COALESCE(SUM(qty),0) FROM sales WHERE sale_date BETWEEN ? AND ?');
$stmt->execute([$startDate, $endDate]);
[$totalSales, $totalQty] = $stmt->fetch(PDO::FETCH_NUM);

$stmt = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?');
$stmt->execute([$startDate, $endDate]);
$totalExpenses = (float) $stmt->fetchColumn();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="lpms-report-' . $startDate . '-to-' . $endDate . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['LPMS Report', $startDate . ' to ' . $endDate]);
fputcsv($out, []);
fputcsv($out, ['Summary', 'Amount']);
fputcsv($out, ['Total Sales', $totalSales]);
fputcsv($out, ['Total Expenses', $totalExpenses]);
fputcsv($out, ['Net Profit', (float) $totalSales - $totalExpenses]);
fputcsv($out, ['Total Quantity Sold', $totalQty]);
fputcsv($out, []);
fputcsv($out, ['Sale Type', 'Quantity', 'Total Sales']);
foreach ($rows as $row) {
    fputcsv($out, [$row['sale_type'], $row['qty'], $row['total']]);
}
fclose($out);

