<?php
/**
 * Customer Portal Plugin - Menu
 * Add admin link to test the portal
 */

// Add to main menu (admin only)
$app_plugin_menu['menu'][] = array(
    'title' => 'Customer Portal',
    'url' => url_for('customer_portal/quote/index'),
    'class' => 'fa-users',
    'position' => 50
);
