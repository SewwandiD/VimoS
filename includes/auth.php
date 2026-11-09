<?php
// includes/auth.php

// Start a PHP session if one has not already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a host is currently logged in.
 * Returns true if logged in, false otherwise.
 */
function is_host_logged_in(): bool
{
    return isset($_SESSION['host_id']);
}

/**
 * Enforces that only an authenticated host can access the page.
 * Every host page calls this at the very top.
 */
function require_login(): void
{
    // Prevent the browser from caching protected pages (stops Back-button viewing after logout)
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (!is_host_logged_in()) {
        // Redirect the unauthorized browser to the host login page
        header('Location: login.php');
        // Stop executing the rest of the protected page immediately
        exit;
    }
}

/**
 * Alias kept so existing calls to require_host_login() also use require_login().
 */
function require_host_login(): void
{
    require_login();
}