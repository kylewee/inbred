<?php
// Debug webhook - logs ALL incoming requests
$logFile = __DIR__ . '/debug_webhook.log';

$entry = [
    'ts' => date('c'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
    'get' => $_GET,
    'post' => $_POST,
    'raw_body' => file_get_contents('php://input'),
    'headers' => getallheaders()
];

file_put_contents($logFile, json_encode($entry, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Return 200 OK
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'received' => date('c')]);
