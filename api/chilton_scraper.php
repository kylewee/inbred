<?php
/**
 * Chilton Library Labor Hours Scraper
 * Logs into NC Live, searches for labor hours by vehicle/repair
 */

header('Content-Type: application/json');

class ChiltonScraper {
    private $username = 'nclivemdcp';
    private $password = 'nclive001';
    private $cookieFile;
    private $baseUrl = 'https://www.library.nclive.org';
    
    public function __construct() {
        $this->cookieFile = sys_get_temp_dir() . '/chilton_cookies_' . md5($this->username) . '.txt';
    }
    
    /**
     * Login to Chilton via NC Live
     */
    public function login() {
        $ch = curl_init($this->baseUrl . '/nclive/ezproxy');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'user' => $this->username,
            'pass' => $this->password,
            'url' => 'https://chiltonlibrary.com'
        ]));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200 || $httpCode === 302;
    }
    
    /**
     * Search for labor hours by year, make, model, repair
     */
    public function searchLaborHours($year, $make, $model, $repairType) {
        // Ensure logged in
        if (!file_exists($this->cookieFile) || (time() - filemtime($this->cookieFile)) > 3600) {
            $this->login();
        }
        
        // Search Chilton - URL structure may need refinement after testing
        $searchUrl = 'https://chiltonlibrary.com/search';
        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'year' => $year,
            'make' => $make,
            'model' => $model,
            'category' => 'labor',
            'query' => $repairType
        ]));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['error' => 'Search failed', 'http_code' => $httpCode];
        }
        
        // Parse HTML for labor hours (this will need refinement based on actual HTML structure)
        return $this->parseLaborHours($html, $repairType);
    }
    
    /**
     * Parse labor hours from Chilton HTML response
     */
    private function parseLaborHours($html, $repairType) {
        // Common repair patterns to look for
        $patterns = [
            'starter' => '/starter.*?(\d+\.\d+)\s*hr/i',
            'alternator' => '/alternator.*?(\d+\.\d+)\s*hr/i',
            'battery' => '/battery.*?(\d+\.\d+)\s*hr/i',
            'brake' => '/brake.*?(\d+\.\d+)\s*hr/i',
            'oil change' => '/oil\s*change.*?(\d+\.\d+)\s*hr/i',
            'tire' => '/tire.*?(\d+\.\d+)\s*hr/i',
            'transmission' => '/transmission.*?(\d+\.\d+)\s*hr/i',
            'engine' => '/engine.*?(\d+\.\d+)\s*hr/i',
        ];
        
        $results = [];
        
        // Try to match repair type
        foreach ($patterns as $repair => $pattern) {
            if (stripos($repairType, $repair) !== false) {
                if (preg_match($pattern, $html, $matches)) {
                    $results[] = [
                        'repair' => $repair,
                        'hours' => floatval($matches[1]),
                        'source' => 'chilton'
                    ];
                }
            }
        }
        
        // Generic pattern for any labor hour mention
        if (empty($results)) {
            if (preg_match_all('/(\d+\.\d+)\s*(?:hour|hr)/i', $html, $matches)) {
                foreach ($matches[1] as $hours) {
                    $h = floatval($hours);
                    if ($h > 0 && $h < 20) { // Reasonable labor hour range
                        $results[] = [
                            'repair' => $repairType,
                            'hours' => $h,
                            'source' => 'chilton',
                            'confidence' => 'medium'
                        ];
                    }
                }
            }
        }
        
        return $results ?: ['error' => 'No labor hours found', 'html_sample' => substr(strip_tags($html), 0, 500)];
    }
    
    /**
     * Cache labor hours in database
     */
    public function cacheLaborHours($year, $make, $model, $repair, $hours) {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbName = getenv('DB_NAME') ?: 'rukovoditel';
        $dbUser = getenv('DB_USER') ?: 'kylewee2';
        $dbPass = getenv('DB_PASSWORD') ?: getenv('CRM_PASSWORD');
        
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            
            // Create cache table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS chilton_labor_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vehicle_year INT,
                vehicle_make VARCHAR(100),
                vehicle_model VARCHAR(100),
                repair_type VARCHAR(255),
                labor_hours DECIMAL(4,2),
                source VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_lookup (vehicle_year, vehicle_make, vehicle_model, repair_type),
                INDEX idx_vehicle (vehicle_year, vehicle_make, vehicle_model)
            )");
            
            // Insert or update
            $stmt = $pdo->prepare("INSERT INTO chilton_labor_cache 
                (vehicle_year, vehicle_make, vehicle_model, repair_type, labor_hours, source) 
                VALUES (?, ?, ?, ?, ?, 'chilton')
                ON DUPLICATE KEY UPDATE labor_hours = ?, created_at = NOW()");
            
            $stmt->execute([$year, $make, $model, $repair, $hours, $hours]);
            
            return ['success' => true, 'cached' => true];
        } catch (PDOException $e) {
            return ['error' => 'Cache failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get cached labor hours (faster than scraping)
     */
    public function getCachedLaborHours($year, $make, $model, $repair) {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbName = getenv('DB_NAME') ?: 'rukovoditel';
        $dbUser = getenv('DB_USER') ?: 'kylewee2';
        $dbPass = getenv('DB_PASSWORD') ?: getenv('CRM_PASSWORD');
        
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            
            $stmt = $pdo->prepare("SELECT labor_hours, UNIX_TIMESTAMP(created_at) as cached_at 
                FROM chilton_labor_cache 
                WHERE vehicle_year = ? AND vehicle_make = ? AND vehicle_model = ? AND repair_type = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)"); // 90 day cache
            
            $stmt->execute([$year, $make, $model, $repair]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}

// API endpoint handling
$scraper = new ChiltonScraper();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = intval($_GET['year'] ?? 0);
    $make = $_GET['make'] ?? '';
    $model = $_GET['model'] ?? '';
    $repair = $_GET['repair'] ?? '';
    
    if (!$year || !$make || !$model || !$repair) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters: year, make, model, repair']);
        exit;
    }
    
    // Check cache first
    $cached = $scraper->getCachedLaborHours($year, $make, $model, $repair);
    if ($cached) {
        echo json_encode([
            'success' => true,
            'labor_hours' => $cached['labor_hours'],
            'source' => 'cache',
            'cached_at' => date('Y-m-d H:i:s', $cached['cached_at'])
        ]);
        exit;
    }
    
    // Scrape from Chilton
    $result = $scraper->searchLaborHours($year, $make, $model, $repair);
    
    if (!empty($result) && !isset($result['error'])) {
        // Cache the first result
        if (isset($result[0]['hours'])) {
            $scraper->cacheLaborHours($year, $make, $model, $repair, $result[0]['hours']);
        }
    }
    
    echo json_encode($result);
}

elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'test_login') {
    $loginSuccess = $scraper->login();
    echo json_encode([
        'success' => $loginSuccess,
        'message' => $loginSuccess ? 'Login successful' : 'Login failed'
    ]);
}

else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request. GET: ?year=2020&make=Honda&model=Civic&repair=starter, POST: ?action=test_login']);
}
