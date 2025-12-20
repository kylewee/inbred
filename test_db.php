<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current file: " . __FILE__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

// Test MySQL connection
try {
    $mysqli = new mysqli('localhost', 'kylewee', 'rainonin', 'rukovoditel');
    echo "✓ Connected to MySQL via localhost (Unix socket)\n";
    $mysqli->close();
} catch (Exception $e) {
    echo "✗ localhost failed: " . $e->getMessage() . "\n";
}

try {
    $mysqli = new mysqli('127.0.0.1', 'kylewee', 'rainonin', 'rukovoditel', 3306);
    echo "✓ Connected to MySQL via 127.0.0.1 (TCP)\n";
    $mysqli->close();
} catch (Exception $e) {
    echo "✗ 127.0.0.1 failed: " . $e->getMessage() . "\n";
}
