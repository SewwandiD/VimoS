<?php
// public/delete_request.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Every host action must enforce login first
require_login();

// 2. Only allow deletion via POST (never via a GET link)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

// 3. Read the request_id to delete and where to redirect back ('dashboard.php' or 'requests.php')
$request_id = (int) ($_POST['request_id'] ?? 0);
$redirect = ($_POST['from'] ?? '') === 'requests' ? 'visit_requests.php' : 'dashboard.php';

if ($request_id > 0) {
    // 4. Delete ONLY if owned by this host AND status is 'pending' or 'rejected'
    //    Approved, checked_in, and checked_out rows are never deleted (audit trail)
    $stmt = $pdo->prepare("
        DELETE FROM visit_requests
        WHERE request_id = ?
          AND host_id = ?
          AND status IN ('pending', 'rejected')
    ");
    $stmt->execute([$request_id, $_SESSION['host_id']]);
}

// 5. Return to the page the host clicked Delete from
header('Location: ' . $redirect);
exit;