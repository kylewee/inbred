<?php
/**
 * A/B Analytics Menu Registration
 */

// Add menu item for admins
if ($app_user['group_id'] == 0) {
    $app_plugin_menu['extension'][] = [
        'title' => 'A/B Analytics',
        'url' => url_for('ext/ab_analytics/dashboard'),
        'icon' => 'fa-chart-line'
    ];
}
