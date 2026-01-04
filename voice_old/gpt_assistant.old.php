<?php
/**
 * GPT-Powered Voice Assistant
 * Config-driven - works for any business type
 *
 * Flow:
 * 1. Greet customer
 * 2. Listen and understand what they need
 * 3. Collect relevant info based on business type
 * 4. Offer estimate if requested
 * 5. Create CRM lead
 * 6. Confirm and end call
 */
declare(strict_types=1);

header('Content-Type: text/xml');

// Load config with webhook domain detection
require_once __DIR__ . '/../config/webhook_bootstrap.php';

// Load CRM helper if enabled
if (config('crm.enabled', false)) {
    require_once __DIR__ . '/../lib/CRMHelper.php';
    // Load recording_callback for create_crm_lead function
    if (!function_exists('create_crm_lead')) {
        define('VOICE_LIB_ONLY', true);
        require_once __DIR__ . '/recording_callback.php';
    }
}

// Get call data
$callSid = $_REQUEST['CallSid'] ?? $_REQUEST['call_id'] ?? uniqid('call_');
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';
$speechResult = $_REQUEST['SpeechResult'] ?? '';

// Session file for this call
$sessionDir = __DIR__ . '/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0755, true);
}
$sessionFile = $sessionDir . '/' . md5($callSid) . '.json';

// Get business-specific fields to collect
$businessType = config('estimates.estimate_type', config('business.type', 'general'));
$collectFields = getCollectFields($businessType);

// Load or create session
$session = file_exists($sessionFile) ? json_decode(file_get_contents($sessionFile), true) : null;
if (!$session) {
    $session = [
        'call_sid' => $callSid,
        'from' => $from,
        'started_at' => date('c'),
        'messages' => [],
        'collected' => array_merge(
            ['name' => '', 'phone' => preg_replace('/[^\d]/', '', $from)],
            array_fill_keys($collectFields, '')
        ),
        'estimate_given' => false,
        'lead_created' => false,
        'turn_count' => 0
    ];
}

// Add customer's speech to conversation
if ($speechResult) {
    $session['messages'][] = ['role' => 'user', 'content' => $speechResult];
    $session['turn_count']++;
}

// Log
$log = [
    'ts' => date('c'),
    'event' => 'gpt_assistant',
    'domain' => config('site.domain'),
    'call_sid' => $callSid,
    'from' => $from,
    'speech' => $speechResult,
    'turn' => $session['turn_count']
];

// Build system prompt based on business type
$systemPrompt = buildSystemPrompt($businessType, $session['collected']);

// Get GPT response
$gptResponse = callGPT($systemPrompt, $session['messages'], $session['turn_count'] === 0);

// Parse response and extract data
$siteName = config('site.name', 'our company');
$assistantText = $gptResponse['text'] ?? "I'll have someone from {$siteName} call you right back.";
$extractedData = $gptResponse['data'] ?? [];

// Update collected info
foreach ($collectFields as $field) {
    if (!empty($extractedData[$field])) {
        $session['collected'][$field] = $extractedData[$field];
    }
}
if (!empty($extractedData['name'])) {
    $session['collected']['name'] = $extractedData['name'];
}

// Check if customer wants estimate - SIMPLIFIED to avoid timeout
// The estimate API calls were causing SignalWire to timeout waiting for TwiML
if (!empty($extractedData['wants_estimate']) && !$session['estimate_given']) {
    // Just acknowledge and let human follow up with actual estimate
    $session['collected']['wants_estimate'] = true;
    $session['estimate_given'] = true;
    $log['estimate_requested'] = true;
    // GPT already said something about the estimate, don't add more
}

// Check if ready to end call - ONLY when GPT explicitly says so or after 15 turns
// Don't force hangup just because we hit a turn count
$readyToEnd = !empty($extractedData['ready_to_end']) && $session['turn_count'] >= 3; // Must have at least 3 turns AND GPT says done

// SAFEGUARD: If response ends with a question mark, DON'T hang up - GPT is confused
if ($readyToEnd && preg_match('/\?\s*$/', $assistantText)) {
    $readyToEnd = false;
    @file_put_contents(__DIR__ . '/gpt_debug.log', date('c') . " | OVERRIDE: Response ends with ? so NOT hanging up: {$assistantText}\n", FILE_APPEND);
}

