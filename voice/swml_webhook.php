<?php
/**
 * SignalWire SWML Webhook Handler
 * Processes call state events and fetches recordings via API
 */

require_once __DIR__ . '/../api/.env.local.php';

$logFile = __DIR__ . '/swml_webhook.log';

// Get raw JSON input
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// Log incoming event
$entry = [
    'ts' => date('c'),
    'event_type' => $data['event_type'] ?? 'unknown',
    'call_id' => $data['params']['call_id'] ?? '',
    'call_state' => $data['params']['call_state'] ?? '',
    'direction' => $data['params']['direction'] ?? '',
    'from' => $data['params']['device']['params']['from_number'] ?? '',
    'to' => $data['params']['device']['params']['to_number'] ?? ''
];
@file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);

// Only process inbound calls that have ended
if (($data['event_type'] ?? '') === 'calling.call.state'
    && ($data['params']['call_state'] ?? '') === 'ended'
    && ($data['params']['direction'] ?? '') === 'inbound') {

    $callId = $data['params']['call_id'] ?? '';
    $from = $data['params']['device']['params']['from_number'] ?? '';
    $to = $data['params']['device']['params']['to_number'] ?? '';

    // Wait a moment for recording to be processed
    sleep(3);

    // Fetch recordings for this call via SignalWire API
    $auth = base64_encode(SIGNALWIRE_PROJECT_ID . ':' . SIGNALWIRE_API_TOKEN);
    $url = "https://" . SIGNALWIRE_SPACE . "/api/fabric/calls/{$callId}/recordings";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic {$auth}",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $recordings = json_decode($response, true);

    $entry['recordings_fetch'] = [
        'url' => $url,
        'http_code' => $httpCode,
        'response' => $recordings
    ];

    // If we got recordings, process the first one
    if (!empty($recordings['data'][0]['url'])) {
        $recordingUrl = $recordings['data'][0]['url'];

        // Forward to recording_callback.php with the data it expects
        $postData = [
            'CallSid' => $callId,
            'RecordingSid' => $recordings['data'][0]['id'] ?? $callId,
            'RecordingUrl' => $recordingUrl,
            'RecordingDuration' => $recordings['data'][0]['duration'] ?? 0,
            'From' => $from,
            'To' => $to,
            'CallStatus' => 'completed'
        ];

        // Call recording_callback.php internally
        $ch = curl_init('https://mechanicstaugustine.com/voice/recording_callback.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $result = curl_exec($ch);
        curl_close($ch);

        $entry['forwarded_to_callback'] = true;
        $entry['callback_result'] = $result;
    }

    @file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);
}

// Return success
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
