<?php
/**
 * SWAIG Functions for SignalWire AI Agent
 * These functions are called by the AI during conversations
 *
 * Updated: Config-driven estimates for any business type
 */

header('Content-Type: application/json');

// Load config with webhook domain detection
require_once __DIR__ . '/../config/webhook_bootstrap.php';

// Load recording_callback for create_crm_lead function
define('VOICE_LIB_ONLY', true);
require_once __DIR__ . '/recording_callback.php';

// Get request
$input = json_decode(file_get_contents('php://input'), true);
$function = $input['function'] ?? '';
$argument = $input['argument'] ?? [];

$response = ['response' => 'Function not found'];

/**
 * Get estimate using GPT - works for any business type
 * Pulls prompt template and settings from config
 */
function get_gpt_estimate(array $inputs): array {
    $apiKey = config('openai.api_key', '');
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'OpenAI not configured'];
    }

    $estimateConfig = config('estimates', []);
    if (empty($estimateConfig['enabled'])) {
        return ['success' => false, 'error' => 'Estimates not enabled'];
    }

    $laborRate = $estimateConfig['labor_rate'] ?? 75;
    $minCharge = $estimateConfig['min_charge'] ?? 150;
    $estimateType = $estimateConfig['estimate_type'] ?? 'general';
    $systemPrompt = $estimateConfig['system_prompt'] ?? 'You are a professional estimator.';

    // Get the prompt template for this business type
    $promptTemplate = $estimateConfig['prompts'][$estimateType] ?? $estimateConfig['prompts']['general'] ??
        'Give an estimate for this job. Respond with JSON: {"job_name": "", "total": 0, "notes": ""}';

    // Replace placeholders in prompt with actual values
    $prompt = $promptTemplate;
    foreach ($inputs as $key => $value) {
        $displayValue = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
        $prompt = str_replace('{' . $key . '}', $displayValue, $prompt);
    }

    // Clean up any unreplaced placeholders
    $prompt = preg_replace('/\{[^}]+\}/', 'N/A', $prompt);

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
                ['role' => 'system', 'content' => $systemPrompt . ' Always respond with valid JSON only, no markdown.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 400
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'GPT API error', 'http_code' => $httpCode];
    }

    $data = json_decode($result, true);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Clean up response (remove markdown code blocks if present)
    $content = preg_replace('/```json\s*/', '', $content);
    $content = preg_replace('/```\s*/', '', $content);
    $content = trim($content);

    $estimate = json_decode($content, true);
    if (!$estimate) {
        return ['success' => false, 'error' => 'Failed to parse GPT response', 'raw' => $content];
    }

    // Ensure minimum charge
    if (isset($estimate['total']) && $estimate['total'] < $minCharge) {
        $estimate['total'] = $minCharge;
        $estimate['notes'] = ($estimate['notes'] ?? '') . " (Minimum charge: \${$minCharge})";
    }

    return [
        'success' => true,
        'estimate' => $estimate,
        'business_type' => $estimateType,
        'labor_rate' => $laborRate
    ];
}

/**
 * Format estimate response based on business type
 */
function format_estimate_response(array $estimate, array $inputs): string {
    $businessType = config('estimates.estimate_type', 'general');
    $siteName = config('site.name', 'our company');
    $data = $estimate['estimate'];

    switch ($businessType) {
        case 'mechanic':
            $repair = $data['repair_name'] ?? $inputs['problem'] ?? 'the repair';
            $vehicle = trim(($inputs['year'] ?? '') . ' ' . ($inputs['make'] ?? '') . ' ' . ($inputs['model'] ?? ''));
            $total = number_format($data['total'] ?? ($data['labor_cost'] ?? 0) + ($data['parts_cost'] ?? 0), 2);
            return "For a {$repair} on a {$vehicle}, the estimated cost is approximately \${$total} including parts and labor. " . ($data['notes'] ?? '');

        case 'landscaping':
            $job = $data['job_name'] ?? $inputs['service_type'] ?? 'the project';
            $total = number_format($data['total'] ?? 0, 2);
            $timeframe = !empty($data['timeframe']) ? " Typical timeframe: {$data['timeframe']}." : '';
            return "For {$job}, the estimated cost is approximately \${$total}.{$timeframe} " . ($data['notes'] ?? '');

        case 'roofing':
            $job = $data['job_name'] ?? 'roof work';
            $total = number_format($data['total'] ?? 0, 2);
            return "For {$job}, the estimated cost is approximately \${$total}. " . ($data['notes'] ?? '');

        case 'plumbing':
            $job = $data['job_name'] ?? $inputs['service_type'] ?? 'the service';
            $total = number_format($data['total'] ?? 0, 2);
            return "For {$job}, the estimated cost is approximately \${$total}. " . ($data['notes'] ?? '');

        default:
            $total = number_format($data['total'] ?? 0, 2);
            return "The estimated cost is approximately \${$total}. " . ($data['notes'] ?? '');
    }
}

