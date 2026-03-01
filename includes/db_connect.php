<?php

// Define path to the SQLite database file
$dbPath = __DIR__ . '/../database/construction.db';

try {
    // Create a new PDO instance
    $pdo = new PDO("sqlite:$dbPath");
    
    // Set error mode to exception for better debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}