<?php
/**
 * SMS Estimate Approval Handler
 * Handles incoming SMS replies (YES/NO) for estimate approvals
 * Webhook for SignalWire incoming SMS
 */
declare(strict_types=1);

// Load environment
$envPath = __DIR__ . '/../api/.env.local.php';
if (file_exists($envPath) && !defined('SIGNALWIRE_PROJECT_ID')) {
    require_once $envPath;
}

header('Content-Type: application/xml');

// Get SMS data
$from = $_REQUEST['From'] ?? '';
$to = $_REQUEST['To'] ?? '';
$body = trim($_REQUEST['Body'] ?? '');
$messageSid = $_REQUEST['MessageSid'] ?? '';

// Log incoming SMS
$log = [
    'ts' => date('c'),
    'event' => 'sms_incoming',
    'from' => $from,
    'to' => $to,
    'body' => $body,
    'message_sid' => $messageSid
];
@file_put_contents(__DIR__ . '/sms_estimate.log', json_encode($log) . "\n", FILE_APPEND);

// Check if this is from the mechanic's number
$mechanicNumber = defined('MECHANIC_CELL_NUMBER') ? MECHANIC_CELL_NUMBER : '';
$fromNormalized = preg_replace('/[^\d]/', '', $from);
$mechanicNormalized = preg_replace('/[^\d]/', '', $mechanicNumber);

$isMechanic = (substr($fromNormalized, -10) === substr($mechanicNormalized, -10));

// Start TwiML response
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

if (!$isMechanic) {
    // Not from mechanic - could be customer inquiry
    echo "  <Message>Thanks for your message! We'll get back to you shortly. Call 904-706-6669 for immediate assistance.</Message>\n";
    echo "</Response>";
    exit;
}

// Mechanic is responding - check for YES/NO and estimate ID
$bodyUpper = strtoupper($body);

// Load pending estimates
$pendingFile = __DIR__ . '/pending_estimates.json';
$pending = [];
if (file_exists($pendingFile)) {
    $pending = json_decode(file_get_contents($pendingFile), true) ?: [];
}

// Find estimate ID in message
$estimateId = null;
if (preg_match('/\best_[a-z0-9]+\b/i', $body, $m)) {
    $estimateId = $m[0];
}

// If no ID found, use most recent pending estimate
if (!$estimateId && !empty($pending)) {
    $sorted = $pending;
    usort($sorted, function($a, $b) {
        return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
    });
    $mostRecent = reset($sorted);
    $estimateId = $mostRecent['id'] ?? null;
}

// Check response type
if (strpos($bodyUpper, 'YES') !== false) {
    // Find and process the estimate
    if ($estimateId && isset($pending[$estimateId])) {
        $est = $pending[$estimateId];
        $customerPhone = $est['lead']['phone'] ?? '';
        
        if ($customerPhone) {
            // Send estimate to customer
            $result = send_estimate_to_customer($est);
            
            if ($result['ok']) {
                // Mark as sent
                $pending[$estimateId]['sent'] = true;
                $pending[$estimateId]['sent_at'] = date('c');
                file_put_contents($pendingFile, json_encode($pending, JSON_PRETTY_PRINT));
                
                echo "  <Message>✅ Estimate sent to {$est['lead']['name']}!</Message>\n";
                
                @file_put_contents(__DIR__ . '/sms_estimate.log', json_encode([
                    'ts' => date('c'),
                    'event' => 'estimate_sent',
                    'estimate_id' => $estimateId,
                    'customer' => $customerPhone,
                    'result' => $result
                ]) . "\n", FILE_APPEND);
            } else {
                echo "  <Message>❌ Failed to send estimate: {$result['error']}</Message>\n";
            }
        } else {
            echo "  <Message>❌ No customer phone number found for this estimate.</Message>\n";
        }
    } else {
        echo "  <Message>❌ Could not find that estimate. It may have expired.</Message>\n";
    }
} elseif (strpos($bodyUpper, 'NO') !== false) {
    // Decline estimate
    if ($estimateId && isset($pending[$estimateId])) {
        $pending[$estimateId]['declined'] = true;
        $pending[$estimateId]['declined_at'] = date('c');
        file_put_contents($pendingFile, json_encode($pending, JSON_PRETTY_PRINT));
        
        echo "  <Message>👍 Estimate declined. No message sent to customer.</Message>\n";
    } else {
        echo "  <Message>OK, no estimate was sent.</Message>\n";
    }
} elseif (strpos($bodyUpper, 'LIST') !== false || strpos($bodyUpper, 'PENDING') !== false) {
    // List pending estimates
    $active = array_filter($pending, function($e) {
        return empty($e['sent']) && empty($e['declined']) && strtotime($e['expires_at'] ?? 0) > time();
    });
    
    if (empty($active)) {
        echo "  <Message>No pending estimates.</Message>\n";
    } else {
        $msg = "Pending estimates:\n";
        foreach ($active as $e) {
            $msg .= "• {$e['lead']['name']} - \${$e['estimate']['grand_total']} ({$e['id']})\n";
        }
        echo "  <Message>" . htmlspecialchars($msg) . "</Message>\n";
    }
} else {
    // Help message
    echo "  <Message>Commands:\nYES - Send estimate to customer\nNO - Decline estimate\nLIST - Show pending estimates</Message>\n";
}