// SAFEGUARD 2: Only hang up if customer actually said bye/goodbye/thanks/take care
$goodbyeWords = '/\b(bye|goodbye|good bye|thanks|thank you|take care|later|see ya|have a good|love you)\b/i';
if ($readyToEnd && !preg_match($goodbyeWords, $speechResult)) {
    $readyToEnd = false;
    @file_put_contents(__DIR__ . '/gpt_debug.log', date('c') . " | OVERRIDE: Customer didn't say bye, not hanging up. Speech: {$speechResult}\n", FILE_APPEND);
}

// SAFEGUARD 3: If GPT response shows confusion, DON'T hang up
$confusionPhrases = '/\b(didn\'t catch|sorry.*understand|could you repeat|what did you|pardon|come again|say that again)\b/i';
if ($readyToEnd && preg_match($confusionPhrases, $assistantText)) {
    $readyToEnd = false;
    @file_put_contents(__DIR__ . '/gpt_debug.log', date('c') . " | OVERRIDE: GPT seems confused, not hanging up. Response: {$assistantText}\n", FILE_APPEND);
}

// SAFEGUARD 4: Don't hang up if we have very little info collected
$hasMinimumInfo = !empty($session['collected']['problem']) ||
    (!empty($session['collected']['year']) && !empty($session['collected']['make']));
if ($readyToEnd && !$hasMinimumInfo && $session['turn_count'] < 10) {
    $readyToEnd = false;
    @file_put_contents(__DIR__ . '/gpt_debug.log', date('c') . " | OVERRIDE: Not enough info collected yet, not hanging up.\n", FILE_APPEND);
}

if ($session['turn_count'] >= 15) {
    $readyToEnd = true; // Safety valve at 15 turns
}

// Create/update lead - wait for actual data before creating, update at end of call
$hasRealInfo = !empty($session['collected']['phone']) &&
    (!empty($session['collected']['problem']) || !empty($session['collected']['name']) || !empty($session['collected']['year']));

// Create lead early only if we have real info (not just phone + turn count)
if (!$session['lead_created'] && $hasRealInfo && config('crm.enabled', false)) {
    $leadResult = createLead($session['collected']);
    $session['lead_created'] = true;
    $session['lead_id'] = $leadResult['lead_id'] ?? null;
    $log['lead_created'] = $leadResult;
}

// At end of call: create lead if none exists, or update existing lead
if ($readyToEnd && config('crm.enabled', false)) {
    // Build transcript from conversation
    $transcript = "";
    foreach ($session['messages'] as $msg) {
        $role = $msg['role'] === 'user' ? 'Customer' : 'AI';
        $transcript .= "{$role}: {$msg['content']}\n";
    }

    // Add transcript to collected data
    $session['collected']['transcript'] = $transcript;
    $session['collected']['notes'] = "AI Voice Conversation:\n" . $transcript;

    if (!$session['lead_created'] && !empty($session['collected']['phone'])) {
        // No lead yet - create one with whatever we have
        $leadResult = createLead($session['collected']);
        $session['lead_created'] = true;
        $session['lead_id'] = $leadResult['lead_id'] ?? null;
        $log['lead_created_at_end'] = $leadResult;
    } elseif ($session['lead_id']) {
        // Update existing lead with final data
        $updateResult = updateLeadWithCollected($session['lead_id'], $session['collected']);
        $log['lead_updated'] = $updateResult;
    }
}

// Add assistant response to history
$session['messages'][] = ['role' => 'assistant', 'content' => $assistantText];

// Save session
file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

$log['response'] = $assistantText;
$log['collected'] = $session['collected'];
$log['ready_to_end'] = $readyToEnd;
@file_put_contents(__DIR__ . '/gpt_assistant.log', json_encode($log) . "\n", FILE_APPEND);

// Build TwiML response
$host = $_SERVER['HTTP_HOST'] ?? config('site.domain', 'localhost');
$baseUrl = 'https://' . $host . '/voice';
$voice = "Polly.Matthew";

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n<Response>\n";

