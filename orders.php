<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$statuses = ['Pending', 'Ready', 'Completed', 'Cancelled'];
$orderTypes = ['Tempahan', 'Frozen'];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM orders WHERE id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Order deleted.';
        redirect('orders.php');
    }

    if ($action === 'status') {
        $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'Pending';
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Order status updated.';
        redirect('orders.php');
    }

    $qty = max(0, (int) ($_POST['qty'] ?? 0));
    $unitPrice = max(0, (float) ($_POST['unit_price'] ?? 0));
    $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'Pending';
    $orderType = in_array($_POST['order_type'] ?? '', $orderTypes, true) ? $_POST['order_type'] : 'Tempahan';
    $values = [
        trim($_POST['customer_name'] ?? ''),
        trim($_POST['phone'] ?? ''),
        $orderType,
        $_POST['pickup_date'] ?? date('Y-m-d'),
        $qty,
        $unitPrice,
        $qty * $unitPrice,
        $status,
        trim($_POST['remarks'] ?? ''),
    ];

    if ($action === 'update') {
        $stmt = db()->prepare('UPDATE orders SET customer_name = ?, phone = ?, order_type = ?, pickup_date = ?, qty = ?, unit_price = ?, total = ?, status = ?, remarks = ? WHERE id = ?');
        $stmt->execute([...$values, (int) ($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = 'Order updated.';
        redirect('orders.php');
    }

    $stmt = db()->prepare('INSERT INTO orders (customer_name, phone, order_type, pickup_date, qty, unit_price, total, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute($values);
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
    $stmt = db()->prepare('SELECT * FROM orders WHERE status = ? ORDER BY pickup_date ASC, id DESC');
    $stmt->execute([$filterStatus]);
    $rows = $stmt->fetchAll();
} else {
    $rows = db()->query('SELECT * FROM orders ORDER BY pickup_date ASC, id DESC')->fetchAll();
}

$pageTitle = 'Tempahan';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <h1 class="h5 mb-3"><?= $editRow ? 'Edit Order' : 'Add Order' ?></h1>
            <form method="post" data-calc-total>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
                <?php if ($editRow): ?><input type="hidden" name="id" value="<?= h((string) $editRow['id']) ?>"><?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Customer Name</label>
                    <input class="form-control" name="customer_name" value="<?= h($editRow['customer_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input class="form-control" name="phone" inputmode="tel" value="<?= h($editRow['phone'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="order_type" required>
                        <?php foreach ($orderTypes as $type): ?>
                            <option value="<?= h($type) ?>" <?= (($editRow['order_type'] ?? 'Tempahan') === $type) ? 'selected' : '' ?>><?= h($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Pickup Date</label>
                    <input class="form-control" type="date" name="pickup_date" value="<?= h($editRow['pickup_date'] ?? date('Y-m-d')) ?>" required>
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
                    <input class="form-control" type="number" step="0.01" data-total readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= h($status) ?>" <?= (($editRow['status'] ?? '') === $status) ? 'selected' : '' ?>><?= h($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" name="remarks" rows="3"><?= h($editRow['remarks'] ?? '') ?></textarea>
                </div>
                <button class="btn btn-primary w-100" type="submit"><?= $editRow ? 'Update Order' : 'Save Order' ?></button>
                <?php if ($editRow): ?><a class="btn btn-outline-secondary w-100 mt-2" href="orders.php">Cancel Edit</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="table-card">
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0">Order List</h2>
                <form class="d-flex gap-2" method="get">
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= h($status) ?>" <?= $filterStatus === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle datatable">
                    <thead><tr><th>Customer</th><th>Phone</th><th>Type</th><th>Pickup</th><th>Qty</th><th>Total</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= h($row['customer_name']) ?></td>
                            <td><?= h($row['phone']) ?></td>
                            <td><?= h($row['order_type'] ?? 'Tempahan') ?></td>
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
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
