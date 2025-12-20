<?php
header('Content-Type: application/json');

require_once __DIR__ . '/api/.env.local.php';

$result = [
    'openai_defined' => defined('OPENAI_API_KEY'),
    'openai_getenv' => getenv('OPENAI_API_KEY') ? true : false,
    'openai_value' => defined('OPENAI_API_KEY') ? substr(OPENAI_API_KEY, 0, 10) . '...' : 'NOT DEFINED',
    'twilio_sid_defined' => defined('TWILIO_ACCOUNT_SID'),
    'twilio_token_defined' => defined('TWILIO_AUTH_TOKEN')
];

echo json_encode($result, JSON_PRETTY_PRINT);
?>