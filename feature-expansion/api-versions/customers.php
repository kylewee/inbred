<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        // Get all customers or specific customer
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($customer ?: ['error' => 'Customer not found']);
        } else {
            $stmt = $db->query("SELECT * FROM customers ORDER BY created_at DESC");
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($customers);
        }
        break;

    case 'POST':
        // Create new customer
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $db->prepare("
            INSERT INTO customers (first_name, last_name, phone, address, car_year, car_make, car_model, engine_size, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['car_year'] ?? '',
            $data['car_make'] ?? '',
            $data['car_model'] ?? '',
            $data['engine_size'] ?? '',
            $data['notes'] ?? ''
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create customer']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
