<?php
// Simple test for callback system
echo "Testing callback system...\n";

// Test 1: Valid customer call
$testData1 = [
    'CallSid' => 'test_001',
    'RecordingUrl' => 'https://example.com/test.mp3',
    'RecordingDuration' => 45,
    'RecordingStatus' => 'completed'
];

echo "Test 1: Valid call\n";
echo json_encode($testData1) . "\n\n";

// Test 2: Short call (should be ignored)
$testData2 = [
    'CallSid' => 'test_002', 
    'RecordingUrl' => 'https://example.com/test.mp3',
    'RecordingDuration' => 2,
    'RecordingStatus' => 'completed'
];

echo "Test 2: Short call\n";
echo json_encode($testData2) . "\n\n";

// Test 3: Missed call
$testData3 = [
    'CallSid' => 'test_003',
    'RecordingDuration' => 0,
    'RecordingStatus' => 'failed'
];

echo "Test 3: Missed call\n";
echo json_encode($testData3) . "\n\n";

echo "Tests prepared. Run with: php test_callback.php\n";
?>