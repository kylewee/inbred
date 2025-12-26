<?php
/**
 * CRM Telephony Call Logging
 * Logs calls directly to Rukovoditel's app_ext_call_history table via DB
 */

/**
 * Log a call to CRM's telephony call history table directly via database.
 * @param string $phone Phone number
 * @param string $direction 'inbound' or 'outbound'
 * @param int $duration Call duration in seconds
 * @return array Result with 'ok' and 'error' keys
 */
function log_crm_telephony_call(string $phone, string $direction = 'inbound', int $duration = 0): array {
  // Clean phone to digits only
  $phoneClean = preg_replace('/\D/', '', $phone);
  if (empty($phoneClean)) {
    return ['ok' => false, 'error' => 'empty_phone'];
  }
  
  // Database connection settings from CRM config
  $dbHost = defined('DB_SERVER') ? DB_SERVER : 'db';
  $dbUser = defined('DB_SERVER_USERNAME') ? DB_SERVER_USERNAME : 'root';
  $dbPass = defined('DB_SERVER_PASSWORD') ? DB_SERVER_PASSWORD : 'root';
  $dbPort = defined('DB_SERVER_PORT') ? (int)DB_SERVER_PORT : 3306;
  $dbName = defined('DB_DATABASE') ? DB_DATABASE : 'crm';
  
  try {
    $pdo = new PDO(
      "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
      $dbUser,
      $dbPass,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $pdo->prepare("
      INSERT INTO app_ext_call_history 
        (type, date_added, direction, phone, duration, sms_text, recording, client_name, comments, is_star, is_new, module) 
      VALUES 
        ('phone', :date_added, :direction, :phone, :duration, '', '', '', '', 0, 1, '')
    ");
    
    $stmt->execute([
      'date_added' => time(),
      'direction' => $direction,
      'phone' => $phoneClean,
      'duration' => $duration
    ]);
    
    $insertId = $pdo->lastInsertId();
    error_log("CRM_TELEPHONY: Logged call from $phone ($direction, {$duration}s) - ID: $insertId");
    
    return ['ok' => true, 'id' => $insertId];
    
  } catch (PDOException $e) {
    error_log("CRM_TELEPHONY: DB error - " . $e->getMessage());
    return ['ok' => false, 'error' => $e->getMessage()];
  }
}
