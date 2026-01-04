<?php
/**
 * Incoming SMS Handler
 * Handles YES replies to approve quotes
 */

header('Content-Type: text/xml');

require_once __DIR__ . '/../lib/CustomerFlow/Flow.php';

$from = $_POST['From'] ?? '';
$body = strtoupper(trim($_POST['Body'] ?? ''));

// Log
@file_put_contents(__DIR__ . '/sms_reply.log', date('c') . " | {$from} | {$body}\n", FILE_APPEND);

echo '<?xml version="1.0" encoding="UTF-8"?>';

if (in_array($body, ['YES', 'Y', 'APPROVE', 'OK', 'BOOK'])) {
    $flow = new CustomerFlow\Flow();
    $result = $flow->approveByPhone($from);

    if ($result['success']) {
        echo '<Response><Message>Confirmed! Kyle will contact you to schedule. - EZ Mobile Mechanic (904) 706-6669</Message></Response>';
    } else {
        echo '<Response><Message>No pending quote found. Call (904) 706-6669 for help.</Message></Response>';
    }
} else {
    echo '<Response><Message>Thanks! Call (904) 706-6669 for immediate help. - EZ Mobile Mechanic</Message></Response>';
}
