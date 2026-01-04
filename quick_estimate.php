#!/usr/bin/env php
<?php
/**
 * Quick Labor Estimate Calculator
 * Usage: php quick_estimate.php "2004 BMW 330xi" "power steering hose" 1.5
 */

// CLI only - redirect web visitors to the proper estimate page
if (php_sapi_name() !== 'cli') {
    header('Location: /get-estimate.php');
    exit;
}

// OLD: require_once __DIR__ . '/scraper/auto_estimate.php';
// Scraper system archived - this script needs updating to use GPT estimates

// Labor rates
define('LABOR_FIRST_HOUR', 150.00);
define('LABOR_ADDITIONAL_HOUR', 100.00);

// Parse command line arguments
if ($argc < 4) {
    echo "Usage: php quick_estimate.php \"YEAR MAKE MODEL\" \"REPAIR_NAME\" LABOR_HOURS [PARTS_COST]\n";
    echo "Example: php quick_estimate.php \"2004 BMW 330xi\" \"power steering hose\" 1.5 45\n";
    exit(1);
}

$vehicleStr = $argv[1];
$repairName = $argv[2];
$laborHours = (float)$argv[3];
$partsCost = isset($argv[4]) ? (float)$argv[4] : 0;

// Parse vehicle string
if (preg_match('/(\d{4})\s+(\w+)\s+(.+)/i', $vehicleStr, $m)) {
    $year = $m[1];
    $make = $m[2];
    $model = $m[3];
} else {
    echo "Error: Could not parse vehicle. Use format: YEAR MAKE MODEL\n";
    exit(1);
}

// Find vehicle data for multiplier
$vehicleData = find_vehicle($year, $make, $model);
$multiplier = 1.0;

if ($vehicleData) {
    $multiplier = (float)($vehicleData['vehicle']['characteristics']['price_multiplier'] ?? 1.0);
    $matchedVehicle = $vehicleData['vehicle'];
    echo "Matched: {$matchedVehicle['year']} {$matchedVehicle['make']} {$matchedVehicle['model']}\n";
    echo "Luxury multiplier: {$multiplier}x\n";
} else {
    echo "Vehicle not found in database, using standard pricing (1.0x)\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ESTIMATE: $year $make $model\n";
echo "Repair: $repairName\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Calculate labor cost (tiered pricing)
if ($laborHours <= 1.0) {
    $laborCost = $laborHours * LABOR_FIRST_HOUR;
} else {
    $laborCost = LABOR_FIRST_HOUR + (($laborHours - 1.0) * LABOR_ADDITIONAL_HOUR);
}

// Apply multiplier to parts only (not labor)
$adjustedPartsCost = $partsCost * $multiplier;

echo sprintf("Labor: %.1f hrs\n", $laborHours);
echo "  • First hour: $" . LABOR_FIRST_HOUR . "\n";
if ($laborHours > 1.0) {
    echo sprintf("  • Additional: %.1f hrs × $%d = $%.2f\n",
        ($laborHours - 1.0),
        LABOR_ADDITIONAL_HOUR,
        ($laborHours - 1.0) * LABOR_ADDITIONAL_HOUR
    );
}
echo sprintf("  Labor subtotal: $%.2f\n\n", $laborCost);

if ($partsCost > 0) {
    echo sprintf("Parts (customer supplied): $%.2f\n", $partsCost);
    if ($multiplier > 1.0) {
        echo sprintf("  • With %sx BMW premium: $%.2f\n", $multiplier, $adjustedPartsCost);
        echo "  (Note: Customer has part, so no premium applied)\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo sprintf("TOTAL (Labor Only): $%.2f\n", $laborCost);
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Show common labor times
echo "\n💡 Common labor times:\n";
echo "  • Quick jobs (hose, belt): 0.5-1.0 hrs\n";
echo "  • Standard repairs (brakes, battery): 1.0-1.5 hrs\n";
echo "  • Complex jobs (alternator, starter): 2.0-3.0 hrs\n";
echo "  • Major work (timing belt, transmission): 3.0-5.0 hrs\n";
