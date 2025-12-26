<?php
/**
 * A/B Testing Event Tracking API
 *
 * Endpoints:
 * POST /api/ab-track.php - Track an event
 * GET /api/ab-track.php?experiment=name - Get experiment stats
 *
 * Events:
 * - view: Page was viewed
 * - conversion: User converted (form submission, call click, etc.)
 * - click: User clicked a CTA
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../lib/ABTesting.php';

try {
    $ab = new ABTesting();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Track an event
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['experiment'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing experiment name']);
            exit;
        }

        $experiment = $input['experiment'];
        $eventType = $input['event'] ?? 'view';
        $metadata = $input['metadata'] ?? [];

        $result = $ab->trackEvent($experiment, $eventType, $metadata);

        echo json_encode([
            'success' => $result,
            'experiment' => $experiment,
            'event' => $eventType
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get experiment stats
        $experiment = $_GET['experiment'] ?? null;

        if ($experiment) {
            $stats = $ab->getStats($experiment);
            echo json_encode([
                'success' => true,
                'experiment' => $experiment,
                'stats' => $stats
            ]);
        } else {
            // List all experiments
            $experiments = $ab->getAllExperiments();
            echo json_encode([
                'success' => true,
                'experiments' => $experiments
            ]);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
