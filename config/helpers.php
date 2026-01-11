<?php

// --- NAVIGATION FUNCTIONS ---

if (!function_exists('redirect')) {
    function redirect($url) {
        // If the URL starts with '/', prepend the project folder (BASE_PATH)
        // This fixes the "Not Found" errors automatically
        if (defined('BASE_PATH') && strpos($url, '/') === 0) {
            $url = BASE_PATH . $url;
        }
        header("Location: $url");
        exit();
    }
}

// --- FLASH MESSAGE FUNCTIONS ---

if (!function_exists('set_flash')) {
    function set_flash($key, $message) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('flash')) {
    function flash($key, $message) {
        set_flash($key, $message);
    }
}

if (!function_exists('get_flash')) {
    function get_flash($key) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }
}

// --- SECURITY & USER FUNCTIONS ---

if (!function_exists('safe_output')) {
    function safe_output($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('current_user')) {
    function current_user() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['user'] ?? null;
    }
}

// --- AUTHENTICATION CHECKS (Restored) ---

if (!function_exists('require_role')) {
    function require_role($allowed_roles) {
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        
        $user = current_user();
        
        // Check if user is logged in AND has the correct role
        if (!$user || !in_array($user['role'], $allowed_roles)) {
            set_flash('error', 'You must log in to access this page.');
            
            // Redirect to login (Smart redirect will handle the path)
            redirect('/auth/login.php');
        }
    }
}

// --- DATABASE LOGGING ---

if (!function_exists('log_action')) {
    function log_action($pdo, $user_id, $action) {
        try {
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $action]);
        } catch (Exception $e) {
            // Silently fail if logs table doesn't exist so the app doesn't crash
        }
    }
}
?>