// ===================
// FUNCTION HANDLERS
// ===================

// Get Estimate (any business type)
if ($function === 'get_estimate') {
    $estimate = get_gpt_estimate($argument);

    if ($estimate['success']) {
        $response = [
            'response' => format_estimate_response($estimate, $argument),
            'data' => $estimate['estimate']
        ];
    } else {
        $fallbackMsg = config('estimates.estimate_type') === 'mechanic'
            ? "I don't have exact pricing for that repair right now, but the mechanic can give you a detailed quote when he calls you back. Most repairs range from $150 to $800 depending on the issue."
            : "I don't have exact pricing for that right now, but we can give you a detailed quote. Most jobs range based on the scope of work.";

        $response = [
            'response' => $fallbackMsg
        ];
    }
}

// Create CRM Lead
elseif ($function === 'create_lead') {
    $businessType = config('estimates.estimate_type', 'general');
    $siteName = config('site.name', 'our company');

    $leadData = [
        'first_name' => $argument['first_name'] ?? '',
        'last_name' => $argument['last_name'] ?? '',
        'phone' => $argument['phone'] ?? '',
        'email' => $argument['email'] ?? '',
        'address' => $argument['address'] ?? '',
        'notes' => $argument['notes'] ?? '',
        'source' => 'AI Agent Call',
        'created_at' => date('c')
    ];

    // Add business-specific fields
    if ($businessType === 'mechanic') {
        $leadData['year'] = $argument['year'] ?? '';
        $leadData['make'] = $argument['make'] ?? '';
        $leadData['model'] = $argument['model'] ?? '';
    } elseif ($businessType === 'landscaping') {
        $leadData['sqft'] = $argument['sqft'] ?? '';
        $leadData['service_type'] = $argument['service_type'] ?? $argument['grass_type'] ?? '';
    }

    if (function_exists('create_crm_lead') && config('crm.enabled', false)) {
        $result = create_crm_lead($leadData);

        if (!empty($result['api_response']['data']['id']) || !empty($result['fallback']['id'])) {
            $leadId = $result['api_response']['data']['id'] ?? $result['fallback']['id'];

            $response = [
                'response' => "Perfect! I've got all your information saved. Someone from {$siteName} will call you back shortly.",
                'data' => ['lead_id' => $leadId]
            ];
        } else {
            $response = [
                'response' => "I've noted your information and someone will get back to you soon."
            ];
        }
    } else {
        // CRM disabled - just acknowledge
        $response = [
            'response' => "Thanks! Someone from {$siteName} will be in touch soon."
        ];
    }
}

// Get available services (for AI to know what we offer)
elseif ($function === 'get_services') {
    $services = config('business.services', []);
    $siteName = config('site.name', 'our company');

    $response = [
        'response' => "{$siteName} offers: " . implode(', ', $services),
        'data' => ['services' => $services]
    ];
}

// Get contact info
elseif ($function === 'get_contact_info') {
    $response = [
        'response' => "You can reach us at " . config('site.phone') . " or email " . config('site.email') . ". We serve " . config('site.service_area') . ".",
        'data' => [
            'phone' => config('site.phone'),
            'email' => config('site.email'),
            'address' => config('site.address'),
            'service_area' => config('site.service_area')
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
