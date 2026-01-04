<?php
/**
 * AI Quote Explainer - Phone Call Handler
 *
 * When customer taps "Hear AI Explain", SignalWire calls them
 * and this TwiML script provides the AI voice explanation
 */

header('Content-Type: text/xml');

require_once __DIR__ . '/../lib/QuoteSMS.php';

$quoteId = $_GET['quote_id'] ?? '';

if (!$quoteId) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say>Quote not found. Please contact us at 904-706-6669.</Say></Response>';
    exit;
}

$quoteSMS = new QuoteSMS();
$quote = $quoteSMS->getQuote($quoteId);

if (!$quote) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response><Say>Quote not found. Please contact us at 904-706-6669.</Say></Response>';
    exit;
}

// Build AI explanation script
$vehicle = $quote['vehicle'];
$total = number_format($quote['total_price'], 2);
// Services are already decoded by getQuote()
$services = $quote['services'];

$explanation = "Hi! This is the EZ Mobile Mechanic AI assistant. ";
$explanation .= "I'm calling to explain your quote for your " . $vehicle . ". ";
$explanation .= "\n\n";

if (!empty($services)) {
    $explanation .= "Here's what we'll be doing:\n\n";

    foreach ($services as $idx => $service) {
        $serviceName = $service['name'] ?? $service;
        $servicePrice = isset($service['price']) ? number_format($service['price'], 2) : '';

        $explanation .= ($idx + 1) . ". " . $serviceName;

        // Add detailed explanation based on service type
        if (stripos($serviceName, 'brake') !== false) {
            $explanation .= ". We'll inspect your brake pads, rotors, calipers, and brake fluid. ";
            $explanation .= "If needed, we'll replace worn components and test the system. ";
        } elseif (stripos($serviceName, 'oil') !== false || stripos($serviceName, 'change') !== false) {
            $explanation .= ". We'll drain the old oil, replace the filter, and add fresh oil. ";
            $explanation .= "We use quality synthetic or conventional oil based on your vehicle's needs. ";
        } elseif (stripos($serviceName, 'diagnostic') !== false || stripos($serviceName, 'check engine') !== false) {
            $explanation .= ". Using our Snap-on ZEUS advanced scanner, we'll read all error codes, ";
            $explanation .= "analyze live data from your vehicle's computer, and pinpoint the exact problem. ";
        } elseif (stripos($serviceName, 'battery') !== false) {
            $explanation .= ". We'll test your battery, alternator, and charging system. ";
            $explanation .= "If replacement is needed, we'll install a quality battery with warranty. ";
        }

        if ($servicePrice) {
            $explanation .= "This service is $" . $servicePrice . ". ";
        }

        $explanation .= "\n\n";
    }
}

$explanation .= "Your total for all services is $" . $total . ". ";
$explanation .= "\n\n";
$explanation .= "We're a mobile service, so we come to you - your home, work, or anywhere in St. Augustine. ";
$explanation .= "You don't have to waste time sitting in a waiting room. ";
$explanation .= "\n\n";
$explanation .= "All our work is guaranteed, and we only recommend what you actually need. ";
$explanation .= "No upselling, no hidden fees. ";
$explanation .= "\n\n";
$explanation .= "To approve this quote and schedule service, just tap the green button in the text message we sent you. ";
$explanation .= "Or call us directly at 904-706-6669. ";
$explanation .= "\n\n";
$explanation .= "Do you have any questions about this quote? Press 1 to speak with Kyle, or hang up if you're all set. ";

// Generate TwiML
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
    <Say voice="Polly.Joanna">
        <?php echo htmlspecialchars($explanation, ENT_XML1); ?>
    </Say>
    <Gather numDigits="1" timeout="5" action="/voice/quote_explainer_action.php?quote_id=<?php echo urlencode($quoteId); ?>" method="POST">
        <Say voice="Polly.Joanna">Press 1 to speak with Kyle now, or just hang up if you're ready to approve.</Say>
    </Gather>
    <Say voice="Polly.Joanna">Thanks for choosing EZ Mobile Mechanic. Have a great day!</Say>
</Response>