if ($readyToEnd) {
    echo "  <Say voice=\"{$voice}\">" . xmlEscape($assistantText) . "</Say>\n";
    echo "  <Hangup />\n";
    @unlink($sessionFile);
} else {
    echo "  <Gather input=\"speech\" timeout=\"10\" speechTimeout=\"auto\" action=\"{$baseUrl}/gpt_assistant.php\" method=\"POST\">\n";
    echo "    <Say voice=\"{$voice}\">" . xmlEscape($assistantText) . "</Say>\n";
    echo "  </Gather>\n";
    echo "  <Say voice=\"{$voice}\">I'm still here, take your time.</Say>\n";
    echo "  <Gather input=\"speech\" timeout=\"10\" speechTimeout=\"auto\" action=\"{$baseUrl}/gpt_assistant.php\" method=\"POST\"></Gather>\n";
    echo "  <Say voice=\"{$voice}\">I didn't catch that. Someone will give you a call back shortly. Thanks for calling!</Say>\n";
    echo "  <Hangup />\n";
}

echo "</Response>\n";

// === Helper Functions ===

function getCollectFields(string $businessType): array {
    switch ($businessType) {
        case 'mechanic':
            return ['year', 'make', 'model', 'problem'];
        case 'landscaping':
            return ['sqft', 'service_type', 'address', 'notes'];
        case 'roofing':
            return ['sqft', 'roof_type', 'address', 'notes'];
        case 'plumbing':
            return ['service_type', 'address', 'notes'];
        default:
            return ['service_type', 'address', 'notes'];
    }
}

