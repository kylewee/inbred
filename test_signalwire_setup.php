#!/usr/bin/env php
<?php
/**
 * SignalWire Setup Test Script
 * Tests all configuration and connectivity for the phone system
 */

// Load configuration
require_once __DIR__ . '/api/.env.local.php';

echo "\n=== SignalWire Setup Test ===\n\n";

$tests = [];
$warnings = [];

// Test 1: SignalWire Space
echo "1. Checking SignalWire Space... ";
if (defined('SIGNALWIRE_SPACE') && SIGNALWIRE_SPACE) {
    echo "✅ " . SIGNALWIRE_SPACE . "\n";
    $tests['signalwire_space'] = true;
} else {
    echo "❌ NOT CONFIGURED\n";
    $tests['signalwire_space'] = false;
}

// Test 2: SignalWire Project ID
echo "2. Checking SignalWire Project ID... ";
if (defined('TWILIO_ACCOUNT_SID') && TWILIO_ACCOUNT_SID && TWILIO_ACCOUNT_SID !== '') {
    $sid = TWILIO_ACCOUNT_SID;
    if ($sid === 'YOUR_SIGNALWIRE_API_TOKEN_HERE') {
        echo "❌ PLACEHOLDER VALUE\n";
        $tests['project_id'] = false;
    } else {
        echo "✅ " . substr($sid, 0, 8) . "..." . "\n";
        $tests['project_id'] = true;
    }
} else {
    echo "❌ NOT CONFIGURED\n";
    $tests['project_id'] = false;
}

// Test 3: SignalWire API Token
echo "3. Checking SignalWire API Token... ";
if (defined('TWILIO_AUTH_TOKEN') && TWILIO_AUTH_TOKEN && TWILIO_AUTH_TOKEN !== '') {
    $token = TWILIO_AUTH_TOKEN;
    if ($token === 'YOUR_SIGNALWIRE_API_TOKEN_HERE') {
        echo "⚠️  PLACEHOLDER - NEEDS YOUR ACTUAL TOKEN\n";
        $tests['api_token'] = false;
        $warnings[] = "Get your API token from: https://mobilemechanic.signalwire.com/credentials";
    } else {
        echo "✅ " . substr($token, 0, 6) . "..." . "\n";
        $tests['api_token'] = true;
    }
} else {
    echo "❌ NOT CONFIGURED\n";
    $tests['api_token'] = false;
}

// Test 4: Phone Number
echo "4. Checking Phone Number... ";
if (defined('TWILIO_SMS_FROM') && TWILIO_SMS_FROM) {
    echo "✅ " . TWILIO_SMS_FROM . "\n";
    $tests['phone_number'] = true;
} else {
    echo "❌ NOT CONFIGURED\n";
    $tests['phone_number'] = false;
}

// Test 5: OpenAI API Key
echo "5. Checking OpenAI API Key... ";
if (defined('OPENAI_API_KEY') && OPENAI_API_KEY && OPENAI_API_KEY !== '') {
    echo "✅ " . substr(OPENAI_API_KEY, 0, 8) . "..." . "\n";
    $tests['openai'] = true;
} else {
    echo "❌ NOT CONFIGURED\n";
    $tests['openai'] = false;
}

// Test 6: CRM API Key
echo "6. Checking CRM API Key... ";
if (defined('CRM_API_KEY') && CRM_API_KEY && CRM_API_KEY !== '') {
    echo "✅ " . substr(CRM_API_KEY, 0, 8) . "..." . "\n";
    $tests['crm_api'] = true;
} else {
    echo "❌ NOT CONFIGURED\n";
    $tests['crm_api'] = false;
}

