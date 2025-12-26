<?php
/**
 * SWAIG Functions for SignalWire AI Agent
 * These functions are called by the AI during conversations
 */

header('Content-Type: application/json');

// Load environment and recording_callback functions
$envPath = __DIR__ . '/../api/.env.local.php';
if (file_exists($envPath)) {
    require_once $envPath;
}

// Load recording_callback to access estimate functions
define('VOICE_LIB_ONLY', true);
require_once __DIR__ . '/recording_callback.php';

// Get request
$input = json_decode(file_get_contents('php://input'), true);
$function = $input['function'] ?? '';
$argument = $input['argument'] ?? [];

$response = ['response' => 'Function not found'];

// Get Auto Estimate
if ($function === 'get_estimate') {
    $year = $argument['year'] ?? '';
    $make = $argument['make'] ?? '';
    $model = $argument['model'] ?? '';
    $problem = $argument['problem'] ?? '';

    // Generate transcript-style description
    $transcript = "Customer has a $year $make $model with the following issue: $problem";

    // Call estimate function
    if (function_exists('auto_estimate_from_transcript')) {
        $estimate = auto_estimate_from_transcript($transcript, $year, $make, $model);

        if (!empty($estimate['success']) && !empty($estimate['estimates'])) {
            // Find the most relevant estimate
            $topEstimate = $estimate['estimates'][0];
            $price = number_format($topEstimate['total'], 2);
            $repair = $topEstimate['repair'];

            $response = [
                'response' => "For a $repair on a $year $make $model, the estimated cost is approximately $$price including parts and labor. This is based on current parts pricing and typical labor rates.",
                'data' => [
                    'repair' => $repair,
                    'total' => $topEstimate['total'],
                    'labor_cost' => $topEstimate['labor_cost'],
                    'parts_cost' => $topEstimate['parts_cost'],
                    'all_estimates' => $estimate['estimates']
                ]
            ];
        } else {
            $response = [
                'response' => "I don't have exact pricing for that repair right now, but the mechanic can give you a detailed quote when he calls you back. Most of our repairs range from $150 to $800 depending on the issue."
            ];
        }
    } else {
        $response = [
            'response' => "I can have the mechanic call you back with a quote. Most repairs are between $150 and $800."
        ];
    }
}

// Create CRM Lead
elseif ($function === 'create_lead') {
    $leadData = [
        'first_name' => $argument['first_name'] ?? '',
        'last_name' => $argument['last_name'] ?? '',
        'phone' => $argument['phone'] ?? '',
        'year' => $argument['year'] ?? '',
        'make' => $argument['make'] ?? '',
        'model' => $argument['model'] ?? '',
        'notes' => $argument['notes'] ?? '',
        'source' => 'AI Agent Call',
        'created_at' => date('c')
    ];

    if (function_exists('create_crm_lead')) {
        $result = create_crm_lead($leadData);

        if (!empty($result['api_response']['data']['id']) || !empty($result['fallback']['id'])) {
            $leadId = $result['api_response']['data']['id'] ?? $result['fallback']['id'];

            // Trigger SMS
            if ($leadId && class_exists('sms')) {
                try {
                    require_once(__DIR__ . '/../crm/plugins/ext/classes/sms.php');
                    sms::send_by_id(26, $leadId, 1);
                } catch (Exception $e) {
                    // Ignore SMS errors
                }
            }

            $response = [
                'response' => "Perfect! I've got all your information saved. The mechanic will call you back shortly.",
                'data' => ['lead_id' => $leadId]
            ];
        } else {
            $response = [
                'response' => "I've noted your information and the mechanic will get back to you soon."
            ];
        }
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
