<?php
/**
 * Service Completion API
 * Triggers Step 4 (completion SMS) and schedules Step 5 (follow-up)
 *
 * POST /api/service-complete.php
 * {
 *   "customer_phone": "+19045551234",
 *   "customer_name": "John",
 *   "vehicle": "2018 Honda Accord",
 *   "lead_id": 123,
 *   "payment_link": "https://..." (optional)
 * }
 */

header('Content-Type: application/json');

// Load config
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../lib/PostServiceFlow.php';

if (config('crm.enabled', false)) {
    require_once __DIR__ . '/../lib/CRMHelper.php';
}

// Simple token auth
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$expectedToken = config('phone.recordings_token', '');
if (!$expectedToken || $token !== $expectedToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if (empty($input['customer_phone'])) {
        throw new Exception('customer_phone required');
    }

    $flow = new PostServiceFlow();

    $result = $flow->sendCompletionSMS([
        'customer_phone' => $input['customer_phone'],
        'customer_name' => $input['customer_name'] ?? '',
        'vehicle' => $input['vehicle'] ?? '',
        'lead_id' => $input['lead_id'] ?? null,
        'payment_link' => $input['payment_link'] ?? '',
        'services' => $input['services'] ?? []
    ]);

    // Update CRM lead stage to "Completed"
    $crmResult = null;
    if (!empty($input['lead_id'])) {
        $leadId = (int)$input['lead_id'];
        $services = is_array($input['services'] ?? null) ? implode(', ', $input['services']) : ($input['services'] ?? 'Service');
        $crmResult = CRMHelper::transitionStage(
            $leadId,
            CRMHelper::STAGE_COMPLETED,
            "🔧 **Service Completed**\nVehicle: " . ($input['vehicle'] ?? 'N/A') . "\nServices: {$services}\nCompleted at: " . date('Y-m-d H:i:s')
        );
    }

    echo json_encode([
        'success' => $result['success'],
        'service_id' => $result['service_id'],
        'message' => 'Completion SMS sent. Follow-up scheduled for 24hrs.',
        'followup_note' => 'Run cron: php services/send_scheduled_followups.php',
        'crm_updated' => $crmResult['success'] ?? false
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
