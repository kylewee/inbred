<?php
declare(strict_types=1);

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Define constants if not already defined
if (!defined('SIGNALWIRE_API_KEY')) {
    define('SIGNALWIRE_API_KEY', $_ENV['SIGNALWIRE_API_KEY'] ?? '');
}
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
}
if (!defined('CRM_API_URL')) {
    define('CRM_API_URL', $_ENV['CRM_API_URL'] ?? '');
}
if (!defined('CRM_API_KEY')) {
    define('CRM_API_KEY', $_ENV['CRM_API_KEY'] ?? '');
}

/**
 * SignalWire webhook handler for call recordings
 * Downloads recordings, transcribes them, extracts customer data, and creates CRM leads
 */
function handle_signalwire_webhook(): void
{
    // Log webhook receipt
    error_log("VOICE_WEBHOOK: Received SignalWire webhook");
    
    // Get webhook data
    $webhookData = json_decode(file_get_contents('php://input'), true);
    
    if (!$webhookData) {
        error_log("VOICE_WEBHOOK: Invalid JSON received");
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    // Validate webhook
    if (!validate_signalwire_webhook($webhookData)) {
        error_log("VOICE_WEBHOOK: Invalid webhook data");
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook data']);
        return;
    }
    
    // Process recording
    $result = process_call_recording($webhookData);
    
    // Return response
    http_response_code(200);
    echo json_encode($result);
    
    // Log final result for debugging
    error_log("VOICE_WEBHOOK: Final result - " . json_encode($result));
}

/**
 * Validate SignalWire webhook data
 */
function validate_signalwire_webhook(array $data): bool
{
    // Check required fields
    $required = ['CallSid', 'RecordingUrl', 'RecordingDuration'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            error_log("VOICE_WEBHOOK: Missing required field: $field");
            return false;
        }
    }
    
    // Validate call duration and recording status
    $duration = (int)$data['RecordingDuration'];
    $recordingStatus = $data['RecordingStatus'] ?? '';
    
    // Only process if we have a valid recording
    if ($duration < 3 || $recordingStatus !== 'completed') {
        error_log("VOICE_WEBHOOK: Ignoring webhook - Duration: {$duration}s, Status: {$recordingStatus}");
        return false;
    }
    
    // Mark very short calls as test calls
    if ($duration < 5) {
        error_log("VOICE_WEBHOOK: Test call detected - Duration: {$duration}s, skipping processing");
        return false;
    }
    
    if ($duration === 0) {
        error_log("VOICE_WEBHOOK: Call marked as missed (0 seconds)");
        $data['call_status'] = 'missed';
    }
    
    return true;
}

/**
 * Process call recording
 */
function process_call_recording(array $data): array
{
    $result = [
        'call_sid' => $data['CallSid'],
        'status' => 'processing',
        'customer_data' => null,
        'transcript' => null,
        'crm_lead' => null
    ];
    
    try {
        // Download recording
        $recordingFile = download_recording($data['RecordingUrl'], $data['CallSid']);
        if (!$recordingFile) {
            throw new Exception("Failed to download recording");
        }
        
        // Transcribe recording
        $transcript = transcribe_recording($recordingFile);
        if (!$transcript) {
            throw new Exception("Failed to transcribe recording");
        }
        
        $result['transcript'] = $transcript;
        
        // Extract customer data
        $customerData = extract_customer_data($transcript);
        $result['customer_data'] = $customerData;
        
        // Create CRM lead only if we have meaningful customer data
        if ($customerData && !empty($customerData['first_name']) && strlen(trim($transcript ?? '')) > 10) {
            $crmResult = create_crm_lead($customerData, $data);
            $result['crm_lead'] = $crmResult;
        } else {
            // Skip CRM creation for low-quality or empty data
            $result['crm_lead'] = ['skipped' => 'Insufficient customer data for CRM creation'];
            error_log("VOICE_CRM: Skipped CRM lead creation - insufficient customer data from transcript: " . substr($transcript, 0, 100));
        }
        
        $result['status'] = 'completed';
        
        // Clean up recording file
        if (file_exists($recordingFile)) {
            unlink($recordingFile);
        }
        
    } catch (Exception $e) {
        error_log("VOICE_WEBHOOK: Processing failed: " . $e->getMessage());
        $result['status'] = 'error';
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

/**
 * Download recording from SignalWire
 */
function download_recording(string $url, string $callSid): ?string
{
    $filename = sys_get_temp_dir() . "/recording_{$callSid}.mp3";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SIGNALWIRE_API_KEY
        ]
    ]);
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error || $httpCode !== 200) {
        error_log("VOICE_WEBHOOK: Failed to download recording - HTTP $httpCode: $error");
        return null;
    }
    
    if (file_put_contents($filename, $data)) {
        error_log("VOICE_WEBHOOK: Recording downloaded to $filename");
        return $filename;
    }
    
    return null;
}

