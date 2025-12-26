<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../lib/CustomerFlow/Flow.php';

$flow = new CustomerFlow\Flow();

$result = $flow->sendQuote([
    'phone' => '+19046156899',
    'name' => 'Kyle Test',
    'vehicle' => '2020 Honda Accord',
    'services' => [['name' => 'Engine Diagnostic', 'price' => 150]],
    'total' => 150.00,
    'lead_id' => 320
]);

echo json_encode($result, JSON_PRETTY_PRINT);

// Check if it was saved
$job = $flow->getJob($result['job_id'] ?? '');
echo "\n\nSaved job: " . json_encode($job, JSON_PRETTY_PRINT);
