<?php
// public/login.php

// Load database connection and session/auth helpers
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// If the host is already logged in, skip the login page entirely
if (is_host_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

// Only process when form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Do NOT trim — spaces in passwords are intentional

    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        try {
            // Look up host by email only — never include password in SQL query
            $stmt = $pdo->prepare("SELECT host_id, name, password_hash FROM hosts WHERE email = ?");
            $stmt->execute([$email]);
            $host = $stmt->fetch(); // Returns one row as associative array, or false if not found

            // password_verify compares plain text against the stored bcrypt hash
            if ($host && password_verify($password, $host['password_hash'])) {

                // Prevent session fixation: generate a brand-new session ID on login
                session_regenerate_id(true);

                // Store the authenticated host's identity in the server-side session
                $_SESSION['host_id'] = $host['host_id'];
                $_SESSION['host_name'] = $host['name'];

                // Send the now-authenticated host to the dashboard
                header('Location: dashboard.php');
                exit;

            } else {
                // Same message whether email is wrong OR password is wrong (prevents enumeration)
                $error_message = 'Invalid email or password.';
            }

        } catch (PDOException $e) {
            $error_message = 'A system error occurred. Please try again later.';
            // Note: never expose $e->getMessage() to the user in production
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Login - VimoS</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .login-card {
            max-width: 400px;
            margin: 80px auto;
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

        .note {
            margin-top: 16px;
            font-size: 0.82rem;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <h1>🔐 Host Portal Login</h1>

        <?php if ($error_message): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <div class="form-group">
                <label for="email">Host Email Address</label>
                <!-- Re-fill email after a failed attempt so user doesn't retype it -->
                <input type="email" id="email" name="email" required placeholder="sarah@vimos.local"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <!-- Never re-fill the password field after a failed attempt -->
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit">Sign In</button>
        </form>

        <p class="note">Authorized hosts only. Visitors do not need an account.</p>
    </div>

</body>

</html>