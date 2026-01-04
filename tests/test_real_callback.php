<?php
// Test the actual callback system by sending a POST request
echo "Testing actual callback system...\n";

// Test data
$testData = [
    'CallSid' => 'real_test_001',
    'RecordingUrl' => 'https://example.com/test-recording.mp3',
    'RecordingDuration' => 45,
    'RecordingStatus' => 'completed',
    'From' => '+19045551234',
    'To' => '+19047066669'
];

// Use cURL to send POST request
$ch = curl_init('http://localhost/voice/recording_callback.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


echo "HTTP Code: {$httpCode}\n";
echo "Response: {$response}\n";

?>