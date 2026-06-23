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
<div class="table-card">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <h1 class="h5 mb-0">Daily Sales Records</h1>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#saleModal">
            <i class="bi bi-plus-lg me-1"></i>Add Daily Sale
        </button>
    </div>
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
<div class="modal fade" id="saleModal" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" data-calc-total>
                <div class="modal-header">
                    <h2 class="modal-title h5" id="saleModalLabel"><?= $editRow ? 'Edit Daily Sale' : 'Add Daily Sale' ?></h2>
                    <a class="btn-close" href="sales.php" aria-label="Close"></a>
                </div>
                <div class="modal-body">
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
                </div>
                <div class="modal-footer">
                    <?php if ($editRow): ?><a class="btn btn-outline-secondary" href="sales.php">Cancel</a><?php endif; ?>
                    <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update Daily Sale' : 'Save Daily Sale' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($editRow): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('saleModal')).show();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
