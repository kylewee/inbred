<?php
// Test script for the callback system
// Simulates SignalWire webhook requests

require_once __DIR__ . '/recording_callback.php';

// Test 1: Valid customer call
$testCall1 = [
    'CallSid' => 'test_call_001',
    'RecordingUrl' => 'https://example.com/test-recording.mp3',
    'RecordingDuration' => 45,
    'RecordingStatus' => 'completed',
    'From' => '+19045551234',
    'To' => '+19042175152'
];

echo "=== Test 1: Valid Customer Call ===\n";
echo "Sending: " . json_encode($testCall1) . "\n";

// Capture output
ob_start();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
handle_signalwire_webhook();
$output1 = ob_get_clean();
ob_end_clean();

echo "Response: " . $output1 . "\n";

// Test 2: Short test call (should be ignored)
$testCall2 = [
    'CallSid' => 'test_call_002', 
    'RecordingUrl' => 'https://example.com/test-recording.mp3',
    'RecordingDuration' => 2,
    'RecordingStatus' => 'completed',
    'From' => '+19045551234',
    'To' => '+19042175152'
];

echo "\n=== Test 2: Short Test Call (Should Be Ignored) ===\n";
echo "Sending: " . json_encode($testCall2) . "\n";

ob_start();
handle_signalwire_webhook();
$output2 = ob_get_clean();
ob_end_clean();

echo "Response: " . $output2 . "\n";

// Test 3: Missed call
$testCall3 = [
    'CallSid' => 'test_call_003',
    'RecordingUrl' => '', // No recording URL for missed calls
    'RecordingDuration' => 0,
    'RecordingStatus' => 'failed',
    'From' => '+19045551234',
    'To' => '+19042175152'
];

echo "\n=== Test 3: Missed Call ===\n";
echo "Sending: " . json_encode($testCall3) . "\n";

ob_start();
handle_signalwire_webhook();
$output3 = ob_get_clean();
ob_end_clean();

echo "Response: " . $output3 . "\n";

echo "\n=== Test Summary ===\n";
echo "Test 1 (Valid): Should process and create CRM lead\n";
echo "Test 2 (Short): Should be ignored\n";
echo "Test 3 (Missed): Should handle as missed call\n";

// Check responses
$response1 = json_decode($output1, true);
$response2 = json_decode($output2, true);
$response3 = json_decode($output3, true);

echo "Test 1 Status: " . ($response1['status'] ?? 'unknown') . "\n";
echo "Test 2 Status: " . ($response2['status'] ?? 'unknown') . "\n";
echo "Test 3 Status: " . ($response3['status'] ?? 'unknown') . "\n";

if ($response1['status'] === 'completed') {
    echo "✅ Test 1 PASSED - Valid call processed correctly\n";
} else {
    echo "❌ Test 1 FAILED - " . ($response1['error'] ?? 'Unknown error') . "\n";
}

if ($response2['status'] === 'processing' || $response2['status'] === 'error') {
    echo "❌ Test 2 FAILED - Short call should have been ignored\n";
} else {
    echo "✅ Test 2 PASSED - Short call correctly ignored\n";
}

if ($response3['status'] === 'completed' || ($response3['crm_lead']['skipped'] ?? false)) {
    echo "❌ Test 3 FAILED - Missed call should not create CRM lead\n";
} else {
    echo "✅ Test 3 PASSED - Missed call handled correctly\n";
}

?>