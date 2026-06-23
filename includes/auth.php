<?php
declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your session expired. Please log in again.';
    }

    $_SESSION['last_activity'] = time();
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['last_activity'] = time();
}

function logout_user(): void
{
    session_unset();
    session_destroy();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

