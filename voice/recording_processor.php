<?php
/**
 * Recording Processor
 * Handles all recording callbacks and data extraction
 *
 * Modes:
 * 1. Answered call recording - transcribe full conversation, extract data, create CRM lead
 * 2. Prompt recording - transcribe individual answer, store in session
 * 3. Session processing - combine all prompt answers, create CRM lead
 */
declare(strict_types=1);

// Load config with webhook domain detection
require_once __DIR__ . '/../config/webhook_bootstrap.php';

// ============================================================================
// CORE FUNCTIONS
// ============================================================================

/**
 * Fetch recording from SignalWire as MP3
 */
function fetch_recording(string $recordingSid): array {
    $projectId = config('phone.project_id', '');
    $apiToken = config('phone.api_token', '');
    $space = config('phone.space', '');

    if (!$projectId || !$apiToken || !$space) {
        return ['ok' => false, 'error' => 'SignalWire not configured'];
    }

    $url = "https://{$space}/api/laml/2010-04-01/Accounts/{$projectId}/Recordings/{$recordingSid}.mp3";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => "{$projectId}:{$apiToken}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $data = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $ok = ($http >= 200 && $http < 300 && $data !== false && strlen($data) > 1000);
    return ['ok' => $ok, 'data' => $data, 'http' => $http, 'error' => $error];
}

/**
 * Transcribe audio with OpenAI Whisper
 */
function transcribe(string $audioBytes, string $filename = 'audio.mp3'): array {
    $apiKey = config('openai.api_key', '');
    if (!$apiKey) {
        return ['ok' => false, 'error' => 'OpenAI not configured'];
    }

    $boundary = '----boundary-' . bin2hex(random_bytes(8));
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"model\"\r\n\r\nwhisper-1\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
    $body .= "Content-Type: audio/mpeg\r\n\r\n";
    $body .= $audioBytes . "\r\n";
    $body .= "--{$boundary}--\r\n";

    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: multipart/form-data; boundary=' . $boundary,
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http < 200 || $http >= 300) {
        return ['ok' => false, 'error' => "HTTP {$http}", 'body' => $resp];
    }

    $json = json_decode($resp, true);
    if (!isset($json['text'])) {
        return ['ok' => false, 'error' => 'No text in response'];
    }

    return ['ok' => true, 'text' => trim($json['text'])];
}

/**
 * Extract customer data from transcript using GPT
 * Includes vehicle info for mechanic business type
 */
function extract_customer_data(string $transcript): array {
    $apiKey = config('openai.api_key', '');
    if (!$apiKey) {
        return [];
    }

    // Check if we should extract vehicle info
    $businessType = config('business.type', 'service');
    $extractVehicle = ($businessType === 'mechanic');

    $vehicleFields = $extractVehicle ? '
  "year": "vehicle year (4 digits like 2018)",
  "make": "vehicle make (Honda, Ford, Toyota, etc)",
  "model": "vehicle model",
  "problem": "what needs to be fixed or the issue described"' : '';

    $vehicleRules = $extractVehicle ? '
- For year: extract 4-digit year if mentioned
- For make/model: standardize common brands
- For problem: summarize the repair or issue needed' : '';

    $prompt = <<<PROMPT
Extract customer information from this transcript. Return ONLY a JSON object with these keys (use null for missing data):

{
  "name": "full name",
  "phone": "phone number (digits only, like 9045551234)",
  "address": "street address or location",
  "email": "email address"{$vehicleFields}
}

Rules:
- Extract only what's clearly stated
- For phone: digits only, no formatting
- For email: if spelled out, reconstruct it (e.g., "john at gmail dot com" = "john@gmail.com")
- Return null for anything not mentioned
- Do NOT guess or make up information{$vehicleRules}

Transcript:
{$transcript}
PROMPT;

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 200,
            'temperature' => 0.1,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $resp = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($resp, true);
    $content = $json['choices'][0]['message']['content'] ?? '';

    // Extract JSON from response (handle markdown code blocks)
    if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
        $extracted = json_decode($matches[0], true);
        if (is_array($extracted)) {
            return $extracted;
        }
    }

    return [];
}

/**
 * Generate estimate for mechanic repairs using GPT
 */
