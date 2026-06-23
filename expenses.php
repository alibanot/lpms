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
<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <h1 class="h5 mb-3"><?= $editRow ? 'Edit Expense' : 'Add Expense' ?></h1>
            <form method="post">
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
                <button class="btn btn-primary w-100" type="submit"><?= $editRow ? 'Update Expense' : 'Save Expense' ?></button>
                <?php if ($editRow): ?><a class="btn btn-outline-secondary w-100 mt-2" href="expenses.php">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="table-card">
            <h2 class="h5 mb-3">Expense Records</h2>
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
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
