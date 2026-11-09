<?php
// public/visitors.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Only logged-in hosts may access the Visitor Directory
require_login();

$success_message = '';
$error_message = '';

// Show confirmation banner if redirected after adding, updating, or deleting
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $success_message = 'Visitor added to the directory successfully.';
    } elseif ($_GET['msg'] === 'updated') {
        $success_message = 'Visitor details updated successfully.';
    } elseif ($_GET['msg'] === 'deleted') {
        $success_message = 'Visitor deleted from the directory.';
    }
}

// 2. Handle POST actions (Add Visitor in Task 7a; Delete Visitor will be added in Task 7b)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action = $_POST['form_action'] ?? '';

    if ($form_action === 'add_visitor') {
        $name = trim($_POST['name'] ?? '');
        $nic = strtoupper(trim($_POST['nic'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validate using the exact same rules as request.php
        if (empty($name) || empty($nic) || empty($email)) {
            $error_message = 'Name, NIC, and Email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif (!preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/', $nic)) {
            $error_message = 'Invalid NIC format. Enter 9 digits + V/X or 12 digits.';
        } elseif ($phone !== '' && !preg_match('/^0[0-9]{9}$/', $phone)) {
            $error_message = 'Phone number must be 10 digits starting with 0.';
        } else {
            try {
                // Check if a visitor with this NIC already exists
                $checkStmt = $pdo->prepare("SELECT visitor_id FROM visitors WHERE nic = ?");
                $checkStmt->execute([$nic]);

                if ($checkStmt->fetch()) {
                    $error_message = 'A visitor with this NIC already exists in the directory.';
                } else {
                    // Insert the new visitor
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO visitors (name, nic, phone, email) VALUES (?, ?, ?, ?)"
                    );
                    $insertStmt->execute([$name, $nic, $phone !== '' ? $phone : null, $email]);

                    // Redirect (Post-Redirect-Get) so refreshing doesn't re-submit
                    header('Location: visitors.php?msg=added');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = 'A database error occurred while adding the visitor.';
            }
        }
    } elseif ($form_action === 'delete_visitor') {
        $visitor_id = (int) ($_POST['visitor_id'] ?? 0);

        if ($visitor_id > 0) {
            try {
                // Step A: Check in PHP first if this visitor has any visit requests
                // (Never rely on a raw MySQL foreign key error for normal business rules)
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM visit_requests WHERE visitor_id = ?");
                $countStmt->execute([$visitor_id]);
                $request_count = (int) $countStmt->fetchColumn();

                if ($request_count > 0) {
                    $error_message = "Cannot delete this visitor because they have $request_count visit request(s) on record.";
                } else {
                    // Step B: Safe to delete — this visitor has 0 visit requests
                    $delStmt = $pdo->prepare("DELETE FROM visitors WHERE visitor_id = ?");
                    $delStmt->execute([$visitor_id]);

                    header('Location: visitors.php?msg=deleted');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = 'A database error occurred while deleting the visitor.';
            }
        }
    }
}

// 3. Read & Search Visitors (by Name or NIC), including how many visit requests each visitor has
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("
        SELECT v.visitor_id, v.name, v.nic, v.phone, v.email,
               COUNT(vr.request_id) AS request_count
        FROM visitors v
        LEFT JOIN visit_requests vr ON vr.visitor_id = v.visitor_id
        WHERE v.name LIKE ? OR v.nic LIKE ?
        GROUP BY v.visitor_id
        ORDER BY v.name ASC
    ");
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("
        SELECT v.visitor_id, v.name, v.nic, v.phone, v.email,
               COUNT(vr.request_id) AS request_count
        FROM visitors v
        LEFT JOIN visit_requests vr ON vr.visitor_id = v.visitor_id
        GROUP BY v.visitor_id
        ORDER BY v.name ASC
    ");
}

$visitors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Directory - VimoS</title>
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
            margin-bottom: 24px;
        }

        .card h2 {
            margin-top: 0;
            font-size: 1.15rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #444;
        }

        .form-group input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #0066cc;
            color: #fff;
            border: none;
            padding: 9px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.9rem;
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

        .btn-clear {
            background: #e2e6ea;
            color: #333;
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
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

        .btn-edit {
            background: #0066cc;
            color: #fff;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.82rem;
            display: inline-block;
        }

        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.82rem;
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

        <?php if ($success_message): ?>
            <div class="alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- CREATE: Add a New Visitor Card -->
        <section class="card">
            <h2>➕ Add New Visitor</h2>
            <form action="visitors.php" method="POST" class="form-grid">
                <input type="hidden" name="form_action" value="add_visitor">

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Kasun Silva"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="nic">NIC *</label>
                    <input type="text" id="nic" name="nic" required maxlength="12" pattern="([0-9]{9}[vVxX]|[0-9]{12})"
                        placeholder="123456789V or 12 digits" value="<?= htmlspecialchars($_POST['nic'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone (optional)</label>
                    <input type="tel" id="phone" name="phone" maxlength="10" pattern="0[0-9]{9}"
                        placeholder="0771234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required placeholder="kasun@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div>
                    <button type="submit" class="btn-primary">Add Visitor</button>
                </div>
            </form>
        </section>

        <!-- READ & SEARCH: Visitor Directory Table -->
        <section class="card">
            <h2>📋 Registered Visitors (
                <?= count($visitors) ?>)
            </h2>

            <form action="visitors.php" method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Search by visitor name or NIC..."
                    value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="visitors.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>

            <?php if (empty($visitors)): ?>
                <p class="empty">No visitors found.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>NIC</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Visits</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($visitors as $v): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($v['name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($v['nic']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($v['phone'] ?? '—') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($v['email']) ?>
                            </td>
                            <td>
                                <?= (int) $v['request_count'] ?>
                            </td>
                            <td>
                                <!-- Edit link passes visitor_id in URL to visitor_edit.php -->
                                <a href="visitor_edit.php?id=<?= (int) $v['visitor_id'] ?>" class="btn-edit">Edit</a>

                                <!-- Delete form submits via POST with confirmation -->
                                <form action="visitors.php" method="POST" style="display:inline">
                                    <input type="hidden" name="form_action" value="delete_visitor">
                                    <input type="hidden" name="visitor_id" value="<?= (int) $v['visitor_id'] ?>">
                                    <button type="submit" class="btn-delete"
                                        onclick="return confirm('Delete this visitor from the directory?')">Delete</button>
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