<?php
/**
 * Quote System Test & Diagnostic
 */

echo "=== EZ Mobile Mechanic Quote System Test ===\n\n";

// Check required files
$files = [
    'lib/QuoteSMS.php',
    'lib/PostServiceFlow.php',
    'quote/index.php',
    'voice/quote_explainer.php',
    'voice/quote_explainer_action.php',
    'api/quote-approve.php'
];

echo "1. Checking required files...\n";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "   ✓ $file\n";
    } else {
        echo "   ✗ MISSING: $file\n";
    }
}

// Check data directory
echo "\n2. Checking data directory...\n";
$dataDir = __DIR__ . '/data';
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "   ✓ Created data directory\n";
} else {
    echo "   ✓ Data directory exists\n";
}

// Check SignalWire config
echo "\n3. Checking SignalWire configuration...\n";
require_once __DIR__ . '/api/.env.local.php';

if (defined('SIGNALWIRE_PROJECT_ID') && SIGNALWIRE_PROJECT_ID) {
    echo "   ✓ SignalWire Project ID: " . substr(SIGNALWIRE_PROJECT_ID, 0, 8) . "...\n";
} else {
    echo "   ✗ SignalWire Project ID not configured\n";
}

if (defined('SIGNALWIRE_SPACE') && SIGNALWIRE_SPACE) {
    echo "   ✓ SignalWire Space: " . SIGNALWIRE_SPACE . "\n";
} else {
    echo "   ✗ SignalWire Space not configured\n";
}

if (defined('SIGNALWIRE_PHONE_NUMBER') && SIGNALWIRE_PHONE_NUMBER) {
    echo "   ✓ Business Number: " . SIGNALWIRE_PHONE_NUMBER . "\n";
} else {
    echo "   ✗ Business Number not configured\n";
}

// Test QuoteSMS library
echo "\n4. Testing QuoteSMS library...\n";
require_once __DIR__ . '/lib/QuoteSMS.php';

try {
    $quoteSMS = new QuoteSMS();
    echo "   ✓ QuoteSMS initialized\n";

    // Create a test quote (won't send SMS, just create in DB)
    $testQuote = $quoteSMS->sendQuote([
        'customer_phone' => '+19046634789', // Your cell
        'customer_name' => 'Test Customer',
        'vehicle' => '2020 Toyota Camry',
        'services' => [
            ['name' => 'Diagnostic Scan', 'price' => 95],
            ['name' => 'Oil Change', 'price' => 65]
        ],
        'total' => 160,
        'breakdown' => 'Test quote for system verification',
        'lead_id' => null
    ]);

    if ($testQuote['success']) {
        echo "   ✓ Test quote created: " . $testQuote['quote_id'] . "\n";
        echo "   ✓ Quote URL: https://mechanicstaugustine.com/quote/" . $testQuote['quote_id'] . "\n";
        echo "   ✓ AI Explainer URL: https://mechanicstaugustine.com/quote/" . $testQuote['quote_id'] . "/explain\n";

        if ($testQuote['sms_sent']) {
            echo "   ✓ SMS sent successfully!\n";
        } else {
            echo "   ⚠ Quote created but SMS not sent (check SignalWire credentials)\n";
        }
    } else {
        echo "   ✗ Failed to create test quote\n";
    }

} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n5. Testing PostServiceFlow library...\n";
require_once __DIR__ . '/lib/PostServiceFlow.php';

try {
    $flow = new PostServiceFlow();
    echo "   ✓ PostServiceFlow initialized\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== System Ready ===\n\n";

echo "TO TEST WITH LIVE CALL:\n";
echo "1. Call: " . (SIGNALWIRE_PHONE_NUMBER ?? '(904) 706-6669') . "\n";
echo "2. Say something like:\n";
echo "   'Hi, my name is John Smith. I have a 2018 Honda Accord with a check engine light on. Can you help?'\n";
echo "3. The system will:\n";
echo "   - Record your call\n";
echo "   - Transcribe it with AI\n";
echo "   - Generate an auto-estimate\n";
echo "   - Send you a quote SMS with AI explainer button\n";
echo "4. Check your phone for the SMS!\n\n";

echo "TO MANUALLY SEND QUOTE SMS (bypass call):\n";
echo "php " . __DIR__ . "/test_send_quote.php\n\n";

echo "TO VIEW TEST QUOTE IN BROWSER:\n";
if (isset($testQuote['quote_id'])) {
    echo "https://mechanicstaugustine.com/quote/" . $testQuote['quote_id'] . "\n\n";
}

echo "TO CHECK LOGS:\n";
echo "tail -f voice/voice.log | grep quote_sms_sent\n\n";
