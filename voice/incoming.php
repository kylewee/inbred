<?php
// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Content-Type: text/xml');

// Log incoming request
$logFile = __DIR__ . '/voice.log';
$entry = [
    'ts' => date('c'),
    'event' => 'incoming_v4',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'from' => $_POST['From'] ?? $_POST['caller'] ?? '',
    'to' => $_POST['To'] ?? $_POST['called'] ?? '',
    'call_sid' => $_POST['CallSid'] ?? $_POST['call_id'] ?? ''
];
@file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);

// Config
$to = '+19046634789';
$from = $_POST['From'] ?? $_POST['caller'] ?? '+19047066669';
$baseUrl = 'https://mechanicstaugustine.com/voice';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Dial timeout="25" 
          callerId="<?= htmlspecialchars($from) ?>"
          action="<?= $baseUrl ?>/dial_result.php"
          method="POST"
          record="record-from-answer-dual"
          recordingStatusCallback="<?= $baseUrl ?>/recording_callback.php"
          recordingStatusCallbackMethod="POST">
        <Number><?= $to ?></Number>
    </Dial>
</Response>