echo "</Response>";

// Clean up expired estimates
foreach ($pending as $id => $est) {
    if (strtotime($est['expires_at'] ?? 0) < time()) {
        unset($pending[$id]);
    }
}
file_put_contents($pendingFile, json_encode($pending, JSON_PRETTY_PRINT));

/**
 * Send estimate to customer via SMS
 */
function send_estimate_to_customer(array $est): array {
    $lead = $est['lead'] ?? [];
    $estimate = $est['estimate'] ?? [];
    $customerPhone = $lead['phone'] ?? '';
    
    if (!$customerPhone) {
        return ['ok' => false, 'error' => 'No phone number'];
    }
    
    // Normalize phone
    $customerPhone = preg_replace('/[^\d]/', '', $customerPhone);
    if (strlen($customerPhone) === 10) {
        $customerPhone = '+1' . $customerPhone;
    } elseif ($customerPhone[0] !== '+') {
        $customerPhone = '+' . $customerPhone;
    }
    
    // Build message
    $vehicle = "{$lead['year']} {$lead['make']} {$lead['model']}";
    $total = number_format($estimate['grand_total'], 2);
    
    $msg = "Hi {$lead['first_name']}! Here's your estimate from St. Augustine Mobile Mechanic:\n\n";
    $msg .= "Vehicle: {$vehicle}\n\n";
    
    foreach ($estimate['estimates'] as $e) {
        $msg .= "• " . ucfirst($e['repair']) . ": \$" . number_format($e['total'], 2) . "\n";
    }
    
    $msg .= "\n=== TOTAL: \${$total} ===\n\n";
    $msg .= "Includes parts & labor. Reply or call 904-706-6669 to schedule!";
    
    return send_sms($customerPhone, $msg);
}

/**
 * Send SMS via SignalWire
 */
function send_sms(string $to, string $message): array {
    if (!defined('SIGNALWIRE_PROJECT_ID') || !defined('SIGNALWIRE_API_TOKEN') || !defined('SIGNALWIRE_SPACE')) {
        return ['ok' => false, 'error' => 'SignalWire not configured'];
    }
    
    $fromNumber = defined('SIGNALWIRE_PHONE_NUMBER') ? SIGNALWIRE_PHONE_NUMBER : '';
    if (!$fromNumber) {
        return ['ok' => false, 'error' => 'No from number configured'];
    }
    
    $projectId = SIGNALWIRE_PROJECT_ID;
    $apiToken = SIGNALWIRE_API_TOKEN;
    $space = SIGNALWIRE_SPACE;
    
    $url = "https://{$space}/api/laml/2010-04-01/Accounts/{$projectId}/Messages.json";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'From' => $fromNumber,
            'To' => $to,
            'Body' => $message
        ]),
        CURLOPT_USERPWD => "{$projectId}:{$apiToken}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    
    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'ok' => ($http >= 200 && $http < 300),
        'http' => $http,
        'error' => $error,
        'response' => json_decode($resp, true)
    ];
}
