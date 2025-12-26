<?php
/**
 * Auto-Estimation System
 * Generates repair estimates from vehicle/repair keywords
 */

define('SCRAPER_DATA_PATH', __DIR__ . '/charm_data.json');
define('LABOR_RATE_FIRST_HOUR', 150.00); // First hour rate
define('LABOR_RATE_PER_HOUR', 100.00);   // Additional hours rate

/**
 * Load repair data from JSON
 */
function load_repair_data(): array {
    static $data = null;
    if ($data !== null) return $data;
    
    $path = SCRAPER_DATA_PATH;
    if (!file_exists($path)) return [];
    
    $json = file_get_contents($path);
    $data = json_decode($json, true) ?: [];
    return $data;
}

/**
 * Find matching vehicle in database
 */
function find_vehicle(string $year, string $make, string $model): ?array {
    $data = load_repair_data();
    $year = (int)$year;
    $make = strtolower(trim($make));
    $model = strtolower(trim($model));
    
    foreach ($data as $entry) {
        $v = $entry['vehicle'] ?? [];
        $vYear = (int)($v['year'] ?? 0);
        $vMake = strtolower($v['make'] ?? '');
        $vModel = strtolower($v['model'] ?? '');
        
        // Exact match
        if ($vYear === $year && $vMake === $make && $vModel === $model) {
            return $entry;
        }
    }
    
    // Try fuzzy match on make/model (within 3 years)
    foreach ($data as $entry) {
        $v = $entry['vehicle'] ?? [];
        $vYear = (int)($v['year'] ?? 0);
        $vMake = strtolower($v['make'] ?? '');
        $vModel = strtolower($v['model'] ?? '');
        
        if ($vMake === $make && $vModel === $model && abs($vYear - $year) <= 3) {
            return $entry;
        }
    }
    
    // Match just on make (use generic pricing)
    foreach ($data as $entry) {
        $v = $entry['vehicle'] ?? [];
        $vMake = strtolower($v['make'] ?? '');
        
        if ($vMake === $make) {
            return $entry;
        }
    }
    
    // Return first entry as default
    return $data[0] ?? null;
}

/**
 * Parse transcript for repair keywords
 */
function detect_repairs(string $transcript): array {
    $transcript = strtolower($transcript);
    $repairs = [];
    
    $keywords = [
        'oil change' => ['oil change', 'oil', 'oil service'],
        'brake pads replacement' => ['brake', 'brakes', 'brake pads', 'front brakes', 'rear brakes', 'brake job'],
        'battery replacement' => ['battery', 'dead battery', 'car won\'t start', 'no power'],
        'alternator replacement' => ['alternator', 'charging', 'battery light'],
        'starter replacement' => ['starter', 'won\'t crank', 'clicking', 'won\'t turn over'],
        'timing belt replacement' => ['timing belt', 'timing chain'],
        'ac recharge' => ['ac', 'air conditioning', 'a/c', 'no cold air', 'ac not working'],
        'engine diagnostic' => ['check engine', 'diagnostic', 'engine light', 'code', 'scan'],
        'transmission service' => ['transmission', 'trans fluid', 'slipping', 'gear'],
        'spark plugs replacement' => ['spark plugs', 'tune up', 'misfire', 'tune-up'],
    ];
    
    foreach ($keywords as $repair => $phrases) {
        foreach ($phrases as $phrase) {
            if (strpos($transcript, $phrase) !== false) {
                $repairs[] = $repair;
                break;
            }
        }
    }
    
    return array_unique($repairs);
}

/**
 * Generate estimate for a repair
 */
function generate_estimate(array $vehicleData, string $repairName): ?array {
    $repairs = $vehicleData['repairs'] ?? [];
    
    // Try exact match
    foreach ($repairs as $name => $info) {
        if (strtolower($name) === strtolower($repairName)) {
            return calculate_estimate($name, $info, $vehicleData['vehicle'] ?? []);
        }
    }
    
    // Try partial match
    foreach ($repairs as $name => $info) {
        if (stripos($name, $repairName) !== false || stripos($repairName, $name) !== false) {
            return calculate_estimate($name, $info, $vehicleData['vehicle'] ?? []);
        }
    }
    
    return null;
}

/**
 * Calculate final estimate with labor
 */
function calculate_estimate(string $repairName, array $repairInfo, array $vehicle): array {
    $laborHours = (float)($repairInfo['labor_time'] ?? 1.0);

    // Tiered pricing: $150 first hour, $100 each additional hour
    if ($laborHours <= 1.0) {
        $laborCost = $laborHours * LABOR_RATE_FIRST_HOUR;
    } else {
        $laborCost = LABOR_RATE_FIRST_HOUR + (($laborHours - 1.0) * LABOR_RATE_PER_HOUR);
    }

    // Apply vehicle multiplier
    $multiplier = (float)($vehicle['characteristics']['price_multiplier'] ?? 1.0);

    $parts = $repairInfo['parts'] ?? [];
    $partsCost = 0;
    $partsList = [];

    foreach ($parts as $part) {
        $price = (float)($part['price'] ?? 0) * $multiplier;
        $partsCost += $price;
        $partsList[] = [
            'name' => $part['name'],
            'price' => round($price, 2),
            'part_number' => $part['part_number'] ?? ''
        ];
    }

    $total = round($laborCost + $partsCost, 2);

    return [
        'repair' => $repairName,
        'labor_hours' => $laborHours,
        'labor_rate' => '1st hr: $' . LABOR_RATE_FIRST_HOUR . ', then $' . LABOR_RATE_PER_HOUR . '/hr',
        'labor_cost' => round($laborCost, 2),
        'parts' => $partsList,
        'parts_cost' => round($partsCost, 2),
        'total' => $total,
        'complexity' => $repairInfo['labor_complexity'] ?? 'Standard'
    ];
}

