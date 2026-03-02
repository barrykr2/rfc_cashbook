<?php
require_once __DIR__ . '/../includes/db_connect.php';

$message = '';
$currentKey = '';

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newKey = trim($_POST['api_key']);
    if ($newKey) {
        // INSERT OR REPLACE is a standard SQLite command to upsert
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO app_config (key, value) VALUES ('google_maps_key', ?)");
        $stmt->execute([$newKey]);
        $message = "✅ Key updated successfully! You can now delete this file.";
    }
}

// Fetch current value to verify
$stmt = $pdo->prepare("SELECT value FROM app_config WHERE key = 'google_maps_key'");
$stmt->execute();
$currentKey = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<body style="font-family: sans-serif; padding: 2rem; max-width: 600px; margin: 0 auto;">
    <h1>Google Maps Key Fixer</h1>
    
    <?php if($message): ?><p style="color: green; font-weight: bold;"><?= $message ?></p><?php endif; ?>

    <form method="post" style="background: #f4f4f9; padding: 20px; border-radius: 8px;">
        <label style="display:block; margin-bottom:10px;"><strong>Paste API Key:</strong></label>
        <input type="text" name="api_key" value="<?= htmlspecialchars($currentKey ?: '') ?>" style="width: 100%; padding: 10px; margin-bottom: 15px; box-sizing: border-box;">
        <button type="submit" style="background: #2196F3; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px;">Save to Database</button>
    </form>
    
    <p style="margin-top: 20px; color: #666;">Database Path: <code><?= realpath(__DIR__ . '/../database/construction.db') ?></code></p>
</body>
</html>