<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transcript = $_POST['TranscriptionText'] ?? '';
    $recording_url = $_POST['RecordingUrl'] ?? '';
    $call_sid = $_POST['CallSid'] ?? '';
    $from = $_POST['From'] ?? '';

    if ($transcript) {
        // Process the transcript and extract customer information
        $process_data = [
            'transcript' => $transcript,
            'recording_url' => $recording_url,
            'phone' => $from,
            'call_sid' => $call_sid
        ];

        // Use the process_call.php logic
        $db = getDB();

        // Extract customer info from transcript
        function parseTranscript($transcript)
        {
            $customer = [];

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

        $customer_info = parseTranscript($transcript);
        $customer_info['phone'] = $from;

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
                $from,
                $recording_url,
                $transcript
            ]);

            error_log("Customer created from call transcript: ID $customer_id");
        }
    }

    // Return empty response for Twilio
    http_response_code(200);
} else {
    http_response_code(405);
}
