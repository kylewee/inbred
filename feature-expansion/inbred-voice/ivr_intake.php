<?php
/**
 * IVR Intake System - 22nd Century Edition
 */
declare(strict_types=1);

$envPath = __DIR__ . '/../api/.env.local.php';
if (file_exists($envPath) && !defined('SIGNALWIRE_PROJECT_ID')) {
    require_once $envPath;
}

header('Content-Type: application/xml');

$host = $_SERVER['HTTP_HOST'] ?? 'mechanicstaugustine.com';
$baseUrl = 'https://' . $host . '/voice';

$step = (int)($_REQUEST['step'] ?? 0);
$callSid = $_REQUEST['CallSid'] ?? '';
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';

// Session handling
$sessionDir = __DIR__ . '/ivr_sessions';
if (!is_dir($sessionDir)) @mkdir($sessionDir, 0755, true);
$sessionFile = $sessionDir . '/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';
$session = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) ?: [] : [];
$session['from'] = $from;
$session['call_sid'] = $callSid;
$session['last_step'] = $step;
$session['updated'] = date('c');

$recordingUrl = $_REQUEST['RecordingUrl'] ?? '';
if ($recordingUrl) {
    $labels = ['vehicle', 'problem'];
    $prev = $step - 1;
    if ($prev >= 0 && isset($labels[$prev])) {
        $session['recordings'][$labels[$prev]] = $recordingUrl;
    }
}
file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

@file_put_contents(__DIR__ . '/ivr_intake.log', json_encode(['ts'=>date('c'),'step'=>$step,'from'=>$from]) . "\n", FILE_APPEND);

// Use neural voice for more natural sound
$voice = "Polly.Matthew-Neural";

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

if ($step == 0) {
    // QUESTION 1: Vehicle info
    $actionUrl = "{$baseUrl}/ivr_intake.php?step=1";
    $callbackUrl = "{$baseUrl}/ivr_recording.php?step=0";
    
    echo "  <Say voice=\"{$voice}\">Hey! St. Augustine Mobile Mechanic here. I'll get back to you quick. Just need two things.</Say>\n";
    echo "  <Pause length=\"1\"/>\n";
    echo "  <Say voice=\"{$voice}\">First - year, make, and model. Go ahead.</Say>\n";
    echo "  <Record maxLength=\"30\" playBeep=\"false\" timeout=\"5\" action=\"{$actionUrl}\" recordingStatusCallback=\"{$callbackUrl}\" />\n";
    echo "  <Redirect>{$actionUrl}</Redirect>\n";
    
} elseif ($step == 1) {
    // QUESTION 2: Problem description
    $actionUrl = "{$baseUrl}/ivr_intake.php?step=2";
    $callbackUrl = "{$baseUrl}/ivr_recording.php?step=1";
    
    echo "  <Say voice=\"{$voice}\">Got it. Now what's going on with it?</Say>\n";
    echo "  <Record maxLength=\"60\" playBeep=\"false\" timeout=\"6\" action=\"{$actionUrl}\" recordingStatusCallback=\"{$callbackUrl}\" />\n";
    echo "  <Redirect>{$actionUrl}</Redirect>\n";
    
} else {
    // DONE
    echo "  <Say voice=\"{$voice}\">Perfect. I'll text you a quote shortly. Talk soon!</Say>\n";
    echo "  <Hangup />\n";
    $session['complete'] = true;
    file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
}

echo "</Response>\n";
