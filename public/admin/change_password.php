<?php
require_once __DIR__ . '/../../includes/auth.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // 1. Verify Current Password
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (!validate_password_strength($new_password)) {
        $error = "New password must be 8+ chars, include uppercase, lowercase, number, and special character.";
    } else {
        // 2. Update Password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        $success = "Password changed successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/navbar.css">
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
<body class="container">
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    
    <div class="card container-sm" style="margin: 0 auto;">
        <h1>Change Password</h1>

        <?php if ($success): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>Current Password</label>
            <div class="password-wrapper">
                <input type="password" name="current_password" id="curr_pwd" required>
                <button type="button" class="toggle-password" onclick="togglePassword('curr_pwd', this)">👁️</button>
            </div>

            <label>New Password</label>
            <div class="password-wrapper">
                <input type="password" name="new_password" id="new_pwd" required>
                <button type="button" class="toggle-password" onclick="togglePassword('new_pwd', this)">👁️</button>
            </div>
            <p class="text-muted" style="font-size: 0.8em;">Min 8 chars, Upper, Lower, Number, Special.</p>

            <label>Confirm New Password</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="conf_pwd" required>
                <button type="button" class="toggle-password" onclick="togglePassword('conf_pwd', this)">👁️</button>
            </div>

            <button type="submit">Update Password</button>
        </form>
    </div>
</body>
</html>