/**
 * Transcribe recording using OpenAI Whisper
 */
function transcribe_recording(string $filename): ?string
{
    if (!defined('OPENAI_API_KEY') || !OPENAI_API_KEY) {
        error_log("VOICE_WEBHOOK: OpenAI API key not configured");
        return null;
    }
    
    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($filename),
            'model' => 'whisper-1',
            'language' => 'en'
        ],
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error || $httpCode !== 200) {
        error_log("VOICE_WEBHOOK: OpenAI transcription failed - HTTP $httpCode: $error");
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['text'] ?? null;
}

/**
 * Extract customer data from transcript
 */
function extract_customer_data(string $transcript): ?array
{
    // Try AI extraction first
    if (defined('OPENAI_API_KEY') && OPENAI_API_KEY) {
        $data = extract_customer_data_ai($transcript);
        if ($data) {
            return $data;
        }
    }
    
    // Fallback to pattern matching
    return extract_customer_data_patterns($transcript);
}

/**
 * Extract customer data using OpenAI
 */
function extract_customer_data_ai(string $transcript): ?array
{
    $prompt = "You are analyzing a phone call transcript between a mobile mechanic and a customer. Extract the CUSTOMER's information (not the mechanic's) and return ONLY a JSON object with these exact keys (use null for missing data):

{
  \"first_name\": \"customer's first name\",
  \"last_name\": \"customer's last name\",
  \"phone\": \"phone number in format like 9045551234 (digits only)\",
  \"address\": \"location/address mentioned\",
  \"year\": \"vehicle year (4 digits)\",
  \"make\": \"vehicle make/brand\",
  \"model\": \"vehicle model\",
  \"engine\": \"engine size/type if mentioned\",
  \"notes\": \"problem description or service needed\"
}

Rules:
- IGNORE voicemail system messages like: 'press 1', 'leave a message', 'record after the tone', etc.
- IGNORE generic greetings or automated system prompts
- ONLY extract information from actual customer speech about vehicle service needs
- If the transcript is ONLY voicemail prompts or system messages, return all null values
- Extract actual customer info ONLY, ignore anything the mechanic or system says
- Ignore words like \"OK\", \"alright\", \"got it\" - those are confirmations, not data
- For phone: digits only, no formatting (example: 9045551234)
- For year: must be 4-digit year between 1990-2030
- For make/model: standardize common brands (Honda, Toyota, Ford, Chevy/Chevrolet, etc.)
- For notes: summarize the actual problem/service the customer needs
- Return null for any field that's not clearly stated in the transcript
- Do not make up or guess any information

Example (GOOD - extract this):
Customer: \"Hi, my name is Bill Richards at 1486 Mogul Road in St. Augustine. I have a 2010 Toyota Camry that needs new timing belts. My number is 904-207-5052.\"
→ Extract: first_name=\"Bill\", last_name=\"Richards\", phone=\"9042075052\", address=\"1486 Mogul Road, St. Augustine\", year=\"2010\", make=\"Toyota\", model=\"Camry\", notes=\"Needs new timing belts\"

Example (BAD - return all null):
\"Please leave your message for... press 1 to take this call... record after the tone\"
→ Extract: all fields = null (this is just voicemail system)

Transcript: " . $transcript;

    $payload = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 300,
        'temperature' => 0.1
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error || $httpCode !== 200) {
        error_log("VOICE_AI: OpenAI API error - HTTP $httpCode: $error");
        return null;
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        error_log("VOICE_AI: Invalid OpenAI response format");
        return null;
    }
    
    $aiResponse = trim($data['choices'][0]['message']['content']);
    $extracted = json_decode($aiResponse, true);
    
    if (!is_array($extracted)) {
        error_log("VOICE_AI: Failed to parse AI response as JSON: " . $aiResponse);
        return null;
    }
    
    // Clean and validate AI response
    $result = [];
    foreach (['first_name', 'last_name', 'phone', 'address', 'year', 'make', 'model', 'engine', 'notes'] as $field) {
        $value = $extracted[$field] ?? null;
        if ($value && trim($value) && strtolower(trim($value)) !== 'null') {
            $result[$field] = trim($value);
        }
    }
    
    return $result;
}

