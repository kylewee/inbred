<?php
/**
 * Lead Webhook Receiver
 * Receives leads from ezlead4u.com and delivers via:
 * - Log to file
 * - Email notification
 * - SMS notification
 * - CRM entry (Rukovoditel)
 */

header('Content-Type: application/json');

// Get POST data
$input = file_get_contents('php://input');
$lead = json_decode($input, true);

if (!$lead) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$logFile = __DIR__ . '/../data/leads_received.log';
$timestamp = date('Y-m-d H:i:s');

// 1. Log to file
$logEntry = [
    'timestamp' => $timestamp,
    'lead_id' => $lead['lead_id'] ?? null,
    'source' => $lead['source'] ?? null,
    'vertical' => $lead['vertical'] ?? null,
    'contact' => $lead['contact'] ?? [],
    'location' => $lead['location'] ?? [],
    'description' => $lead['description'] ?? '',
];
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);

// Extract contact info
$firstName = $lead['contact']['first_name'] ?? '';
$lastName = $lead['contact']['last_name'] ?? '';
$phone = $lead['contact']['phone'] ?? '';
$email = $lead['contact']['email'] ?? '';
$address = $lead['location']['address'] ?? '';
$city = $lead['location']['city'] ?? '';
$state = $lead['location']['state'] ?? '';
$zip = $lead['location']['zip_code'] ?? '';
$vertical = $lead['vertical'] ?? 'unknown';
$source = $lead['source'] ?? 'unknown';
$description = $lead['description'] ?? '';

// 2. Send Email
$toEmail = 'sodjacksonville@gmail.com';
$subject = "New {$vertical} Lead from {$source}";
$body = "NEW LEAD RECEIVED\n";
$body .= "================\n\n";
$body .= "Name: {$firstName} {$lastName}\n";
$body .= "Phone: {$phone}\n";
$body .= "Email: {$email}\n";
$body .= "Address: {$address}, {$city}, {$state} {$zip}\n";
$body .= "Vertical: {$vertical}\n";
$body .= "Source: {$source}\n";
$body .= "\nDescription:\n{$description}\n";
$body .= "\n---\nLead ID: {$lead['lead_id']}\n";
$body .= "Received: {$timestamp}\n";

$emailSent = mail($toEmail, $subject, $body, "From: leads@ezlead4u.com");

// 3. Send SMS
$smsResult = null;
$smsPhone = '9042175152'; // Kyle's phone
if (!empty($phone)) {
    $smsMessage = "New {$vertical} lead: {$firstName} {$lastName}, {$phone}, {$city} {$state}. Source: {$source}";

    // Try SignalWire SMS
    $envFile = __DIR__ . '/.env.local.php';
    if (file_exists($envFile)) {
        require_once $envFile;

        if (defined('SIGNALWIRE_PROJECT_ID') && defined('SIGNALWIRE_API_TOKEN')) {
            $projectId = SIGNALWIRE_PROJECT_ID;
            $apiToken = SIGNALWIRE_API_TOKEN;
            $space = SIGNALWIRE_SPACE ?? 'mobilemechanic.signalwire.com';
            $fromNumber = SIGNALWIRE_PHONE_NUMBER ?? '+19047066669';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://{$space}/api/laml/2010-04-01/Accounts/{$projectId}/Messages.json");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'From' => $fromNumber,
                'To' => '+1' . preg_replace('/[^0-9]/', '', $smsPhone),
                'Body' => $smsMessage
            ]));
            curl_setopt($ch, CURLOPT_USERPWD, "{$projectId}:{$apiToken}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $smsResult = curl_exec($ch);
            curl_close($ch);
        }
    }
}

// 4. CRM Entry (Rukovoditel) - if not already synced by ezlead4u
// The lead distribution already syncs to CRM, so we just log confirmation
$crmNote = "Lead received via webhook. Email sent: " . ($emailSent ? 'yes' : 'no') . ", SMS sent: " . ($smsResult ? 'yes' : 'no');

// Response
$response = [
    'success' => true,
    'message' => 'Lead received and processed',
    'lead_id' => $lead['lead_id'],
    'actions' => [
        'logged' => true,
        'email_sent' => $emailSent,
        'sms_sent' => !empty($smsResult),
    ]
];

echo json_encode($response);
