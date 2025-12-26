<?php
// Minimal SignalWire -> Twilio field mapper adapter.
// Maps SignalWire webhook fields into the Twilio-like keys expected by
// `voice/recording_callback.php`, then includes that handler so it runs
// with the remapped `$_POST` data.

// Accept both form-encoded SignalWire params and JSON payloads.
$raw = file_get_contents('php://input');
$input = $_POST;
if (!$input && $raw) {
    $json = json_decode($raw, true);
    if (is_array($json)) $input = $json;
}

$map = [
    'call_id' => 'CallSid',
    'recording_id' => 'RecordingSid',
    'recording_url' => 'RecordingUrl',
    'recording_url_secure' => 'RecordingUrl',
    'caller' => 'From',
    'called' => 'To',
    'transcript' => 'TranscriptionText',
    'duration' => 'RecordingDuration',
    'account_sid' => 'AccountSid',
    'direction' => 'Direction',
    'call_status' => 'CallStatus',
];

foreach ($map as $from => $to) {
    if (isset($input[$from]) && !isset($_POST[$to])) {
        $_POST[$to] = $input[$from];
    }
}

// Some SignalWire LāML params come capitalized differently; copy any close matches
$aliases = [
    'CallSid', 'RecordingSid', 'RecordingUrl', 'From', 'To', 'TranscriptionText', 'RecordingDuration'
];
foreach ($aliases as $a) {
    if (!isset($_POST[$a])) {
        // check common lowercased variants in $input
        $lower = strtolower($a);
        if (isset($input[$lower])) $_POST[$a] = $input[$lower];
    }
}

// Log the mapping for debugging
// Optional signature verification: set environment var SIGNALWIRE_WEBHOOK_SECRET
// to enable. SignalWire may send a signature header; we check common header
// names and validate HMAC-SHA256 of the raw body.
$secret = getenv('SIGNALWIRE_WEBHOOK_SECRET');
if (!$secret) {
    // also allow a local file for convenience
    $secret_file = __DIR__ . '/.signalwire_secret';
    if (file_exists($secret_file)) $secret = trim(file_get_contents($secret_file));
}

if ($secret) {
    $sigs = [];
    foreach (['X-Signature', 'X-SignalWire-Signature', 'X-Signalwire-Signature', 'X-Signature-SHA256'] as $h) {
        $v = null;
        foreach (getallheaders() as $hk => $hv) {
            if (strcasecmp($hk, $h) === 0) { $v = $hv; break; }
        }
        if ($v) $sigs[] = $v;
    }

    $ok_sig = false;
    if (!empty($sigs)) {
        $expected_hex = hash_hmac('sha256', $raw, $secret);
        $expected_b64 = base64_encode(hex2bin($expected_hex));
        foreach ($sigs as $s) {
            if (hash_equals($expected_hex, $s) || hash_equals($expected_b64, $s)) { $ok_sig = true; break; }
        }
    }

    if (!$ok_sig) {
        http_response_code(403);
        header('Content-Type: application/json');
        $msg = ['ok'=>false,'error'=>'signature verification failed'];
        file_put_contents(__DIR__ . '/voice_signalwire_adapter.log', json_encode(['ts'=>time(),'sig_check'=>false,'headers'=>getallheaders(),'post'=>$_POST]) . "\n", FILE_APPEND);
        echo json_encode($msg);
        exit;
    }
}

file_put_contents(__DIR__ . '/voice_signalwire_adapter.log', json_encode(['ts'=>time(),'post'=>$_POST,'raw'=>strlen($raw)?$raw:null,'sig_enabled'=>!!$secret]) . "\n", FILE_APPEND);

// Now invoke the existing handler in this directory which expects Twilio-like keys.
// Use require so it runs in this request with the modified $_POST.
$handler = __DIR__ . '/recording_callback.php';
if (file_exists($handler)) {
    require $handler;
    // recording_callback.php typically emits JSON and exits. If it doesn't,
    // ensure we don't fall through silently.
    exit;
}

// If handler missing, return 404 for visibility.
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['ok'=>false,'error'=>'recording_callback.php not found']);

?>