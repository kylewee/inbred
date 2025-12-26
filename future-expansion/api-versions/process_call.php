<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

// Extract customer info from transcript using basic parsing
function parseTranscript($transcript)
{
    $customer = [];

    // Simple parsing - look for patterns like "first name John OK"
    if (preg_match('/first name\s+(.*?)\s+ok/i', $transcript, $matches)) {
        $customer['first_name'] = trim($matches[1]);
    }
    if (preg_match('/last name\s+(.*?)\s+ok/i', $transcript, $matches)) {
        $customer['last_name'] = trim($matches[1]);
    }
    if (preg_match('/address\s+(.*?)\s+ok/i', $transcript, $matches)) {
        $customer['address'] = trim($matches[1]);
    }
    if (preg_match('/car\s+year\s+(.*?)\s+ok/i', $transcript, $matches)) {
        $customer['car_year'] = trim($matches[1]);
    }
    if (preg_match('/car\s+make\s+(.*?)\s+ok/i', $transcript, $matches)) {
        $customer['car_make'] = trim($matches[1]);
    }
    if (preg_match('/car\s+model\s+(.*?)\s+ok/i', $transcript, $matches)) {
        $customer['car_model'] = trim($matches[1]);
    }
    if (preg_match('/engine\s+size\s+(.*?)\s+ok/i', $transcript, $matches)) {
        $customer['engine_size'] = trim($matches[1]);
    }

    return $customer;
}

// Store call recording and extract customer info
if (isset($data['transcript'])) {
    $customer_info = parseTranscript($data['transcript']);
    $customer_info['phone'] = $data['phone'] ?? '';

    // Create customer record
    $stmt = $db->prepare("
        INSERT INTO customers (first_name, last_name, phone, address, car_year, car_make, car_model, engine_size)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $customer_info['first_name'] ?? '',
        $customer_info['last_name'] ?? '',
        $customer_info['phone'] ?? '',
        $customer_info['address'] ?? '',
        $customer_info['car_year'] ?? '',
        $customer_info['car_make'] ?? '',
        $customer_info['car_model'] ?? '',
        $customer_info['engine_size'] ?? ''
    ]);

    if ($result) {
        $customer_id = $db->lastInsertId();

        // Store call recording
        $stmt = $db->prepare("
            INSERT INTO call_recordings (customer_id, phone_number, recording_url, transcript)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $customer_id,
            $data['phone'] ?? '',
            $data['recording_url'] ?? '',
            $data['transcript']
        ]);

        echo json_encode([
            'success' => true,
            'customer_id' => $customer_id,
            'extracted_info' => $customer_info
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process call']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No transcript provided']);
}
