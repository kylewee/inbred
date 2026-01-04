<?php
declare(strict_types=1);
header('Content-Type: text/xml');

// Load configuration
$env = __DIR__ . '/../api/.env.local.php';
if (is_file($env)) {
  require $env;
}

$host = $_SERVER['HTTP_HOST'] ?? 'mechanicstaugustine.com';
$callback = 'https://' . $host . '/voice/outgoing_callback.php';

// Get phone number from request
$to = $_POST['To'] ?? $_GET['To'] ?? null;
if (!$to) {
  echo '<?xml version="1.0" encoding="UTF-8"?>';
  echo '<Response><Say voice="alice">No phone number specified.</Say><Hangup /></Response>';
  exit;
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
  <Dial record="true" recordingStatusCallback="<?=htmlspecialchars($callback, ENT_QUOTES)?>" recordingStatusCallbackMethod="POST" callerId="+19047066669"><?=htmlspecialchars($to, ENT_QUOTES)?></Dial>
  <Say voice="alice">The call could not be completed.</Say>
</Response>