<?php
/**
 * Customer Intake Form
 * Text customers a link to this form - they fill out vehicle info
 * and we generate an estimate automatically.
 */
declare(strict_types=1);

$envPath = __DIR__ . '/../api/.env.local.php';
if (file_exists($envPath) && !defined('CRM_API_URL')) {
    require_once $envPath;
}

// Include auto-estimate
if (!function_exists('auto_estimate_from_transcript')) {
    require_once __DIR__ . '/../scraper/auto_estimate.php';
}

// Local SMS function
function send_approval_sms_local(array $estimate, array $leadData): array {
    if (!defined('SIGNALWIRE_PROJECT_ID') || !defined('SIGNALWIRE_API_TOKEN') || !defined('SIGNALWIRE_SPACE')) {
        return ['ok' => false, 'error' => 'SignalWire not configured'];
    }
    
    $mechanicNumber = defined('MECHANIC_CELL_NUMBER') ? MECHANIC_CELL_NUMBER : '';
    $fromNumber = defined('SIGNALWIRE_PHONE_NUMBER') ? SIGNALWIRE_PHONE_NUMBER : '';
    
    if (!$mechanicNumber || !$fromNumber) {
        return ['ok' => false, 'error' => 'Phone numbers not configured'];
    }
    
    // Store pending estimate
    $pendingFile = __DIR__ . '/../voice/pending_estimates.json';
    $pending = [];
    if (file_exists($pendingFile)) {
        $pending = json_decode(file_get_contents($pendingFile), true) ?: [];
    }
    
    $pendingId = uniqid('est_');
    $pending[$pendingId] = [
        'id' => $pendingId,
        'estimate' => $estimate,
        'lead' => $leadData,
        'created_at' => date('c'),
        'expires_at' => date('c', strtotime('+24 hours'))
    ];
    file_put_contents($pendingFile, json_encode($pending, JSON_PRETTY_PRINT));
    
    // Build message
    $vehicle = "{$leadData['year']} {$leadData['make']} {$leadData['model']}";
    $customerPhone = $leadData['phone'] ?? 'Unknown';
    $total = number_format($estimate['grand_total'], 2);
    
    $repairs = [];
    foreach ($estimate['estimates'] as $est) {
        $repairs[] = $est['repair'];
    }
    
    $msg = "NEW ESTIMATE REQUEST\n";
    $msg .= "Customer: {$leadData['name']}\n";
    $msg .= "Phone: {$customerPhone}\n";
    $msg .= "Vehicle: {$vehicle}\n";
    $msg .= "Repairs: " . implode(', ', $repairs) . "\n";
    $msg .= "Total: \${$total}\n\n";
    $msg .= "Reply YES to send to customer.\n";
    $msg .= "(ID: {$pendingId})";
    
    // Send via SignalWire
    $url = "https://" . SIGNALWIRE_SPACE . "/api/laml/2010-04-01/Accounts/" . SIGNALWIRE_PROJECT_ID . "/Messages.json";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'From' => $fromNumber,
            'To' => $mechanicNumber,
            'Body' => $msg
        ]),
        CURLOPT_USERPWD => SIGNALWIRE_PROJECT_ID . ":" . SIGNALWIRE_API_TOKEN,
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Include helpers for CRM - use lib mode
    if (!function_exists('create_crm_lead')) {
        define('VOICE_LIB_ONLY', true);
        require_once __DIR__ . '/../voice/recording_callback.php';
    }
    
    // Get form data
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'phone' => preg_replace('/[^\d\+]/', '', $_POST['phone'] ?? ''),
        'year' => trim($_POST['year'] ?? ''),
        'make' => trim($_POST['make'] ?? ''),
        'model' => trim($_POST['model'] ?? ''),
        'engine' => trim($_POST['engine'] ?? ''),
        'problem' => trim($_POST['problem'] ?? ''),
    ];
    
    // Split name
    $nameParts = explode(' ', $data['name'], 2);
    $data['first_name'] = $nameParts[0] ?? $data['name'];
    $data['last_name'] = $nameParts[1] ?? '';
    
    // Validation
    $errors = [];
    if (empty($data['name'])) $errors[] = 'Name is required';
    if (empty($data['phone']) || strlen($data['phone']) < 10) $errors[] = 'Valid phone is required';
    if (empty($data['year'])) $errors[] = 'Year is required';
    if (empty($data['make'])) $errors[] = 'Make is required';
    if (empty($data['model'])) $errors[] = 'Model is required';
    if (empty($data['problem'])) $errors[] = 'Please describe what\'s wrong';
    
    if (!empty($errors)) {
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }
    
    // Generate estimate
    $estimate = auto_estimate_from_transcript(
        $data['problem'],
        $data['year'],
        $data['make'],
        $data['model']
    );
    
    // Create CRM lead
    $crmResult = null;
    $smsResult = null;
    if (function_exists('create_crm_lead')) {
        $leadData = [
            'phone' => $data['phone'],
            'name' => $data['name'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'year' => $data['year'],
            'make' => $data['make'],
            'model' => $data['model'],
            'engine' => $data['engine'],
            'notes' => $data['problem'],
            'source' => 'Web Form',
            'transcript' => "Vehicle: {$data['year']} {$data['make']} {$data['model']}\nEngine: {$data['engine']}\nProblem: {$data['problem']}",
            'created_at' => date('c'),
        ];
        
        // Add estimate to notes if successful
        if (!empty($estimate['success'])) {
            $leadData['notes'] .= "\n\n" . format_estimate_text($estimate);
            // Send approval SMS to mechanic
            $smsResult = send_approval_sms_local($estimate, $leadData);
        }
        
        $crmResult = create_crm_lead($leadData);
    }
    
    // Log
    $log = [
        'ts' => date('c'),
        'event' => 'form_submission',
        'data' => $data,
        'estimate' => $estimate,
        'crm_result' => $crmResult,
        'sms_result' => $smsResult,
    ];
    @file_put_contents(__DIR__ . '/customer_form.log', json_encode($log) . "\n", FILE_APPEND);
    
    // Return success with estimate
    echo json_encode([
        'ok' => true,
        'estimate' => $estimate,
        'message' => !empty($estimate['success']) 
            ? 'Thanks! Your estimate is $' . number_format($estimate['grand_total'], 2) . '. We\'ll text you shortly!'
            : 'Thanks! We\'ll get you a quote and text you shortly!'
    ]);
    exit;
}

