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
 * If not logged in, redirects immediately to login.php.
 */
function require_host_login(): void
{
    if (!is_host_logged_in()) {
        // Redirect the unauthorized browser to the host login page
        header('Location: login.php');
        // Stop executing the rest of the protected page
        exit;
    }

}
