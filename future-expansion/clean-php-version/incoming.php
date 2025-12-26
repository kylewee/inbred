<?php
declare(strict_types=1);
header('Content-Type: application/json');

// Load shared config (CRM/Twilio values live here)
$env = __DIR__ . '/../api/.env.local.php';
if (is_file($env)) {
  require $env;
}

$host = $_SERVER['HTTP_HOST'] ?? 'mechanicstaugustine.com';
$callback = 'https://' . $host . '/voice/recording_callback.php';

// Log webhook hit for diagnostics
try {
  $logFile = __DIR__ . '/voice.log';
  $entry = [
    'ts' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'event' => 'incoming_call',
    'from' => $_POST['From'] ?? $_GET['From'] ?? null,
    'to' => $_POST['To'] ?? $_GET['To'] ?? null,
    'call_sid' => $_POST['CallSid'] ?? $_GET['CallSid'] ?? null,
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'raw_post' => file_get_contents('php://input'),
    'post_vars' => $_POST,
    'get_vars' => $_GET,
    'headers' => getallheaders(),
  ];
  @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
} catch (\Throwable $e) {
  // ignore logging errors
}

$swml = [
    "version" => "1.0.0",
    "sections" => [
        "main" => [
            [
                "play" => [
                    "url" => "https://mechanicstaugustine.com/voice/greeting.mp3"
                ]
            ],
            [
                "dial" => [
                    "to" => (defined('TWILIO_FORWARD_TO') ? TWILIO_FORWARD_TO : '+19046634789'),
                    "timeout" => 30,
                    "record" => true,
                    "recording_status_callback" => $callback
                ]
            ]
        ]
    ]
];

echo json_encode($swml, JSON_PRETTY_PRINT);