function generate_estimate(array $vehicleData): ?array {
    if (!config('estimates.enabled', false)) {
        return null;
    }

    $apiKey = config('openai.api_key', '');
    if (!$apiKey) {
        return null;
    }

    // Need at least year/make/model and problem
    $year = $vehicleData['year'] ?? '';
    $make = $vehicleData['make'] ?? '';
    $model = $vehicleData['model'] ?? '';
    $problem = $vehicleData['problem'] ?? '';

    if (!$year || !$make || !$model || !$problem) {
        return null;
    }

    $laborRate = config('estimates.labor_rate', 95);
    $minCharge = config('estimates.min_charge', 150);

    $prompt = <<<PROMPT
You are an automotive labor time expert. Analyze this repair request and provide an estimate.

Vehicle: {$year} {$make} {$model}
Problem: {$problem}

Respond in this EXACT JSON format only:
{
  "repair_name": "Brief name of the repair",
  "description": "What the repair involves",
  "labor_hours": 1.5,
  "parts_estimate_low": 50,
  "parts_estimate_high": 150,
  "complexity": "standard",
  "notes": "Any additional notes"
}

Labor rate is \${$laborRate}/hour. Minimum charge is \${$minCharge}.
Use industry-standard book time estimates.
PROMPT;

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an automotive repair estimator. Always respond with valid JSON only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 300,
            'temperature' => 0.3,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http !== 200) {
        return null;
    }

    $json = json_decode($resp, true);
    $content = $json['choices'][0]['message']['content'] ?? '';

    // Clean up markdown
    $content = preg_replace('/```json\s*/', '', $content);
    $content = preg_replace('/```\s*/', '', $content);

    $estimate = json_decode(trim($content), true);
    if (!$estimate || !isset($estimate['labor_hours'])) {
        return null;
    }

    // Calculate costs
    $laborHours = (float)$estimate['labor_hours'];
    $laborCost = max($laborHours * $laborRate, $minCharge);
    $partsLow = (float)($estimate['parts_estimate_low'] ?? 0);
    $partsHigh = (float)($estimate['parts_estimate_high'] ?? 0);

    return [
        'repair_name' => $estimate['repair_name'] ?? 'Repair',
        'description' => $estimate['description'] ?? '',
        'labor_hours' => $laborHours,
        'labor_cost' => round($laborCost, 2),
        'parts_low' => round($partsLow, 2),
        'parts_high' => round($partsHigh, 2),
        'total_low' => round($laborCost + $partsLow, 2),
        'total_high' => round($laborCost + $partsHigh, 2),
        'complexity' => $estimate['complexity'] ?? 'standard',
        'notes' => $estimate['notes'] ?? '',
    ];
}

/**
 * Create CRM lead in Rukovoditel
 */
function create_lead(array $data): array {
    if (!config('crm.enabled', false)) {
        return ['ok' => false, 'error' => 'CRM not enabled'];
    }

    $apiUrl = config('crm.api_url', '');
    $apiKey = config('crm.api_key', '');
    $username = config('crm.username', '');
    $password = config('crm.password', '');
    $entityId = config('crm.entity_id', 26);
    $fieldMap = config('crm.field_map', []);

    if (!$apiUrl) {
        return ['ok' => false, 'error' => 'CRM URL not configured'];
    }

    // Build field payload
    $fields = [];
    foreach ($fieldMap as $key => $fieldId) {
        if (isset($data[$key]) && $data[$key] !== null && $data[$key] !== '') {
            $fields["field_{$fieldId}"] = $data[$key];
        }
    }

    // Set default stage to "New Lead" (choice ID 68)
    if (isset($fieldMap['stage']) && empty($fields["field_{$fieldMap['stage']}"])) {
        $fields["field_{$fieldMap['stage']}"] = '68';
    }

    // Login to get token
    $loginCh = curl_init($apiUrl);
    curl_setopt_array($loginCh, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'action' => 'login',
            'username' => $username,
            'password' => $password,
            'key' => $apiKey,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $loginResp = curl_exec($loginCh);
    curl_close($loginCh);

    $loginJson = json_decode($loginResp, true);
    $token = $loginJson['token'] ?? '';

    // Build insert request
    $post = [
        'action' => 'insert',
        'entity_id' => $entityId,
        'token' => $token,
        'key' => $apiKey,
        'username' => $username,
        'password' => $password,
    ];

    foreach ($fields as $fieldKey => $value) {
        $post["items[0][{$fieldKey}]"] = $value;
    }

    if ($createdBy = config('crm.created_by')) {
        $post['items[0][created_by]'] = $createdBy;
    }

    // Insert lead
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);

    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($resp, true);
    $success = ($http === 200 && isset($json['status']) && $json['status'] === 'success');

    return [
        'ok' => $success,
        'http' => $http,
        'response' => $json,
        'fields_sent' => $fields,
    ];
}

