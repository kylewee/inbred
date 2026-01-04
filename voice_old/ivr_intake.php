<?php
/**
 * IVR Intake System
 * Asks structured questions when the mechanic doesn't answer
 * Questions: Name, Year, Make, Model, Engine Size, Problem Description
 */
declare(strict_types=1);

// Load environment
$envPath = __DIR__ . '/../api/.env.local.php';
if (file_exists($envPath) && !defined('SIGNALWIRE_PROJECT_ID')) {
    require_once $envPath;
}

header('Content-Type: application/xml');

// IVR State Machine
$step = (int)($_REQUEST['step'] ?? 0);
$callSid = $_REQUEST['CallSid'] ?? '';
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';
$to = $_REQUEST['To'] ?? $_REQUEST['Called'] ?? '';

// Store session data
$sessionDir = __DIR__ . '/ivr_sessions';
if (!is_dir($sessionDir)) @mkdir($sessionDir, 0755, true);
$sessionFile = $sessionDir . '/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';

// Load existing session data
$session = [];
if (file_exists($sessionFile)) {
    $session = json_decode(file_get_contents($sessionFile), true) ?: [];
}
$session['from'] = $from;
$session['to'] = $to;
$session['call_sid'] = $callSid;
$session['last_step'] = $step;
$session['updated'] = date('c');

// Handle recording from previous step
$recordingUrl = $_REQUEST['RecordingUrl'] ?? '';
if ($recordingUrl) {
    $stepLabels = ['name', 'year', 'make', 'model', 'engine', 'problem'];
    $prevStep = $step - 1;
    if ($prevStep >= 0 && isset($stepLabels[$prevStep])) {
        $session['recordings'][$stepLabels[$prevStep]] = $recordingUrl;
    }
}

// Save session
file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

// Log
$log = [
    'ts' => date('c'),
    'event' => 'ivr_step',
    'step' => $step,
    'call_sid' => $callSid,
    'from' => $from,
    'recording_url' => $recordingUrl
];
@file_put_contents(__DIR__ . '/ivr_intake.log', json_encode($log) . "\n", FILE_APPEND);

// IVR Questions - Each step records an answer then moves to next
$questions = [
    0 => "Hi! This is St. Augustine Mobile Mechanic. I'm not available right now, but I'd love to help you. Let me get some information. First, what's your name?",
    1 => "Great! What year is your vehicle?",
    2 => "Got it. What's the make? Like Honda, Ford, or Toyota?",
    3 => "And what's the model? Like Civic, F-150, or Camry?",
    4 => "Almost done. What's the engine size if you know it? You can say something like 2 point 4 liter or V6. If you don't know, just say skip.",
    5 => "Last question. Briefly describe what's wrong or what service you need."
];

$totalSteps = count($questions);

// Generate TwiML
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

if ($step < $totalSteps) {
    // Ask the question and record
    $question = $questions[$step];
    $nextStep = $step + 1;
    $actionUrl = "ivr_intake.php?step={$nextStep}";
    
    echo "  <Say voice=\"Polly.Matthew\">{$question}</Say>\n";
    echo "  <Pause length=\"1\"/>\n";
    echo "  <Record maxLength=\"30\" playBeep=\"true\" timeout=\"3\" action=\"{$actionUrl}\" recordingStatusCallback=\"ivr_recording.php?step={$step}\" />\n";
    echo "  <Say voice=\"Polly.Matthew\">I didn't catch that. Let me ask again.</Say>\n";
    echo "  <Redirect>{$actionUrl}</Redirect>\n";
} else {
    // All questions answered - thank them and process
    echo "  <Say voice=\"Polly.Matthew\">Thank you! I've got all your information. I'll get back to you as soon as possible. Have a great day!</Say>\n";
    echo "  <Hangup />\n";
    
    // Mark session complete
    $session['complete'] = true;
    $session['completed_at'] = date('c');
    file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    
    // Trigger processing
    $processUrl = "http://localhost/voice/ivr_recording.php?action=process&call_sid=" . urlencode($callSid);
    @file_get_contents($processUrl);
}

echo "</Response>\n";
