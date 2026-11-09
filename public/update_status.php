<?php
// public/update_status.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Every host page/action must enforce login first
require_login();

// 2. Only accept POST requests — never allow state changes via a GET link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

// 3. Cast request_id to integer and read the button action ('approve' or 'reject')
$request_id = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

// 4. Update status ONLY if the request belongs to the logged-in host AND is currently 'pending'
if ($action === 'approve') {
    $stmt = $pdo->prepare("
        UPDATE visit_requests
        SET status = 'approved'
        WHERE request_id = ? AND host_id = ? AND status = 'pending'
    ");
    $stmt->execute([$request_id, $_SESSION['host_id']]);

} elseif ($action === 'reject') {
    $stmt = $pdo->prepare("
        UPDATE visit_requests
        SET status = 'rejected'
        WHERE request_id = ? AND host_id = ? AND status = 'pending'
    ");
    $stmt->execute([$request_id, $_SESSION['host_id']]);
}
$redirect = ($_POST['from'] ?? '') === 'requests' ? 'visit_requests.php' : 'dashboard.php';
header('Location: ' . $redirect);
exit;