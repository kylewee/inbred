#!/usr/bin/env python3
"""
PartsTech API Integration for Mechanic Saint Augustine
Handles real-time parts pricing and availability for newer vehicles (2014+)
"""

import requests
import json
import time
from datetime import datetime
import os

class PartsTechAPI:
    def __init__(self, api_key="c522bfbb64174741b59c3a4681db7558", email="sodjacksonville@gmail.com"):
        self.api_key = api_key
        self.email = email
        self.base_url = "https://api.partstech.com"
        self.session = requests.Session()
        
        # Standard headers for API requests
        self.headers = {
            'Authorization': f'Bearer {self.api_key}',
            'Content-Type': 'application/json',
            'X-Location-Email': self.email,
            'User-Agent': 'MechanicStAugustine/1.0'
        }
        
        # Common repair type mappings
        self.repair_mappings = {
            'Oil Change': ['oil filter', 'engine oil', 'drain plug gasket'],
            'Brake Pads Replacement': ['brake pads', 'brake rotors', 'brake cleaner'],
            'Battery Replacement': ['car battery', 'battery terminal'],
            'Alternator Replacement': ['alternator', 'alternator belt', 'wiring harness'],
            'Starter Replacement': ['starter motor', 'starter solenoid'],
            'Timing Belt': ['timing belt', 'timing belt tensioner', 'water pump'],
            'AC Recharge': ['refrigerant', 'ac compressor oil', 'ac dye'],
            'Engine Diagnostic': [],  # No parts for diagnostic
            'Transmission Service': ['transmission fluid', 'transmission filter', 'transmission gasket'],
            'Spark Plugs Replacement': ['spark plugs', 'ignition coils', 'spark plug wires']
        }
        
        # Labor time estimates for common repairs (in hours)
        self.standard_labor_times = {
            'Oil Change': 0.5,
            'Brake Pads Replacement': 1.5,
            'Battery Replacement': 0.3,
            'Alternator Replacement': 2.5,
            'Starter Replacement': 2.0,
            'Timing Belt': 4.0,
            'AC Recharge': 0.8,
            'Engine Diagnostic': 1.0,
            'Transmission Service': 2.5,
            'Spark Plugs Replacement': 1.5
        }
    
    def test_api_connection(self):
        """Test if the PartsTech API is accessible with our credentials"""
        try:
            # Try to access API info endpoint (adjust based on actual API)
            test_url = f"{self.base_url}/v1/info"
            response = self.session.get(test_url, headers=self.headers, timeout=10)
            
            print(f"API Test Response: {response.status_code}")
            
            if response.status_code == 200:
                print("✅ PartsTech API connection successful")
                return True
            elif response.status_code == 401:
                print("❌ API authentication failed - check credentials")
                return False
            elif response.status_code == 403:
                print("❌ API access forbidden - check permissions")
                return False
            else:
                print(f"⚠️  API returned status {response.status_code}")
                return False
                
        except requests.exceptions.RequestException as e:
            print(f"❌ API connection error: {e}")
            return False
    
    def search_parts(self, year, make, model, part_keywords):
        """Search for parts using PartsTech API"""
        try:
            search_url = f"{self.base_url}/v1/parts/search"
            
            payload = {
                'vehicle': {
                    'year': int(year),
                    'make': make.title(),
                    'model': model.title()
                },
                'parts': part_keywords,
                'location': {
                    'city': 'Jacksonville',
                    'state': 'FL',
                    'zip': '32207'
                },
                'options': {
                    'include_pricing': True,
                    'include_availability': True,
                    'max_results': 10
                }
            }
            
            response = self.session.post(
                search_url, 
                headers=self.headers, 
                json=payload, 
                timeout=15
            )
            
            if response.status_code == 200:
                return response.json()
            else:
                print(f"Parts search failed: {response.status_code}")
                if response.content:
                    print(f"Error details: {response.text}")
                return None
                
        except Exception as e:
            print(f"Parts search error: {e}")
            return None
    
    def get_labor_estimate(self, year, make, model, repair_type):
        """Get labor time estimate for a specific repair"""
        try:
            labor_url = f"{self.base_url}/v1/labor/estimate"
            
            payload = {
                'vehicle': {
                    'year': int(year),
                    'make': make.title(),
                    'model': model.title()
                },
                'repair_type': repair_type,
                'location': {
                    'city': 'Jacksonville',
                    'state': 'FL'
                }
            }
            
            response = self.session.post(
                labor_url,
                headers=self.headers,
                json=payload,
                timeout=15
            )
            
            if response.status_code == 200:
                return response.json()
            else:
                print(f"Labor estimate failed: {response.status_code}")
                # Fallback to standard estimates
                return {
                    'labor_hours': self.standard_labor_times.get(repair_type, 1.5),
                    'complexity': 'Standard',
                    'source': 'fallback'
                }
                
        except Exception as e:
            print(f"Labor estimate error: {e}")
            return {
                'labor_hours': self.standard_labor_times.get(repair_type, 1.5),
                'complexity': 'Standard',
                'source': 'fallback'
            }
    
    def get_complete_repair_data(self, year, make, model, repair_type):
        """Get complete repair data (parts + labor) for a specific repair"""
        
        print(f"Getting PartsTech data for {year} {make} {model} - {repair_type}")
        
        # Get part keywords for this repair type
        part_keywords = self.repair_mappings.get(repair_type, [])
        
        # Get parts data
        parts_data = None
        if part_keywords:
            parts_data = self.search_parts(year, make, model, part_keywords)
        
        # Get labor estimate
        labor_data = self.get_labor_estimate(year, make, model, repair_type)
        
        # Format the response
        return self.format_repair_data(parts_data, labor_data, year, make, model, repair_type)
    
    def format_repair_data(self, parts_data, labor_data, year, make, model, repair_type):
        """Format PartsTech API responses into our standard format"""
        
        formatted_data = {
            'vehicle': {
                'year': int(year),
                'make': make,
                'model': model
            },
            'repair_type': repair_type,
            'parts': [],
            'total_parts_cost': 0.0,
            'labor_time': labor_data.get('labor_hours', 1.5),
            'labor_complexity': labor_data.get('complexity', 'Standard'),
            'data_source': 'partstech_api',
            'api_success': True,
            'fetched_at': datetime.now().isoformat()
        }
        
        # Process parts data if available
        if parts_data and 'parts' in parts_data:
            total_cost = 0.0
            
            for part in parts_data['parts'][:5]:  # Limit to top 5 parts
                part_info = {
                    'name': part.get('description', part.get('name', 'Unknown Part')),
                    'price': float(part.get('price', 0)),
                    'part_number': part.get('part_number', part.get('sku', '')),
                    'brand': part.get('manufacturer', part.get('brand', 'OEM')),
                    'availability': part.get('availability', 'In Stock')
                }
                formatted_data['parts'].append(part_info)
                total_cost += part_info['price']
            
            formatted_data['total_parts_cost'] = round(total_cost, 2)
        
        else:
            # No parts data available, use fallback estimates
            formatted_data['api_success'] = False
            formatted_data['parts'] = self.get_fallback_parts_estimate(repair_type, year, make)
            formatted_data['total_parts_cost'] = sum(part['price'] for part in formatted_data['parts'])
        
        return formatted_data
    
    def get_fallback_parts_estimate(self, repair_type, year, make):
        """Generate estimated parts list when API data unavailable"""
        
        # Base multiplier for vehicle characteristics
        multiplier = 1.0
        
        # Luxury brand adjustment
        if make.lower() in ['bmw', 'mercedes', 'audi', 'lexus', 'acura', 'infiniti']:
            multiplier *= 1.3
        
        # Age adjustment
        if int(year) < 2015:
            multiplier *= 0.95  # Older parts sometimes cheaper
        
        # Standard parts estimates by repair type
        fallback_parts = {
            'Oil Change': [
                {'name': 'Oil Filter', 'price': 15.99 * multiplier, 'part_number': 'OF-STD', 'brand': 'OEM'},
                {'name': 'Motor Oil (5qt)', 'price': 28.99 * multiplier, 'part_number': 'OIL-5W30', 'brand': 'Valvoline'}
            ],
            'Brake Pads Replacement': [
                {'name': 'Front Brake Pads', 'price': 89.99 * multiplier, 'part_number': 'BP-FRT', 'brand': 'Raybestos'},
                {'name': 'Brake Cleaner', 'price': 9.99 * multiplier, 'part_number': 'BC-CRC', 'brand': 'CRC'}
            ],
            'Battery Replacement': [
                {'name': 'Car Battery', 'price': 139.99 * multiplier, 'part_number': 'BAT-STD', 'brand': 'Interstate'}
            ],
            'Alternator Replacement': [
                {'name': 'Alternator', 'price': 299.99 * multiplier, 'part_number': 'ALT-STD', 'brand': 'Bosch'},
                {'name': 'Serpentine Belt', 'price': 39.99 * multiplier, 'part_number': 'BELT-SERP', 'brand': 'Gates'}
            ],
            'Starter Replacement': [
                {'name': 'Starter Motor', 'price': 199.99 * multiplier, 'part_number': 'START-STD', 'brand': 'Denso'}
            ]
        }
        
        parts_list = fallback_parts.get(repair_type, [
            {'name': 'Standard Parts', 'price': 50.00 * multiplier, 'part_number': 'STD-PART', 'brand': 'OEM'}
        ])
        
        # Round prices
        for part in parts_list:
            part['price'] = round(part['price'], 2)
            part['availability'] = 'Estimated'
        
        return parts_list
    
    def test_repair_lookup(self):
        """Test the complete repair lookup process"""
        
        test_cases = [
            {'year': 2023, 'make': 'Honda', 'model': 'Civic', 'repair': 'Oil Change'},
            {'year': 2020, 'make': 'Ford', 'model': 'F-150', 'repair': 'Brake Pads Replacement'},
            {'year': 2019, 'make': 'Toyota', 'model': 'Camry', 'repair': 'Battery Replacement'}
        ]
        
        print("Testing PartsTech API Repair Lookups")
        print("=" * 50)
        
        results = []
        
        for test in test_cases:
            print(f"\nTesting {test['year']} {test['make']} {test['model']} - {test['repair']}")
            
            data = self.get_complete_repair_data(
                test['year'], test['make'], test['model'], test['repair']
            )
            
            if data:
                print(f"✅ Success - Parts: ${data['total_parts_cost']:.2f}, Labor: {data['labor_time']}hrs")
                results.append(data)
            else:
                print("❌ Failed to get repair data")
                
            time.sleep(1)  # Be respectful to API
        
        return results


