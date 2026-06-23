<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$categories = ['Pisang', 'Tepung', 'Telur', 'Gas', 'Packaging', 'Equipment', 'Others'];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM expenses WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Expense deleted.';
        redirect('expenses.php');
    }

    $category = in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'Others';
    $values = [
        $_POST['expense_date'] ?? date('Y-m-d'),
        $category,
        max(0, (float) ($_POST['amount'] ?? 0)),
        trim($_POST['remarks'] ?? ''),
    ];

    if ($action === 'update') {
        $stmt = db()->prepare('UPDATE expenses SET expense_date = ?, category = ?, amount = ?, remarks = ? WHERE id = ?');
        $stmt->execute([...$values, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Expense updated.';
        redirect('expenses.php');
    }

    $stmt = db()->prepare('INSERT INTO expenses (expense_date, category, amount, remarks) VALUES (?, ?, ?, ?)');
    $stmt->execute($values);
    $_SESSION['flash_success'] = 'Expense saved.';
    redirect('expenses.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM expenses WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editRow = $stmt->fetch() ?: null;
}

$rows = db()->query('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC')->fetchAll();
$pageTitle = 'Expenses';
include __DIR__ . '/includes/header.php';
?>
<div class="table-card">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <h1 class="h5 mb-0">Expense Records</h1>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#expenseModal">
            <i class="bi bi-plus-lg me-1"></i>Add Expense
        </button>
    </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable">
                    <thead><tr><th>Date</th><th>Category</th><th>Amount</th><th>Remarks</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h($row['expense_date']) ?></td>
                            <td><?= h($row['category']) ?></td>
                            <td><?= money($row['amount']) ?></td>
                            <td><?= h($row['remarks']) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="expenses.php?edit=<?= h((string) $row['id']) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this expense?')">
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
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="expenseModalLabel"><?= $editRow ? 'Edit Expense' : 'Add Expense' ?></h2>
                    <a class="btn-close" href="expenses.php" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
                    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= h((string) $editRow['id']) ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Expense Date</label>
                        <input class="form-control" type="date" name="expense_date" value="<?= h($editRow['expense_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= h($category) ?>" <?= (($editRow['category'] ?? '') === $category) ? 'selected' : '' ?>><?= h($category) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <?php if ($editRow): ?><a class="btn btn-outline-secondary" href="expenses.php">Cancel</a><?php endif; ?>
                    <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update Expense' : 'Save Expense' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($editRow): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('expenseModal')).show();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
