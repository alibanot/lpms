<?php
require_once __DIR__ . '/includes/init.php';
require_login();

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Catering deleted.';
        redirect('events.php');
    }

    $depositPaid = max(0, (float) ($_POST['deposit_paid'] ?? 0));
    $balancePaid = max(0, (float) ($_POST['balance_paid'] ?? 0));
    $depositDate = $depositPaid > 0 ? ($_POST['deposit_date'] ?: date('Y-m-d')) : null;
    $balancePaidDate = $balancePaid > 0 ? ($_POST['balance_paid_date'] ?: date('Y-m-d')) : null;

    $values = [
        $_POST['event_date'] ?? date('Y-m-d'),
        trim($_POST['event_place'] ?? ''),
        trim($_POST['package_name'] ?? ''),
        max(0, (float) ($_POST['price'] ?? 0)),
        $depositPaid,
        $depositDate,
        $balancePaid,
        $balancePaidDate,
    ];

    if ($action === 'update') {
        $stmt = db()->prepare('UPDATE events SET event_date = ?, event_place = ?, package_name = ?, price = ?, deposit_paid = ?, deposit_date = ?, balance_paid = ?, balance_paid_date = ? WHERE id = ?');
        $stmt->execute([...$values, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Catering updated.';
        redirect('events.php');
    }

    $stmt = db()->prepare('INSERT INTO events (event_date, event_place, package_name, price, deposit_paid, deposit_date, balance_paid, balance_paid_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute($values);
    $_SESSION['flash_success'] = 'Catering saved.';
    redirect('events.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editRow = $stmt->fetch() ?: null;
}

$rows = db()->query('SELECT * FROM events ORDER BY event_date DESC, id DESC')->fetchAll();
$pageTitle = 'Catering';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <h1 class="h5 mb-3"><?= $editRow ? 'Edit Catering' : 'Add Catering' ?></h1>
            <form method="post" data-event-balance>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?= h((string) $editRow['id']) ?>"><?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Catering Date</label>
                    <input class="form-control" type="date" name="event_date" value="<?= h($editRow['event_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catering Place</label>
                    <input class="form-control" name="event_place" value="<?= h($editRow['event_place'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Package</label>
                    <input class="form-control" name="package_name" value="<?= h($editRow['package_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input class="form-control" type="number" name="price" min="0" step="0.01" value="<?= h((string) ($editRow['price'] ?? '')) ?>" data-event-price required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deposit Paid</label>
                    <input class="form-control" type="number" name="deposit_paid" min="0" step="0.01" value="<?= h((string) ($editRow['deposit_paid'] ?? '0.00')) ?>" data-event-deposit required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deposit Date</label>
                    <input class="form-control" type="date" name="deposit_date" value="<?= h($editRow['deposit_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Balance Paid</label>
                    <input class="form-control" type="number" name="balance_paid" min="0" step="0.01" value="<?= h((string) ($editRow['balance_paid'] ?? '0.00')) ?>" data-event-balance-paid required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Balance Paid Date</label>
                    <input class="form-control" type="date" name="balance_paid_date" value="<?= h($editRow['balance_paid_date'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Balance Remaining</label>
                    <input class="form-control" type="number" step="0.01" data-event-balance-output readonly>
                </div>
                <button class="btn btn-primary w-100" type="submit"><?= $editRow ? 'Update Catering' : 'Save Catering' ?></button>
                <?php if ($editRow): ?><a class="btn btn-outline-secondary w-100 mt-2" href="events.php">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="table-card">
            <h2 class="h5 mb-3">Catering Records</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable">
                    <thead><tr><th>Catering Date</th><th>Place</th><th>Package</th><th>Price</th><th>Deposit</th><th>Balance Paid</th><th>Balance Remaining</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h($row['event_date']) ?></td>
                            <td><?= h($row['event_place']) ?></td>
                            <td><?= h($row['package_name']) ?></td>
                            <td><?= money($row['price']) ?></td>
                            <td><?= money($row['deposit_paid']) ?><div class="small text-muted"><?= h($row['deposit_date'] ?? '') ?></div></td>
                            <td><?= money($row['balance_paid']) ?><div class="small text-muted"><?= h($row['balance_paid_date'] ?? '') ?></div></td>
                            <td><?= money(max(0, (float) $row['price'] - (float) $row['deposit_paid'] - (float) $row['balance_paid'])) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="events.php?edit=<?= h((string) $row['id']) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this catering record?')">
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
