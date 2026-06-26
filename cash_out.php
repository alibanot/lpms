<?php
require_once __DIR__ . '/includes/init.php';
require_login();

db()->exec("
    CREATE TABLE IF NOT EXISTS cash_out (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cash_out_date DATE NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        remarks TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM cash_out WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Cash out deleted.';
        redirect('cash_out.php');
    }

    $values = [
        $_POST['cash_out_date'] ?? date('Y-m-d'),
        max(0, (float) ($_POST['amount'] ?? 0)),
        trim($_POST['remarks'] ?? ''),
    ];

    if ($action === 'update') {
        $stmt = db()->prepare('UPDATE cash_out SET cash_out_date = ?, amount = ?, remarks = ? WHERE id = ?');
        $stmt->execute([...$values, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Cash out updated.';
        redirect('cash_out.php');
    }

    $stmt = db()->prepare('INSERT INTO cash_out (cash_out_date, amount, remarks) VALUES (?, ?, ?)');
    $stmt->execute($values);
    $_SESSION['flash_success'] = 'Cash out saved.';
    redirect('cash_out.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM cash_out WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editRow = $stmt->fetch() ?: null;
}

$rows = db()->query('SELECT * FROM cash_out ORDER BY cash_out_date DESC, id DESC')->fetchAll();
$pageTitle = 'Cash Out';
include __DIR__ . '/includes/header.php';
?>
<div class="table-card">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <div>
            <h1 class="h5 mb-1">Cash Out Records</h1>
            <div class="text-muted small">Owner or personal use withdrawals.</div>
        </div>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#cashOutModal">
            <i class="bi bi-plus-lg me-1"></i>Add Cash Out
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h($row['cash_out_date']) ?></td>
                    <td><?= money($row['amount']) ?></td>
                    <td><?= h($row['remarks'] ?? '') ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a class="btn btn-sm btn-outline-primary" href="cash_out.php?edit=<?= h((string) $row['id']) ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this cash out record?')">
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

<div class="modal fade" id="cashOutModal" tabindex="-1" aria-labelledby="cashOutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="cashOutModalLabel"><?= $editRow ? 'Edit Cash Out' : 'Add Cash Out' ?></h2>
                    <a class="btn-close" href="cash_out.php" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
                    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= h((string) $editRow['id']) ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input class="form-control" type="date" name="cash_out_date" value="<?= h($editRow['cash_out_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input class="form-control" type="number" name="amount" min="0" step="0.01" value="<?= h((string) ($editRow['amount'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="3"><?= h($editRow['remarks'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($editRow): ?><a class="btn btn-outline-secondary" href="cash_out.php">Cancel</a><?php endif; ?>
                    <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update Cash Out' : 'Save Cash Out' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editRow): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('cashOutModal')).show();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
