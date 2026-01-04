<?php
/**
 * Dial Result Handler
 * Called when the Dial verb completes
 * Routes to missed_call.php if call wasn't answered
 */
declare(strict_types=1);

header('Content-Type: text/xml');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Load config with webhook domain detection
require_once __DIR__ . '/../config/webhook_bootstrap.php';

// Get dial result
$dialStatus = strtolower(trim($_REQUEST['DialCallStatus'] ?? ''));
$callSid = $_REQUEST['CallSid'] ?? '';
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';
$to = $_REQUEST['To'] ?? $_REQUEST['Called'] ?? '';

// Log
$log = [
    'ts' => date('c'),
    'event' => 'dial_result',
    'domain' => config('site.domain'),
    'dial_status' => $dialStatus,
    'call_sid' => $callSid,
    'from' => $from,
];
@file_put_contents(__DIR__ . '/voice.log', json_encode($log) . "\n", FILE_APPEND);

// Build base URL
$host = $_SERVER['HTTP_HOST'] ?? config('site.domain', 'localhost');
$baseUrl = 'https://' . $host . '/voice';

// Determine if call was answered
$answered = ($dialStatus === 'completed');

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

if ($answered) {
    // Call was answered - recording callback will handle data extraction
    echo "  <Hangup />\n";
} else {
    // Call was NOT answered - play voicemail greeting and record
    $siteName = config('site.name', 'our company');
    $recordingCallback = "{$baseUrl}/recording_processor.php?call_sid=" . urlencode($callSid);

    echo "  <Say voice=\"man\">Hey there! Sorry I missed your call. I really want to help you out. Go ahead and leave me your name, your phone number, and what you need help with. I'll get back to you as soon as I can. Thanks!</Say>\n";
    echo "  <Record maxLength=\"120\" playBeep=\"true\" timeout=\"10\" transcribe=\"false\" recordingStatusCallback=\"{$recordingCallback}\" recordingStatusCallbackMethod=\"POST\" />\n";
    echo "  <Say voice=\"man\">Thank you. Talk to you soon!</Say>\n";
    echo "  <Hangup />\n";
}

/*
 * OLD CODE - Multi-step IVR prompts for missed calls
 * Commented out 2025-12-31 - replaced with simple voicemail above
 *
 * // Create session file
 * $sessionDir = __DIR__ . '/sessions';
 * $sessionFile = $sessionDir . '/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';
 * $session = [
 *     'call_sid' => $callSid,
 *     'caller_id' => $from,
 *     'called' => $to,
 *     'updated' => date('c'),
 *     'type' => 'missed_call',
 * ];
 * @file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
 *
 * $recordingCallback = "{$baseUrl}/recording_processor.php?type=prompt&step=0&call_sid=" . urlencode($callSid);
 * $nextStep = "{$baseUrl}/missed_call.php?step=1";
 *
 * echo "  <Say voice=\"man\">Hi! I missed your call. I'd love to help you out. Let me get some quick information. First, what's your name?</Say>\n";
 * echo "  <Pause length=\"1\" />\n";
 * echo "  <Record maxLength=\"30\" playBeep=\"true\" timeout=\"5\" action=\"{$nextStep}\" recordingStatusCallback=\"{$recordingCallback}\" recordingStatusCallbackMethod=\"POST\" />\n";
 * echo "  <Say voice=\"man\">I didn't catch that. Let me ask again.</Say>\n";
 * echo "  <Redirect method=\"POST\">{$baseUrl}/missed_call.php?step=0</Redirect>\n";
 */

echo "</Response>\n";
