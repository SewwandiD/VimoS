<?php
// Public/logout.php

// 1. Load auth.php so session_start() is called first (PHP must open the session before it can destroy it)
require_once __DIR__ . '/../Includes/auth.php';

// 2. Empty all session variables in PHP's current memory (removes $_SESSION['host_id'] and $_SESSION['host_name'])
$_SESSION = [];

// 3. Delete the actual session data file stored on the XAMPP server
session_destroy();

// 4. Send the browser back to the login page and stop script execution
header('Location: login.php');
exit;