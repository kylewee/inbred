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

require_once __DIR__ . '/../api/.env.local.php';
require_once __DIR__ . '/../lib/PostServiceFlow.php';

// Simple token auth
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== 'msarec-2b7c9f1a5d4e') {
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

    echo json_encode([
        'success' => $result['success'],
        'service_id' => $result['service_id'],
        'message' => 'Completion SMS sent. Follow-up scheduled for 24hrs.',
        'followup_note' => 'Run cron: php services/send_scheduled_followups.php'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
