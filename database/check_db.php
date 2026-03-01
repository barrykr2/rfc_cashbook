<?php

$dbPath = __DIR__ . '/construction.db';

echo "Script initialized. Connecting to: $dbPath\n";

if (!file_exists($dbPath)) {
    die("Database file not found at: $dbPath\n");
}

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Prevent indefinite hanging if database is locked by another process (wait max 5s)
    $pdo->exec("PRAGMA busy_timeout = 5000");

    echo "Database found. Listing tables and row counts:\n";
    echo "---------------------------------------------\n";

    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo str_pad($table, 25) . ": $count rows\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
