<?php
/**
 * Customer Portal Plugin - Public Modules
 * These pages are accessible without login
 */

// Phone number lookup page
$allowed_modules[] = 'customer_portal/quote/index';

// Quote details view page
$allowed_modules[] = 'customer_portal/quote/view';

// Quote approval/decline handler
$allowed_modules[] = 'customer_portal/quote/approve';
