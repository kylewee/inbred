<?php
/**
 * Call Tracking API - A/B Test Integration
 *
 * Endpoints:
 * POST /api/call-track.php?action=intent  - Track click-to-call intent
 * POST /api/call-track.php?action=call    - Track incoming call (from voice webhook)
 * GET  /api/call-track.php?action=stats   - Get call stats by experiment
 */

require_once __DIR__ . '/../lib/CallTracking.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $tracker = new CallTracking();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'intent':
            // Track when user clicks a phone link (before the call)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required for intent tracking');
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            // Get visitor/session info from cookies or request
            $data = [
                'visitor_id' => $input['visitor_id'] ?? $_COOKIE['ez_ab_visitor'] ?? '',
                'session_id' => $input['session_id'] ?? session_id(),
                'phone_clicked' => $input['phone'] ?? '',
                'source_page' => $input['page'] ?? $_SERVER['HTTP_REFERER'] ?? '',
                'ab_experiment' => $input['experiment'] ?? $_COOKIE['ez_ab_experiment'] ?? '',
                'ab_variant' => $input['variant'] ?? $_COOKIE['ez_ab_variant'] ?? '',
                'utm_source' => $input['utm_source'] ?? $_GET['utm_source'] ?? '',
                'utm_medium' => $input['utm_medium'] ?? $_GET['utm_medium'] ?? '',
                'utm_campaign' => $input['utm_campaign'] ?? $_GET['utm_campaign'] ?? ''
            ];

            $intentId = $tracker->trackCallIntent($data);

            echo json_encode([
                'success' => true,
                'intent_id' => $intentId,
                'message' => 'Call intent tracked'
            ]);
            break;

        case 'call':
            // Track incoming call - called from voice webhook
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required for call tracking');
            }

            // Verify webhook token for security
            $token = $_GET['token'] ?? $_POST['token'] ?? '';
            $expectedToken = 'calltrack-' . date('Ymd'); // Simple daily token

            // Also accept the voice system token
            if ($token !== $expectedToken && $token !== 'msarec-2b7c9f1a5d4e') {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            $result = $tracker->trackIncomingCall($input);

            echo json_encode([
                'success' => true,
                'call_id' => $result['call_id'],
                'attributed' => $result['attributed'],
                'ab_experiment' => $result['ab_experiment'],
                'ab_variant' => $result['ab_variant'],
                'attribution_method' => $result['attribution_method']
            ]);
            break;

        case 'update':
            // Update call status (completion, duration, etc.)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required for call update');
            }

            $token = $_GET['token'] ?? $_POST['token'] ?? '';
            if ($token !== 'calltrack-' . date('Ymd') && $token !== 'msarec-2b7c9f1a5d4e') {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $callSid = $input['call_sid'] ?? $input['CallSid'] ?? '';

            if (!$callSid) {
                throw new Exception('call_sid required');
            }

            $updateData = [
                'call_status' => $input['call_status'] ?? $input['CallStatus'] ?? null,
                'call_duration' => $input['call_duration'] ?? $input['CallDuration'] ?? null,
                'recording_url' => $input['recording_url'] ?? $input['RecordingUrl'] ?? null,
                'was_answered' => isset($input['was_answered']) ? (bool)$input['was_answered'] : null,
                'lead_created' => isset($input['lead_created']) ? (bool)$input['lead_created'] : null,
                'lead_id' => $input['lead_id'] ?? null
            ];

            // Remove nulls
            $updateData = array_filter($updateData, fn($v) => $v !== null);

            $success = $tracker->updateCall($callSid, $updateData);

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Call updated' : 'Update failed'
            ]);
            break;

        case 'stats':
            // Get call statistics for A/B experiments
            $experiment = $_GET['experiment'] ?? null;
            $stats = $tracker->getABCallStats($experiment);

            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;

        case 'recent':
            // Get recent calls
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $calls = $tracker->getRecentCalls($limit);

            echo json_encode([
                'success' => true,
                'calls' => $calls
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid action',
                'valid_actions' => ['intent', 'call', 'update', 'stats', 'recent']
            ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