/**
 * Log to voice.log
 */
function voice_log(array $data): void {
    $data['ts'] = date('c');
    @file_put_contents(__DIR__ . '/voice.log', json_encode($data) . "\n", FILE_APPEND);
}

// ============================================================================
// REQUEST HANDLING
// ============================================================================

$action = $_REQUEST['action'] ?? '';
$type = $_REQUEST['type'] ?? '';
$callSid = $_REQUEST['call_sid'] ?? $_REQUEST['CallSid'] ?? '';
$recordingSid = $_REQUEST['RecordingSid'] ?? '';
$recordingUrl = $_REQUEST['RecordingUrl'] ?? '';
$from = $_REQUEST['From'] ?? $_REQUEST['Caller'] ?? '';

// Route based on action/type
if ($action === 'process_session') {
    // Process a completed missed call session
    process_session($callSid);
} elseif ($type === 'prompt') {
    // Individual prompt recording callback
    process_prompt_recording($callSid, (int)($_REQUEST['step'] ?? 0), $recordingSid);
} elseif ($type === 'outgoing' && $recordingSid) {
    // Outgoing call recording callback - save to calls.db
    process_outgoing_recording($callSid, $recordingSid, $recordingUrl);
} elseif ($recordingSid) {
    // Answered call recording callback
    process_call_recording($recordingSid, $recordingUrl, $from, $callSid);
} else {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Unknown request type']);
}

// ============================================================================
// PROCESSING FUNCTIONS
// ============================================================================

/**
 * Process a completed missed call session
 * Transcribes all recordings, extracts data, creates CRM lead
 */
function process_session(string $callSid): void {
    header('Content-Type: application/json');

    $sessionFile = __DIR__ . '/sessions/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';

    if (!file_exists($sessionFile)) {
        echo json_encode(['ok' => false, 'error' => 'Session not found']);
        return;
    }

    $session = json_decode(file_get_contents($sessionFile), true);
    if (!$session) {
        echo json_encode(['ok' => false, 'error' => 'Invalid session']);
        return;
    }

    // Already processed?
    if (!empty($session['processed'])) {
        echo json_encode(['ok' => true, 'message' => 'Already processed']);
        return;
    }

    voice_log(['event' => 'process_session_start', 'call_sid' => $callSid]);

    // Transcribe each recording if not already done
    $fields = ['name', 'phone', 'address', 'email'];
    $transcripts = $session['transcripts'] ?? [];

    foreach ($fields as $field) {
        if (!empty($transcripts[$field])) continue;

        $rec = $session['recordings'][$field] ?? null;
        if (!$rec || empty($rec['sid'])) continue;

        // Fetch and transcribe
        $download = fetch_recording($rec['sid']);
        if ($download['ok']) {
            $whisper = transcribe($download['data'], "{$field}.mp3");
            if ($whisper['ok']) {
                $transcripts[$field] = $whisper['text'];
            }
        }
    }

    $session['transcripts'] = $transcripts;

    // Build customer data
    $customerData = [
        'name' => $transcripts['name'] ?? null,
        'phone' => $transcripts['phone'] ?? $session['caller_id'] ?? null,
        'address' => $transcripts['address'] ?? null,
        'email' => $transcripts['email'] ?? null,
        'source' => 'Phone - Missed Call',
        'notes' => "Collected via voice prompts.\n\nTranscripts:\n" . json_encode($transcripts, JSON_PRETTY_PRINT),
    ];

    // Clean phone number (extract digits)
    if (!empty($customerData['phone'])) {
        $digits = preg_replace('/\D/', '', $customerData['phone']);
        if (strlen($digits) >= 10) {
            $customerData['phone'] = substr($digits, -10);
        }
    }

    // Split name into first/last
    if (!empty($customerData['name'])) {
        $parts = explode(' ', trim($customerData['name']), 2);
        $customerData['first_name'] = $parts[0];
        $customerData['last_name'] = $parts[1] ?? '';
    }

    // Use GPT to clean up/validate email if it looks like it was spelled out
    if (!empty($transcripts['email']) && !filter_var($transcripts['email'], FILTER_VALIDATE_EMAIL)) {
        $extracted = extract_customer_data("Email: " . $transcripts['email']);
        if (!empty($extracted['email'])) {
            $customerData['email'] = $extracted['email'];
        }
    }

    // Create CRM lead
    $crmResult = create_lead($customerData);

    // Send to ezlead4u.com for distribution (HQ system)
    require_once __DIR__ . '/../lib/EzleadClient.php';
    $ezlead = new EzleadClient();
    $ezleadResult = null;
    if ($ezlead->isEnabled()) {
        $description = "Source: IVR Session\n";
        $description .= "Notes: " . ($customerData['notes'] ?? 'No notes');

        $ezleadResult = $ezlead->directPost(
            defined('EZLEAD_VERTICAL') ? EZLEAD_VERTICAL : 'mechanic',
            $customerData['state'] ?? 'FL',
            $customerData['first_name'] ?? '',
            $customerData['phone'] ?? '',
            $customerData['last_name'] ?? null,
            $customerData['email'] ?? null,
            $customerData['city'] ?? null,
            $customerData['zip'] ?? null,
            $customerData['address'] ?? null,
            $description
        );
        if (!$ezleadResult['success']) {
            error_log("EzLead submission failed: " . ($ezleadResult['error'] ?? 'Unknown error'));
        }
    }

    // Update session
    $session['customer_data'] = $customerData;
    $session['crm_result'] = $crmResult;
    $session['ezlead_result'] = $ezleadResult;
    $session['processed'] = true;
    $session['processed_at'] = date('c');
    file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));

    voice_log([
        'event' => 'process_session_complete',
        'call_sid' => $callSid,
        'customer_data' => $customerData,
        'crm_ok' => $crmResult['ok'] ?? false,
        'ezlead_ok' => $ezleadResult['success'] ?? false,
    ]);

    echo json_encode(['ok' => true, 'customer' => $customerData, 'crm' => $crmResult, 'ezlead' => $ezleadResult]);
}

