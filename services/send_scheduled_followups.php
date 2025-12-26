<?php
/**
 * Scheduled Follow-up SMS Sender
 *
 * Run via cron: 0 10 * * * php /home/kylewee/code/inbred/services/send_scheduled_followups.php
 *
 * Sends follow-up + review request SMS 24 hours after service completion
 */

require_once __DIR__ . '/../lib/PostServiceFlow.php';

$flow = new PostServiceFlow();
$result = $flow->sendScheduledFollowUps();

echo date('Y-m-d H:i:s') . " - Follow-up SMS sent: {$result['sent']}, failed: {$result['failed']}\n";

// Log to file
$logFile = __DIR__ . '/../data/followup_log.txt';
file_put_contents($logFile, date('c') . " - Sent: {$result['sent']}, Failed: {$result['failed']}\n", FILE_APPEND);
