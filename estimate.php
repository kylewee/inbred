<?php
/**
 * AI-Powered Labor Estimate Calculator
 * Uses GPT-4 to analyze repair descriptions and return accurate book times
 */

require_once __DIR__ . '/api/.env.local.php';

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['request'])) {
    $request = trim($_POST['request']);

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
    "notes": "E46 chassis"
  },
  "repair": {
    "name": "Power steering pressure hose replacement",
    "description": "Replace high pressure power steering hose from reservoir to cooling line",
    "book_time_low": 0.8,
    "book_time_high": 1.2,
    "book_time_typical": 1.0,
    "complexity": "standard",
    "notes": "Customer supplying part"
  }
}

IMPORTANT GUIDELINES:
- book_time values are in HOURS (decimal)
- Use industry-standard "book time" labor estimates (AllData/Mitchell equivalent)
- Be accurate - mechanics rely on this for real quotes
- complexity can be: simple, standard, moderate, complex, major
- If vehicle info is missing, make reasonable assumptions
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

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Clean up response - remove markdown code blocks if present
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);
        $content = trim($content);

        $result = json_decode($content, true);

        if ($result && isset($result['repair']['book_time_typical'])) {
            // Calculate price
            $hours = (float)$result['repair']['book_time_typical'];
            if ($hours <= 1.0) {
                $laborCost = $hours * 150;
            } else {
                $laborCost = 150 + (($hours - 1.0) * 100);
            }
            $result['estimate'] = [
                'labor_hours' => $hours,
                'labor_cost' => round($laborCost, 2),
                'rate_info' => '$150 first hour, $100/hr after'
            ];
        }
    } else {
        $error = "API error (HTTP $httpCode)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Labor Estimate - EZ Mobile Mechanic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #94a3b8;
            margin-bottom: 30px;
        }
        form {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 12px;
            color: #cbd5e1;
        }
        textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid rgba(148, 163, 184, 0.3);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.8);
            color: #e2e8f0;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
        }
        textarea:focus {
            outline: none;
            border-color: #2563eb;
        }
        textarea::placeholder {
            color: #64748b;
        }
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
        }
        button:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        .result {
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 24px;
            margin-top: 24px;
        }
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .vehicle-info {
            font-size: 14px;
            color: #94a3b8;
        }
        .vehicle-info strong {
            color: #e2e8f0;
            font-size: 18px;
            display: block;
            margin-bottom: 4px;
        }
        .repair-name {
            font-size: 20px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 8px;
        }
        .repair-desc {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .estimate-box {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 10px;
            padding: 24px;
            text-align: center;
            color: white;
        }
        .estimate-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        .estimate-price {
            font-size: 48px;
            font-weight: 700;
        }
        .estimate-details {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 12px;
        }
        .time-range {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            padding: 16px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 8px;
        }
        .time-item {
            flex: 1;
            text-align: center;
        }
        .time-item .value {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
        }
        .time-item .label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
        }
        .notes {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
            font-size: 14px;
            color: #fcd34d;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 16px;
            color: #fca5a5;
        }
        .example {
            color: #64748b;
            font-size: 13px;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Labor Estimate Calculator</h1>
        <p class="subtitle">Paste the customer request - AI will calculate accurate book time</p>

        <form method="POST">
            <label>Customer Request / Repair Description</label>
            <textarea name="request" placeholder="Example: 2004 BMW 330xi - has a leak from the power steering reservoir to the cooler, I ordered the part should be here Monday what would you charge for something like that?"><?= htmlspecialchars($_POST['request'] ?? '') ?></textarea>
            <button type="submit">Get Labor Estimate</button>
            <p class="example">Paste the customer's message exactly as they sent it - year, make, model, and repair description</p>
        </form>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($result): ?>
            <div class="result">
                <div class="result-header">
                    <div class="vehicle-info">
                        <strong><?= htmlspecialchars($result['vehicle']['year'] . ' ' . $result['vehicle']['make'] . ' ' . $result['vehicle']['model']) ?></strong>
                        <?= htmlspecialchars($result['vehicle']['notes'] ?? '') ?>
                    </div>
                </div>

                <div class="repair-name"><?= htmlspecialchars($result['repair']['name']) ?></div>
                <div class="repair-desc"><?= htmlspecialchars($result['repair']['description']) ?></div>

                <div class="time-range">
                    <div class="time-item">
                        <div class="value"><?= number_format($result['repair']['book_time_low'], 1) ?></div>
                        <div class="label">Low (hrs)</div>
                    </div>
                    <div class="time-item">
                        <div class="value"><?= number_format($result['repair']['book_time_typical'], 1) ?></div>
                        <div class="label">Typical (hrs)</div>
                    </div>
                    <div class="time-item">
                        <div class="value"><?= number_format($result['repair']['book_time_high'], 1) ?></div>
                        <div class="label">High (hrs)</div>
                    </div>
                </div>

                <div class="estimate-box">
                    <div class="estimate-label">Your Labor Estimate</div>
                    <div class="estimate-price">$<?= number_format($result['estimate']['labor_cost'], 2) ?></div>
                    <div class="estimate-details">
                        <?= number_format($result['estimate']['labor_hours'], 1) ?> hours × <?= $result['estimate']['rate_info'] ?>
                    </div>
                </div>

                <?php if (!empty($result['repair']['notes'])): ?>
                    <div class="notes">📝 <?= htmlspecialchars($result['repair']['notes']) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
