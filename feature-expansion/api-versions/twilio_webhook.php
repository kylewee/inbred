<?php
require_once 'config.php';

// Twilio webhook endpoint for handling incoming calls
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = $_POST['From'] ?? '';
    $to = $_POST['To'] ?? '';
    $call_sid = $_POST['CallSid'] ?? '';

    // Log the call
    error_log("Incoming call from: $from to: $to, SID: $call_sid");

    // TwiML response to handle the call
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response>';
    echo '<Say voice="alice">Hello, you have reached Mechanic Saint Augustine. Please hold while we record this call for quality purposes.</Say>';
    echo '<Record action="/api/recording_complete.php" recordingStatusCallback="/api/recording_status.php" transcribe="true" transcribeCallback="/api/transcription_complete.php" maxLength="300" />';
    echo '<Say voice="alice">Thank you for calling. We will process your request shortly.</Say>';
    echo '</Response>';
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
