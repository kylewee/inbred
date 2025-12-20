<?php
/**
 * Dial Result Handler
 * Called when the Dial verb completes
 * Redirects to IVR if call wasn't answered
 */
declare(strict_types=1);

// Load environment
$envPath = __DIR__ . '/../api/.env.local.php';
if (file_exists($envPath) && !defined('SIGNALWIRE_PROJECT_ID')) {
    require_once $envPath;
}

header('Content-Type: text/xml');

// Get dial status
$dialStatus = strtolower(trim($_REQUEST['DialCallStatus'] ?? ''));
$callSid = $_REQUEST['CallSid'] ?? '';
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';
$to = $_REQUEST['To'] ?? $_REQUEST['Called'] ?? '';

// Build base URL
$host = $_SERVER['HTTP_HOST'] ?? 'mechanicstaugustine.com';
$baseUrl = 'https://' . $host . '/voice';

// Log
$log = [
    'ts' => date('c'),
    'event' => 'dial_result',
    'dial_status' => $dialStatus,
    'call_sid' => $callSid,
    'from' => $from,
    'to' => $to
];
@file_put_contents(__DIR__ . '/voice.log', json_encode($log) . "\n", FILE_APPEND);

// Generate TwiML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

// If call was NOT answered, redirect to IVR
$noAnswer = in_array($dialStatus, ['no-answer', 'busy', 'failed', 'canceled', ''], true);

if ($noAnswer || $dialStatus === '') {
    // Redirect to IVR intake with full URL
    echo "  <Redirect>{$baseUrl}/ivr_intake.php?step=0</Redirect>\n";
} elseif ($dialStatus === 'completed') {
    // Call was answered - recording callback will handle the rest
    echo "  <Hangup />\n";
} else {
    // Unknown status - redirect to IVR anyway
    echo "  <Redirect>{$baseUrl}/ivr_intake.php?step=0</Redirect>\n";
}

echo "</Response>\n";
