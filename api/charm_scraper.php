<?php
/**
 * charm.li Scraper - OEM Parts Pricing + Labor Hours
 * Scrapes parts prices and labor times from charm.li
 */

header('Content-Type: application/json');

class CharmScraper {
    private $cookieFile;
    private $baseUrl = 'https://charm.li';
    private $dbConfigCache;
    
    public function __construct() {
        $this->cookieFile = sys_get_temp_dir() . '/charm_cookies.txt';
    }
    
    /**
     * Get labor hours from charm.li URL pattern
     * HTML: <tr><td><b>Replace</b></td><td>0.6</td><td>0.5</td><td>B</td><td></td></tr>
     */
    public function getLaborHours($year, $make, $model, $engine, $repairPath) {
        // Build full model string with engine (e.g., "E 350 V8-5.4L")
        $fullModel = trim($model . ' ' . $engine);
        $modelEncoded = str_replace(' ', '%20', $fullModel);
        $repairEncoded = str_replace(' ', '%20', $repairPath);
        $url = "https://charm.li/$make/$year/$modelEncoded/Parts%20and%20Labor/$repairEncoded/Labor%20Times/";
        
        $html = @file_get_contents($url);
        
        if (!$html) {
            return null;
        }
        
        // Extract Standard Hours (2nd column): <tr><td><b>Replace</b></td><td>0.6</td>
        if (preg_match('/<tr><td><b>Replace<\/b><\/td><td>(\d+\.\d+)<\/td>/i', $html, $matches)) {
            return floatval($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Get parts pricing from charm.li
     * HTML structure: <tr><td>Starter</td><tr><td>From 11/18/08</td><td>FOR</td><td>DL3Z11002A</td><td>210.83</td><td></td></tr>
     */
    public function getPartsPricing($year, $make, $model, $engine, $repairPath) {
        // Build full model string with engine (e.g., "E 350 V8-5.4L")
        $fullModel = trim($model . ' ' . $engine);
        $modelEncoded = str_replace(' ', '%20', $fullModel);
        $repairEncoded = str_replace(' ', '%20', $repairPath);
        $url = "https://charm.li/$make/$year/$modelEncoded/Parts%20and%20Labor/$repairEncoded/Parts%20Information/";
        
        $html = @file_get_contents($url);
        
        if (!$html) {
            return [];
        }
        
        $parts = [];
        
        // Extract part name from first <tr><td>
        $partName = 'Part';
        if (preg_match('/<tr><td>([^<]+)<\/td><tr>/i', $html, $nameMatch)) {
            $partName = trim($nameMatch[1]);
        }
        
        // Extract OEM and price: <td>FOR</td><td>DL3Z11002A</td><td>210.83</td>
        if (preg_match('/<td>([A-Z]{3})<\/td><td>([A-Z0-9]+)<\/td><td>(\d+\.\d+)<\/td>/i', $html, $match)) {
            $parts[] = [
                'name' => $partName,
                'manufacturer' => $match[1],
                'oem_number' => $match[2],
                'price' => floatval($match[3])
            ];
        }
        
        return $parts;
    }
    
    /**
     * Get parts and labor for specific repair
     */
    public function getRepairData($vehicleId, $repairType) {
        $ch = curl_init($this->baseUrl . "/vehicle/$vehicleId/repairs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['error' => 'Repair data fetch failed', 'http_code' => $httpCode];
        }
        
        return $this->parseRepairData($html, $repairType);
    }
    
    /**
     * Parse vehicle ID from search results
     */
    private function parseVehicleId($html) {
        // Look for vehicle ID in HTML (pattern may vary)
        if (preg_match('/vehicle[\/\-](\d+)/i', $html, $matches)) {
            return ['vehicle_id' => $matches[1]];
        }
        
        if (preg_match('/data-vehicle-id=["\'](\d+)["\']/i', $html, $matches)) {
            return ['vehicle_id' => $matches[1]];
        }
        
        return ['error' => 'Vehicle ID not found', 'html_sample' => substr(strip_tags($html), 0, 500)];
    }
    
    /**
     * Parse repair data (parts + labor) from HTML
     */
    private function parseRepairData($html, $repairType) {
        $results = [
            'repair_type' => $repairType,
            'parts' => [],
            'labor_hours' => null,
            'source' => 'charm.li'
        ];
        
        // Parse labor hours
        if (preg_match('/labor.*?(\d+\.\d+)\s*(?:hour|hr)/i', $html, $matches)) {
            $results['labor_hours'] = floatval($matches[1]);
        } elseif (preg_match('/(\d+\.\d+)\s*(?:hour|hr)/i', $html, $matches)) {
            $results['labor_hours'] = floatval($matches[1]);
        }
        
        // Parse parts with prices
        // Pattern: part name ... $XX.XX
        preg_match_all('/<div[^>]*class="[^"]*part[^"]*"[^>]*>.*?<span[^>]*>([^<]+)<\/span>.*?\$(\d+\.\d+)/is', $html, $partMatches, PREG_SET_ORDER);
        
        foreach ($partMatches as $match) {
            $results['parts'][] = [
                'name' => trim(strip_tags($match[1])),
                'price' => floatval($match[2])
            ];
        }
        
        // Fallback: simpler price pattern
        if (empty($results['parts'])) {
            preg_match_all('/([A-Za-z\s]+(?:starter|alternator|battery|brake|filter|belt|pump)[A-Za-z\s]*).*?\$(\d+\.\d+)/i', $html, $simpleMatches, PREG_SET_ORDER);
            
            foreach ($simpleMatches as $match) {
                $partName = trim($match[1]);
                if (strlen($partName) > 5 && strlen($partName) < 100) {
                    $results['parts'][] = [
                        'name' => $partName,
                        'price' => floatval($match[2])
                    ];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Full lookup: vehicle + repair data using charm.li URL pattern
     */
    public function getFullRepairEstimate($year, $make, $model, $engine, $repairPath) {
        $laborUrl = "https://charm.li/$make/$year/" . str_replace(' ', '%20', $model) . "/Parts%20and%20Labor/$repairPath/Labor%20Times/";
        $partsUrl = "https://charm.li/$make/$year/" . str_replace(' ', '%20', $model) . "/Parts%20and%20Labor/$repairPath/Parts%20Information/";
        
        $laborHours = $this->getLaborHours($year, $make, $model, $engine, $repairPath);
        $parts = $this->getPartsPricing($year, $make, $model, $engine, $repairPath);
        
        $partsTotal = $parts ? array_sum(array_column($parts, 'price')) : 0;
        
        // Calculate labor cost
        $laborCost = 0;
        if ($laborHours) {
            $laborCost = $laborHours <= 1 ? $laborHours * 150 : 150 + (($laborHours - 1) * 100);
        }
        
        return [
            'vehicle' => "$year $make $model",
            'repair_path' => str_replace('%20', ' ', $repairPath),
            'labor_hours' => $laborHours,
            'labor_url' => $laborUrl,
            'parts' => $parts,
            'parts_url' => $partsUrl,
            'parts_total' => round($partsTotal, 2),
            'labor_cost' => round($laborCost, 2),
            'total_estimate' => round($partsTotal + $laborCost, 2),
            'source' => 'charm.li'
        ];
    }
    
    /**
     * Cache results in database
     */
    public function cacheResult($year, $make, $model, $repair, $data) {
        $dbConfig = $this->resolveDbConfig();
        if ($dbConfig === null) {
            return ['error' => 'Database configuration not available'];
        }
        $dbHost = $dbConfig['host'];
        $dbName = $dbConfig['name'];
        $dbUser = $dbConfig['user'];
        $dbPass = $dbConfig['pass'];
        $dbPort = $dbConfig['port'];
        
        try {
            $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
            if ($dbPort !== null) {
                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
            }
            $pdo = new PDO($dsn, $dbUser, $dbPass);
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS charm_repair_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vehicle_year INT,
                vehicle_make VARCHAR(100),
                vehicle_model VARCHAR(100),
                repair_type VARCHAR(255),
                parts_json TEXT,
                labor_hours DECIMAL(4,2),
                parts_total DECIMAL(10,2),
                labor_cost DECIMAL(10,2),
                total_estimate DECIMAL(10,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_lookup (vehicle_year, vehicle_make, vehicle_model, repair_type),
                INDEX idx_vehicle (vehicle_year, vehicle_make, vehicle_model)
            )");
            
            $stmt = $pdo->prepare("INSERT INTO charm_repair_cache 
                (vehicle_year, vehicle_make, vehicle_model, repair_type, parts_json, labor_hours, parts_total, labor_cost, total_estimate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    parts_json = ?, labor_hours = ?, parts_total = ?, labor_cost = ?, total_estimate = ?, created_at = NOW()");
            
            $partsJson = json_encode($data['parts'] ?? []);
            
            $stmt->execute([
                $year, $make, $model, $repair, 
                $partsJson, 
                $data['labor_hours'] ?? null,
                $data['parts_total'] ?? null,
                $data['labor_cost'] ?? null,
                $data['total_estimate'] ?? null,
                // For ON DUPLICATE KEY UPDATE
                $partsJson, 
                $data['labor_hours'] ?? null,
                $data['parts_total'] ?? null,
                $data['labor_cost'] ?? null,
                $data['total_estimate'] ?? null
            ]);
            
            return ['success' => true, 'cached' => true];
        } catch (PDOException $e) {
            return ['error' => 'Cache failed: ' . $e->getMessage()];
        }
    }

    /**
     * Resolve database credentials from environment or local config.
     */
    private function resolveDbConfig() {
        if ($this->dbConfigCache !== null) {
            return $this->dbConfigCache;
        }

        $config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'name' => getenv('DB_NAME') ?: 'rukovoditel',
            'user' => getenv('DB_USER') ?: '',
            'pass' => getenv('DB_PASSWORD') ?: getenv('CRM_PASSWORD') ?: '',
            'port' => null,
        ];

        $crmConfig = __DIR__ . '/../crm/config/database.php';
        if (is_file($crmConfig)) {
            require_once $crmConfig;
            if (defined('DB_SERVER')) {
                $config['host'] = (string)DB_SERVER;
            }
            if (defined('DB_DATABASE')) {
                $config['name'] = (string)DB_DATABASE;
            }
            if (defined('DB_SERVER_USERNAME')) {
                $config['user'] = (string)DB_SERVER_USERNAME;
            }
            if (defined('DB_SERVER_PASSWORD')) {
                $config['pass'] = (string)DB_SERVER_PASSWORD;
            }
        }

        $envLocal = __DIR__ . '/.env.local.php';
        if (is_file($envLocal)) {
            require_once $envLocal;
            if ($config['user'] === '' && defined('CRM_USERNAME')) {
                $config['user'] = (string)CRM_USERNAME;
            }
            if ($config['pass'] === '' && defined('CRM_PASSWORD')) {
                $config['pass'] = (string)CRM_PASSWORD;
            }
        }

        if ($config['user'] === '') {
            $config['user'] = 'root';
        }

        if (strpos($config['host'], ':') !== false) {
            [$host, $port] = explode(':', $config['host'], 2);
            $config['host'] = $host;
            if ($port !== '') {
                $config['port'] = (int)$port;
            }
        }

        if ($config['pass'] === null) {
            $config['pass'] = '';
        }

        if ($config['user'] === '' || $config['name'] === '') {
            return $this->dbConfigCache = null;
        }

        return $this->dbConfigCache = $config;
    }
}

// API endpoint
$scraper = new CharmScraper();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = intval($_GET['year'] ?? 0);
    $make = $_GET['make'] ?? '';
    $model = $_GET['model'] ?? '';
    $engine = $_GET['engine'] ?? '';
    $repair = $_GET['repair'] ?? '';
    
    if (!$year || !$make || !$model || !$repair) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing: year, make, model, repair']);
        exit;
    }
    
    $result = $scraper->getFullRepairEstimate($year, $make, $model, $engine, $repair);
    
    // Cache successful results
    if (isset($result['total_estimate']) && $result['total_estimate'] > 0) {
        $scraper->cacheResult($year, $make, $model, $repair, $result);
    }
    
    echo json_encode($result);
}

else {
    http_response_code(400);
    echo json_encode(['error' => 'GET: ?year=2013&make=Ford&model=E%20350&engine=V8-5.4L&repair=Starting%20and%20Charging/Starting%20System/Starter%20Motor']);
}
