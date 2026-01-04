<?php
/**
 * Form Handler - sodjacksonvillefl.com
 * Receives form submissions, creates CRM lead, distributes to buyers
 */

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../data/php_errors.log');

// Force domain for this site BEFORE loading config
$_SERVER['HTTP_HOST'] = 'sodjacksonvillefl.com';

// Load config
require_once __DIR__ . '/../../../config/bootstrap.php';

// Get form data
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$grass_type = trim($_POST['grass_type'] ?? '');
$sqft = trim($_POST['sqft'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$source = trim($_POST['source'] ?? 'sodjacksonvillefl.com');

// Validate required fields
if (empty($name) || empty($phone) || empty($address)) {
    http_response_code(400);
    die('Missing required fields: name, phone, and address are required.');
}

// Split name into first/last
$nameParts = explode(' ', $name, 2);
$firstName = $nameParts[0];
$lastName = $nameParts[1] ?? '';

// Clean phone number
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($cleanPhone) === 10) {
    $cleanPhone = '1' . $cleanPhone;
}

// Build notes field
$fullNotes = "Source: {$source}\n";
$fullNotes .= "Grass Type: {$grass_type}\n";
$fullNotes .= "Square Footage: {$sqft}\n";
if (!empty($notes)) {
    $fullNotes .= "Details: {$notes}\n";
}
$fullNotes .= "Submitted: " . date('Y-m-d H:i:s');

// Log submission
$logFile = __DIR__ . '/../../../data/form_submissions.log';
$logEntry = [
    'timestamp' => date('c'),
    'source' => $source,
    'name' => $name,
    'phone' => $cleanPhone,
    'email' => $email,
    'address' => $address,
    'grass_type' => $grass_type,
    'sqft' => $sqft,
    'notes' => $notes,
];
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);

// Create CRM lead
$crmLeadId = null;
$crmConfig = config('crm');

if ($crmConfig && !empty($crmConfig['api_url'])) {
    $fieldMap = $crmConfig['field_map'] ?? [];

    $crmData = [
        'action' => 'insert',
        'entity_id' => $crmConfig['entity_id'] ?? 26,
        'created_by' => $crmConfig['created_by'] ?? 2,
    ];

    // Map fields
    if (!empty($fieldMap['first_name'])) $crmData['field_' . $fieldMap['first_name']] = $firstName;
    if (!empty($fieldMap['last_name'])) $crmData['field_' . $fieldMap['last_name']] = $lastName;
    if (!empty($fieldMap['phone'])) $crmData['field_' . $fieldMap['phone']] = $cleanPhone;
    if (!empty($fieldMap['email'])) $crmData['field_' . $fieldMap['email']] = $email;
    if (!empty($fieldMap['address'])) $crmData['field_' . $fieldMap['address']] = $address;
    if (!empty($fieldMap['notes'])) $crmData['field_' . $fieldMap['notes']] = $fullNotes;
    if (!empty($fieldMap['sqft'])) $crmData['field_' . $fieldMap['sqft']] = $sqft;
    if (!empty($fieldMap['grass_type'])) $crmData['field_' . $fieldMap['grass_type']] = $grass_type;
    if (!empty($fieldMap['source'])) $crmData['field_' . $fieldMap['source']] = $source;

    // Make API request
    $ch = curl_init($crmConfig['api_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($crmData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-KEY: ' . ($crmConfig['api_key'] ?? ''),
        ],
        CURLOPT_USERPWD => ($crmConfig['username'] ?? '') . ':' . ($crmConfig['password'] ?? ''),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $crmLeadId = $result['id'] ?? null;
    }
}

// Send to EzLead HQ (ezlead4u.com) for distribution
$ezleadResult = null;
$ezleadConfig = config('ezlead');
if ($ezleadConfig && !empty($ezleadConfig['enabled'])) {
    require_once __DIR__ . '/../../../lib/EzleadClient.php';
    $ezClient = new EzleadClient();

    if ($ezClient->isEnabled()) {
        $ezleadResult = $ezClient->directPost(
            $ezleadConfig['vertical'] ?? 'sod',
            $ezleadConfig['state'] ?? 'FL',
            $firstName,
            $cleanPhone,
            $lastName,
            $email,
            null,  // city
            null,  // zip
            $address,
            $fullNotes
        );

        // Log result
        $logFile = $ezleadConfig['log_file'] ?? __DIR__ . '/../../../data/ezlead.log';
        file_put_contents($logFile, json_encode([
            'ts' => date('c'),
            'source' => $source,
            'result' => $ezleadResult
        ]) . "\n", FILE_APPEND | LOCK_EX);
    }
}

// Distribute to local buyers (fallback/backup)
$buyerDbFile = __DIR__ . '/../../../data/buyers.db';
if (file_exists($buyerDbFile)) {
    require_once __DIR__ . '/../../../buyer/LeadDistributor.php';

    $distributor = new LeadDistributor();
    $leadData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $cleanPhone,
        'email' => $email,
        'address' => $address,
        'grass_type' => $grass_type,
        'sqft' => $sqft,
        'notes' => $notes,
    ];

    // Use CRM lead ID if available, otherwise generate a unique ID
    $leadId = $crmLeadId ?? time();
    $distributor->distributeLead($leadData, $source, $leadId);
}

// Show thank you page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You | Sod Jacksonville FL</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        .checkmark {
            width: 80px;
            height: 80px;
            background: #15803d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .checkmark svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        h1 { color: #15803d; margin-bottom: 15px; }
        p { margin-bottom: 15px; color: #666; }
        .phone {
            font-size: 1.5em;
            font-weight: bold;
            color: #15803d;
            margin: 20px 0;
        }
        .phone a { color: #15803d; text-decoration: none; }
        .btn {
            display: inline-block;
            background: #15803d;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
        }
        .btn:hover { background: #0f5c2e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkmark">
            <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <h1>Thank You!</h1>
        <p>We received your request for a sod installation estimate.</p>
        <p>One of our specialists will contact you shortly at:</p>
        <div class="phone"><a href="tel:<?= htmlspecialchars($cleanPhone) ?>"><?= htmlspecialchars($phone) ?></a></div>
        <p><strong>What's next?</strong></p>
        <p>We'll call you within 1-2 business hours to discuss your project and provide a free estimate.</p>
        <a href="/" class="btn">Back to Home</a>
    </div>
</body>
</html>
