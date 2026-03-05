<?php

$dbPath = __DIR__ . '/construction.db';
$sqlPath = __DIR__ . '/create_tables.sql';

echo "Building database at: $dbPath\n";

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents($sqlPath);
    $pdo->exec($sql);

    echo "Database structure created successfully.\n";

    // Insert essential application configuration
    echo "Inserting default application configuration...\n";
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO app_config (key, value) VALUES (?, ?)");
    
    // Security settings
    $stmt->execute(['max_login_attempts', '3']);
    $stmt->execute(['lockout_minutes', '10']);
    $stmt->execute(['session_timeout_minutes', '10']);
    $stmt->execute(['forbidden_usernames', 'admin,administrator,root,system']);
    
    // API Keys (with a placeholder for Google Maps)
    $stmt->execute(['google_maps_key', 'xxxxx']); // Start with an empty key

    $pdo->commit();
    echo "Default configuration inserted.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
