<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

// Load price catalog
$catalog = json_decode(file_get_contents('../price-catalog.json'), true);

function calculatePrice($repair, $year, $make, $model, $engine_size, $catalog)
{
    // Find repair in catalog
    $repair_data = null;
    foreach ($catalog as $item) {
        if (stripos($item['repair'], $repair) !== false) {
            $repair_data = $item;
            break;
        }
    }

    if (!$repair_data) {
        return ['error' => 'Service not found'];
    }

    $base_price = $repair_data['price'];
    $multiplier = 1.0;

    // Apply multipliers
    if (isset($repair_data['multipliers'])) {
        // V8 engine multiplier
        if (stripos($engine_size, 'v8') !== false && isset($repair_data['multipliers']['v8'])) {
            $multiplier *= $repair_data['multipliers']['v8'];
        }

        // Old car multiplier (15+ years)
        if (is_numeric($year) && (date('Y') - intval($year)) >= 15 && isset($repair_data['multipliers']['old_car'])) {
            $multiplier *= $repair_data['multipliers']['old_car'];
        }
    }

    $final_price = $base_price * $multiplier;

    return [
        'service' => $repair_data['repair'],
        'base_price' => $base_price,
        'multiplier' => $multiplier,
        'final_price' => round($final_price, 2),
        'estimated_time' => $repair_data['time'] . ' hours'
    ];
}

if (isset($data['repair']) && isset($data['year']) && isset($data['make']) && isset($data['model'])) {
    $quote = calculatePrice(
        $data['repair'],
        $data['year'],
        $data['make'],
        $data['model'],
        $data['engine_size'] ?? '',
        $catalog
    );

    if (!isset($quote['error'])) {
        // Store service request in database
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO service_requests (customer_id, service_type, estimated_price)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $data['customer_id'] ?? null,
            $quote['service'],
            $quote['final_price']
        ]);

        $quote['request_id'] = $db->lastInsertId();
    }

    echo json_encode($quote);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
}
