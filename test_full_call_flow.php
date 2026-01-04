#!/usr/bin/env php
<?php
/**
 * Test Full Call Flow - Simulates SignalWire webhook
 * Tests: Recording → Transcription → AI Extraction → Auto-Estimate → CRM Lead
 */

echo "═══════════════════════════════════════════════════\n";
echo "  FULL CALL FLOW TEST\n";
echo "═══════════════════════════════════════════════════\n\n";

// Simulate realistic SignalWire webhook data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$_POST = [
    'CallSid' => 'CA_TEST_' . uniqid(),
    'RecordingSid' => 'RE_TEST_' . uniqid(),
    'RecordingUrl' => 'https://api.signalwire.com/test/recording.mp3',
    'RecordingDuration' => '85',
    'From' => '+19045551234',
    'To' => '+19047066669',
    'CallStatus' => 'completed',
    'DialCallDuration' => '85',

    // Realistic customer transcript
    'TranscriptionText' => "Hi, I'm calling about my 2015 Honda Accord. The starter is making a clicking noise and the car won't start. I'm at 123 Main Street in Jacksonville. My name is Sarah Johnson and my number is 904-555-1234. Can you help me today?",

    // Bypass deduplication for testing
    'BypassDedupe' => '1'
];

echo "Test Call Data:\n";
echo "  Customer: Sarah Johnson\n";
echo "  Phone: +19045551234\n";
echo "  Vehicle: 2015 Honda Accord\n";
echo "  Issue: Starter clicking, won't start\n";
echo "  Location: 123 Main Street, Jacksonville\n\n";

echo "Processing webhook...\n\n";

// Capture output from recording_callback.php
ob_start();
require __DIR__ . '/voice/recording_callback.php';
$output = ob_get_clean();

// Parse the JSON response
$response = json_decode($output, true);

echo "═══════════════════════════════════════════════════\n";
echo "  RESULTS\n";
echo "═══════════════════════════════════════════════════\n\n";

if ($response && isset($response['ok'])) {
    echo "✅ Webhook processed successfully!\n\n";
} else {
    echo "Response: " . $output . "\n\n";
}

// Check voice.log for the created entry
$logFile = __DIR__ . '/voice/voice.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastEntry = json_decode(end($lines), true);

    if ($lastEntry) {
        echo "📋 Extracted Customer Data:\n";
        if (isset($lastEntry['crm_lead'])) {
            $lead = $lastEntry['crm_lead'];
            echo "  Name: " . ($lead['first_name'] ?? '') . " " . ($lead['last_name'] ?? '') . "\n";
            echo "  Phone: " . ($lead['phone'] ?? 'N/A') . "\n";
            echo "  Vehicle: " . ($lead['year'] ?? '') . " " . ($lead['make'] ?? '') . " " . ($lead['model'] ?? '') . "\n";
            echo "  Issue: " . (substr($lead['notes'] ?? '', 0, 100)) . "...\n\n";
        }

        echo "💰 Auto-Estimate:\n";
        if (isset($lastEntry['auto_estimate'])) {
            $estimate = $lastEntry['auto_estimate'];
            if (!empty($estimate['estimates'])) {
                foreach ($estimate['estimates'] as $est) {
                    echo "  • " . $est['repair'] . "\n";
                    echo "    Labor: " . $est['labor_hours'] . " hrs = $" . $est['labor_cost'] . "\n";
                    echo "    Parts: $" . $est['parts_cost'] . "\n";
                    echo "    Total: $" . $est['total'] . "\n\n";
                }
                echo "  GRAND TOTAL: $" . ($estimate['grand_total'] ?? 'N/A') . "\n\n";
            } else {
                echo "  No repairs detected in transcript\n\n";
            }
        } else {
            echo "  Auto-estimate not generated (check if function exists)\n\n";
        }

        echo "🗄️  CRM Result:\n";
        if (isset($lastEntry['crm_result'])) {
            $crm = $lastEntry['crm_result'];
            if (isset($crm['error'])) {
                echo "  ❌ Error: " . $crm['error'] . "\n";
            } elseif (isset($crm['fallback']['id'])) {
                echo "  ✅ Lead created (DB fallback): ID " . $crm['fallback']['id'] . "\n";
            } elseif (isset($crm['data']['id'])) {
                echo "  ✅ Lead created (API): ID " . $crm['data']['id'] . "\n";
            } else {
                echo "  Status: " . json_encode($crm) . "\n";
            }
        } else {
            echo "  No CRM result logged\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════\n";
echo "  Verify in CRM:\n";
echo "  mysql -u kylewee -p rukovoditel\n";
echo "  SELECT * FROM app_entity_26 ORDER BY id DESC LIMIT 1;\n";
echo "═══════════════════════════════════════════════════\n";
