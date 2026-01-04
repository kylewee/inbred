<?php
/**
 * Test Call Script - Triggers a call that goes directly to GPT assistant
 * Usage: php test_gpt_call.php [phone_number]
 */

require_once __DIR__ . '/config/bootstrap.php';

$to = $argv[1] ?? '+19046634789';  // Default to mechanic's cell
$from = config('phone.phone_number', '+19047066669');
$projectId = config('phone.project_id');
$apiToken = config('phone.api_token');
$space = config('phone.space');

$url = "https://{$space}/api/laml/2010-04-01/Accounts/{$projectId}/Calls.json";

echo "Calling {$to} from {$from}...\n";
echo "Will connect to GPT assistant when answered.\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_USERPWD => "{$projectId}:{$apiToken}",
    CURLOPT_POSTFIELDS => http_build_query([
        'To' => $to,
        'From' => $from,
        'Url' => 'https://mechanicstaugustine.com/voice/gpt_assistant.php',
    ]),
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP: {$http}\n";
echo "Response: {$response}\n";
