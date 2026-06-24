<?php
require_once __DIR__ . '/includes/init.php';
require_login();

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM frozen_stock WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Frozen stock record deleted.';
        redirect('frozen_stock.php');
    }

    $units = max(0, (int) ($_POST['units'] ?? 0));
    $unitsRemaining = $units;
    $piecesPerUnit = max(0, (int) ($_POST['pieces_per_unit'] ?? 0));
    $dateMade = $_POST['date_made'] ?? date('Y-m-d');
    $expiryDate = $_POST['expiry_date'] ?: (new DateTimeImmutable($dateMade))->modify('+2 months')->format('Y-m-d');
    $batchNo = trim($_POST['batch_no'] ?? '');

    if ($batchNo === '') {
        $prefix = 'FZ-' . date('Ymd', strtotime($dateMade));
        $stmt = db()->prepare('SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(batch_no, "-", -1) AS UNSIGNED)), 0) FROM frozen_stock WHERE batch_no LIKE ?');
        $stmt->execute([$prefix . '-%']);
        $batchNo = $prefix . '-' . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    $values = [
        $batchNo,
        $dateMade,
        $expiryDate,
        $units,
        $unitsRemaining,
        $piecesPerUnit,
        trim($_POST['remarks'] ?? ''),
    ];

    if ($action === 'update') {
        $stmt = db()->prepare('UPDATE frozen_stock SET batch_no = ?, date_made = ?, expiry_date = ?, units = ?, units_remaining = ?, pieces_per_unit = ?, remarks = ? WHERE id = ?');
        $stmt->execute([...$values, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Frozen stock record updated.';
        redirect('frozen_stock.php');
    }

    $stmt = db()->prepare('INSERT INTO frozen_stock (batch_no, date_made, expiry_date, units, units_remaining, pieces_per_unit, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute($values);
    $_SESSION['flash_success'] = 'Frozen stock record saved.';
    redirect('frozen_stock.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM frozen_stock WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editRow = $stmt->fetch() ?: null;
}

$today = date('Y-m-d');
$soon = (new DateTimeImmutable($today))->modify('+7 days')->format('Y-m-d');

$stmt = db()->prepare('SELECT COALESCE(SUM(units),0) units, COALESCE(SUM(units * pieces_per_unit),0) pieces FROM frozen_stock WHERE expiry_date >= ?');
$stmt->execute([$today]);
$currentStock = $stmt->fetch();

$stmt = db()->prepare('SELECT COALESCE(SUM(units),0) units, COALESCE(SUM(units * pieces_per_unit),0) pieces FROM frozen_stock WHERE expiry_date < ?');
$stmt->execute([$today]);
$expiredStock = $stmt->fetch();

$stmt = db()->prepare('SELECT COALESCE(SUM(units),0) units, COALESCE(SUM(units * pieces_per_unit),0) pieces FROM frozen_stock WHERE expiry_date BETWEEN ? AND ?');
$stmt->execute([$today, $soon]);
$expiringStock = $stmt->fetch();

$rows = db()->query('SELECT * FROM frozen_stock ORDER BY expiry_date ASC, date_made DESC, id DESC')->fetchAll();
$pageTitle = 'Frozen Stock';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="text-muted small">Current Stock</div>
                <div class="h4 mb-0"><?= number_plain($currentStock['units']) ?> units</div>
                <div class="text-muted small"><?= number_plain($currentStock['pieces']) ?> pieces</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="text-muted small">Expiring in 7 Days</div>
                <div class="h4 mb-0"><?= number_plain($expiringStock['units']) ?> units</div>
                <div class="text-muted small"><?= number_plain($expiringStock['pieces']) ?> pieces</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="text-muted small">Expired Stock</div>
                <div class="h4 mb-0"><?= number_plain($expiredStock['units']) ?> units</div>
                <div class="text-muted small"><?= number_plain($expiredStock['pieces']) ?> pieces</div>
            </div>
        </div>
    </div>
</div>
<div class="table-card">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <h1 class="h5 mb-0">Frozen Stock Records</h1>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#frozenStockModal">
            <i class="bi bi-plus-lg me-1"></i>Add Stock
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle datatable">
            <thead><tr><th>Batch No</th><th>Date Made</th><th>Expiry Date</th><th>Units</th><th>Pieces / Unit</th><th>Total Pieces</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $isExpired = $row['expiry_date'] < $today;
                $isExpiring = !$isExpired && $row['expiry_date'] <= $soon;
                $status = $isExpired ? 'Expired' : ($isExpiring ? 'Expiring Soon' : 'Available');
                $badge = $isExpired ? 'text-bg-danger' : ($isExpiring ? 'text-bg-warning' : 'text-bg-success');
                ?>
                <tr>
                    <td><?= h($row['batch_no']) ?></td>
                    <td><?= h($row['date_made']) ?></td>
                    <td><?= h($row['expiry_date']) ?></td>
                    <td><?= number_plain($row['units']) ?></td>
                    <td><?= number_plain($row['pieces_per_unit']) ?></td>
                    <td><?= number_plain((int) $row['units'] * (int) $row['pieces_per_unit']) ?></td>
                    <td><span class="badge <?= h($badge) ?>"><?= h($status) ?></span></td>
                    <td><?= h($row['remarks']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a class="btn btn-sm btn-outline-primary" href="frozen_stock.php?edit=<?= h((string) $row['id']) ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this frozen stock record?')">
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
<div class="modal fade" id="frozenStockModal" tabindex="-1" aria-labelledby="frozenStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" data-frozen-stock>
                <div class="modal-header">
                    <h2 class="modal-title h5" id="frozenStockModalLabel"><?= $editRow ? 'Edit Frozen Stock' : 'Add Frozen Stock' ?></h2>
                    <a class="btn-close" href="frozen_stock.php" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
                    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= h((string) $editRow['id']) ?>"><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Batch No</label>
                            <input class="form-control" name="batch_no" value="<?= h($editRow['batch_no'] ?? '') ?>" placeholder="Auto generated if blank">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Date Made</label>
                            <input class="form-control" type="date" name="date_made" value="<?= h($editRow['date_made'] ?? date('Y-m-d')) ?>" data-date-made required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Date Expiry</label>
                            <input class="form-control" type="date" name="expiry_date" value="<?= h($editRow['expiry_date'] ?? (new DateTimeImmutable())->modify('+2 months')->format('Y-m-d')) ?>" data-expiry-date required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Units</label>
                            <input class="form-control" type="number" name="units" min="0" value="<?= h((string) ($editRow['units'] ?? 1)) ?>" data-stock-units required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Pieces Per Unit</label>
                            <input class="form-control" type="number" name="pieces_per_unit" min="0" value="<?= h((string) ($editRow['pieces_per_unit'] ?? 1)) ?>" data-stock-pieces required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Total Pieces</label>
                            <input class="form-control" type="number" data-stock-total readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="3"><?= h($editRow['remarks'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($editRow): ?><a class="btn btn-outline-secondary" href="frozen_stock.php">Cancel</a><?php endif; ?>
                    <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update Stock' : 'Save Stock' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($editRow): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('frozenStockModal')).show();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
