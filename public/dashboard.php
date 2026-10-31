<?php
// public/dashboard.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only logged-in hosts may see this page
require_host_login();

$host_id = $_SESSION['host_id'];

// Fetch ONLY this host's pending requests, joined with visitor details
$stmt = $pdo->prepare("
    SELECT vr.request_id, v.name, v.nic, v.email, vr.purpose, vr.expected_visit_at
    FROM visit_requests vr
    JOIN visitors v ON v.visitor_id = vr.visitor_id
    WHERE vr.host_id = ? AND vr.status = 'pending'
    ORDER BY vr.expected_visit_at ASC
");
$stmt->execute([$host_id]);
$pending = $stmt->fetchAll();

// Count this host's requests per status for the summary cards
$stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM visit_requests WHERE host_id = ? GROUP BY status");
$stmt->execute([$host_id]);
$counts = array_column($stmt->fetchAll(), 'total', 'status'); // e.g. ['pending' => 2, 'approved' => 1]
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Dashboard - VimoS</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            color: #333;
        }

        /* Top bar */
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

        .topbar a {
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            border: 1px solid rgba(255, 255, 255, 0.6);
            padding: 6px 12px;
            border-radius: 4px;
        }

        .topbar a:hover {
            background: #0052a3;
        }

        .container {
            max-width: 1000px;
            margin: 24px auto;
            padding: 0 20px;
        }

        .welcome {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        /* Summary cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat {
            background: #fff;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            border-left: 4px solid #0066cc;
        }

        .stat .num {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0066cc;
        }

        .stat .label {
            font-size: 0.85rem;
            color: #888;
        }

        /* Main card */
        .card {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            margin-bottom: 24px;
        }

        .card h2 {
            margin-top: 0;
            font-size: 1.1rem;
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

        tr:hover td {
            background: #fafbfd;
        }

        .empty {
            color: #888;
            font-style: italic;
        }

        .btn-approve,
        .btn-reject {
            border: none;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .btn-approve {
            background: #28a745;
        }

        .btn-reject {
            background: #dc3545;
        }
    </style>
</head>

<body>

    <header class="topbar">
        <h1>🏢 VimoS Host Dashboard</h1>
        <a href="logout.php">Log out</a>
    </header>

    <main class="container">
        <p class="welcome">Welcome, <strong><?= htmlspecialchars($_SESSION['host_name']) ?></strong></p>

        <section class="stats">
            <div class="stat">
                <div class="num"><?= $counts['pending'] ?? 0 ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat">
                <div class="num"><?= $counts['approved'] ?? 0 ?></div>
                <div class="label">Approved</div>
            </div>
            <div class="stat">
                <div class="num"><?= $counts['checked_in'] ?? 0 ?></div>
                <div class="label">Checked In</div>
            </div>
            <div class="stat">
                <div class="num"><?= $counts['checked_out'] ?? 0 ?></div>
                <div class="label">Checked Out</div>
            </div>
        </section>

        <section class="card">
            <h2>Pending Visit Requests (<?= count($pending) ?>)</h2>

            <?php if (empty($pending)): ?>
                <p class="empty">No pending requests.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Visitor</th>
                        <th>NIC</th>
                        <th>Purpose</th>
                        <th>Expected</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($pending as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= htmlspecialchars($r['nic']) ?></td>
                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                            <td><?= htmlspecialchars($r['expected_visit_at']) ?>
                            <td>
                                <form action="update_status.php" method="POST" style="display:inline">
                                    <input type="hidden" name="request_id" value="<?= (int) $r['request_id'] ?>">
                                    <button name="action" value="approve" class="btn-approve">Approve</button>
                                    <button name="action" value="reject" class="btn-reject"
                                        onclick="return confirm('Reject this request?')">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </section>
    </main>

</body>

</html>