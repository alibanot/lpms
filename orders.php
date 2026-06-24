<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$statuses = ['Pending', 'Ready', 'Completed', 'Cancelled'];
$orderTypes = ['Tempahan', 'Frozen'];

function frozen_stock_available_units(int $frozenStockId, int $orderId = 0): int
{
    $stmt = db()->prepare('
        SELECT fs.units + COALESCE(SUM(fsm.units), 0) AS available_units
        FROM frozen_stock fs
        LEFT JOIN frozen_stock_movements fsm ON fsm.frozen_stock_id = fs.id AND fsm.order_id <> ?
        WHERE fs.id = ?
        GROUP BY fs.id, fs.units
    ');
    $stmt->execute([$orderId, $frozenStockId]);
    return max(0, (int) $stmt->fetchColumn());
}

function sync_frozen_stock_movement(int $orderId): bool
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        return true;
    }

    if (($order['order_type'] ?? '') !== 'Frozen' || ($order['status'] ?? '') !== 'Completed') {
        $stmt = db()->prepare('DELETE FROM frozen_stock_movements WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return true;
    }

    $frozenStockId = (int) ($order['frozen_stock_id'] ?? 0);
    $qty = (int) ($order['qty'] ?? 0);

    if ($frozenStockId <= 0) {
        $_SESSION['flash_error'] = 'Choose a frozen batch before completing this frozen order.';
        return false;
    }

    if ($qty > frozen_stock_available_units($frozenStockId, $orderId)) {
        $_SESSION['flash_error'] = 'Not enough frozen stock available for the selected batch.';
        return false;
    }

    $stmt = db()->prepare('
        INSERT INTO frozen_stock_movements (frozen_stock_id, order_id, movement_date, units)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE frozen_stock_id = VALUES(frozen_stock_id), movement_date = VALUES(movement_date), units = VALUES(units)
    ');
    $stmt->execute([$frozenStockId, $orderId, $order['pickup_date'], -$qty]);
    return true;
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM frozen_stock_movements WHERE order_id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $stmt = db()->prepare('DELETE FROM orders WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Order deleted.';
        redirect('orders.php');
    }

    if ($action === 'status') {
        $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'Pending';
        $orderId = (int) ($_POST['id'] ?? 0);
        if ($status === 'Completed') {
            $stmt = db()->prepare('SELECT order_type, frozen_stock_id, qty FROM orders WHERE id = ? LIMIT 1');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            if (($order['order_type'] ?? '') === 'Frozen' && empty($order['frozen_stock_id'])) {
                $_SESSION['flash_error'] = 'Choose a frozen batch before completing this frozen order.';
                redirect('orders.php');
            }
            if (($order['order_type'] ?? '') === 'Frozen' && (int) ($order['qty'] ?? 0) > frozen_stock_available_units((int) $order['frozen_stock_id'], $orderId)) {
                $_SESSION['flash_error'] = 'Not enough frozen stock available for the selected batch.';
                redirect('orders.php');
            }
        }
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $orderId]);
        sync_frozen_stock_movement($orderId);
        $_SESSION['flash_success'] = 'Order status updated.';
        redirect('orders.php');
    }

    $qty = max(0, (int) ($_POST['qty'] ?? 0));
    $unitPrice = max(0, (float) ($_POST['unit_price'] ?? 0));
    $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'Pending';
    $orderType = in_array($_POST['order_type'] ?? '', $orderTypes, true) ? $_POST['order_type'] : 'Tempahan';
    $frozenStockId = $orderType === 'Frozen' ? (int) ($_POST['frozen_stock_id'] ?? 0) : null;
    $orderId = (int) ($_POST['id'] ?? 0);

    if ($orderType === 'Frozen' && $status === 'Completed' && !$frozenStockId) {
        $_SESSION['flash_error'] = 'Choose a frozen batch before completing this frozen order.';
        redirect($action === 'update' && $orderId ? 'orders.php?edit=' . $orderId : 'orders.php');
    }

    if ($orderType === 'Frozen' && $status === 'Completed' && $frozenStockId && $qty > frozen_stock_available_units($frozenStockId, $orderId)) {
        $_SESSION['flash_error'] = 'Not enough frozen stock available for the selected batch.';
        redirect($action === 'update' && $orderId ? 'orders.php?edit=' . $orderId : 'orders.php');
    }

    $values = [
        trim($_POST['customer_name'] ?? ''),
        trim($_POST['phone'] ?? ''),
        $orderType,
        $frozenStockId,
        $_POST['pickup_date'] ?? date('Y-m-d'),
        $qty,
        $unitPrice,
        $qty * $unitPrice,
        $status,
        trim($_POST['remarks'] ?? ''),
    ];

    if ($action === 'update') {
        $stmt = db()->prepare('UPDATE orders SET customer_name = ?, phone = ?, order_type = ?, frozen_stock_id = ?, pickup_date = ?, qty = ?, unit_price = ?, total = ?, status = ?, remarks = ? WHERE id = ?');
        $stmt->execute([...$values, $orderId]);
        sync_frozen_stock_movement($orderId);
        $_SESSION['flash_success'] = 'Order updated.';
        redirect('orders.php');
    }

    $stmt = db()->prepare('INSERT INTO orders (customer_name, phone, order_type, frozen_stock_id, pickup_date, qty, unit_price, total, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute($values);
    sync_frozen_stock_movement((int) db()->lastInsertId());
    $_SESSION['flash_success'] = 'Order saved.';
    redirect('orders.php');
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editRow = $stmt->fetch() ?: null;
}

