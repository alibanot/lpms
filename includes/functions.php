<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|string|null $value): string
{
    return 'RM ' . number_format((float) $value, 2);
}

function number_plain(float|int|string|null $value): string
{
    return number_format((float) $value, 0);
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function get_setting(string $key, mixed $default = null): mixed
{
    $stmt = db()->query('SELECT business_name, default_price FROM settings ORDER BY id ASC LIMIT 1');
    $settings = $stmt->fetch() ?: [];
    return $settings[$key] ?? $default;
}

function current_business_name(): string
{
    return (string) get_setting('business_name', 'Lempeng Pisang');
}

function current_default_price(): float
{
    return (float) get_setting('default_price', 1.50);
}

function active_class(string $page): string
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return $current === $page ? 'active' : '';
}

function report_date_range(string $filter, ?string $start = null, ?string $end = null): array
{
    $today = new DateTimeImmutable('today');

    return match ($filter) {
        'yesterday' => [$today->modify('-1 day')->format('Y-m-d'), $today->modify('-1 day')->format('Y-m-d')],
        'this_week' => [$today->modify('monday this week')->format('Y-m-d'), $today->modify('sunday this week')->format('Y-m-d')],
        'this_month' => [$today->format('Y-m-01'), $today->format('Y-m-t')],
        'custom' => [$start ?: $today->format('Y-m-d'), $end ?: $today->format('Y-m-d')],
        default => [$today->format('Y-m-d'), $today->format('Y-m-d')],
    };
}
