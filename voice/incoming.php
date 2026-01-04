<?php
/**
 * Incoming Call Handler
 * Tries owner first, falls back to voicemail if no answer
 */
declare(strict_types=1);

header('Content-Type: text/xml');

require_once __DIR__ . '/../config/webhook_bootstrap.php';

$callSid = $_POST['CallSid'] ?? $_POST['call_id'] ?? '';
$from = $_POST['From'] ?? $_POST['Caller'] ?? '';
$to = $_POST['To'] ?? $_POST['Called'] ?? '';

@file_put_contents(__DIR__ . '/voice.log', json_encode([
    'ts' => date('c'),
    'event' => 'incoming_call',
    'from' => $from,
    'to' => $to,
    'call_sid' => $callSid,
]) . "\n", FILE_APPEND);

// Create session
$sessionFile = __DIR__ . '/sessions/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';
@file_put_contents($sessionFile, json_encode([
    'call_sid' => $callSid,
    'caller_id' => $from,
    'called' => $to,
    'updated' => date('c'),
    'type' => 'incoming',
], JSON_PRETTY_PRINT));

$siteName = config('site.name', 'our company');
$host = $_SERVER['HTTP_HOST'] ?? config('site.domain', 'localhost');
$baseUrl = "https://{$host}/voice";

// Owner's phone number to forward to
$forwardTo = config('phone.forward_to', '');
$dialTimeout = 20; // seconds to ring before going to voicemail

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';

if ($forwardTo) {
    // Greet caller and try owner first
    echo '<Say voice="man">Thanks for calling ' . htmlspecialchars($siteName) . '. Let me connect you now.</Say>';
    echo '<Dial timeout="' . $dialTimeout . '" callerId="' . htmlspecialchars($from) . '" action="' . $baseUrl . '/dial_result.php" method="POST" record="record-from-answer" recordingStatusCallback="' . $baseUrl . '/recording_processor.php?call_sid=' . urlencode($callSid) . '" recordingStatusCallbackMethod="POST">';
    echo '<Number>' . htmlspecialchars($forwardTo) . '</Number>';
    echo '</Dial>';
} else {
    // No forward number - go straight to voicemail
    echo '<Say voice="man">Hey there! Thanks so much for calling ' . htmlspecialchars($siteName) . '. I really appreciate you reaching out. I\'m not available right now, but I definitely want to help you out. Go ahead and leave me your name, your phone number, and the best way to reach you, and I\'ll get back to you as soon as I can. Talk to you soon!</Say>';
    echo '<Record maxLength="120" playBeep="true" timeout="10" transcribe="false" recordingStatusCallback="' . $baseUrl . '/recording_processor.php?call_sid=' . urlencode($callSid) . '" recordingStatusCallbackMethod="POST" />';
    echo '<Say voice="man">Thank you. Goodbye.</Say>';
}

/*
 * OLD CODE - Direct to voicemail (no call forwarding)
 * Commented out 2025-12-31 - replaced with try-owner-first flow above
 *
 * echo '<Say voice="man">Hey there! Thanks so much for calling ' . htmlspecialchars($siteName) . '. I really appreciate you reaching out. I\'m not available right now, but I definitely want to help you out. Go ahead and leave me your name, your phone number, and the best way to reach you, and I\'ll get back to you as soon as I can. Talk to you soon!</Say>';
 * echo '<Record maxLength="120" playBeep="true" timeout="10" transcribe="false" recordingStatusCallback="' . $baseUrl . '/recording_processor.php?call_sid=' . urlencode($callSid) . '" recordingStatusCallbackMethod="POST" />';
 * echo '<Say voice="man">Thank you. Goodbye.</Say>';
 */

echo '</Response>';