/**
 * Process individual prompt recording (called per-step)
 */
function process_prompt_recording(string $callSid, int $step, string $recordingSid): void {
    header('Content-Type: application/json');

    if (!$recordingSid) {
        echo json_encode(['ok' => false, 'error' => 'No recording SID']);
        return;
    }

    $fields = ['name', 'phone', 'address', 'email'];
    $field = $fields[$step] ?? null;

    if (!$field) {
        echo json_encode(['ok' => false, 'error' => 'Invalid step']);
        return;
    }

    voice_log(['event' => 'prompt_recording', 'call_sid' => $callSid, 'step' => $step, 'field' => $field]);

    // Try to transcribe immediately
    $download = fetch_recording($recordingSid);
    $transcript = null;

    if ($download['ok']) {
        $whisper = transcribe($download['data'], "{$field}.mp3");
        if ($whisper['ok']) {
            $transcript = $whisper['text'];
        }
    }

    // Update session with transcript
    if ($transcript) {
        $sessionFile = __DIR__ . '/sessions/' . preg_replace('/[^A-Za-z0-9]/', '', $callSid) . '.json';
        if (file_exists($sessionFile)) {
            $session = json_decode(file_get_contents($sessionFile), true) ?: [];
            $session['transcripts'][$field] = $transcript;
            $session['updated'] = date('c');
            file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
        }
    }

    echo json_encode(['ok' => true, 'field' => $field, 'transcript' => $transcript]);
}

/**
 * Process answered call recording
 * Transcribes full conversation, extracts customer data, creates CRM lead
 */
