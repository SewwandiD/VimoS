<?php
// public/update_status.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_host_login();

// Only accept POST — a status change must never happen from a simple link click
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$request_id = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'approve') {
    // 32 random bytes → 64 hex characters (fits qr_token varchar(64))
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("
        UPDATE visit_requests
        SET status = 'approved', qr_token = ?
        WHERE request_id = ? AND host_id = ? AND status = 'pending'
    ");
    $stmt->execute([$token, $request_id, $_SESSION['host_id']]);

} elseif ($action === 'reject') {
    $stmt = $pdo->prepare("
        UPDATE visit_requests
        SET status = 'rejected'
        WHERE request_id = ? AND host_id = ? AND status = 'pending'
    ");
    $stmt->execute([$request_id, $_SESSION['host_id']]);
}

header('Location: dashboard.php');
exit;
