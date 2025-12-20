<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$result = ['tests' => []];

// Test 1: Load config
$configFile = __DIR__ . '/../crm/config/database.php';
if (is_file($configFile)) {
    require_once $configFile;
    $result['tests']['config_loaded'] = 'YES';
    $result['config'] = [
        'DB_SERVER' => DB_SERVER,
        'DB_DATABASE' => DB_DATABASE,
        'DB_SERVER_USERNAME' => DB_SERVER_USERNAME
    ];
} else {
    $result['tests']['config_loaded'] = 'NO - file not found: ' . $configFile;
}

// Test 2: Try connection
try {
    $mysqli = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    $result['tests']['mysql_connection'] = 'SUCCESS';
    $result['server_info'] = $mysqli->server_info;
    $mysqli->close();
} catch (Exception $e) {
    $result['tests']['mysql_connection'] = 'FAILED';
    $result['error'] = $e->getMessage();
    $result['error_code'] = $e->getCode();
}

echo json_encode($result, JSON_PRETTY_PRINT);
