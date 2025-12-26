<?php
/**
 * Smart Call Router with Recording
 *
 * Flow:
 * 1. Incoming call is RECORDED
 * 2. System attempts to dial mechanic (+19046634789)
 * 3. After 27 seconds with no answer → route to AI Agent (Sarah)
 * 4. If mechanic answers, they can press "1" to transfer caller to AI Agent
 */

header('Content-Type: application/xml');

$mechanic_phone = '+19046634789';
$fallback_url = 'https://mechanicstaugustine.com/voice/call_router_fallback.php';
$transfer_url = 'https://mechanicstaugustine.com/voice/call_router_transfer.php';

// Log call
@file_put_contents(
    __DIR__ . '/call_router.log',
    '[' . date('Y-m-d H:i:s') . '] Incoming call - From: ' . ($_REQUEST['From'] ?? 'Unknown') . "\n",
    FILE_APPEND
);

// Check if caller pressed a digit
$digits = $_REQUEST['Digits'] ?? '';

// If caller pressed "1" during the dial, transfer to AI
if ($digits === '1') {
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Response>' . "\n";
    echo '  <Say>Transferring you to our virtual assistant.</Say>' . "\n";
    echo '  <Redirect method="POST">' . htmlspecialchars($transfer_url) . '</Redirect>' . "\n";
    echo '</Response>' . "\n";
    exit;
}

// Normal flow: Record and dial mechanic
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<Response>' . "\n";
echo '  <!-- Record the entire call (both channels) -->' . "\n";
echo '  <Record timeout="3600" transcribe="false" playBeep="false" />' . "\n";
echo '  ' . "\n";
echo '  <!-- Try to dial mechanic - will ring for 27 seconds -->' . "\n";
echo '  <Dial timeout="27" action="' . htmlspecialchars($fallback_url) . '" method="POST" record="do-not-record" finishOnKey="1">' . "\n";
echo '    <Number>' . htmlspecialchars($mechanic_phone) . '</Number>' . "\n";
echo '  </Dial>' . "\n";
echo '</Response>' . "\n";
