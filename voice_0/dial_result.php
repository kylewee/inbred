<?php
/**
 * Dial Result Handler
 * Called when the Dial verb completes
 * Redirects to assistant if call wasn't answered
 * Creates "Callback Needed" lead for missed calls
 */
declare(strict_types=1);

// Load config with webhook domain detection
require_once __DIR__ . '/../config/webhook_bootstrap.php';

// Early logging to confirm we're being called
@file_put_contents(__DIR__ . '/voice.log', json_encode([
    'ts' => date('c'),
    'event' => 'dial_result_entry',
    'domain' => config('site.domain'),
    'post' => $_POST,
    'get' => $_GET
]) . "\n", FILE_APPEND);

// Load CRM helper for stage updates (if CRM enabled)
if (config('crm.enabled', false)) {
    require_once __DIR__ . '/../lib/CRMHelper.php';
}

header('Content-Type: text/xml');

// Get dial status
$dialStatus = strtolower(trim($_REQUEST['DialCallStatus'] ?? ''));
$callSid = $_REQUEST['CallSid'] ?? '';
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';
$to = $_REQUEST['To'] ?? $_REQUEST['Called'] ?? '';

// Build base URL from config
$host = $_SERVER['HTTP_HOST'] ?? config('site.domain', 'localhost');
$baseUrl = 'https://' . $host . '/voice';

// Log
$log = [
    'ts' => date('c'),
    'event' => 'dial_result',
    'domain' => config('site.domain'),
    'dial_status' => $dialStatus,
    'call_sid' => $callSid,
    'from' => $from,
    'to' => $to
];

// If call was NOT answered
$noAnswer = in_array($dialStatus, ['no-answer', 'busy', 'failed', 'canceled', ''], true);

// For missed calls, check if lead exists and update stage to "Callback Needed"
if ($noAnswer && $from && config('crm.enabled', false) && class_exists('CRMHelper')) {
    $existingLead = CRMHelper::getLeadByPhone($from);
    if ($existingLead && !empty($existingLead['id'])) {
        $leadId = (int)$existingLead['id'];
        $siteName = config('site.name', 'our company');
        $updateResult = CRMHelper::transitionStage(
            $leadId,
            CRMHelper::STAGE_CALLBACK_NEEDED,
            "Missed Call\nCaller: {$from}\nStatus: {$dialStatus}\nTime: " . date('Y-m-d H:i:s') . "\n\nCustomer called {$siteName} but call was not answered. Callback required."
        );
        $log['missed_call_lead_update'] = $updateResult;
    } else {
        // No existing lead - the voicemail recording will create one with "New Lead" stage
        $log['missed_call_no_existing_lead'] = true;
    }
}

// Generate TwiML
ob_start();
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

if ($noAnswer || $dialStatus === '') {
    // Check if voice assistant is enabled
    if (config('features.voice_assistant', true)) {
        // Redirect to GPT assistant
        echo "  <Redirect method=\"POST\">{$baseUrl}/gpt_assistant.php</Redirect>\n";
    } else {
        // No assistant - just take a voicemail
        $siteName = config('site.name', 'our company');
        echo "  <Say voice=\"Polly.Matthew\">Sorry, no one is available right now. Please leave a message after the beep.</Say>\n";
        echo "  <Record maxLength=\"120\" transcribe=\"true\" transcribeCallback=\"{$baseUrl}/recording_callback.php\" />\n";
    }
} elseif ($dialStatus === 'completed') {
    // Call was answered - recording callback will handle the rest
    echo "  <Hangup />\n";
} else {
    // Unknown status - redirect to assistant if enabled
    if (config('features.voice_assistant', true)) {
        echo "  <Redirect method=\"POST\">{$baseUrl}/gpt_assistant.php</Redirect>\n";
    } else {
        echo "  <Hangup />\n";
    }
}

echo "</Response>\n";

$twiml = ob_get_clean();
$log['twiml_response'] = $twiml;
@file_put_contents(__DIR__ . '/voice.log', json_encode($log) . "\n", FILE_APPEND);

echo $twiml;
