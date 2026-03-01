<?php
require_once __DIR__ . '/../../includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

$error = null;
if (isset($_GET['timeout'])) {
    $error = "Session timed out. Please log in again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'];

    if (check_lockout($ip)) {
        $error = "Too many failed attempts. Locked out for 10 minutes.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['last_activity'] = time();
            header("Location: /index.php");
            exit;
        } else {
            record_failed_login($username, $ip);
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
    
    <div class="card login-card">
        <h1 class="text-center">Login</h1>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="pwd" required>
                <button type="button" class="toggle-password" onclick="togglePassword('pwd', this)">👁️</button>
            </div>

            <button type="submit">Log In</button>
        </form>
    </div>
</body>
</html>