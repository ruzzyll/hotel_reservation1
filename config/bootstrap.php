<?php
// 1. Define BASE_PATH first so helpers can use it if needed
if (!defined('BASE_PATH')) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? ''; 
    
    // Look for common project directories to find the root
    $common_dirs = ['/auth/', '/admin/', '/staff/', '/customer/', '/config/'];
    $base_dir = null;
    
    foreach ($common_dirs as $dir) {
        $pos = strpos($script_name, $dir);
        if ($pos !== false) {
            $base_dir = substr($script_name, 0, $pos);
            break;
        }
    }
    
    // Fallback: If currently in a root file (like index.php)
    if ($base_dir === null || $base_dir === '') {
        $base_dir = dirname($script_name);
    }
    
    // Cleanup: Normalize slashes for Windows (XAMPP) and remove trailing slashes
    $base_dir = str_replace('\\', '/', $base_dir);
    $base_dir = rtrim($base_dir, '/');
    
    // Final Safety: If empty, it's just root
    if (empty($base_dir)) {
        $base_dir = ''; 
    }
    
    define('BASE_PATH', $base_dir);
}

// 2. Start Session Safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Require dependencies
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
?>