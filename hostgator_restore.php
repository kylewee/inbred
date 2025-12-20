<?php
// hostgator_restore.php
// Place this file in the SAME directory as your .tar.gz and .sql backup files (e.g. public_html/tmp)
// Access it via browser: http://yourdomain.com/tmp/hostgator_restore.php

ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes
error_reporting(E_ALL);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'extract') {
        // --- EXTRACTION LOGIC ---
        $archive = $_POST['archive_file'] ?? '';
        $target = __DIR__ . '/../'; // Parent directory (public_html)
        
        if (file_exists($archive)) {
            try {
                $phar = new PharData($archive);
                $phar->extractTo($target, null, true); // Overwrite existing
                $message .= "<div style='color:green'>Successfully extracted $archive to $target</div>";
            } catch (Exception $e) {
                $message .= "<div style='color:red'>Extraction failed: " . $e->getMessage() . "</div>";
                // Fallback to system command if Phar fails
                $output = [];
                $return = 0;
                exec("tar -xzf " . escapeshellarg($archive) . " -C " . escapeshellarg($target) . " 2>&1", $output, $return);
                if ($return === 0) {
                     $message .= "<div style='color:green'>System extraction successful.</div>";
                } else {
                     $message .= "<div style='color:red'>System extraction also failed: " . implode("\n", $output) . "</div>";
                }
            }
        } else {
            $message .= "<div style='color:red'>Archive file not found.</div>";
        }
    } 
    elseif ($action === 'db_import') {
        // --- DATABASE IMPORT LOGIC ---
        $sqlFile = $_POST['sql_file'] ?? '';
        $dbHost = $_POST['db_host'] ?? 'localhost';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPass = $_POST['db_pass'] ?? '';
        $dbName = $_POST['db_name'] ?? '';
        
        if (file_exists($sqlFile)) {
            try {
                $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Read SQL file (this method is not memory efficient for huge files, but simple for typical dumps)
                $sql = file_get_contents($sqlFile);
                
                // Execute
                $pdo->exec($sql);
                $message .= "<div style='color:green'>Database imported successfully into $dbName</div>";
                
                // --- UPDATE CONFIG FILES ---
                // Update crm/config/database.php
                $crmConfig = __DIR__ . '/../crm/config/database.php';
                if (file_exists($crmConfig)) {
                    $content = file_get_contents($crmConfig);
                    $content = preg_replace("/define\('DB_SERVER', '.*?'\);/", "define('DB_SERVER', '$dbHost');", $content);
                    $content = preg_replace("/define\('DB_SERVER_USERNAME', '.*?'\);/", "define('DB_SERVER_USERNAME', '$dbUser');", $content);
                    $content = preg_replace("/define\('DB_SERVER_PASSWORD', '.*?'\);/", "define('DB_SERVER_PASSWORD', '$dbPass');", $content);
                    $content = preg_replace("/define\('DB_DATABASE', '.*?'\);/", "define('DB_DATABASE', '$dbName');", $content);
                    file_put_contents($crmConfig, $content);
                    $message .= "<div style='color:green'>Updated crm/config/database.php</div>";
                }
                
                // Update api/.env.local.php
                $apiConfig = __DIR__ . '/../api/.env.local.php';
                if (file_exists($apiConfig)) {
                    $content = file_get_contents($apiConfig);
                    // Assuming similar define or array structure, simplified regex replacement
                    // This might need adjustment based on exact file format
                    $content = preg_replace("/'DB_HOST' => '.*?'/", "'DB_HOST' => '$dbHost'", $content);
                    $content = preg_replace("/'DB_USER' => '.*?'/", "'DB_USER' => '$dbUser'", $content);
                    $content = preg_replace("/'DB_PASS' => '.*?'/", "'DB_PASS' => '$dbPass'", $content);
                    $content = preg_replace("/'DB_NAME' => '.*?'/", "'DB_NAME' => '$dbName'", $content);
                     // Also check for defines
                    $content = preg_replace("/define\('DB_HOST', '.*?'\);/", "define('DB_HOST', '$dbHost');", $content);
                    $content = preg_replace("/define\('DB_USER', '.*?'\);/", "define('DB_USER', '$dbUser');", $content);
                    $content = preg_replace("/define\('DB_PASS', '.*?'\);/", "define('DB_PASS', '$dbPass');", $content);
                    $content = preg_replace("/define\('DB_NAME', '.*?'\);/", "define('DB_NAME', '$dbName');", $content);

                    file_put_contents($apiConfig, $content);
                    $message .= "<div style='color:green'>Updated api/.env.local.php</div>";
                }
                
            } catch (PDOException $e) {
                $message .= "<div style='color:red'>Database Error: " . $e->getMessage() . "</div>";
            }
        } else {
             $message .= "<div style='color:red'>SQL file not found.</div>";
        }
    }
}

// Get file lists
$files = scandir(__DIR__);
$archives = array_filter($files, function($f) { return strpos($f, '.tar.gz') !== false; });
$sqls = array_filter($files, function($f) { return strpos($f, '.sql') !== false; });

?>
<!DOCTYPE html>
<html>
<head>
    <title>Site Restore Tool</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .section { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        input[type="text"], input[type="password"] { padding: 5px; width: 200px; }
    </style>
</head>
<body>
    <h1>HostGator Migration Assistant</h1>
    <?= $message ?>
    
    <div class="section">
        <h2>1. Extract Files</h2>
        <form method="post">
            <input type="hidden" name="action" value="extract">
            <label>Select Archive:</label>
            <select name="archive_file">
                <?php foreach($archives as $f): ?>
                    <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <button type="submit" onclick="return confirm('This will extract files to ../ (public_html). Continue?')">Extract Files</button>
        </form>
    </div>

    <div class="section">
        <h2>2. Import Database & Update Config</h2>
        <form method="post">
            <input type="hidden" name="action" value="db_import">
            <label>Select SQL Dump:</label>
            <select name="sql_file">
                <?php foreach($sqls as $f): ?>
                    <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <h3>New Database Credentials</h3>
            <p>Host: <input type="text" name="db_host" value="localhost"></p>
            <p>DB Name: <input type="text" name="db_name" placeholder="cpunccte_dbname"></p>
            <p>User: <input type="text" name="db_user" placeholder="cpunccte_user"></p>
            <p>Pass: <input type="password" name="db_pass"></p>
            <br>
            <button type="submit">Import DB & Update Config</button>
        </form>
    </div>
</body>
</html>
