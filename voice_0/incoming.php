<?php
/**
 * Incoming Call Handler
 * Entry point for all inbound calls
 * Routes to forward number, then to assistant if no answer
 */

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Content-Type: text/xml');

// Load config with webhook domain detection
require_once __DIR__ . '/../config/webhook_bootstrap.php';

// Log incoming request
$logFile = __DIR__ . '/voice.log';
$entry = [
    'ts' => date('c'),
    'event' => 'incoming',
    'domain' => config('site.domain'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'from' => $_POST['From'] ?? $_POST['caller'] ?? '',
    'to' => $_POST['To'] ?? $_POST['called'] ?? '',
    'call_sid' => $_POST['CallSid'] ?? $_POST['call_id'] ?? ''
];
@file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);

// Config values
$forwardTo = config('phone.forward_to', '');
$callerFrom = $_POST['From'] ?? $_POST['caller'] ?? config('phone.phone_number', '');
$siteName = config('site.name', 'our company');
$host = $_SERVER['HTTP_HOST'] ?? config('site.domain', 'localhost');
$baseUrl = 'https://' . $host . '/voice';

// Build greeting based on business type
$businessType = config('business.type', 'contractor');
switch ($businessType) {
    case 'mechanic':
        $greeting = "Thanks for calling {$siteName}. Let me get our mechanic on the line for you.";
        break;
    case 'landscaper':
        $greeting = "Thanks for calling {$siteName}. Let me connect you with our team.";
        break;
    default:
        $greeting = "Thanks for calling {$siteName}. Please hold while I connect you.";
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
<?php if (!empty($forwardTo)): ?>
    <Say voice="Polly.Matthew"><?= htmlspecialchars($greeting) ?></Say>
    <Dial timeout="18"
          callerId="<?= htmlspecialchars($callerFrom) ?>"
          action="<?= $baseUrl ?>/dial_result.php"
          method="POST"
          record="record-from-answer"
          recordingStatusCallback="<?= $baseUrl ?>/recording_callback.php"
          recordingStatusCallbackMethod="POST"
          ringTone="us">
        <Number><?= htmlspecialchars($forwardTo) ?></Number>
    </Dial>
<?php else: ?>
    <!-- No forward number configured - go straight to assistant -->
    <Say voice="Polly.Matthew">Thanks for calling <?= htmlspecialchars($siteName) ?>.</Say>
    <Redirect method="POST"><?= $baseUrl ?>/gpt_assistant.php</Redirect>
<?php endif; ?>
</Response>