/**
 * Main function: Generate full estimate from transcript
 */
function auto_estimate_from_transcript(string $transcript, string $year = '', string $make = '', string $model = ''): array {
    // Parse transcript for vehicle info if not provided
    if (empty($year) || empty($make) || empty($model)) {
        if (preg_match('/\b(19|20)\d{2}\b/', $transcript, $ym)) {
            $year = $year ?: $ym[0];
        }
        
        $makes = ['honda', 'toyota', 'ford', 'chevrolet', 'chevy', 'nissan', 'bmw', 'mercedes', 'dodge', 'jeep', 'hyundai', 'kia', 'subaru', 'mazda', 'volkswagen', 'vw', 'gmc', 'ram', 'chrysler', 'buick', 'cadillac', 'lexus', 'acura', 'infiniti', 'audi', 'volvo', 'lincoln', 'mitsubishi'];
        
        $lower = strtolower($transcript);
        foreach ($makes as $m) {
            if (strpos($lower, $m) !== false) {
                $make = $make ?: ucfirst($m);
                if ($m === 'chevy') $make = 'Chevrolet';
                if ($m === 'vw') $make = 'Volkswagen';
                break;
            }
        }
        
        $models = ['civic', 'accord', 'camry', 'corolla', 'f-150', 'f150', 'silverado', 'altima', 'maxima', 'mustang', 'tacoma', 'rav4', 'highlander', 'explorer', 'escape', 'pilot', 'crv', 'cr-v', 'wrangler', 'grand cherokee', 'malibu', 'impala'];
        
        foreach ($models as $mdl) {
            if (strpos($lower, $mdl) !== false) {
                $model = $model ?: ucwords(str_replace('-', ' ', $mdl));
                if ($mdl === 'f150') $model = 'F-150';
                if ($mdl === 'crv') $model = 'CR-V';
                break;
            }
        }
    }
    
    // Find vehicle data
    $vehicleData = find_vehicle($year, $make, $model);
    if (!$vehicleData) {
        return [
            'success' => false,
            'error' => 'No matching vehicle data found',
            'detected' => ['year' => $year, 'make' => $make, 'model' => $model]
        ];
    }
    
    // Detect repairs from transcript
    $repairsNeeded = detect_repairs($transcript);
    if (empty($repairsNeeded)) {
        return [
            'success' => false,
            'error' => 'No repair keywords detected in transcript',
            'vehicle' => $vehicleData['vehicle'] ?? []
        ];
    }
    
    // Generate estimates
    $estimates = [];
    $grandTotal = 0;
    
    foreach ($repairsNeeded as $repair) {
        $est = generate_estimate($vehicleData, $repair);
        if ($est) {
            $estimates[] = $est;
            $grandTotal += $est['total'];
        }
    }
    
    return [
        'success' => true,
        'vehicle' => [
            'year' => $year,
            'make' => $make,
            'model' => $model,
            'matched' => $vehicleData['vehicle'] ?? []
        ],
        'estimates' => $estimates,
        'grand_total' => round($grandTotal, 2),
        'labor_rate' => LABOR_RATE_PER_HOUR
    ];
}

/**
 * Format estimate as readable text
 */
function format_estimate_text(array $result): string {
    if (empty($result['success'])) {
        return "Could not generate estimate: " . ($result['error'] ?? 'Unknown error');
    }
    
    $v = $result['vehicle'] ?? [];
    $lines = [];
    $lines[] = "=== AUTO ESTIMATE ===";
    $lines[] = sprintf("Vehicle: %s %s %s", $v['year'] ?? '', $v['make'] ?? '', $v['model'] ?? '');
    $lines[] = "";
    
    foreach ($result['estimates'] as $est) {
        $lines[] = "► " . $est['repair'];
        $lines[] = sprintf("  Labor: %.1f hrs @ $%.2f = $%.2f", $est['labor_hours'], $est['labor_rate'], $est['labor_cost']);
        
        if (!empty($est['parts'])) {
            $lines[] = "  Parts:";
            foreach ($est['parts'] as $part) {
                $lines[] = sprintf("    - %s: $%.2f", $part['name'], $part['price']);
            }
        }
        $lines[] = sprintf("  Subtotal: $%.2f", $est['total']);
        $lines[] = "";
    }
    
    $lines[] = sprintf("GRAND TOTAL: $%.2f", $result['grand_total']);
    $lines[] = "(Labor rate: $" . number_format($result['labor_rate'], 2) . "/hr)";
    
    return implode("\n", $lines);
}

// CLI test
if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF'] ?? '') === 'auto_estimate.php') {
    $test = "I have a 2018 Honda Civic and I need brakes and an oil change";
    echo "Testing: \"$test\"\n\n";
    
    $result = auto_estimate_from_transcript($test);
    echo format_estimate_text($result) . "\n";
}
