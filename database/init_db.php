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
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
