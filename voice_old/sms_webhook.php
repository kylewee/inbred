<?php
/**
 * Incoming SMS Webhook Handler
 * Handles "YES" replies to approve quotes
 */

require_once __DIR__ . '/../lib/QuoteSMS.php';

header('Content-Type: text/xml');

$from = $_POST['From'] ?? '';
$body = strtoupper(trim($_POST['Body'] ?? ''));

// Log incoming SMS
$log = [
    'ts' => date('c'),
    'from' => $from,
    'body' => $body
];
@file_put_contents(__DIR__ . '/sms_incoming.log', json_encode($log) . "\n", FILE_APPEND);

// Handle YES reply
if ($body === 'YES' || $body === 'Y' || $body === 'APPROVE') {
    $quoteSMS = new QuoteSMS();

    // Find most recent quote for this phone number
    $db = new SQLite3(__DIR__ . '/../data/quotes.db');
    $stmt = $db->prepare("SELECT quote_id FROM quotes WHERE customer_phone LIKE :phone AND status = 'sent' ORDER BY created_at DESC LIMIT 1");

    // Normalize phone for matching
    $phonePattern = '%' . preg_replace('/[^\d]/', '', $from);
    $phonePattern = '%' . substr($phonePattern, -10); // Last 10 digits
    $stmt->bindValue(':phone', $phonePattern, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result) {
        $quoteSMS->approveQuote($result['quote_id']);

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Response><Message>✓ Quote approved! Kyle will contact you shortly to schedule. - EZ Mobile Mechanic</Message></Response>';
        exit;
    }
}

// Default response
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response><Message>Thanks for your message! Call (904) 706-6669 for immediate help. - EZ Mobile Mechanic</Message></Response>';
