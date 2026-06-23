<?php
require_once __DIR__ . '/includes/init.php';
require_login();

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM sales WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Sale deleted.';
        redirect('sales.php');
    }

    $qty = max(0, (int) ($_POST['qty'] ?? 0));
    $unitPrice = max(0, (float) ($_POST['unit_price'] ?? 0));
    $total = $qty * $unitPrice;
    $values = [
        $_POST['sale_date'] ?? date('Y-m-d'),
        'Gerai',
        $qty,
        $unitPrice,
        $total,
        trim($_POST['remarks'] ?? ''),
    ];

    if ($action === 'update') {
        $stmt = db()->prepare('UPDATE sales SET sale_date = ?, sale_type = ?, qty = ?, unit_price = ?, total = ?, remarks = ? WHERE id = ?');
        $stmt->execute([...$values, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Sale updated.';
        redirect('sales.php');
    }

    $stmt = db()->prepare('INSERT INTO sales (sale_date, sale_type, qty, unit_price, total, remarks) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute($values);
    $_SESSION['flash_success'] = 'Sale saved.';
    redirect('sales.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM sales WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editRow = $stmt->fetch() ?: null;
}

$rows = db()->query("SELECT * FROM sales WHERE sale_type = 'Gerai' ORDER BY sale_date DESC, id DESC")->fetchAll();
$pageTitle = 'Daily Sales';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <h1 class="h5 mb-3"><?= $editRow ? 'Edit Daily Sale' : 'Add Daily Sale' ?></h1>
            <form method="post" data-calc-total>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?= h((string) $editRow['id']) ?>"><?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Sale Date</label>
                    <input class="form-control" type="date" name="sale_date" value="<?= h($editRow['sale_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="row g-2">
                    <div class="col-6 mb-3">
                        <label class="form-label">Quantity</label>
                        <input class="form-control" type="number" name="qty" min="1" value="<?= h((string) ($editRow['qty'] ?? 1)) ?>" data-qty required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Unit Price</label>
                        <input class="form-control" type="number" name="unit_price" min="0" step="0.01" value="<?= h((string) ($editRow['unit_price'] ?? current_default_price())) ?>" data-unit-price required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total</label>
                    <input class="form-control" type="number" name="total" step="0.01" data-total readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks" rows="3"><?= h($editRow['remarks'] ?? '') ?></textarea>
                </div>
                <button class="btn btn-primary w-100" type="submit"><?= $editRow ? 'Update Daily Sale' : 'Save Daily Sale' ?></button>
                <?php if ($editRow): ?><a class="btn btn-outline-secondary w-100 mt-2" href="sales.php">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="table-card">
            <h2 class="h5 mb-3">Daily Sales Records</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable">
                    <thead><tr><th>Date</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Remarks</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h($row['sale_date']) ?></td>
                            <td><?= number_plain($row['qty']) ?></td>
                            <td><?= money($row['unit_price']) ?></td>
                            <td><?= money($row['total']) ?></td>
                            <td><?= h($row['remarks']) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="sales.php?edit=<?= h((string) $row['id']) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this sale?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= h((string) $row['id']) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
