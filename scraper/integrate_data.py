#!/usr/bin/env python3
"""
Data integration utility for Mechanic Saint Augustine
Integrates scraped charm.li data with existing price-catalog.json
"""

import json
import os
from datetime import datetime

class DataIntegrator:
    def __init__(self):
        self.price_catalog_path = "../price-catalog.json"
        self.charm_data_path = "charm_data.json" 
        self.integrated_catalog_path = "../price-catalog-enhanced.json"
    
    def load_price_catalog(self):
        """Load existing price catalog"""
        try:
            if os.path.exists(self.price_catalog_path):
                with open(self.price_catalog_path, 'r') as f:
                    data = json.load(f)
                    # Handle both array format and object format
                    if isinstance(data, list):
                        return {"repairs": data}
                    return data
            return {"repairs": []}
        except Exception as e:
            print(f"Error loading price catalog: {e}")
            return {"repairs": []}
    
    def load_charm_data(self):
        """Load scraped charm data"""
        try:
            if os.path.exists(self.charm_data_path):
                with open(self.charm_data_path, 'r') as f:
                    return json.load(f)
            return []
        except Exception as e:
            print(f"Error loading charm data: {e}")
            return []
    
    def calculate_average_parts_cost(self, repair_name, charm_data):
        """Calculate average parts cost across all vehicles for a repair"""
        costs = []
        
        for vehicle_data in charm_data:
            if repair_name in vehicle_data.get('repairs', {}):
                repair_info = vehicle_data['repairs'][repair_name]
                parts_cost = sum([part['price'] for part in repair_info.get('parts', [])])
                costs.append(parts_cost)
        
        return sum(costs) / len(costs) if costs else 0
    
    def calculate_average_labor_time(self, repair_name, charm_data):
        """Calculate average labor time across all vehicles for a repair"""
        times = []
        
        for vehicle_data in charm_data:
            if repair_name in vehicle_data.get('repairs', {}):
                repair_info = vehicle_data['repairs'][repair_name]
                labor_time = repair_info.get('labor_time', 0)
                times.append(labor_time)
        
        return sum(times) / len(times) if times else 0
    
    def enhance_repair_entry(self, repair, charm_data):
        """Enhance a single repair entry with charm data"""
        # Handle both 'name' and 'repair' fields
        repair_name = repair.get('name', repair.get('repair', ''))
        
        # Get charm.li data for this repair
        avg_parts_cost = self.calculate_average_parts_cost(repair_name, charm_data)
        avg_labor_time = self.calculate_average_labor_time(repair_name, charm_data)
        
        # Create enhanced entry
        enhanced_repair = repair.copy()
        
        # Ensure consistent naming
        if 'repair' in enhanced_repair and 'name' not in enhanced_repair:
            enhanced_repair['name'] = enhanced_repair['repair']
        
        # Add charm data if available
        if avg_parts_cost > 0:
            enhanced_repair['charm_parts_cost'] = round(avg_parts_cost, 2)
            enhanced_repair['parts_cost_source'] = 'charm.li'
        
        if avg_labor_time > 0:
            enhanced_repair['charm_labor_time'] = round(avg_labor_time, 2)
            enhanced_repair['labor_time_source'] = 'charm.li'
        
        # Calculate updated pricing if we have charm data
        if avg_parts_cost > 0 and avg_labor_time > 0:
            # Use charm parts cost + labor calculation
            labor_rate = 100  # $100/hour standard rate
            base_price = avg_parts_cost + (avg_labor_time * labor_rate)
            
            enhanced_repair['base_price_enhanced'] = round(base_price, 2)
            enhanced_repair['pricing_source'] = 'charm.li + calculated'
            enhanced_repair['last_updated'] = datetime.now().isoformat()
        
        return enhanced_repair
    
    def integrate_data(self):
        """Integrate charm data with price catalog"""
        print("Loading existing price catalog...")
        catalog = self.load_price_catalog()
        
        print("Loading charm.li data...")
        charm_data = self.load_charm_data()
        
        if not charm_data:
            print("No charm data found. Run charm_scraper.py first.")
            return None
        
        print(f"Integrating data for {len(catalog.get('repairs', []))} repairs...")
        
        # Enhance each repair with charm data
        enhanced_repairs = []
        for repair in catalog.get('repairs', []):
            enhanced_repair = self.enhance_repair_entry(repair, charm_data)
            enhanced_repairs.append(enhanced_repair)
        
        # Create enhanced catalog
        enhanced_catalog = {
            'repairs': enhanced_repairs,
            'integration_info': {
                'charm_vehicles_count': len(charm_data),
                'integrated_at': datetime.now().isoformat(),
                'version': '2.0-charm-enhanced'
            },
            'multipliers': catalog.get('multipliers', {
                "v8": 1.2,
                "old_car": 1.1,
                "mobile_service": 1.2
            })
        }
        
        return enhanced_catalog
    
    def save_enhanced_catalog(self, enhanced_catalog):
        """Save the enhanced catalog"""
        try:
            with open(self.integrated_catalog_path, 'w') as f:
                json.dump(enhanced_catalog, f, indent=2)
            print(f"Enhanced catalog saved to {self.integrated_catalog_path}")
            return True
        except Exception as e:
            print(f"Error saving enhanced catalog: {e}")
            return False
    
    def generate_api_endpoint(self):
        """Generate a simple API endpoint for price lookups"""
        api_content = '''<?php
/**
 * Enhanced Price API with charm.li integration
 * Returns pricing based on integrated charm.li data
 */

header('Content-Type: application/json');

// Load enhanced price catalog
$catalogFile = __DIR__ . '/../price-catalog-enhanced.json';
if (!file_exists($catalogFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Enhanced catalog not found']);
    exit;
}

$catalog = json_decode(file_get_contents($catalogFile), true);

$repair = $_GET['repair'] ?? '';
$year = (int)($_GET['year'] ?? 0);
$engine = $_GET['engine'] ?? '';

if (empty($repair)) {
    http_response_code(400);
    echo json_encode(['error' => 'Repair type required']);
    exit;
}

// Find the repair
$repairData = null;
foreach ($catalog['repairs'] as $r) {
    if (strcasecmp($r['name'], $repair) === 0) {
        $repairData = $r;
        break;
    }
}

if (!$repairData) {
    http_response_code(404);
    echo json_encode(['error' => 'Repair not found']);
    exit;
}

// Calculate price with multipliers
$basePrice = $repairData['base_price_enhanced'] ?? $repairData['base_price'] ?? 0;
$multiplier = 1.0;

// Apply multipliers
if (stripos($engine, 'v8') !== false) {
    $multiplier *= $catalog['multipliers']['v8'] ?? 1.2;
}

if ($year > 0 && $year < 2010) {
    $multiplier *= $catalog['multipliers']['old_car'] ?? 1.1;
}

// Always apply mobile service multiplier
$multiplier *= $catalog['multipliers']['mobile_service'] ?? 1.2;

$finalPrice = $basePrice * $multiplier;

$response = [
    'repair' => $repairData['name'],
    'base_price' => $basePrice,
    'multiplier' => round($multiplier, 2),
    'final_price' => round($finalPrice, 2),
    'labor_time' => $repairData['charm_labor_time'] ?? $repairData['labor_time'] ?? null,
    'parts_cost' => $repairData['charm_parts_cost'] ?? null,
    'data_source' => $repairData['pricing_source'] ?? 'static',
    'last_updated' => $repairData['last_updated'] ?? null
];

echo json_encode($response);
?>'''
        
        try:
            with open('../api/enhanced_pricing.php', 'w') as f:
                f.write(api_content)
            print("Enhanced pricing API created at ../api/enhanced_pricing.php")
            return True
        except Exception as e:
            print(f"Error creating API endpoint: {e}")
            return False


def main():
    integrator = DataIntegrator()
    
    print("Integrating charm.li data with price catalog...")
    
    # Integrate the data
    enhanced_catalog = integrator.integrate_data()
    
    if enhanced_catalog:
        # Save enhanced catalog
        if integrator.save_enhanced_catalog(enhanced_catalog):
            print(f"Integration complete!")
            print(f"Enhanced {len(enhanced_catalog['repairs'])} repair entries")
            
            # Generate API endpoint
            integrator.generate_api_endpoint()
            
            # Show summary
            charm_enhanced = sum(1 for repair in enhanced_catalog['repairs'] 
                               if 'charm_parts_cost' in repair)
            print(f"Repairs enhanced with charm data: {charm_enhanced}")
        else:
            print("Failed to save enhanced catalog")
    else:
        print("Integration failed")


if __name__ == "__main__":
    main()