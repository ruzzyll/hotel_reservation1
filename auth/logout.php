<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Ensure session is started so we can destroy it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// FIX: Just use the filename. 
// The browser will look for this file in the CURRENT folder (auth).
redirect('login.php');