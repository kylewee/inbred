<?php
/**
 * Customer Portal - Quote View Action
 * Display quote details and estimate
 */

// Use public layout (no login required)
$app_layout = 'public_layout.php';

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lead_id === 0) {
    redirect_to('customer_portal/quote/index');
}

// Get lead data
$lead = customer_quote::get_by_id($lead_id);

if (!$lead) {
    redirect_to('customer_portal/quote/index');
}

// Parse estimate from notes
$estimate = customer_quote::parse_estimate($lead['notes']);

// Get dispatch job if exists
$dispatch_job = customer_quote::get_dispatch_job($lead_id);

// Format data for display
$customer_name = trim($lead['first_name'] . ' ' . $lead['last_name']);
if (empty($customer_name) || $customer_name === 'Unknown Caller' || strpos($customer_name, 'Caller') !== false) {
    $customer_name = 'Customer';
}

$vehicle = trim($lead['year'] . ' ' . $lead['make'] . ' ' . $lead['model']);
$phone = customer_quote::format_phone($lead['phone']);

// Extract service description from notes
$service_description = '';
$transcript = '';

if (!empty($lead['notes'])) {
    // Try to find transcript
    if (preg_match('/Transcript:?\s*(.+?)(?:\n---|$)/s', $lead['notes'], $m)) {
        $transcript = trim($m[1]);
        $service_description = substr($transcript, 0, 200);
    } else {
        // Use first part of notes
        $service_description = substr($lead['notes'], 0, 200);
    }
}

// Check if already approved/declined
$status = $lead['stage'] ?? '';
$is_approved = (stripos($status, 'approved') !== false);
$is_declined = (stripos($status, 'declined') !== false);
