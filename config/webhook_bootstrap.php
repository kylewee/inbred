<?php
/**
 * Webhook Bootstrap - Domain detection for SignalWire/Twilio webhooks
 *
 * SignalWire webhooks don't always send the correct HTTP_HOST header,
 * so we detect the domain from the phone number being called.
 *
 * Usage: require_once __DIR__ . '/../config/webhook_bootstrap.php';
 * (instead of bootstrap.php in voice webhook files)
 */

// Phone number to domain mapping
// Add new numbers here as sites are configured
$PHONE_TO_DOMAIN = [
    // Mechanic
    '+19047066669' => 'mechanicstaugustine.com',
    '19047066669'  => 'mechanicstaugustine.com',
    // Sod (904-925-TURF) - all sod sites share this number
    '+19049258873' => 'sodjacksonvillefl.com',
    '19049258873'  => 'sodjacksonvillefl.com',
];

/**
 * Detect domain from SignalWire/Twilio webhook POST data
 */
function detect_webhook_domain(array $phoneMap): ?string {
    // Try various fields where the called number might be
    $candidates = [
        $_POST['To'] ?? null,
        $_POST['Called'] ?? null,
        $_POST['called'] ?? null,
        $_GET['To'] ?? null,
    ];

    // Also check session file if call_sid is provided (for recording callbacks)
    $callSid = $_REQUEST['call_sid'] ?? $_REQUEST['CallSid'] ?? '';
    if ($callSid) {
        $sessionFile = dirname(__DIR__) . '/voice/sessions/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';
        if (file_exists($sessionFile)) {
            $session = @json_decode(file_get_contents($sessionFile), true);
            if (!empty($session['called'])) {
                $candidates[] = $session['called'];
            }
        }
    }

    foreach ($candidates as $number) {
        if (empty($number)) continue;

        // Normalize: remove spaces, keep only digits and +
        $normalized = preg_replace('/[^\d+]/', '', $number);

        // Try exact match
        if (isset($phoneMap[$normalized])) {
            return $phoneMap[$normalized];
        }

        // Try without + prefix
        $withoutPlus = ltrim($normalized, '+');
        if (isset($phoneMap[$withoutPlus])) {
            return $phoneMap[$withoutPlus];
        }

        // Try with + prefix
        $withPlus = '+' . $withoutPlus;
        if (isset($phoneMap[$withPlus])) {
            return $phoneMap[$withPlus];
        }
    }

    return null;
}

// Detect domain from webhook data
$detectedDomain = detect_webhook_domain($PHONE_TO_DOMAIN);

if ($detectedDomain) {
    $_SERVER['HTTP_HOST'] = $detectedDomain;
    $_SERVER['SERVER_NAME'] = $detectedDomain;
}

// Now load the regular bootstrap
require_once __DIR__ . '/bootstrap.php';

// Log domain detection for debugging (only if voice.log exists)
$logFile = dirname(__DIR__) . '/voice/voice.log';
if (file_exists($logFile) && $detectedDomain) {
    @file_put_contents($logFile, json_encode([
        'ts' => date('c'),
        'event' => 'domain_detected',
        'detected' => $detectedDomain,
        'to_field' => $_POST['To'] ?? $_POST['Called'] ?? 'none',
    ]) . "\n", FILE_APPEND);
}
