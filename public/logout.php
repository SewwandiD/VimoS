<?php
// public/logout.php

require_once __DIR__ . '/../includes/auth.php';

// Destroy all session data on the server
session_destroy();

// Send the host back to the login page
header('Location: login.php');
exit;
