<?php
/**
 * Customer-Facing AI Estimate Tool
 * With rate limiting, honeypot protection, CRM integration, and delivery options
 */
session_start();
require_once __DIR__ . '/api/.env.local.php';

// Rate limiting: max 10 estimates per IP per day
$rateLimitFile = __DIR__ . '/voice/estimate_rate_limits.json';

function checkRateLimit($ip) {
    global $rateLimitFile;
    $limits = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];
    $today = date('Y-m-d');
    if (!isset($limits[$today])) $limits[$today] = [];
    if (!isset($limits[$today][$ip])) $limits[$today][$ip] = 0;
    return $limits[$today][$ip] < 10;
}

function incrementRateLimit($ip) {
    global $rateLimitFile;
    $limits = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];
    $today = date('Y-m-d');

    // Clean old dates
    foreach (array_keys($limits) as $date) {
        if ($date < date('Y-m-d', strtotime('-1 day'))) unset($limits[$date]);
    }

    if (!isset($limits[$today])) $limits[$today] = [];
    if (!isset($limits[$today][$ip])) $limits[$today][$ip] = 0;
    $limits[$today][$ip]++;

    file_put_contents($rateLimitFile, json_encode($limits));
}

function normalizePhone($phone) {
    $digits = preg_replace('/[^\d]/', '', $phone);
    if (strlen($digits) === 10) return '+1' . $digits;
    if (strlen($digits) === 11 && $digits[0] === '1') return '+' . $digits;
    return '+' . $digits;
}

function getEstimateFromAI($vehicle, $problem) {
    $request = "{$vehicle['year']} {$vehicle['make']} {$vehicle['model']}";
    if (!empty($vehicle['engine'])) {
        $request .= " ({$vehicle['engine']})";
    }
    $request .= " - $problem";

    $prompt = <<<PROMPT
You are an automotive labor time expert. Analyze this customer repair request and provide an accurate estimate.

CUSTOMER REQUEST:
$request

Respond in this EXACT JSON format only, no other text:
{
  "vehicle": {
    "year": "2004",
    "make": "BMW",
    "model": "330xi",
    "engine": "3.0L I6",
    "notes": "E46 chassis"
  },
  "repair": {
    "name": "Power steering pressure hose replacement",
    "description": "Replace high pressure power steering hose from reservoir to cooling line",
    "book_time_low": 0.8,
    "book_time_high": 1.2,
    "book_time_typical": 1.0,
    "complexity": "standard",
    "notes": "Labor only - customer supplying part"
  }
}

IMPORTANT GUIDELINES:
- book_time values are in HOURS (decimal)
- Use industry-standard "book time" labor estimates (AllData/Mitchell equivalent)
- Be accurate - mechanics rely on this for real quotes
- Factor in the specific engine size when calculating labor time
- complexity can be: simple, standard, moderate, complex, major
- If vehicle info is missing, make reasonable assumptions based on common configurations
- If you can't determine the repair, set book_time values to null
PROMPT;

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an automotive labor time estimator. Always respond with valid JSON only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;

    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $content = preg_replace('/^```json\s*/i', '', $content);
    $content = preg_replace('/\s*```$/i', '', $content);

    $result = json_decode(trim($content), true);

    if ($result && isset($result['repair']['book_time_typical'])) {
        $hours = (float)$result['repair']['book_time_typical'];
        $laborCost = $hours <= 1.0 ? $hours * 150 : 150 + (($hours - 1.0) * 100);
        $result['estimate'] = [
            'labor_hours' => $hours,
            'labor_cost' => round($laborCost, 2),
        ];
    }

    return $result;
}

