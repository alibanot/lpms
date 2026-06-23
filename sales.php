<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$saleTypes = ['Gerai', 'Tempahan', 'Frozen', 'Catering'];

if (is_post()) {
    verify_csrf();
    $qty = max(0, (int) ($_POST['qty'] ?? 0));
    $unitPrice = max(0, (float) ($_POST['unit_price'] ?? 0));
    $total = $qty * $unitPrice;
    $stmt = db()->prepare('INSERT INTO sales (sale_date, sale_type, qty, unit_price, total, remarks) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $_POST['sale_date'] ?? date('Y-m-d'),
        in_array($_POST['sale_type'] ?? '', $saleTypes, true) ? $_POST['sale_type'] : 'Gerai',
        $qty,
        $unitPrice,
        $total,
        trim($_POST['remarks'] ?? ''),
    ]);
    $_SESSION['flash_success'] = 'Sale saved.';
    redirect('sales.php');
}

$rows = db()->query('SELECT * FROM sales ORDER BY sale_date DESC, id DESC')->fetchAll();
$pageTitle = 'Sales';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <h1 class="h5 mb-3">Add Sale</h1>
            <form method="post" data-calc-total>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Sale Date</label>
                    <input class="form-control" type="date" name="sale_date" value="<?= h(date('Y-m-d')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sale Type</label>
                    <select class="form-select" name="sale_type" required>
                        <?php foreach ($saleTypes as $type): ?><option><?= h($type) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6 mb-3">
                        <label class="form-label">Quantity</label>
                        <input class="form-control" type="number" name="qty" min="1" value="1" data-qty required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Unit Price</label>
                        <input class="form-control" type="number" name="unit_price" min="0" step="0.01" value="<?= h((string) current_default_price()) ?>" data-unit-price required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total</label>
                    <input class="form-control" type="number" name="total" step="0.01" data-total readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks" rows="3"></textarea>
                </div>
                <button class="btn btn-primary w-100" type="submit">Save Sale</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="table-card">
            <h2 class="h5 mb-3">Sales Records</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable">
                    <thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h($row['sale_date']) ?></td>
                            <td><?= h($row['sale_type']) ?></td>
                            <td><?= number_plain($row['qty']) ?></td>
                            <td><?= money($row['unit_price']) ?></td>
                            <td><?= money($row['total']) ?></td>
                            <td><?= h($row['remarks']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