function buildSystemPrompt(string $businessType, array $collected): string {
    $siteName = config('site.name', 'our company');
    $serviceArea = config('site.service_area', 'your area');
    $services = implode(', ', config('business.services', ['our services']));

    // Base personality - professional but friendly
    $personality = "You're answering phones for {$siteName}. Be professional and helpful.

CRITICAL RULES:
- NEVER repeat a question for info the customer already gave you
- If they gave year/make/model in one sentence, acknowledge ALL of it
- Keep it SHORT - one sentence replies when possible
- Use professional phrases: \"Got it\", \"Sounds good\", \"Alright\", \"Great\"
- NO slang like \"cool\", \"awesome\", \"gotcha\" - keep it professional
- If you don't understand, just ask them to repeat - DON'T hang up
- It's OK to not have every detail - get what you can naturally
- NEVER set ready_to_end=true unless customer explicitly says bye/goodbye/thanks

We serve: {$serviceArea}
";

    // Business-specific instructions
    switch ($businessType) {
        case 'mechanic':
            // Build a simple status of what we have
            $have = [];
            $need = [];
            if ($collected['name']) $have[] = "name ({$collected['name']})";
            else $need[] = "name";
            if ($collected['year']) $have[] = "year ({$collected['year']})";
            if ($collected['make']) $have[] = "make ({$collected['make']})";
            if ($collected['model']) $have[] = "model ({$collected['model']})";
            if (!$collected['year'] && !$collected['make']) $need[] = "vehicle info";
            if ($collected['problem']) $have[] = "problem ({$collected['problem']})";
            else $need[] = "what's wrong";

            $haveStr = $have ? implode(', ', $have) : 'nothing yet';
            $needStr = $need ? implode(', ', $need) : 'nothing - ready to wrap up';

            $instructions = "CONVERSATION FLOW:
1. They call, you greet and ask what's up with their vehicle
2. Get year/make/model and what's wrong (often given together - acknowledge it all!)
3. Name is nice but optional - don't push for it
4. Once you have vehicle + problem, offer to text an estimate
5. Wrap up - mechanic will follow up

STATUS:
- Have: {$haveStr}
- Still need: {$needStr}

REMEMBER: If they already told you something, DO NOT ask again. Just move forward.

=== REQUIRED JSON (customer won't hear this) ===
End EVERY response with this JSON on its own line:
{\"name\":\"VALUE\",\"year\":\"VALUE\",\"make\":\"VALUE\",\"model\":\"VALUE\",\"problem\":\"VALUE\",\"ready_to_end\":false,\"wants_estimate\":false}

- Fill in what you learned THIS turn (leave empty if not mentioned)
- ready_to_end=true only when customer says bye/thanks/done
- wants_estimate=true if they want an estimate texted";
            break;

        case 'landscaping':
            $have = [];
            $need = [];
            if ($collected['name']) $have[] = "name ({$collected['name']})";
            if ($collected['service_type']) $have[] = "service ({$collected['service_type']})";
            else $need[] = "what service";
            if ($collected['sqft']) $have[] = "size ({$collected['sqft']} sqft)";
            if ($collected['address']) $have[] = "address";
            else $need[] = "address";

            $haveStr = $have ? implode(', ', $have) : 'nothing yet';
            $needStr = $need ? implode(', ', $need) : 'good to go';

            $instructions = "CONVERSATION FLOW:
1. Greet, ask what landscaping work they need
2. Get the service type (sod, irrigation, design, mowing, etc.)
3. Get address or general area
4. Square footage is bonus info - don't push
5. Offer to text estimate, wrap up

STATUS:
- Have: {$haveStr}
- Still need: {$needStr}

=== REQUIRED JSON ===
{\"name\":\"\",\"service_type\":\"\",\"sqft\":\"\",\"address\":\"\",\"notes\":\"\",\"ready_to_end\":false,\"wants_estimate\":false}";
            break;

        default:
            $have = [];
            $need = [];
            if ($collected['name']) $have[] = "name";
            if ($collected['service_type']) $have[] = "service needed";
            else $need[] = "what they need";
            if ($collected['address']) $have[] = "address";

            $haveStr = $have ? implode(', ', $have) : 'nothing yet';
            $needStr = $need ? implode(', ', $need) : 'good to go';

            $instructions = "CONVERSATION FLOW:
1. Greet, ask how you can help
2. Find out what service they need
3. Get address if relevant
4. Offer to have someone follow up

STATUS:
- Have: {$haveStr}
- Still need: {$needStr}

=== REQUIRED JSON ===
{\"name\":\"\",\"service_type\":\"\",\"address\":\"\",\"notes\":\"\",\"ready_to_end\":false,\"wants_estimate\":false}";
    }

    return $personality . "\n" . $instructions;
}

function callGPT(string $systemPrompt, array $messages, bool $isFirstTurn): array {
    $apiKey = config('openai.api_key', '');
    $siteName = config('site.name', 'our company');
    $businessType = config('business.type', 'mechanic');

    // INSTANT first greeting - no API call needed
    if ($isFirstTurn) {
        switch ($businessType) {
            case 'mechanic':
                $greeting = "Hey there! This is {$siteName}. What's going on with your vehicle?";
                break;
            case 'landscaping':
                $greeting = "Thanks for calling {$siteName}! How can we help with your lawn or landscaping?";
                break;
            default:
                $greeting = "Thanks for calling {$siteName}! How can we help you today?";
        }
        return ['text' => $greeting, 'data' => []];
    }

    if (!$apiKey) {
        return ['text' => "Thanks for calling! Someone will call you right back.", 'data' => []];
    }

    $gptMessages = [['role' => 'system', 'content' => $systemPrompt]];

    // Not first turn - add conversation history
    if (!empty($messages)) {
        foreach ($messages as $msg) {
            $gptMessages[] = $msg;
        }
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => config('openai.model', 'gpt-4o-mini'),
            'messages' => $gptMessages,
            'temperature' => 0.7,
            'max_tokens' => 200
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        @file_put_contents(__DIR__ . '/gpt_debug.log', date('c') . " | API FAILED: HTTP {$httpCode} | Response: " . substr($result, 0, 500) . "\n", FILE_APPEND);
        // Don't set ready_to_end on API failure - let safeguards decide
        return ['text' => "Sorry, I missed that. Could you say that again?", 'data' => []];
    }

    $data = json_decode($result, true);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Parse out JSON data block - look for JSON anywhere in response
    $text = $content;
    $extracted = [];

    // Method 1: Look for JSON at end of response (most common)
    if (preg_match('/\{["\s]*name["\s]*:.*?\}$/s', $content, $matches)) {
        $jsonStr = $matches[0];
        $decoded = json_decode($jsonStr, true);
        if ($decoded !== null) {
            $extracted = $decoded;
            $text = trim(substr($content, 0, -strlen($jsonStr)));
        }
    }

    // Method 2: Look for JSON block with newline before it
    if (empty($extracted) && preg_match('/\n\s*(\{[^{}]+\})\s*$/s', $content, $matches)) {
        $decoded = json_decode($matches[1], true);
        if ($decoded !== null) {
            $extracted = $decoded;
            $text = trim(preg_replace('/\n\s*\{[^{}]+\}\s*$/s', '', $content));
        }
    }

    // Method 3: Find any JSON object with expected keys
    if (empty($extracted) && preg_match('/\{[^{}]*"(?:name|year|problem|ready_to_end)"[^{}]*\}/s', $content, $matches)) {
        $decoded = json_decode($matches[0], true);
        if ($decoded !== null) {
            $extracted = $decoded;
            $text = trim(str_replace($matches[0], '', $content));
        }
    }

    // Log GPT raw response for debugging
    @file_put_contents(__DIR__ . '/gpt_debug.log', date('c') . " | RAW: " . $content . " | EXTRACTED: " . json_encode($extracted) . "\n", FILE_APPEND);

    $text = trim($text);
    if (empty($text)) {
        $text = "Got it. Someone will be in touch soon!";
    }

    return ['text' => $text, 'data' => $extracted];
}

function getEstimate(array $collected): array {
    $apiKey = config('openai.api_key', '');
    if (!$apiKey) {
        return ['success' => false];
    }

    $businessType = config('estimates.estimate_type', 'general');
    $laborRate = config('estimates.labor_rate', 75);
    $promptTemplate = config('estimates.prompts.' . $businessType, '');

    if (!$promptTemplate) {
        return ['success' => false];
    }

    // Build prompt from template
    $prompt = $promptTemplate;
    foreach ($collected as $key => $value) {
        $prompt = str_replace('{' . $key . '}', $value ?: 'N/A', $prompt);
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => config('openai.model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'system', 'content' => config('estimates.system_prompt', 'You are a professional estimator.') . ' Respond with valid JSON only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 200
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['success' => false];
    }

    $data = json_decode($result, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $content = preg_replace('/```json\s*|\s*```/', '', $content);

    $estimate = json_decode(trim($content), true);
    if (!$estimate) {
        return ['success' => false];
    }

    // Normalize the response
    $total = $estimate['total'] ?? 0;
    if (!$total && isset($estimate['labor_cost']) && isset($estimate['parts_cost'])) {
        $total = $estimate['labor_cost'] + $estimate['parts_cost'];
    }
    if (!$total && isset($estimate['labor_hours'])) {
        $laborCost = $estimate['labor_hours'] * $laborRate;
        $partsAvg = (($estimate['parts_cost_low'] ?? 100) + ($estimate['parts_cost_high'] ?? 200)) / 2;
        $total = $laborCost + $partsAvg;
    }

    return [
        'success' => true,
        'job_name' => $estimate['job_name'] ?? $estimate['repair_name'] ?? '',
        'total' => round($total, 2),
        'details' => $estimate,
        'notes' => $estimate['notes'] ?? ''
    ];
}

function sendEstimateSMS(string $phone, array $estimate, array $customer): bool {
    $projectId = config('phone.project_id', '');
    $token = config('phone.api_token', '');
    $space = config('phone.space', '');
    $from = config('phone.phone_number', '');
    $siteName = config('site.name', 'our company');
    $sitePhone = config('site.phone', '');

    if (!$projectId || !$phone) return false;

    $phone = preg_replace('/[^\d]/', '', $phone);
    if (strlen($phone) === 10) $phone = '1' . $phone;
    $phone = '+' . $phone;

    $businessType = config('estimates.estimate_type', 'general');
    $msg = "{$siteName} Estimate\n\n";

    // Build message based on business type
    if ($businessType === 'mechanic') {
        $vehicle = trim("{$customer['year']} {$customer['make']} {$customer['model']}");
        $msg .= "Vehicle: {$vehicle}\n";
        $msg .= "Repair: {$estimate['job_name']}\n";
    } elseif ($businessType === 'landscaping') {
        if (!empty($customer['service_type'])) $msg .= "Service: {$customer['service_type']}\n";
        if (!empty($customer['sqft'])) $msg .= "Area: {$customer['sqft']} sq ft\n";
    } else {
        if (!empty($customer['service_type'])) $msg .= "Service: {$customer['service_type']}\n";
    }

    $msg .= "Estimate: $" . number_format($estimate['total'], 2) . "\n\n";
    if ($estimate['notes']) {
        $msg .= $estimate['notes'] . "\n\n";
    }
    $msg .= "Reply YES to schedule";
    if ($sitePhone) {
        $msg .= " or call " . $sitePhone;
    }

    $ch = curl_init("https://{$space}/api/laml/2010-04-01/Accounts/{$projectId}/Messages.json");
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => "{$projectId}:{$token}",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'To' => $phone,
            'From' => $from,
            'Body' => $msg
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

function updateLeadWithCollected(int $leadId, array $collected): array {
    if (!$leadId || !config('crm.enabled', false)) {
        return ['success' => false, 'error' => 'No lead ID or CRM disabled'];
    }

    $fieldMap = config('crm.field_map', []);
    $updateData = [];

    // Map collected fields to CRM field IDs
    foreach ($collected as $key => $value) {
        if (!empty($value) && isset($fieldMap[$key])) {
            $updateData['fields[' . $fieldMap[$key] . ']'] = $value;
        }
    }

    // Handle name specially - split if needed
    if (!empty($collected['name'])) {
        $nameParts = explode(' ', trim($collected['name']), 2);
        if (isset($fieldMap['first_name'])) {
            $updateData['fields[' . $fieldMap['first_name'] . ']'] = $nameParts[0];
        }
        if (isset($fieldMap['last_name']) && isset($nameParts[1])) {
            $updateData['fields[' . $fieldMap['last_name'] . ']'] = $nameParts[1];
        }
    }

    if (empty($updateData)) {
        return ['success' => false, 'error' => 'No data to update'];
    }

    // Build update request
    $apiUrl = config('crm.api_url', '');
    $post = [
        'action' => 'update',
        'entity_id' => config('crm.entity_id', 26),
        'id' => $leadId,
        'key' => config('crm.api_key', ''),
        'username' => config('crm.username', ''),
        'password' => config('crm.password', ''),
    ];
    $post = array_merge($post, $updateData);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($result, true);
    return [
        'success' => $httpCode === 200 && ($response['status'] ?? '') === 'success',
        'http_code' => $httpCode,
        'response' => $response
    ];
}

function createLead(array $collected): array {
    if (!config('crm.enabled', false) || !class_exists('CRMHelper')) {
        return ['success' => false, 'error' => 'CRM not enabled'];
    }

    $businessType = config('estimates.estimate_type', 'general');

    // Build notes from collected info
    $notes = "Source: AI Voice Assistant\n";
    $notes .= "Time: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($collected as $key => $value) {
        if ($value && !in_array($key, ['phone', 'name'])) {
            $notes .= ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
        }
    }

    $fieldMap = config('crm.field_map', []);
    $leadData = [];

    // Map collected fields to CRM fields
    foreach ($collected as $key => $value) {
        if ($value && isset($fieldMap[$key])) {
            $leadData[$key] = $value;
        }
    }

    // Always include basic fields
    $leadData['first_name'] = $collected['name'] ?? 'Voice';
    $leadData['last_name'] = 'Lead';
    $leadData['phone'] = $collected['phone'] ?? '';
    $leadData['notes'] = $notes;
    $leadData['source'] = 'AI Voice Assistant';

    // Use CRMHelper if available
    if (function_exists('create_crm_lead')) {
        return create_crm_lead($leadData);
    }

    return ['success' => false, 'error' => 'create_crm_lead function not available'];
}

function checkRateLimit(string $phone): array {
    $dbPath = __DIR__ . '/../data/rate_limits.db';
    $maxPerDay = 5;

    try {
        $db = new SQLite3($dbPath);
        $db->exec('CREATE TABLE IF NOT EXISTS estimate_requests (
            id INTEGER PRIMARY KEY,
            phone TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $phone = preg_replace('/[^\d]/', '', $phone);
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM estimate_requests WHERE phone = :phone AND created_at > datetime("now", "-1 day")');
        $stmt->bindValue(':phone', $phone);
        $result = $stmt->execute()->fetchArray();

        $count = $result['count'] ?? 0;
        return ['allowed' => $count < $maxPerDay, 'count' => $count];
    } catch (Exception $e) {
        return ['allowed' => true, 'count' => 0];
    }
}

function recordEstimate(string $phone): void {
    $dbPath = __DIR__ . '/../data/rate_limits.db';

    try {
        $db = new SQLite3($dbPath);
        $phone = preg_replace('/[^\d]/', '', $phone);
        $stmt = $db->prepare('INSERT INTO estimate_requests (phone) VALUES (:phone)');
        $stmt->bindValue(':phone', $phone);
        $stmt->execute();
    } catch (Exception $e) {
        // Ignore
    }
}

function xmlEscape(string $text): string {
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
