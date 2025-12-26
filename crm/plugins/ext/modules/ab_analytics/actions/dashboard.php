<?php
/**
 * A/B Analytics Dashboard Action
 */

// Check permissions
if (!users::has_users_access_to_menu('ab_analytics')) {
    redirect_to('dashboard/access_forbidden');
}

$app_title = 'A/B Testing Analytics';

// Breadcrumb
$app_breadcrumb = [
    ['title' => TEXT_DASHBOARD, 'url' => url_for('dashboard/dashboard')],
    ['title' => 'A/B Analytics', 'url' => '']
];
