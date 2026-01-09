<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
start_session();

// Automatically detect the base folder path
if (!defined('BASE_PATH')) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? ''; // e.g. /hotel_reservation/hotel_reservation/admin/index.php
    
    // Look for common project directories in the script path
    $common_dirs = ['/auth/', '/admin/', '/staff/', '/customer/', '/config/'];
    $base_dir = null;
    
    foreach ($common_dirs as $dir) {
        $pos = strpos($script_name, $dir);
        if ($pos !== false) {
            $base_dir = substr($script_name, 0, $pos);
            break;
        }
    }
    
    // If no common directory found (e.g., root index.php), use dirname
    if ($base_dir === null || $base_dir === '') {
        $base_dir = dirname($script_name);
        // Normalize: handle edge cases
        if ($base_dir === '.' || $base_dir === '/' || $base_dir === '\\' || empty($base_dir)) {
            $base_dir = '/hotel_reservation';
        }
    }
    
    // Ensure base_dir is not empty and normalize
    if (empty($base_dir) || $base_dir === '/') {
        $base_dir = '/hotel_reservation';
    }
    
    // Remove trailing slash and normalize slashes
    $base_dir = rtrim(str_replace('\\', '/', $base_dir), '/');
    
    define('BASE_PATH', $base_dir);
}