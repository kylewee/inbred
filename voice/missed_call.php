<?php
/**
 * Missed Call Handler - Structured Prompts
 * Collects: name, phone number, address, email
 * Each prompt records the answer, then moves to next step
 */
declare(strict_types=1);

header('Content-Type: text/xml');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Load config with webhook domain detection
require_once __DIR__ . '/../config/webhook_bootstrap.php';

// Get current step and call info
$step = (int)($_REQUEST['step'] ?? 0);
$callSid = $_REQUEST['CallSid'] ?? $_REQUEST['call_id'] ?? '';
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';
$to = $_REQUEST['To'] ?? $_REQUEST['Called'] ?? '';

// Session file to track this call's recordings
$sessionDir = __DIR__ . '/sessions';
$sessionFile = $sessionDir . '/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';

// Load or create session
$session = [];
if (file_exists($sessionFile)) {
    $session = json_decode(file_get_contents($sessionFile), true) ?: [];
}
$session['call_sid'] = $callSid;
$session['caller_id'] = $from;  // Caller ID (may be lost in forwarding)
$session['called'] = $to;
$session['updated'] = date('c');
$session['type'] = 'missed_call';

// Handle recording from previous step
$recordingUrl = $_REQUEST['RecordingUrl'] ?? '';
$recordingSid = $_REQUEST['RecordingSid'] ?? '';
if ($recordingUrl && $step > 0) {
    $fields = ['name', 'phone', 'address', 'email'];
    $prevStep = $step - 1;
    if (isset($fields[$prevStep])) {
        $session['recordings'][$fields[$prevStep]] = [
            'url' => $recordingUrl,
            'sid' => $recordingSid,
        ];
    }
}

// Save session
file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

// Log
$log = [
    'ts' => date('c'),
    'event' => 'missed_call_step',
    'step' => $step,
    'call_sid' => $callSid,
    'from' => $from,
    'recording_url' => $recordingUrl,
];
@file_put_contents(__DIR__ . '/voice.log', json_encode($log) . "\n", FILE_APPEND);

// Build base URL
$host = $_SERVER['HTTP_HOST'] ?? config('site.domain', 'localhost');
$baseUrl = 'https://' . $host . '/voice';
$siteName = config('site.name', 'our company');

// Prompts for each step
$prompts = [
    0 => "Hi! I missed your call. I'd love to help you out. Let me get some quick information. First, what's your name?",
    1 => "Great! What's the best phone number to reach you at?",
    2 => "Got it. What's your address or location?",
    3 => "Last one. What's your email address? You can spell it out if you'd like.",
];

$totalSteps = count($prompts);

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

if ($step < $totalSteps) {
    // Ask the question and record the answer
    $prompt = $prompts[$step];
    $nextStep = $step + 1;
    $actionUrl = "{$baseUrl}/missed_call.php?step={$nextStep}";

    // Recording callback to process individual recordings
    $recordingCallback = "{$baseUrl}/recording_processor.php?type=prompt&step={$step}&call_sid=" . urlencode($callSid);

    echo "  <Say voice=\"man\">" . htmlspecialchars($prompt) . "</Say>\n";
    echo "  <Pause length=\"1\" />\n";
    echo "  <Record maxLength=\"30\" playBeep=\"true\" timeout=\"5\" action=\"{$actionUrl}\" recordingStatusCallback=\"{$recordingCallback}\" recordingStatusCallbackMethod=\"POST\" />\n";
    // If no recording (silence), retry the question
    echo "  <Say voice=\"man\">I didn't catch that. Let me ask again.</Say>\n";
    echo "  <Redirect method=\"POST\">{$baseUrl}/missed_call.php?step={$step}</Redirect>\n";
} else {
    // All questions answered
    echo "  <Say voice=\"man\">Thank you! I've got your information and will get back to you as soon as possible. Have a great day!</Say>\n";
    echo "  <Hangup />\n";

    // Mark session complete and trigger processing
    $session['complete'] = true;
    $session['completed_at'] = date('c');
    file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

    // Trigger async processing
    $processUrl = $baseUrl . '/recording_processor.php?action=process_session&call_sid=' . urlencode($callSid);
    @file_get_contents($processUrl, false, stream_context_create(['http' => ['timeout' => 1]]));
}

echo "</Response>\n";
