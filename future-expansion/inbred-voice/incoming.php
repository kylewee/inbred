<?php
// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Content-Type: text/xml');

// Load CRM database config for telephony logging
$crmDbConfig = __DIR__ . '/../crm/config/database.php';
if (file_exists($crmDbConfig)) { require_once $crmDbConfig; }

// Load telephony logging helper
require_once __DIR__ . '/telephony_log.php';

// Extract call info
$from = $_POST['From'] ?? $_POST['caller'] ?? $_POST['from_number'] ?? '';
$to = $_POST['To'] ?? $_POST['called'] ?? $_POST['to_number'] ?? '+19047066669';
$callSid = $_POST['CallSid'] ?? $_POST['call_id'] ?? '';

// Log incoming request
$logFile = __DIR__ . '/voice.log';
$entry = [
    'ts' => date('c'),
    'event' => 'incoming_v6',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'from' => $from,
    'to' => $to,
    'call_sid' => $callSid
];

// Log call to CRM telephony history immediately
if (!empty($from)) {
    $telephonyResult = log_crm_telephony_call($from, 'inbound', 0);
    $entry['telephony_log'] = $telephonyResult;
}

@file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);

// Config
$dialTo = '+19046634789';
$baseUrl = 'https://mechanicstaugustine.com/voice';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Dial timeout="25" 
          callerId="<?= htmlspecialchars($from ?: '+19047066669') ?>"
          action="<?= $baseUrl ?>/dial_result.php"
          method="POST"
          record="record-from-answer-dual"
          recordingStatusCallback="<?= $baseUrl ?>/recording_callback.php"
          recordingStatusCallbackMethod="POST">
        <Number><?= $dialTo ?></Number>
    </Dial>
</Response>
