<?php
/**
 * Simple Form Submission Handler
 * Handles POST from landing page quote form
 * Creates lead in CRM and shows thank you page
 */

// Load config
require_once __DIR__ . '/../config/bootstrap.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// Get form data
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

// Get all other fields dynamically
$otherFields = [];
foreach ($_POST as $key => $value) {
    if (!in_array($key, ['name', 'phone', 'email'])) {
        $otherFields[$key] = trim($value);
    }
}

// Validate
$errors = [];
if (empty($name)) $errors[] = 'Name is required';
if (empty($phone)) $errors[] = 'Phone is required';

if (!empty($errors)) {
    // Show error page
    $siteName = config('site.name', 'Our Company');
    $primaryColor = config('branding.primary_color', '#2563eb');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error | <?= htmlspecialchars($siteName) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { font-family: system-ui, sans-serif; padding: 2rem; max-width: 600px; margin: 0 auto; }
            .error { background: #fee; border: 1px solid #f00; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
            a { color: <?= $primaryColor ?>; }
        </style>
    </head>
    <body>
        <h1>Oops!</h1>
        <div class="error">
            <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <p><a href="javascript:history.back()">Go back and try again</a></p>
    </body>
    </html>
    <?php
    exit;
}

// Build notes from all fields
$notes = "Source: Website Form\n";
$notes .= "Submitted: " . date('Y-m-d H:i:s') . "\n\n";
foreach ($otherFields as $key => $value) {
    if ($value) {
        $label = ucfirst(str_replace('_', ' ', $key));
        $notes .= "{$label}: {$value}\n";
    }
}

// Send to EzLead HQ (ezlead4u.com) for distribution
$ezleadResult = null;
if (config('ezlead.enabled', false)) {
    require_once __DIR__ . '/../lib/EzleadClient.php';
    $ezClient = new EzleadClient();

    if ($ezClient->isEnabled()) {
        // Split name
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $ezleadResult = $ezClient->directPost(
            config('ezlead.vertical', 'contractor'),
            config('ezlead.state', 'FL'),
            $firstName,
            $phone,
            $lastName,
            $email,
            $otherFields['city'] ?? null,
            $otherFields['zip'] ?? $otherFields['zip_code'] ?? null,
            $otherFields['address'] ?? null,
            $notes
        );

        // Log result
        @file_put_contents(
            config('ezlead.log_file', __DIR__ . '/../data/ezlead.log'),
            json_encode(['ts' => date('c'), 'result' => $ezleadResult]) . "\n",
            FILE_APPEND
        );
    }
}

// Try to create CRM lead if enabled
$leadCreated = false;
$leadId = null;

if (config('crm.enabled', false)) {
    // Load CRM helper
    require_once __DIR__ . '/../lib/CRMHelper.php';

    // Split name into first/last
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? '';

    // Build lead data
    $leadData = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone,
        'email' => $email,
        'notes' => $notes,
        'source' => 'Website',
    ];

    // Add business-specific fields
    foreach ($otherFields as $key => $value) {
        if ($value) {
            $leadData[$key] = $value;
        }
    }

    // Try CRM API
    try {
        if (defined('CRM_API_URL') && defined('CRM_API_KEY')) {
            $post = [
                'action' => 'insert',
                'key' => CRM_API_KEY,
                'entity_id' => CRM_LEADS_ENTITY_ID ?? 26,
            ];

            if (defined('CRM_USERNAME')) $post['username'] = CRM_USERNAME;
            if (defined('CRM_PASSWORD')) $post['password'] = CRM_PASSWORD;

            // Map fields - CRM API requires items[field_X] format
            $fieldMap = config('crm.field_map', []);
            $items = [];
            foreach ($leadData as $key => $value) {
                if (isset($fieldMap[$key]) && $value !== '') {
                    $items['field_' . $fieldMap[$key]] = $value;
                }
            }
            $post['items'] = $items;

            $ch = curl_init(CRM_API_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($post),  // Properly encode nested arrays
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($result, true);

            if (!empty($response['data']['id'])) {
                $leadCreated = true;
                $leadId = $response['data']['id'];

                // Distribute to PHP buyers (billing, credits, notifications)
                try {
                    require_once __DIR__ . '/../buyer/LeadDistributor.php';
                    $distributor = new LeadDistributor();
                    $distResult = $distributor->distributeLead(
                        $leadData,
                        config('site.domain', ''),
                        (int)$leadId
                    );

                    // Log distribution result
                    @file_put_contents(
                        __DIR__ . '/../data/distribution.log',
                        json_encode(['ts' => date('c'), 'lead_id' => $leadId, 'result' => $distResult]) . "\n",
                        FILE_APPEND
                    );
                } catch (Exception $distEx) {
                    error_log("LeadDistributor error: " . $distEx->getMessage());
                    @file_put_contents(
                        __DIR__ . '/../data/distribution_errors.log',
                        json_encode(['ts' => date('c'), 'lead_id' => $leadId, 'error' => $distEx->getMessage()]) . "\n",
                        FILE_APPEND
                    );
                }
            }
        }
    } catch (Exception $e) {
        // Log but don't fail
        error_log("CRM error: " . $e->getMessage());
    }
}

// Log submission
$logEntry = [
    'ts' => date('c'),
    'event' => 'form_submit',
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'fields' => $otherFields,
    'lead_created' => $leadCreated,
    'lead_id' => $leadId,
];
@file_put_contents(__DIR__ . '/../data/form_submissions.log', json_encode($logEntry) . "\n", FILE_APPEND);

// Show thank you page
$siteName = config('site.name', 'Our Company');
$sitePhone = config('site.phone', '');
$primaryColor = config('branding.primary_color', '#2563eb');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Thank You | <?= htmlspecialchars($siteName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, sans-serif;
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
        .success {
            background: #efe;
            border: 1px solid #0a0;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
        }
        h1 { color: <?= $primaryColor ?>; }
        .btn {
            display: inline-block;
            background: <?= $primaryColor ?>;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1rem;
        }
        .phone {
            font-size: 1.5rem;
            margin: 1rem 0;
        }
        .phone a {
            color: <?= $primaryColor ?>;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($siteName) ?></h1>
    <div class="success">
        <h2>Thank You, <?= htmlspecialchars($name) ?>!</h2>
        <p>We've received your request and will be in touch shortly.</p>
        <?php if ($sitePhone): ?>
        <p class="phone">
            Or call us now: <a href="tel:<?= htmlspecialchars($sitePhone) ?>"><?= htmlspecialchars($sitePhone) ?></a>
        </p>
        <?php endif; ?>
    </div>
    <a href="/" class="btn">Back to Home</a>
</body>
</html>
