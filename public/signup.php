<?php
// public/signup.php

// 1. Load database config (which also defines HOST_REG_CODE) and session helpers
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// 2. If the host is already logged in, skip the sign-up page
if (is_host_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

// 3. Only process when the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';          // Do NOT trim passwords
    $confirm_password = $_POST['confirm_password'] ?? '';
    $reg_code = trim($_POST['reg_code'] ?? '');

    // Step A: Validate all inputs and check the secret registration code
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($reg_code)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif ($reg_code !== HOST_REG_CODE) {
        // Reject anyone who does not know the secret code from config/db.php
        $error_message = 'Invalid host registration code.';
    } else {
        try {
            // Step B: Check if this email is already registered in hosts
            $checkStmt = $pdo->prepare("SELECT host_id FROM hosts WHERE email = ?");
            $checkStmt->execute([$email]);

            if ($checkStmt->fetch()) {
                $error_message = 'An account with this email already exists.';
            } else {
                // Step C: Hash the password with bcrypt (never store plain text)
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Step D: Insert the new host using a prepared statement
                $insertStmt = $pdo->prepare(
                    "INSERT INTO hosts (name, email, password_hash) VALUES (?, ?, ?)"
                );
                $insertStmt->execute([$name, $email, $password_hash]);

                // Step E: Redirect to login page with a success flag (Post-Redirect-Get)
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = 'A system error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Sign Up - VimoS</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .signup-card {
            max-width: 420px;
            margin: 50px auto;
            background: #fff;
            padding: 28px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        h1 {
            margin-top: 0;
            font-size: 1.4rem;
            color: #333;
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

        button {
            width: 100%;
            background: #0066cc;
            color: #fff;
            border: none;
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 4px;
        }

        button:hover {
            background: #0052a3;
        }

        .nav-links {
            margin-top: 18px;
            font-size: 0.88rem;
            color: #555;
            text-align: center;
            line-height: 1.8;
        }

        .nav-links a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 600;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="signup-card">
        <h1>📝 Host Sign Up</h1>

        <!-- Display error banner only when $error_message is non-empty -->
        <?php if ($error_message): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">

            <div class="form-group">
                <label for="name">Full Name *</label>
                <!-- Re-fill name if validation fails so the user doesn't have to retype it -->
                <input type="text" id="name" name="name" required placeholder="e.g. John Perera"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Host Email Address *</label>
                <input type="email" id="email" name="email" required placeholder="john@vimos.local"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password * (min 6 characters)</label>
                <!-- Never re-fill password fields after a failed submit -->
                <input type="password" id="password" name="password" required minlength="6"
                    placeholder="Create a password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                    placeholder="Re-enter your password">
            </div>

            <div class="form-group">
                <label for="reg_code">Host Registration Code *</label>
                <!-- type="password" masks the secret registration code on screen while typing -->
                <input type="password" id="reg_code" name="reg_code" required
                    placeholder="Enter secret staff registration code">
            </div>

            <button type="submit">Create Host Account</button>
        </form>

        <!-- Public navigation links required by scope item #4 -->
        <div class="nav-links">
            Already have an account? <a href="login.php">Host login</a><br>
            Are you a visitor? <a href="request.php">Request a visit</a>
        </div>
    </div>

</body>

</html>