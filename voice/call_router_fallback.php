<?php
/**
 * Fallback Handler - Route to AI Agent if Mechanic Doesn't Answer
 */

header('Content-Type: application/xml');

// Log the fallback event
$dial_status = $_REQUEST['DialCallStatus'] ?? 'unknown';
$from = $_REQUEST['From'] ?? 'Unknown';

file_put_contents(
    __DIR__ . '/call_router.log',
    '[' . date('Y-m-d H:i:s') . '] Fallback triggered - Status: ' . $dial_status . ' - From: ' . $from . "\n",
    FILE_APPEND
);

// If mechanic didn't answer or was busy, route to AI Agent
if (in_array($dial_status, ['no-answer', 'busy', 'failed', 'canceled'])) {
    ?><?xml version="1.0" encoding="UTF-8"?>
    <Response>
        <Say>The mechanic is unavailable. Connecting you to our virtual assistant Sarah.</Say>
        <Redirect method="POST">https://mechanicstaugustine.com/voice/ai_agent_handler.php</Redirect>
    </Response>
    <?php
} else {
    // Call completed (mechanic answered and call ended)
    ?><?xml version="1.0" encoding="UTF-8"?>
    <Response>
        <Say>Thank you for calling. Goodbye.</Say>
    </Response>
    <?php
}