$filterStatus = $_GET['status'] ?? '';
if (in_array($filterStatus, $statuses, true)) {
    $stmt = db()->prepare('SELECT orders.*, frozen_stock.batch_no FROM orders LEFT JOIN frozen_stock ON frozen_stock.id = orders.frozen_stock_id WHERE status = ? ORDER BY pickup_date ASC, orders.id DESC');
    $stmt->execute([$filterStatus]);
    $rows = $stmt->fetchAll();
} else {
    $rows = db()->query('SELECT orders.*, frozen_stock.batch_no FROM orders LEFT JOIN frozen_stock ON frozen_stock.id = orders.frozen_stock_id ORDER BY pickup_date ASC, orders.id DESC')->fetchAll();
}

$frozenBatches = db()->query("
    SELECT fs.id, fs.batch_no, fs.units + COALESCE(SUM(fsm.units), 0) AS available_units
    FROM frozen_stock fs
    LEFT JOIN frozen_stock_movements fsm ON fsm.frozen_stock_id = fs.id
    GROUP BY fs.id, fs.batch_no, fs.units
    ORDER BY fs.expiry_date ASC, fs.batch_no ASC
")->fetchAll();

$pageTitle = 'Tempahan';
include __DIR__ . '/includes/header.php';
?>
<div class="table-card">
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0">Order List</h2>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <form class="d-flex gap-2" method="get">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Status</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= h($status) ?>" <?= $filterStatus === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    </form>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#orderModal">
                        <i class="bi bi-plus-lg me-1"></i>Add Order
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable">
                    <thead><tr><th>Customer</th><th>Phone</th><th>Type</th><th>Batch No</th><th>Pickup</th><th>Qty</th><th>Total</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h($row['customer_name']) ?></td>
                            <td><?= h($row['phone']) ?></td>
                            <td><?= h($row['order_type'] ?? 'Tempahan') ?></td>
                            <td><?= h($row['batch_no'] ?? '') ?></td>
                            <td><?= h($row['pickup_date']) ?></td>
                            <td><?= number_plain($row['qty']) ?></td>
                            <td><?= money($row['total']) ?></td>
                            <td>
                                <form method="post" class="d-flex gap-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?= h((string) $row['id']) ?>">
                                    <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= h($status) ?>" <?= $row['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td><?= h($row['remarks']) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="orders.php?edit=<?= h((string) $row['id']) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this order?')">
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
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" data-calc-total>
                <div class="modal-header">
                    <h2 class="modal-title h5" id="orderModalLabel"><?= $editRow ? 'Edit Order' : 'Add Order' ?></h2>
                    <a class="btn-close" href="orders.php" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
                    <?php if ($editRow): ?><input type="hidden" name="id" value="<?= h((string) $editRow['id']) ?>"><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Customer Name</label>
                            <input class="form-control" name="customer_name" value="<?= h($editRow['customer_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input class="form-control" name="phone" inputmode="tel" value="<?= h($editRow['phone'] ?? '') ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="order_type" required>
                                <?php foreach ($orderTypes as $type): ?>
                                    <option value="<?= h($type) ?>" <?= (($editRow['order_type'] ?? 'Tempahan') === $type) ? 'selected' : '' ?>><?= h($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Frozen Batch No</label>
                            <select class="form-select" name="frozen_stock_id">
                                <option value="">No batch</option>
                                <?php foreach ($frozenBatches as $batch): ?>
                                    <option value="<?= h((string) $batch['id']) ?>" <?= ((int) ($editRow['frozen_stock_id'] ?? 0) === (int) $batch['id']) ? 'selected' : '' ?>>
                                        <?= h($batch['batch_no']) ?> (<?= number_plain($batch['available_units']) ?> units)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Pickup Date</label>
                            <input class="form-control" type="date" name="pickup_date" value="<?= h($editRow['pickup_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Quantity</label>
                            <input class="form-control" type="number" name="qty" min="1" value="<?= h((string) ($editRow['qty'] ?? 1)) ?>" data-qty required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Unit Price</label>
                            <input class="form-control" type="number" name="unit_price" min="0" step="0.01" value="<?= h((string) ($editRow['unit_price'] ?? current_default_price())) ?>" data-unit-price required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Total</label>
                            <input class="form-control" type="number" step="0.01" data-total readonly>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= h($status) ?>" <?= (($editRow['status'] ?? '') === $status) ? 'selected' : '' ?>><?= h($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="3"><?= h($editRow['remarks'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($editRow): ?><a class="btn btn-outline-secondary" href="orders.php">Cancel</a><?php endif; ?>
                    <button class="btn btn-primary" type="submit"><?= $editRow ? 'Update Order' : 'Save Order' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($editRow): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('orderModal')).show();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
