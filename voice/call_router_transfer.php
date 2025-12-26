<?php
/**
 * Transfer Handler - When Mechanic Presses "1" During Call
 * Routes current caller to AI Agent (Sarah)
 */

header('Content-Type: application/xml');

// Log the transfer
file_put_contents(
    __DIR__ . '/call_router.log',
    '[' . date('Y-m-d H:i:s') . '] Transfer to AI triggered by mechanic pressing 1 - From: ' . ($_REQUEST['From'] ?? 'Unknown') . "\n",
    FILE_APPEND
);

?><?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say>Transferring you to our virtual assistant Sarah to collect your information.</Say>
    <Redirect method="POST">https://mechanicstaugustine.com/voice/ai_agent_handler.php</Redirect>
</Response>