def main():
    """Test PartsTech API integration"""
    
    partstech = PartsTechAPI()
    
    print("PartsTech API Integration Test")
    print("=" * 40)
    print(f"API Key: {partstech.api_key[:8]}...")
    print(f"Location: {partstech.email}")
    print()
    
    # Test API connection
    print("1. Testing API Connection...")
    if partstech.test_api_connection():
        print("✅ API connection successful")
    else:
        print("❌ API connection failed - using fallback data")
    
    print()
    
    # Test repair lookups
    print("2. Testing Repair Lookups...")
    results = partstech.test_repair_lookup()
    
    # Save results
    if results:
        output_file = 'partstech_test_results.json'
        with open(output_file, 'w') as f:
            json.dump(results, f, indent=2)
        
        print(f"\n📁 Test results saved to {output_file}")
        
        # Show summary
        total_parts_avg = sum(r['total_parts_cost'] for r in results) / len(results)
        total_labor_avg = sum(r['labor_time'] for r in results) / len(results)
        
        print(f"\n📊 Summary:")
        print(f"   Average Parts Cost: ${total_parts_avg:.2f}")
        print(f"   Average Labor Time: {total_labor_avg:.1f} hours")
        print(f"   API Success Rate: {sum(r['api_success'] for r in results)}/{len(results)}")


if __name__ == "__main__":
    main()