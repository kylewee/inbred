<?php
/**
 * AI Agent Handler - Routes calls to Sarah AI Agent
 * This is called when:
 * 1. Mechanic doesn't answer in 27 seconds
 * 2. Mechanic presses 1 to transfer
 */

header('Content-Type: application/xml');

$agent_id = '1cec5da4-c1b6-4490-869e-f270d78c974f';  // Sarah agent ID

file_put_contents(
    __DIR__ . '/call_router.log',
    '[' . date('Y-m-d H:i:s') . '] Connecting to AI Agent (Sarah) - From: ' . ($_REQUEST['From'] ?? 'Unknown') . "\n",
    FILE_APPEND
);

?><?xml version="1.0" encoding="UTF-8"?>
<Response>
    <!-- Connect to AI Agent Sarah -->
    <ConnectAIAgent agentId="<?php echo htmlspecialchars($agent_id); ?>" />
</Response>