$host = $_SERVER['HTTP_HOST'] ?? 'mechanicstaugustine.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get a Quote - St. Augustine Mobile Mechanic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            padding: 20px;
            color: #e2e8f0;
        }
        .container { max-width: 500px; margin: 0 auto; }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo h1 { font-size: 24px; color: #60a5fa; }
        .logo p { color: #94a3b8; font-size: 14px; }
        .card {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; color: #94a3b8; font-size: 14px; }
        input, select, textarea {
            width: 100%; padding: 12px 14px; border-radius: 10px;
            border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 16px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        textarea { min-height: 100px; resize: vertical; }
        .row { display: flex; gap: 12px; }
        .row .form-group { flex: 1; }
        button {
            width: 100%; padding: 14px; border-radius: 10px; border: none;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 8px;
        }
        button:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .success { background: #065f46; border: 1px solid #10b981; padding: 16px; border-radius: 10px; text-align: center; display: none; }
        .success h2 { color: #34d399; margin-bottom: 8px; }
        .estimate-box { background: #0f172a; border-radius: 10px; padding: 16px; margin-top: 12px; text-align: left; }
        .estimate-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #334155; }
        .estimate-total { font-weight: bold; font-size: 18px; color: #34d399; padding-top: 12px; }
        .error { color: #f87171; font-size: 14px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔧 St. Augustine Mobile Mechanic</h1>
            <p>Get a free estimate in seconds</p>
        </div>
        <div class="card">
            <form id="quoteForm">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" placeholder="John Smith" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="904-555-1234" required>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="text" id="year" name="year" placeholder="2019" maxlength="4" required>
                    </div>
                    <div class="form-group">
                        <label for="make">Make</label>
                        <input type="text" id="make" name="make" placeholder="Honda" required>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" placeholder="Civic" required>
                    </div>
                    <div class="form-group">
                        <label for="engine">Engine (optional)</label>
                        <input type="text" id="engine" name="engine" placeholder="2.0L or V6">
                    </div>
                </div>
                <div class="form-group">
                    <label for="problem">What's wrong / what service do you need?</label>
                    <textarea id="problem" name="problem" placeholder="e.g. Need brakes and oil change, car is making a grinding noise when I brake..." required></textarea>
                </div>
                <button type="submit" id="submitBtn">Get My Estimate</button>
                <div class="error" id="errorMsg"></div>
            </form>
            <div class="success" id="successBox">
                <h2>✅ Got It!</h2>
                <p id="successMsg"></p>
                <div class="estimate-box" id="estimateBox" style="display:none;">
                    <div id="estimateItems"></div>
                    <div class="estimate-item estimate-total">
                        <span>TOTAL</span>
                        <span id="estimateTotal">$0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('quoteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const errorMsg = document.getElementById('errorMsg');
            const successBox = document.getElementById('successBox');
            const form = this;
            btn.disabled = true;
            btn.textContent = 'Getting estimate...';
            errorMsg.textContent = '';
            try {
                const formData = new FormData(form);
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.ok) {
                    form.style.display = 'none';
                    successBox.style.display = 'block';
                    document.getElementById('successMsg').textContent = result.message;
                    if (result.estimate && result.estimate.success) {
                        const estimateBox = document.getElementById('estimateBox');
                        const itemsDiv = document.getElementById('estimateItems');
                        estimateBox.style.display = 'block';
                        let html = '';
                        result.estimate.estimates.forEach(est => {
                            html += `<div class="estimate-item"><span>${est.repair}</span><span>$${est.total.toFixed(2)}</span></div>`;
                        });
                        itemsDiv.innerHTML = html;
                        document.getElementById('estimateTotal').textContent = '$' + result.estimate.grand_total.toFixed(2);
                    }
                } else {
                    errorMsg.textContent = result.errors ? result.errors.join(', ') : 'Something went wrong';
                    btn.disabled = false;
                    btn.textContent = 'Get My Estimate';
                }
            } catch (err) {
                errorMsg.textContent = 'Connection error. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Get My Estimate';
            }
        });
        document.getElementById('phone').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    </script>
</body>
</html>