// Test 7: CRM Database Connection
echo "7. Checking CRM Database... ";
if (file_exists(__DIR__ . '/crm/config/database.php')) {
    require_once __DIR__ . '/crm/config/database.php';
    $mysqli = @new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    if ($mysqli->connect_errno) {
        echo "❌ CONNECTION FAILED: " . $mysqli->connect_error . "\n";
        $tests['crm_db'] = false;
    } else {
        echo "✅ Connected to " . DB_DATABASE . "\n";
        $tests['crm_db'] = true;

        // Check if leads table exists
        $result = $mysqli->query("SHOW TABLES LIKE 'app_entity_26'");
        if ($result && $result->num_rows > 0) {
            echo "   ✅ Leads table (app_entity_26) exists\n";
        } else {
            echo "   ⚠️  Leads table (app_entity_26) not found\n";
            $warnings[] = "Verify CRM_LEADS_ENTITY_ID is correct";
        }
        $mysqli->close();
    }
} else {
    echo "⚠️  CRM database config not found\n";
    $tests['crm_db'] = false;
}

// Test 8: Voice Log File
echo "8. Checking Voice Log... ";
$logFile = __DIR__ . '/voice/voice.log';
if (file_exists($logFile)) {
    echo "✅ Exists (" . number_format(filesize($logFile)) . " bytes)\n";
    if (is_writable($logFile)) {
        echo "   ✅ Writable\n";
    } else {
        echo "   ⚠️  Not writable\n";
        $warnings[] = "Voice log file is not writable";
    }
    $tests['voice_log'] = true;
} else {
    echo "⚠️  Not found (will be created on first call)\n";
    $tests['voice_log'] = true;
}

// Test 9: SignalWire API Connectivity (if token is configured)
if ($tests['api_token'] && TWILIO_AUTH_TOKEN !== 'YOUR_SIGNALWIRE_API_TOKEN_HERE') {
    echo "9. Testing SignalWire API Connection... ";
    $apiBase = 'https://' . SIGNALWIRE_SPACE;
    $url = $apiBase . '/api/laml/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '.json';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "✅ Connected successfully\n";
        $tests['api_connectivity'] = true;
    } elseif ($httpCode === 401) {
        echo "❌ Authentication failed (401)\n";
        $tests['api_connectivity'] = false;
        $warnings[] = "SignalWire API token is invalid or incorrect";
    } else {
        echo "⚠️  HTTP $httpCode" . ($error ? " - $error" : "") . "\n";
        $tests['api_connectivity'] = false;
    }
} else {
    echo "9. SignalWire API Connection... ⏭️  Skipped (waiting for API token)\n";
    $tests['api_connectivity'] = null;
}

// Summary
echo "\n=== Summary ===\n";
$passed = count(array_filter($tests, fn($v) => $v === true));
$failed = count(array_filter($tests, fn($v) => $v === false));
$total = count(array_filter($tests, fn($v) => $v !== null));

echo "Passed: $passed/$total\n";
if ($failed > 0) {
    echo "Failed: $failed\n";
}

if (!empty($warnings)) {
    echo "\n⚠️  Warnings:\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". $warning\n";
    }
}

// Next steps
echo "\n=== Next Steps ===\n";
if (!$tests['api_token'] || TWILIO_AUTH_TOKEN === 'YOUR_SIGNALWIRE_API_TOKEN_HERE') {
    echo "1. Get your SignalWire API Token:\n";
    echo "   → Go to: https://mobilemechanic.signalwire.com/credentials\n";
    echo "   → Copy your API Token\n";
    echo "   → Update line 12 in: api/.env.local.php\n";
    echo "   → Or set environment variable: export TWILIO_AUTH_TOKEN=\"your_token\"\n\n";
}

if ($passed === $total) {
    echo "✅ All tests passed! Your system is ready.\n\n";
    echo "Test your phone system:\n";
    echo "1. Call: +1 (904) 217-5152\n";
    echo "2. Monitor logs: tail -f voice/voice.log\n";
    echo "3. Check recordings: https://mechanicstaugustine.com/voice/recording_callback.php?action=recordings&token=msarec-2b7c9f1a5d4e\n";
} else {
    echo "⚠️  Please complete the configuration steps above.\n";
}

echo "\n";
