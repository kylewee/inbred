<?php
/**
 * Quote Approval API
 * Handles customer approval of quotes
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../lib/QuoteSMS.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $quoteId = $input['quote_id'] ?? '';

    if (!$quoteId) {
        throw new Exception('Quote ID required');
    }

    $quoteSMS = new QuoteSMS();
    $quote = $quoteSMS->getQuote($quoteId);

    if (!$quote) {
        throw new Exception('Quote not found');
    }

    // Approve quote
    $quoteSMS->approveQuote($quoteId);

    // Send confirmation SMS
    $confirmMessage = "✓ Quote approved! We'll contact you within 2 hours to schedule your service.\n\n";
    $confirmMessage .= "EZ Mobile Mechanic\n";
    $confirmMessage .= "(904) 217-5152";

    // TODO: Trigger CRM notification/task creation
    // Could integrate with Rukovoditel API to create a task or update lead status

    echo json_encode([
        'success' => true,
        'message' => 'Quote approved successfully',
        'quote_id' => $quoteId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
