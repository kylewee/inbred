<?php
// Find customer by phone and open their page
require_once('../includes/config.php');
require_once('../includes/application.php');

$phone = preg_replace('/[^\d]/', '', $_GET['phone'] ?? '');

if ($phone) {
    $query = db_query("
        SELECT id
        FROM app_entity_26
        WHERE field_227 LIKE '%{$phone}%'
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($row = db_fetch_array($query)) {
        // Redirect to customer page
        header("Location: /crm/items/info.php?id={$row['id']}&path=26");
        exit;
    }
}

// If not found, go to leads list
header("Location: /crm/items/items_list.php?path=26");
