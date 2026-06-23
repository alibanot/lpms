<?php
require_once __DIR__ . '/includes/init.php';
require_login();

if (is_post()) {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $stmt = db()->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $_SESSION['flash_error'] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $_SESSION['flash_error'] = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = 'New password confirmation does not match.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$hash, (int) $_SESSION['user_id']]);
            $_SESSION['flash_success'] = 'Password updated.';
        }

        redirect('settings.php');
    }

    $businessName = trim($_POST['business_name'] ?? 'Lempeng Pisang');
    $defaultPrice = max(0, (float) ($_POST['default_price'] ?? 0));
    $exists = (int) db()->query('SELECT COUNT(*) FROM settings')->fetchColumn();

    if ($exists) {
        $stmt = db()->prepare('UPDATE settings SET business_name = ?, default_price = ? ORDER BY id ASC LIMIT 1');
        $stmt->execute([$businessName, $defaultPrice]);
    } else {
        $stmt = db()->prepare('INSERT INTO settings (business_name, default_price) VALUES (?, ?)');
        $stmt->execute([$businessName, $defaultPrice]);
    }

    $_SESSION['flash_success'] = 'Settings updated.';
    redirect('settings.php');
}

$stmt = db()->query('SELECT * FROM settings ORDER BY id ASC LIMIT 1');
$settings = $stmt->fetch() ?: ['business_name' => 'Lempeng Pisang', 'default_price' => 1.50];

$pageTitle = 'Settings';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-12 col-lg-6 col-xl-5">
        <div class="form-card">
            <h1 class="h5 mb-3">Business Settings</h1>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Business Name</label>
                    <input class="form-control" name="business_name" value="<?= h($settings['business_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Default Piece Price</label>
                    <input class="form-control" type="number" name="default_price" min="0" step="0.01" value="<?= h((string) $settings['default_price']) ?>" required>
                </div>
                <button class="btn btn-primary" type="submit">Save Settings</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-6 col-xl-5">
        <div class="form-card">
            <h2 class="h5 mb-3">Change Password</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input class="form-control" type="password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input class="form-control" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input class="form-control" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="btn btn-outline-primary" type="submit">Update Password</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
