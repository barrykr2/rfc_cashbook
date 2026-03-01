<?php
require_once __DIR__ . '/../../includes/auth.php';

// Double check: If users exist, deny access
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount > 0) {
    header("Location: login.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (!validate_username($username)) {
        $error = "Username cannot be 'admin' or 'administrator'.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!validate_password_strength($password)) {
        $error = "Password must be 8+ chars, include uppercase, lowercase, number, and special character.";
    } else {
        // Create User
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        try {
            $stmt->execute([$username, $hash]);
            // Auto login
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['last_activity'] = time();
            header("Location: /index.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error creating user: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Run Setup</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                btn.innerText = "🙈";
            } else {
                input.type = "password";
                btn.innerText = "👁️";
            }
        }
    </script>
</head>
<body class="container-sm auth-body">
    
    <div class="card">
        <h1>Welcome! 👋</h1>
        <p class="text-muted">This is the first time you are running the app. Please create your owner account.</p>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required placeholder="e.g. Dave">
            <p class="text-muted" style="font-size: 0.8em;">Cannot be 'admin' or 'administrator'.</p>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="pwd1" required>
                <button type="button" class="toggle-password" onclick="togglePassword('pwd1', this)">👁️</button>
            </div>
            <p class="text-muted" style="font-size: 0.8em;">Min 8 chars, Upper, Lower, Number, Special.</p>

            <label>Confirm Password</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="pwd2" required>
                <button type="button" class="toggle-password" onclick="togglePassword('pwd2', this)">👁️</button>
            </div>

            <button type="submit">Create Account</button>
        </form>
    </div>
</body>
</html>