function process_call_recording(string $recordingSid, string $recordingUrl, string $from, string $callSid): void {
    header('Content-Type: application/json');

    // Dedupe check
    $processedFile = __DIR__ . '/sessions/processed_recordings.json';
    $processed = file_exists($processedFile) ? json_decode(file_get_contents($processedFile), true) ?: [] : [];

    // Clean old entries (1 hour)
    $processed = array_filter($processed, fn($ts) => $ts > time() - 3600);

    if (isset($processed[$recordingSid])) {
        echo json_encode(['ok' => true, 'message' => 'Already processed']);
        return;
    }

    $processed[$recordingSid] = time();
    file_put_contents($processedFile, json_encode($processed));

    voice_log(['event' => 'call_recording', 'recording_sid' => $recordingSid, 'from' => $from]);

    // Fetch recording (with retry)
    $download = fetch_recording($recordingSid);
    if (!$download['ok']) {
        sleep(3);
        $download = fetch_recording($recordingSid);
    }

    if (!$download['ok']) {
        voice_log(['event' => 'recording_download_failed', 'recording_sid' => $recordingSid, 'error' => $download['error'] ?? 'unknown']);
        echo json_encode(['ok' => false, 'error' => 'Failed to download recording']);
        return;
    }

    // Transcribe
    $whisper = transcribe($download['data'], 'call.mp3');
    if (!$whisper['ok']) {
        voice_log(['event' => 'transcription_failed', 'recording_sid' => $recordingSid, 'error' => $whisper['error'] ?? 'unknown']);
        echo json_encode(['ok' => false, 'error' => 'Transcription failed']);
        return;
    }

    $transcript = $whisper['text'];
    voice_log(['event' => 'transcribed', 'recording_sid' => $recordingSid, 'length' => strlen($transcript)]);

    // Extract customer data with GPT
    $extracted = extract_customer_data($transcript);

    // Build lead data
    $customerData = [
        'name' => $extracted['name'] ?? null,
        'phone' => $extracted['phone'] ?? preg_replace('/\D/', '', $from),
        'address' => $extracted['address'] ?? null,
        'email' => $extracted['email'] ?? null,
        'source' => 'Phone - Voicemail',
        'notes' => "Transcript:\n{$transcript}",
    ];

    // Add vehicle info for mechanic type
    if (!empty($extracted['year'])) $customerData['year'] = $extracted['year'];
    if (!empty($extracted['make'])) $customerData['make'] = $extracted['make'];
    if (!empty($extracted['model'])) $customerData['model'] = $extracted['model'];

    // Generate estimate if vehicle info present
    $estimate = null;
    if (!empty($extracted['year']) && !empty($extracted['make']) && !empty($extracted['model']) && !empty($extracted['problem'])) {
        $estimate = generate_estimate($extracted);
        if ($estimate) {
            $customerData['notes'] .= "\n\n--- ESTIMATE ---\n";
            $customerData['notes'] .= "Repair: {$estimate['repair_name']}\n";
            $customerData['notes'] .= "Description: {$estimate['description']}\n";
            $customerData['notes'] .= "Labor: {$estimate['labor_hours']} hrs @ \$" . config('estimates.labor_rate', 95) . "/hr = \${$estimate['labor_cost']}\n";
            $customerData['notes'] .= "Parts: \${$estimate['parts_low']} - \${$estimate['parts_high']}\n";
            $customerData['notes'] .= "TOTAL: \${$estimate['total_low']} - \${$estimate['total_high']}\n";
            if ($estimate['notes']) {
                $customerData['notes'] .= "Notes: {$estimate['notes']}\n";
            }
            voice_log(['event' => 'estimate_generated', 'estimate' => $estimate]);
        }
    }

    // Split name
    if (!empty($customerData['name'])) {
        $parts = explode(' ', trim($customerData['name']), 2);
        $customerData['first_name'] = $parts[0];
        $customerData['last_name'] = $parts[1] ?? '';
    }

    // Clean phone
    if (!empty($customerData['phone'])) {
        $digits = preg_replace('/\D/', '', $customerData['phone']);
        if (strlen($digits) >= 10) {
            $customerData['phone'] = substr($digits, -10);
        }
    }

    // Create CRM lead
    $crmResult = create_lead($customerData);

    // Send to ezlead4u.com for distribution (HQ system)
    require_once __DIR__ . '/../lib/EzleadClient.php';
    $ezlead = new EzleadClient();
    $ezleadResult = null;
    if ($ezlead->isEnabled()) {
        $description = "Source: Phone Call\n";
        $description .= "Transcript: " . ($customerData['notes'] ?? 'No transcript available');

        $ezleadResult = $ezlead->directPost(
            defined('EZLEAD_VERTICAL') ? EZLEAD_VERTICAL : 'mechanic',
            $customerData['state'] ?? 'FL',
            $customerData['first_name'] ?? '',
            $customerData['phone'] ?? '',
            $customerData['last_name'] ?? null,
            $customerData['email'] ?? null,
            $customerData['city'] ?? null,
            $customerData['zip'] ?? null,
            $customerData['address'] ?? null,
            $description
        );
        if (!$ezleadResult['success']) {
            error_log("EzLead submission failed: " . ($ezleadResult['error'] ?? 'Unknown error'));
        }
    }

    // Distribute to buyers if CRM lead was created (legacy local distribution)
    $distributionResult = null;
    if ($crmResult['ok'] ?? false) {
        $crmLeadId = $crmResult['response']['insert_id'] ?? $crmResult['response']['item_id'] ?? 0;
        if ($crmLeadId && !$ezlead->isEnabled()) {
            // Only use local distribution if ezlead is not enabled
            require_once __DIR__ . '/../buyer/LeadDistributor.php';
            $distributor = new LeadDistributor();
            $distributionResult = $distributor->distributeLead(
                $customerData,
                config('site.domain', ''),
                (int)$crmLeadId
            );
        }
    }

    voice_log([
        'event' => 'call_recording_processed',
        'recording_sid' => $recordingSid,
        'customer_data' => $customerData,
        'crm_ok' => $crmResult['ok'] ?? false,
        'ezlead_ok' => $ezleadResult['success'] ?? false,
        'distributed_to' => $distributionResult ? count($distributionResult) : 0,
    ]);

    echo json_encode(['ok' => true, 'customer' => $customerData, 'crm' => $crmResult, 'ezlead' => $ezleadResult, 'distribution' => $distributionResult]);
}

