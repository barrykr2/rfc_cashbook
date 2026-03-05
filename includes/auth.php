<?php
session_start();
require_once __DIR__ . '/db_connect.php';

function get_config($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM app_config WHERE key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

function require_login() {
    global $pdo;

    // 1. Check if ANY users exist (First Run Logic)
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        // Prevent redirect loop if already on setup page
        if (basename($_SERVER['PHP_SELF']) !== 'setup_user.php') {
            header("Location: /admin/setup_user.php");
            exit;
        }
        return;
    }

    // 2. Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: /admin/login.php");
        exit;
    }

    // 3. Check Session Timeout (Auto Logout)
    $timeout_minutes = get_config('session_timeout_minutes') ?? 10;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > ($timeout_minutes * 60))) {
        session_unset();
        session_destroy();
        header("Location: /admin/login.php?timeout=1");
        exit;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

function check_lockout($ip_address) {
    global $pdo;
    $max_attempts = get_config('max_login_attempts') ?? 3;
    $lockout_minutes = get_config('lockout_minutes') ?? 10;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_time > datetime('now', '-' || ? || ' minutes')
    ");
    $stmt->execute([$ip_address, $lockout_minutes]);
    $attempts = $stmt->fetchColumn();

    return $attempts >= $max_attempts;
}

function record_failed_login($username, $ip_address) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)");
    $stmt->execute([$username, $ip_address]);
}

function validate_password_strength($password) {
    // 8 chars, upper, lower, number, special char
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    if (!preg_match('/[\W_]/', $password)) return false; // \W matches any non-word character
    return true;
}

function validate_username($username) {
    // Fetch from config, default to a safe list if not set.
    $forbidden_str = get_config('forbidden_usernames') ?? 'admin,administrator';
    $forbidden = array_map('trim', explode(',', strtolower($forbidden_str)));
    return !in_array(strtolower($username), $forbidden);
}

// If we are on a public page (not login/setup), enforce login immediately
$current_script = basename($_SERVER['PHP_SELF']);
if (!in_array($current_script, ['login.php', 'setup_user.php', 'logout.php'])) {
    require_login();
}
?>