<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$categories = ['Pisang', 'Tepung', 'Telur', 'Gas', 'Packaging', 'Equipment', 'Others'];

if (is_post()) {
    verify_csrf();
    $category = in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'Others';
    $stmt = db()->prepare('INSERT INTO expenses (expense_date, category, amount, remarks) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $_POST['expense_date'] ?? date('Y-m-d'),
        $category,
        max(0, (float) ($_POST['amount'] ?? 0)),
        trim($_POST['remarks'] ?? ''),
    ]);
    $_SESSION['flash_success'] = 'Expense saved.';
    redirect('expenses.php');
}

$rows = db()->query('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC')->fetchAll();
$pageTitle = 'Expenses';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <h1 class="h5 mb-3">Add Expense</h1>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Expense Date</label>
                    <input class="form-control" type="date" name="expense_date" value="<?= h(date('Y-m-d')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category" required>
                        <?php foreach ($categories as $category): ?><option><?= h($category) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input class="form-control" type="number" name="amount" min="0" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks" rows="3"></textarea>
                </div>
                <button class="btn btn-primary w-100" type="submit">Save Expense</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="table-card">
            <h2 class="h5 mb-3">Expense Records</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable">
                    <thead><tr><th>Date</th><th>Category</th><th>Amount</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h($row['expense_date']) ?></td>
                            <td><?= h($row['category']) ?></td>
                            <td><?= money($row['amount']) ?></td>
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

