<?php
/**
 * AI Quote Explainer - TwiML Webhook
 * Called by SignalWire when customer requests AI explanation
 */

header('Content-Type: text/xml');

require_once __DIR__ . '/../lib/CustomerFlow/Flow.php';

$flow = new CustomerFlow\Flow();
$jobId = $_GET['job'] ?? $_POST['job'] ?? '';

$job = $flow->getJob($jobId);

if (!$job) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say>Sorry, I could not find that quote. Please call 904-217-5152.</Say></Response>';
    exit;
}

// Build explanation
$vehicle = $job['vehicle'] ?: 'your vehicle';
$total = number_format($job['total'], 2);
$services = $job['services'];

$script = "Hi, this is E Z Mobile Mechanic. I'm calling about your quote for {$vehicle}. ";

if (!empty($services)) {
    $script .= "Here's what we'll do: ";
    foreach ($services as $i => $svc) {
        $name = $svc['name'] ?? $svc;
        $price = isset($svc['price']) ? number_format($svc['price'], 2) : '';

        $script .= $name . ". ";

        // Service-specific explanations
        if (stripos($name, 'diagnostic') !== false || stripos($name, 'check engine') !== false) {
            $script .= "Using our Snap-on ZEUS scanner, we'll pull all codes and live data to find the exact problem. ";
        } elseif (stripos($name, 'brake') !== false) {
            $script .= "We'll inspect pads, rotors, and calipers, then replace what's needed. ";
        } elseif (stripos($name, 'oil') !== false) {
            $script .= "We drain the old oil, swap the filter, and fill with fresh oil. ";
        } elseif (stripos($name, 'battery') !== false) {
            $script .= "We test your battery and charging system, and replace if needed. ";
        }

        if ($price) {
            $script .= "That's {$price} dollars. ";
        }
    }
}

$script .= "Your total is {$total} dollars. ";
$script .= "We come to you, so no waiting room. All work is guaranteed. ";
$script .= "To approve, tap the green button in your text, or press 1 now to talk to Kyle. ";

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Say voice="Polly.Matthew"><?= htmlspecialchars($script, ENT_XML1) ?></Say>
    <Gather numDigits="1" timeout="5" action="/voice/explain_action.php?job=<?= urlencode($jobId) ?>" method="POST">
        <Say voice="Polly.Matthew">Press 1 to talk to Kyle, or hang up to approve via text.</Say>
    </Gather>
    <Say voice="Polly.Matthew">Thanks for choosing E Z Mobile Mechanic!</Say>
</Response>
