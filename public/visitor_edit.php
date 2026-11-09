<?php
// public/visitor_edit.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Only logged-in hosts may edit visitor records
require_login();

// 2. Read visitor_id from GET (?id=...) on first load, or from POST on form submit
$visitor_id = (int) ($_GET['id'] ?? $_POST['visitor_id'] ?? 0);

// 3. Fetch the visitor's existing details from the database
$stmt = $pdo->prepare("SELECT visitor_id, name, nic, phone, email FROM visitors WHERE visitor_id = ?");
$stmt->execute([$visitor_id]);
$visitor = $stmt->fetch();

// If no visitor exists with that ID, return to the directory
if (!$visitor) {
    header('Location: visitors.php');
    exit;
}

$error_message = '';

// 4. Process form submission when HTTP method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validate using the same rules as request.php and visitors.php
    if (empty($name) || empty($email)) {
        $error_message = 'Name and Email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif ($phone !== '' && !preg_match('/^0[0-9]{9}$/', $phone)) {
        $error_message = 'Phone number must be 10 digits starting with 0.';
    } else {
        try {
            // Update only name, phone, and email for this visitor_id
            $updateStmt = $pdo->prepare(
                "UPDATE visitors SET name = ?, phone = ?, email = ? WHERE visitor_id = ?"
            );
            $updateStmt->execute([
                $name,
                $phone !== '' ? $phone : null,
                $email,
                $visitor_id
            ]);

            // Redirect back to the directory with a success flag (Post-Redirect-Get)
            header('Location: visitors.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            $error_message = 'A database error occurred while updating the visitor.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Visitor - VimoS</title>
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

        .edit-card {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            padding: 28px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .edit-card h2 {
            margin-top: 0;
            font-size: 1.25rem;
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

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #444;
        }

        input {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.95rem;
        }

        input[readonly] {
            background-color: #e9ecef;
            color: #666;
            cursor: not-allowed;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-save {
            flex: 1;
            background: #0066cc;
            color: #fff;
            border: none;
            padding: 10px;
            font-size: 0.95rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-cancel {
            flex: 1;
            background: #e2e6ea;
            color: #333;
            text-decoration: none;
            padding: 10px;
            font-size: 0.95rem;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <?php require_once __DIR__ . '/../includes/nav.php'; ?>

    <main class="edit-card">
        <h2>✏️ Edit Visitor Details</h2>

        <?php if ($error_message): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form action="visitor_edit.php?id=<?= (int) $visitor['visitor_id'] ?>" method="POST">
            <input type="hidden" name="visitor_id" value="<?= (int) $visitor['visitor_id'] ?>">

            <div class="form-group">
                <label for="nic">NIC (Read-Only)</label>
                <!-- NIC is displayed for reference only and is not modified in the UPDATE query -->
                <input type="text" id="nic" value="<?= htmlspecialchars($visitor['nic']) ?>" readonly>
            </div>

            <div class="form-group">
                <label for="name">Full Name *</label>
                <!-- Uses $_POST['name'] if validation failed, otherwise falls back to $visitor['name'] from DB -->
                <input type="text" id="name" name="name" required
                    value="<?= htmlspecialchars($_POST['name'] ?? $visitor['name']) ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number (optional)</label>
                <input type="tel" id="phone" name="phone" maxlength="10" pattern="0[0-9]{9}" placeholder="0771234567"
                    value="<?= htmlspecialchars($_POST['phone'] ?? $visitor['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required
                    value="<?= htmlspecialchars($_POST['email'] ?? $visitor['email']) ?>">
            </div>

            <div class="actions">
                <button type="submit" class="btn-save">Save Changes</button>
                <a href="visitors.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </main>

</body>

</html>