/**
 * Extract customer data using pattern matching (fallback)
 */
function extract_customer_data_patterns(string $transcript): ?array
{
    $data = [
        'first_name' => null,
        'last_name' => null,
        'phone' => null,
        'address' => null,
        'year' => null,
        'make' => null,
        'model' => null,
        'engine' => null,
        'notes' => null
    ];
    
    // Only process if transcript has meaningful content (longer than 10 characters)
    if (strlen(trim($transcript)) < 10) {
        return $data;
    }
    
    // Extract phone numbers
    if (preg_match('/(\d{3}[-.\s]?\d{3}[-.\s]?\d{4})/', $transcript, $matches)) {
        $data['phone'] = preg_replace('/[^\d]/', '', $matches[1]);
    }
    
    // Extract vehicle year
    if (preg_match('/(19|20)\d{2}/', $transcript, $matches)) {
        $year = (int)$matches[1];
        if ($year >= 1990 && $year <= 2030) {
            $data['year'] = $year;
        }
    }
    
    // Extract common vehicle makes
    $makes = ['honda', 'toyota', 'ford', 'chevy', 'chevrolet', 'nissan', 'bmw', 'mercedes', 'audi', 'volkswagen', 'hyundai', 'kia', 'subaru', 'mazda'];
    foreach ($makes as $make) {
        if (preg_match("/\b$make\b/i", $transcript)) {
            $data['make'] = ucfirst($make);
            break;
        }
    }
    
    // Extract common models
    $models = ['camry', 'corolla', 'civic', 'accord', 'f-150', 'silverado', 'sierra', 'altima', 'sentra', 'elantra', 'sonata'];
    foreach ($models as $model) {
        if (preg_match("/\b$model\b/i", $transcript)) {
            $data['model'] = ucfirst($model);
            break;
        }
    }
    
    // Extract names (basic pattern)
    if (preg_match('/(?:my name is|i am|this is)\s+([A-Z][a-z]+)\s+([A-Z][a-z]+)/i', $transcript, $matches)) {
        $data['first_name'] = $matches[1];
        $data['last_name'] = $matches[2];
    }
    
    // Extract service needs
    $services = ['oil change', 'brake', 'timing belt', 'tires', 'battery', 'transmission', 'engine', 'air conditioning'];
    foreach ($services as $service) {
        if (preg_match("/\b$service\b/i", $transcript)) {
            $data['notes'] = "Customer mentioned: " . $service;
            break;
        }
    }
    
    return $data;
}

/**
 * Create lead in CRM system
 */
function create_crm_lead(array $customerData, array $callData): ?array
{
    // Check if CRM is configured
    if (!defined('CRM_API_URL') || !CRM_API_URL || CRM_API_URL === '') {
        error_log("VOICE_CRM: CRM API not configured");
        return ['error' => 'CRM configuration missing'];
    }
    
    if (!defined('CRM_API_KEY') || !CRM_API_KEY || CRM_API_KEY === '') {
        error_log("VOICE_CRM: CRM API key not configured");
        return ['error' => 'CRM configuration missing'];
    }
    
    $leadData = [
        'first_name' => $customerData['first_name'],
        'last_name' => $customerData['last_name'],
        'phone' => $customerData['phone'],
        'address' => $customerData['address'],
        'vehicle_year' => $customerData['year'],
        'vehicle_make' => $customerData['make'],
        'vehicle_model' => $customerData['model'],
        'service_needed' => $customerData['notes'],
        'call_sid' => $callData['CallSid'],
        'call_duration' => $callData['RecordingDuration'],
        'source' => 'phone_call',
        'status' => 'new'
    ];
    
    $ch = curl_init(CRM_API_URL . '/leads');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($leadData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . CRM_API_KEY
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error || $httpCode !== 201) {
        error_log("VOICE_CRM: Failed to create lead - HTTP $httpCode: $error");
        return ['error' => 'Failed to create CRM lead'];
    }
    
    $result = json_decode($response, true);
    error_log("VOICE_CRM: Lead created successfully - ID: " . ($result['id'] ?? 'unknown'));
    
    return $result;
}

// Handle webhook if this is the main script
if (basename($_SERVER['PHP_SELF']) === 'recording_callback.php') {
    header('Content-Type: application/json');
    handle_signalwire_webhook();
}