/**
 * Process outgoing call recording
 * Saves recording URL to calls.db and downloads MP3
 */
function process_outgoing_recording(string $callSid, string $recordingSid, string $recordingUrl): void {
    header('Content-Type: application/json');

    voice_log(['event' => 'outgoing_recording', 'call_sid' => $callSid, 'recording_sid' => $recordingSid, 'url' => $recordingUrl]);

    // Get duration from POST
    $duration = (int)($_REQUEST['RecordingDuration'] ?? 0);

    // Save recording to local file
    $localPath = '';
    if ($recordingUrl) {
        $mp3Url = $recordingUrl . '.mp3';

        // Get SignalWire credentials for auth
        $projectId = config('phone.project_id', '');
        $apiToken = config('phone.api_token', '');

        // Download recording with Basic Auth
        $ch = curl_init($mp3Url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERPWD => "{$projectId}:{$apiToken}",
        ]);
        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && strlen($audioData) > 1000) {
            $filename = $recordingSid . '.mp3';
            $localPath = __DIR__ . '/recordings/' . $filename;
            file_put_contents($localPath, $audioData);
            voice_log(['event' => 'outgoing_recording_saved', 'path' => $localPath, 'size' => strlen($audioData)]);
        }
    }

    // Update calls.db
    $callsDb = __DIR__ . '/../data/calls.db';
    try {
        $db = new SQLite3($callsDb);

        // Create table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS outgoing_calls (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            call_sid TEXT,
            to_number TEXT,
            from_number TEXT,
            notes TEXT,
            status TEXT DEFAULT 'initiated',
            duration INTEGER,
            recording_url TEXT,
            recording_sid TEXT,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        )");

        // Update the call record
        $stmt = $db->prepare("UPDATE outgoing_calls SET
            status = 'completed',
            duration = :duration,
            recording_url = :url,
            recording_sid = :sid,
            updated_at = datetime('now')
            WHERE call_sid = :call_sid");
        $stmt->bindValue(':duration', $duration);
        $stmt->bindValue(':url', $localPath ? '/voice/recordings/' . basename($localPath) : $recordingUrl);
        $stmt->bindValue(':sid', $recordingSid);
        $stmt->bindValue(':call_sid', $callSid);
        $stmt->execute();

        $changes = $db->changes();
        voice_log(['event' => 'outgoing_call_updated', 'call_sid' => $callSid, 'changes' => $changes]);

        echo json_encode(['ok' => true, 'call_sid' => $callSid, 'recording_sid' => $recordingSid, 'duration' => $duration]);
    } catch (Exception $e) {
        voice_log(['event' => 'outgoing_db_error', 'error' => $e->getMessage()]);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
}
