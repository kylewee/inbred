<?php
declare(strict_types=1);
header('Content-Type: text/xml');

// Load shared config (CRM/Twilio values live here)
$env = __DIR__ . '/../api/.env.local.php';
if (is_file($env)) {
  require $env;
}

$host = $_SERVER['HTTP_HOST'] ?? 'mechanicstaugustine.com';
$callback = 'https://' . $host . '/voice/recording_callback.php';

// REMOVED FORWARDING: Calls are no longer forwarded to personal number
// Instead, caller goes directly to voicemail for recording only
// Previous forwarding to +19046634789 has been disabled per user request

// Log webhook hit for diagnostics
try {
  $logFile = __DIR__ . '/voice.log';
  $entry = [
    'ts' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'event' => 'incoming_voicemail',
    'from' => $_POST['From'] ?? $_GET['From'] ?? null,
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
  ];
  @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
} catch (\Throwable $e) {
  // ignore logging errors
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
  <Dial timeout="20" action="/voice/hangup.php">+19046634789</Dial>
  <Say voice="alice">Thank you for calling Mechanics Saint Augustine. Please leave a detailed message including your name, phone number, vehicle information, and the service you need. We'll get back to you shortly.</Say>
  <Record
    maxLength="300"
    playBeep="true"
    recordingStatusCallback="<?=htmlspecialchars($callback, ENT_QUOTES)?>"
    recordingStatusCallbackMethod="POST"
    transcribe="false"
  />
  <Say voice="alice">Thank you. We will contact you soon.</Say>
  <Hangup />
</Response>
