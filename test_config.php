<?php
header('Content-Type: text/plain');

$file = __DIR__ . '/quote/../crm/config/database.php';
echo "Config file: $file\n";
echo "Real path: " . realpath($file) . "\n";
echo "Exists: " . (is_file($file) ? 'YES' : 'NO') . "\n\n";

if (is_file($file)) {
    require_once $file;
    echo "DB_SERVER: " . (defined('DB_SERVER') ? DB_SERVER : 'NOT DEFINED') . "\n";
    echo "DB_SERVER_USERNAME: " . (defined('DB_SERVER_USERNAME') ? DB_SERVER_USERNAME : 'NOT DEFINED') . "\n";
    echo "DB_DATABASE: " . (defined('DB_DATABASE') ? DB_DATABASE : 'NOT DEFINED') . "\n";
    echo "DB_SERVER_PORT: " . (defined('DB_SERVER_PORT') ? DB_SERVER_PORT : 'NOT DEFINED') . "\n";
}
