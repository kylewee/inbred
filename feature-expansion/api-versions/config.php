<?php
// API Configuration
define('DB_HOST', 'db');
define('DB_USER', 'mechanic');
define('DB_PASS', 'mechanic');
define('DB_NAME', 'mechanic');

// Twilio Configuration
define('TWILIO_SID', getenv('TWILIO_SID') ?: 'your_twilio_sid');
define('TWILIO_TOKEN', getenv('TWILIO_TOKEN') ?: 'your_twilio_token');
define('TWILIO_PHONE', getenv('TWILIO_PHONE') ?: '+19042175152');

// OpenAI Configuration
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'your_openai_key');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
function getDB()
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}