function saveToCRM($data) {
    require_once __DIR__ . '/crm/config/database.php';

    $mysqli = new mysqli(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    if ($mysqli->connect_error) return false;

    $notes = "ONLINE ESTIMATE REQUEST\n";
    $notes .= "========================\n";
    $notes .= "Repair: " . ($data['repair_name'] ?? 'Unknown') . "\n";
    $notes .= "Description: " . ($data['repair_desc'] ?? '') . "\n";
    $notes .= "Book Time: " . ($data['book_time'] ?? '?') . " hrs\n";
    $notes .= "Estimate: $" . number_format($data['labor_cost'] ?? 0, 2) . "\n";
    $notes .= "------------------------\n";
    $notes .= "Customer Problem: " . ($data['problem'] ?? '');

    $sql = "INSERT INTO app_entity_26 (date_added, created_by, field_219, field_220, field_227, field_235, field_231, field_232, field_233, field_230) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    $now = time();
    $createdBy = 0;
    $stmt->bind_param('iissssssss',
        $now,
        $createdBy,
        $data['first_name'],
        $data['last_name'],
        $data['phone'],
        $data['email'],
        $data['year'],
        $data['make'],
        $data['model'],
        $notes
    );

    $result = $stmt->execute();
    $leadId = $result ? $mysqli->insert_id : 0;
    $stmt->close();
    $mysqli->close();

    return $leadId;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Honeypot check - if this field is filled, it's a bot
    if (!empty($_POST['website'])) {
        echo json_encode(['ok' => false, 'error' => 'Invalid request']);
        exit;
    }

    if ($action === 'get_estimate') {
        // Rate limit check
        if (!checkRateLimit($ip)) {
            echo json_encode(['ok' => false, 'error' => 'Too many requests today. Please try again tomorrow or call us at 904-706-6669.']);
            exit;
        }

        // Get form data
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = normalizePhone($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $engine = trim($_POST['engine'] ?? '');
        $problem = trim($_POST['problem'] ?? '');

        // Validation
        if (!$firstName || !$phone || !$year || !$make || !$model || !$problem) {
            echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields']);
            exit;
        }

        // Get AI estimate
        $vehicle = ['year' => $year, 'make' => $make, 'model' => $model, 'engine' => $engine];
        $estimate = getEstimateFromAI($vehicle, $problem);

        if (!$estimate) {
            echo json_encode(['ok' => false, 'error' => 'Could not generate estimate. Please call us at 904-706-6669.']);
            exit;
        }

        // Increment rate limit
        incrementRateLimit($ip);

        // Save to CRM
        $crmData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'year' => $year,
            'make' => $make,
            'model' => $model,
            'repair_name' => $estimate['repair']['name'] ?? '',
            'repair_desc' => $estimate['repair']['description'] ?? '',
            'book_time' => $estimate['repair']['book_time_typical'] ?? 0,
            'labor_cost' => $estimate['estimate']['labor_cost'] ?? 0,
            'problem' => $problem,
        ];

        $leadId = saveToCRM($crmData);

        // Send to ezlead4u.com for distribution
        require_once __DIR__ . '/lib/EzleadClient.php';
        $ezlead = new EzleadClient();
        if ($ezlead->isEnabled()) {
            $description = "Vehicle: $year $make $model" . ($engine ? " ($engine)" : "") . "\n";
            $description .= "Repair: " . ($estimate['repair']['name'] ?? 'Unknown') . "\n";
            $description .= "Problem: $problem\n";
            $description .= "Estimate: $" . ($estimate['estimate']['labor_cost'] ?? '?');

            $ezleadResult = $ezlead->directPost(
                defined('EZLEAD_VERTICAL') ? EZLEAD_VERTICAL : 'mechanic',
                'FL',  // Default state - could be made configurable
                $firstName,
                $phone,
                $lastName,
                $email,
                null,  // city
                null,  // zip
                null,  // address
                $description
            );
            // Log result (non-blocking - don't fail if ezlead fails)
            if (!$ezleadResult['success']) {
                error_log("EzLead submission failed: " . ($ezleadResult['error'] ?? 'Unknown error'));
            }
        }

        // Store for delivery options
        $_SESSION['last_estimate'] = [
            'estimate' => $estimate,
            'customer' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email,
            ],
            'lead_id' => $leadId,
        ];

        echo json_encode(['ok' => true, 'estimate' => $estimate, 'lead_id' => $leadId]);
        exit;
    }

    // Send estimate via text (keeping this for when 10DLC is set up)
    if ($action === 'send_text') {
        echo json_encode(['ok' => false, 'error' => 'Text delivery coming soon! Please take a screenshot or call 904-706-6669.']);
        exit;
    }

    // Send estimate via email
    if ($action === 'send_email') {
        $data = $_SESSION['last_estimate'] ?? null;
        if (!$data || empty($data['customer']['email'])) {
            echo json_encode(['ok' => false, 'error' => 'No email address provided']);
            exit;
        }

        $est = $data['estimate'];
        $cust = $data['customer'];

        $subject = "Your Repair Estimate - EZ Mobile Mechanic";
        $body = "Hi {$cust['first_name']},\n\n";
        $body .= "Thank you for requesting an estimate! Here are the details:\n\n";
        $body .= "Vehicle: {$est['vehicle']['year']} {$est['vehicle']['make']} {$est['vehicle']['model']}";
        if (!empty($est['vehicle']['engine'])) $body .= " ({$est['vehicle']['engine']})";
        $body .= "\n";
        $body .= "Repair: {$est['repair']['name']}\n";
        $body .= "Description: {$est['repair']['description']}\n";
        $body .= "Labor Time: {$est['repair']['book_time_typical']} hours\n\n";
        $body .= "ESTIMATED COST: $" . number_format($est['estimate']['labor_cost'], 2) . " (labor only)\n\n";
        $body .= "Ready to schedule? Call us at 904-706-6669 or reply to this email!\n\n";
        $body .= "- Kyle, EZ Mobile Mechanic\n";
        $body .= "St. Augustine, FL";

        $headers = "From: EZ Mobile Mechanic <noreply@mechanicstaugustine.com>\r\n";
        $sent = @mail($cust['email'], $subject, $body, $headers);
        echo json_encode(['ok' => $sent, 'error' => $sent ? null : 'Failed to send email']);
        exit;
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get a Free Estimate - EZ Mobile Mechanic St. Augustine</title>
    <meta name="description" content="Get an instant repair estimate for your vehicle. Mobile mechanic service in St. Augustine, FL.">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 28px;
            color: #1e293b;
            text-align: center;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 32px;
        }
        .form-section {
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            font-size: 14px;
        }
        label .optional {
            font-weight: 400;
            color: #9ca3af;
        }
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #2563eb;
        }
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        .row-4 {
            display: grid;
            grid-template-columns: 80px 1fr 1fr 100px;
            gap: 12px;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .honeypot {
            position: absolute;
            left: -9999px;
        }
        .result {
            display: none;
        }
        .result.show {
            display: block;
        }
        .result-box {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 12px;
            padding: 24px;
            color: white;
            text-align: center;
            margin-bottom: 24px;
        }
        .result-vehicle {
            font-size: 14px;
            opacity: 0.9;
        }
        .result-repair {
            font-size: 18px;
            font-weight: 600;
            margin: 8px 0;
        }
        .result-price {
            font-size: 48px;
            font-weight: 700;
            margin: 16px 0 8px;
        }
        .result-time {
            font-size: 14px;
            opacity: 0.9;
        }
        .result-note {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 8px;
        }
        .delivery-btns {
            display: grid;
            gap: 12px;
        }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 40px 20px;
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .phone-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: #64748b;
            font-size: 14px;
        }
        .phone-link a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 Free Instant Estimate</h1>
            <p class="subtitle">Get your repair price in seconds</p>

            <div id="error" class="error"></div>
            <div id="success" class="success"></div>

            <!-- Form -->
            <form id="estimateForm">
                <!-- Honeypot field - hidden from users, bots will fill it -->
                <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">

                <div class="form-section">
                    <div class="section-title">Your Information</div>
                    <div class="row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name <span class="optional">(optional)</span></label>
                            <input type="text" name="last_name">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" placeholder="(904) 555-1234" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="optional">(optional)</span></label>
                            <input type="email" name="email">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Vehicle Information</div>
                    <div class="row-4">
                        <div class="form-group">
                            <label>Year</label>
                            <input type="text" name="year" placeholder="2004" required>
                        </div>
                        <div class="form-group">
                            <label>Make</label>
                            <input type="text" name="make" placeholder="BMW" required>
                        </div>
                        <div class="form-group">
                            <label>Model</label>
                            <input type="text" name="model" placeholder="330xi" required>
                        </div>
                        <div class="form-group">
                            <label>Engine <span class="optional">(optional)</span></label>
                            <input type="text" name="engine" placeholder="3.0L">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">What Needs Fixed?</div>
                    <div class="form-group">
                        <label>Describe the problem or repair needed</label>
                        <textarea name="problem" placeholder="Example: Has a leak from the power steering reservoir to the cooler. I already ordered the part." required></textarea>
                    </div>
                </div>

                <button type="submit" class="btn">Get My Free Estimate →</button>
            </form>

            <!-- Loading -->
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p style="color: #64748b; font-size: 16px;">Calculating your estimate...</p>
            </div>

            <!-- Results -->
            <div id="result" class="result">
                <div class="result-box">
                    <div class="result-vehicle" id="resultVehicle"></div>
                    <div class="result-repair" id="resultRepair"></div>
                    <div class="result-price" id="resultPrice"></div>
                    <div class="result-time" id="resultTime"></div>
                    <div class="result-note">Labor only - parts not included</div>
                </div>

                <div class="delivery-btns">
                    <button class="btn btn-secondary" onclick="sendEmail()">📧 Email Me This Estimate</button>
                    <a href="tel:9047066669" class="btn btn-success" style="text-align: center; text-decoration: none;">📞 Call to Schedule: 904-706-6669</a>
                    <button class="btn btn-secondary" onclick="newEstimate()">← Get Another Estimate</button>
                </div>
            </div>

            <p class="phone-link">Questions? Call <a href="tel:9047066669">904-706-6669</a></p>
        </div>
    </div>

    <script>
        const form = document.getElementById('estimateForm');
        const errorDiv = document.getElementById('error');
        const successDiv = document.getElementById('success');
        const loadingDiv = document.getElementById('loading');
        const resultDiv = document.getElementById('result');

        function showError(msg) {
            errorDiv.textContent = msg;
            errorDiv.style.display = 'block';
            setTimeout(() => errorDiv.style.display = 'none', 5000);
        }

        function showSuccess(msg) {
            successDiv.textContent = msg;
            successDiv.style.display = 'block';
            setTimeout(() => successDiv.style.display = 'none', 5000);
        }

        function newEstimate() {
            resultDiv.classList.remove('show');
            form.style.display = 'block';
            form.reset();
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Hide form, show loading
            form.style.display = 'none';
            loadingDiv.style.display = 'block';
            resultDiv.classList.remove('show');

            const formData = new FormData(form);
            formData.append('action', 'get_estimate');

            try {
                const resp = await fetch('', {method: 'POST', body: formData});
                const data = await resp.json();

                loadingDiv.style.display = 'none';

                if (data.ok) {
                    const est = data.estimate;
                    let vehicleText = est.vehicle.year + ' ' + est.vehicle.make + ' ' + est.vehicle.model;
                    if (est.vehicle.engine) vehicleText += ' (' + est.vehicle.engine + ')';

                    document.getElementById('resultVehicle').textContent = vehicleText;
                    document.getElementById('resultRepair').textContent = est.repair.name;
                    document.getElementById('resultPrice').textContent = '$' + est.estimate.labor_cost.toFixed(2);
                    document.getElementById('resultTime').textContent = est.repair.book_time_typical + ' hours labor';

                    resultDiv.classList.add('show');
                } else {
                    showError(data.error || 'Failed to generate estimate');
                    form.style.display = 'block';
                }
            } catch (e) {
                loadingDiv.style.display = 'none';
                showError('Network error. Please try again.');
                form.style.display = 'block';
            }
        });

        async function sendEmail() {
            const formData = new FormData();
            formData.append('action', 'send_email');

            try {
                const resp = await fetch('', {method: 'POST', body: formData});
                const data = await resp.json();

                if (data.ok) {
                    showSuccess('Estimate sent to your email!');
                } else {
                    showError(data.error || 'Failed to send email. Make sure you entered your email address.');
                }
            } catch (e) {
                showError('Network error');
            }
        }
    </script>
</body>
</html>
