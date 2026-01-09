<?php

// BASE_PATH will be auto-detected in bootstrap.php
// Don't define it here to allow bootstrap.php to detect the actual path

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function redirect(string $path): void
{
    // Prefix project base path for relative URLs
    if (isset($path[0]) && $path[0] === '/') {
        $path = BASE_PATH . $path;
    }
    header("Location: {$path}");
    exit;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/auth/login.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $roles, true)) {
        redirect('/auth/login.php');
    }
}

function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function log_action(PDO $pdo, int $userId, string $action): void
{
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, ip_address) VALUES (?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->execute([$userId, $action, $ip]);
}

function safe_output(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
