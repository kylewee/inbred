<?php
/**
 * Customer Portal - Approval Handler
 * Process quote approval or decline
 */

// Use public layout (no login required)
$app_layout = 'public_layout.php';

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($lead_id === 0 || !in_array($action, ['approve', 'decline'])) {
    redirect_to('customer_portal/quote/index');
}

// Get lead data
$lead = customer_quote::get_by_id($lead_id);

if (!$lead) {
    redirect_to('customer_portal/quote/index');
}

// Update approval status
$success = customer_quote::update_approval_status($lead_id, $action);

// Prepare confirmation message
$customer_name = trim($lead['first_name'] . ' ' . $lead['last_name']);
if (empty($customer_name) || $customer_name === 'Unknown Caller' || strpos($customer_name, 'Caller') !== false) {
    $customer_name = 'Customer';
}

$phone = customer_quote::format_phone($lead['phone']);
$is_approved = ($action === 'approve');

// TODO: Send SMS notification to customer (via CRM SMS rules or direct)
// Could trigger CRM SMS rule here: sms::send_by_id(26, $lead_id, $rule_id);
