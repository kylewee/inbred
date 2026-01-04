<?php
/**
 * Make Outgoing Call API
 * Initiates a recorded outbound call via SignalWire
 *
 * POST /api/make-call.php
 * Body: { "to": "+19045551234" }
 */

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$to = $input['to'] ?? '';           // Target number (who you're calling)
$from = $input['from'] ?? '';       // SignalWire number (caller ID)
$agent = $input['agent'] ?? '';     // Your phone number (rings you first)
$notes = $input['notes'] ?? '';

// Validate
if (empty($to)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "to" phone number']);
    exit;
}

// Clean phone numbers
$to = preg_replace('/[^0-9+]/', '', $to);
if (!str_starts_with($to, '+')) {
    $to = '+1' . $to;
}

// Clean agent number (your phone that rings first)
if ($agent) {
    $agent = preg_replace('/[^0-9+]/', '', $agent);
    if (!str_starts_with($agent, '+')) {
        $agent = '+1' . $agent;
    }
}

// Rate limiting - prevent calling same number within 5 minutes
$rateLimitFile = __DIR__ . '/../data/call_rate_limits.json';
$rateLimits = [];
if (file_exists($rateLimitFile)) {
    $rateLimits = json_decode(file_get_contents($rateLimitFile), true) ?? [];
}
$lastCall = $rateLimits[$to] ?? 0;
if (time() - $lastCall < 300) {  // 5 minute cooldown
    http_response_code(429);
    $remaining = 300 - (time() - $lastCall);
    echo json_encode(['error' => "Rate limited. Wait {$remaining} seconds before calling this number again."]);
    exit;
}
$rateLimits[$to] = time();
// Clean old entries (older than 10 min)
$rateLimits = array_filter($rateLimits, fn($t) => time() - $t < 600);
file_put_contents($rateLimitFile, json_encode($rateLimits));

// SignalWire config - hardcoded
$projectId = 'ce4806cb-ccb0-41e9-8bf1-7ea59536adfd';
$space = 'mobilemechanic.signalwire.com';
$apiToken = 'PT1c8cf22d1446d4d9daaf580a26ad92729e48a4a33beb769a';
$defaultFrom = '+19047066669';

if (empty($projectId) || empty($apiToken) || empty($space)) {
    http_response_code(500);
    echo json_encode(['error' => 'SignalWire not configured']);
    exit;
}

// Use default from number if not specified
if (empty($from)) {
    $from = $defaultFrom;
}

// Default agent number (your cell phone)
$defaultAgent = '+19042175152';  // Kyle's number
if (empty($agent)) {
    $agent = $defaultAgent;
}

// Build callback URL - passes target number to dial after you answer
$host = $_SERVER['HTTP_HOST'] ?? 'ezlead4u.com';
$callbackUrl = "https://{$host}/voice/outgoing.php?to=" . urlencode($to) . "&from=" . urlencode($from);

// Log the attempt
@file_put_contents(__DIR__ . '/../voice/voice.log', json_encode([
    'ts' => date('c'),
    'event' => 'outgoing_call_initiated',
    'agent' => $agent,
    'target' => $to,
    'from' => $from,
    'notes' => $notes,
    'provider' => 'signalwire',
]) . "\n", FILE_APPEND);

// Make the call via SignalWire REST API
// Calls YOUR phone first, then connects to target when you answer
$url = "https://{$space}/api/laml/2010-04-01/Accounts/{$projectId}/Calls.json";

$postData = [
    'To' => $agent,      // Call YOU first
    'From' => $from,     // Shows as SignalWire number
    'Url' => $callbackUrl,  // When you answer, dials the target
    'Record' => 'true',
    'RecordingStatusCallback' => "https://{$host}/voice/recording_processor.php?type=outgoing",
    'RecordingStatusCallbackMethod' => 'POST',
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_USERPWD => "{$projectId}:{$apiToken}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: ' . $error]);
    exit;
}

$result = json_decode($response, true);

// Log result
@file_put_contents(__DIR__ . '/../voice/voice.log', json_encode([
    'ts' => date('c'),
    'event' => 'outgoing_call_response',
    'http_code' => $httpCode,
    'result' => $result,
]) . "\n", FILE_APPEND);

if ($httpCode >= 200 && $httpCode < 300) {
    // Save to calls database
    $callsDb = __DIR__ . '/../data/calls.db';
    try {
        $db = new SQLite3($callsDb);
        $db->exec("CREATE TABLE IF NOT EXISTS outgoing_calls (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            call_sid TEXT,
            to_number TEXT,
            from_number TEXT,
            notes TEXT,
            status TEXT DEFAULT 'initiated',
            duration INTEGER,
            recording_url TEXT,
            recording_sid TEXT,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        )");

        $stmt = $db->prepare("INSERT INTO outgoing_calls (call_sid, to_number, from_number, notes, status) VALUES (:sid, :to, :from, :notes, 'initiated')");
        $stmt->bindValue(':sid', $result['sid'] ?? '');
        $stmt->bindValue(':to', $to);
        $stmt->bindValue(':from', $from);
        $stmt->bindValue(':notes', $notes);
        $stmt->execute();
    } catch (Exception $e) {
        // Log but don't fail
        error_log("Failed to save call to DB: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'call_sid' => $result['sid'] ?? '',
        'status' => $result['status'] ?? '',
        'to' => $to,
        'from' => $from,
    ]);
} else {
    http_response_code($httpCode ?: 500);
    echo json_encode([
        'error' => $result['message'] ?? 'Failed to initiate call',
        'code' => $result['code'] ?? $httpCode,
        'details' => $result,
    ]);
}
