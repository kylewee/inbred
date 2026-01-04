<?php
/**
 * Outgoing Call Handler
 * TwiML for outbound calls - connects to customer with recording
 */
declare(strict_types=1);

header('Content-Type: text/xml');

require_once __DIR__ . '/../config/webhook_bootstrap.php';

$callSid = $_POST['CallSid'] ?? $_POST['call_id'] ?? '';
$to = $_REQUEST['to'] ?? '';
$from = $_REQUEST['from'] ?? '';

@file_put_contents(__DIR__ . '/voice.log', json_encode([
    'ts' => date('c'),
    'event' => 'outgoing_call_connect',
    'to' => $to,
    'from' => $from,
    'call_sid' => $callSid,
]) . "\n", FILE_APPEND);

// Create session
$sessionFile = __DIR__ . '/sessions/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';
@file_put_contents($sessionFile, json_encode([
    'call_sid' => $callSid,
    'to' => $to,
    'from' => $from,
    'type' => 'outgoing',
    'started' => date('c'),
], JSON_PRETTY_PRINT));

$host = $_SERVER['HTTP_HOST'] ?? config('site.domain', 'localhost');
$baseUrl = "https://{$host}/voice";

// Format number for display
$displayTo = preg_replace('/^\+1/', '', $to);
$displayTo = preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1-$2-$3', $displayTo);

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';
echo '<Say voice="man">Connecting you to ' . htmlspecialchars($displayTo) . '</Say>';
echo '<Dial callerId="' . htmlspecialchars($from) . '" record="record-from-answer" recordingStatusCallback="' . $baseUrl . '/recording_processor.php?call_sid=' . urlencode($callSid) . '&amp;type=outgoing" recordingStatusCallbackMethod="POST" action="' . $baseUrl . '/hangup.php" method="POST">';
echo '<Number>' . htmlspecialchars($to) . '</Number>';
echo '</Dial>';
echo '<Hangup />';
echo '</Response>';
