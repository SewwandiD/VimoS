<?php
// public/requests.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Only logged-in hosts can view this page
require_login();

$host_id = $_SESSION['host_id'];
// 2. Read optional search keyword from the URL (?q=...)
$search = trim($_GET['q'] ?? '');

// 3. Fetch this host's visit requests, optionally filtered by visitor name, NIC, or purpose
if ($search !== '') {
    // Attach SQL % wildcards in PHP so we can bind safely to LIKE ?
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("
        SELECT vr.request_id, v.name, v.nic, v.email, vr.purpose, vr.expected_visit_at, vr.status
        FROM visit_requests vr
        JOIN visitors v ON v.visitor_id = vr.visitor_id
        WHERE vr.host_id = ?
          AND (v.name LIKE ? OR v.nic LIKE ? OR vr.purpose LIKE ?)
        ORDER BY vr.requested_at DESC
    ");
    $stmt->execute([$host_id, $like, $like, $like]);
} else {
    $stmt = $pdo->prepare("
        SELECT vr.request_id, v.name, v.nic, v.email, vr.purpose, vr.expected_visit_at, vr.status
        FROM visit_requests vr
        JOIN visitors v ON v.visitor_id = vr.visitor_id
        WHERE vr.host_id = ?
        ORDER BY vr.requested_at DESC
    ");
    $stmt->execute([$host_id]);
}

$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Requests - VimoS</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            color: #333;
        }

        .topbar {
            background: #0066cc;
            color: #fff;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            margin: 0;
            font-size: 1.2rem;
        }

        .topbar nav a {
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            margin-left: 12px;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .topbar nav a:hover {
            background: #0052a3;
        }

        .container {
            max-width: 1000px;
            margin: 24px auto;
            padding: 0 20px;
        }

        .card {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .card h2 {
            margin-top: 0;
            font-size: 1.15rem;
        }

        .search-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
        }

        .search-bar input {
            flex: 1;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .search-bar button,
        .search-bar a {
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-search {
            background: #0066cc;
            color: #fff;
            font-weight: bold;
        }

        .btn-clear {
            background: #e2e6ea;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th,
        td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f4f6f9;
            color: #444;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
        }

        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-checked_in {
            background: #cce5ff;
            color: #004085;
        }

        .badge-checked_out {
            background: #e2e3e5;
            color: #383d41;
        }

        .btn-approve,
        .btn-reject,
        .btn-delete {
            border: none;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.82rem;
        }

        .btn-approve {
            background: #28a745;
        }

        .btn-reject {
            background: #dc3545;
        }

        .btn-delete {
            background: #6c757d;
        }

        .locked {
            color: #888;
            font-size: 0.82rem;
            font-style: italic;
        }

        .empty {
            color: #888;
            font-style: italic;
        }
    </style>
</head>

<body>

    <?php require_once __DIR__ . '/../includes/nav.php'; ?>

    <main class="container">
        <section class="card">
            <h2>All Visit Requests for <?= htmlspecialchars($_SESSION['host_name']) ?> (<?= count($requests) ?>)</h2>

            <!-- Search form uses GET so the search term appears in ?q=... -->
            <form action="visit_requests.php" method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Search by visitor name, NIC, or purpose..."
                    value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="visit_requests.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (empty($requests)): ?>
                <p class="empty">No matching visit requests found.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Visitor</th>
                        <th>NIC</th>
                        <th>Purpose</th>
                        <th>Expected</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= htmlspecialchars($r['nic']) ?></td>
                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                            <td><?= htmlspecialchars($r['expected_visit_at']) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($r['status']) ?>">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                            </td>
                            <td>
                                <!-- Approve/Reject only if status is pending -->
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form action="update_status.php" method="POST" style="display:inline">
                                        <input type="hidden" name="request_id" value="<?= (int) $r['request_id'] ?>">
                                        <input type="hidden" name="from" value="requests">
                                        <button name="action" value="approve" class="btn-approve">Approve</button>
                                        <button name="action" value="reject" class="btn-reject"
                                            onclick="return confirm('Reject this request?')">Reject</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete ONLY allowed for pending or rejected requests -->
                                <?php if ($r['status'] === 'pending' || $r['status'] === 'rejected'): ?>
                                    <form action="delete_request.php" method="POST" style="display:inline">
                                        <input type="hidden" name="request_id" value="<?= (int) $r['request_id'] ?>">
                                        <input type="hidden" name="from" value="requests">
                                        <button type="submit" class="btn-delete"
                                            onclick="return confirm('Permanently delete this request?')">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <!-- Approved, checked_in, and checked_out are permanent audit trail -->
                                    <span class="locked">Audit trail (locked)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </section>
    </main>

</body>

</html>