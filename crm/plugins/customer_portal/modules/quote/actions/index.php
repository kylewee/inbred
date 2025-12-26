<?php
/**
 * Customer Portal - Phone Lookup Action
 * Search for customer quote by phone number
 */

// Use public layout (no login required)
$app_layout = 'public_layout.php';

$error = '';
$phone_searched = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $phone_searched = $phone;

    if (empty($phone)) {
        $error = 'Please enter your phone number.';
    } else {
        // Search for customer
        $lead = customer_quote::find_by_phone($phone);

        if ($lead) {
            // Redirect to quote view
            redirect_to('customer_portal/quote/view', 'id=' . $lead['id']);
        } else {
            $error = 'No quote found for this phone number. Please check the number and try again, or call us at (904) 706-6669.';
        }
    }
}
