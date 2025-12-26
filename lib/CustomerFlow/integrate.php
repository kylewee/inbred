<?php
/**
 * CustomerFlow Integration Helper
 *
 * Call this from recording_callback.php after auto-estimate is generated:
 *
 *   require_once __DIR__ . '/../lib/CustomerFlow/integrate.php';
 *   $quoteResult = send_customer_quote($from, $leadData, $autoEstimate, $leadId);
 */

require_once __DIR__ . '/Flow.php';

/**
 * Send quote to customer after call transcription
 */
function send_customer_quote(string $phone, array $leadData, array $estimate, ?int $leadId = null): array {
    if (empty($phone) || empty($estimate['success'])) {
        return ['success' => false, 'error' => 'Missing phone or estimate'];
    }

    $flow = new CustomerFlow\Flow();

    // Build vehicle string
    $vehicle = trim(implode(' ', array_filter([
        $leadData['year'] ?? '',
        $leadData['make'] ?? '',
        $leadData['model'] ?? ''
    ]))) ?: 'Vehicle';

    // Build services array
    $services = [];
    foreach ($estimate['estimates'] ?? [] as $est) {
        $services[] = [
            'name' => $est['repair'] ?? 'Service',
            'price' => $est['total'] ?? 0
        ];
    }

    // Send quote
    return $flow->sendQuote([
        'phone' => $phone,
        'name' => $leadData['name'] ?? $leadData['first_name'] ?? '',
        'vehicle' => $vehicle,
        'services' => $services,
        'total' => $estimate['grand_total'] ?? 0,
        'lead_id' => $leadId
    ]);
}

/**
 * Mark job as complete and send completion SMS
 */
function complete_customer_job(int $leadId, ?string $paymentLink = null): array {
    $flow = new CustomerFlow\Flow();

    // Find job by lead_id
    $db = new SQLite3(dirname(__DIR__, 2) . '/data/customer_flow.db');
    $stmt = $db->prepare("SELECT job_id FROM jobs WHERE lead_id = :id ORDER BY created_at DESC LIMIT 1");
    $stmt->bindValue(':id', $leadId, SQLITE3_INTEGER);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$row) {
        return ['success' => false, 'error' => 'Job not found for lead'];
    }

    return $flow->complete($row['job_id'], $paymentLink);
}
