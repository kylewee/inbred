#!/usr/bin/env python3
"""
Multi-Source Automotive Data Integration System
Combines charm.li (legacy), PartsTech API (current), and Gale database (comprehensive)
for complete vehicle repair pricing coverage
"""

import requests
import json
import time
from datetime import datetime
import os
import sys

class MultiSourceDataManager:
    def __init__(self):
        # PartsTech API Configuration
        self.partstech_api_key = "c522bfbb64174741b59c3a4681db7558"
        self.partstech_email = "sodjacksonville@gmail.com"
        self.partstech_base_url = "https://api.partstech.com"
        
        # Gale Database Configuration  
        self.gale_url = "https://link.gale.com/apps/CHLL"
        self.gale_user = "nclivemdcp"
        self.gale_password = "nclive001"
        
        # Data source routing logic
        self.data_sources = {
            'partstech': {'years': (2014, 2025), 'priority': 1},
            'charm': {'years': (1990, 2013), 'priority': 2},  
            'gale': {'years': (1980, 2025), 'priority': 3},
            'fallback': {'years': (1980, 2025), 'priority': 4}
        }
        
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
        
    def determine_data_source(self, year):
        """Determine the best data source for a given vehicle year"""
        year = int(year)
        
        # Route based on vehicle year and data source availability
        if year >= 2014:
            return ['partstech', 'gale', 'fallback']
        elif year >= 1990:
            return ['charm', 'gale', 'fallback']
        else:
            return ['gale', 'fallback']
    
    def get_partstech_data(self, year, make, model, repair_type):
        """Get parts and labor data from PartsTech API"""
        try:
            print(f"Fetching PartsTech data for {year} {make} {model} - {repair_type}")
            
            # PartsTech API endpoints (adjust based on actual API documentation)
            search_url = f"{self.partstech_base_url}/v1/parts/search"
            labor_url = f"{self.partstech_base_url}/v1/labor/estimate"
            
            headers = {
                'Authorization': f'Bearer {self.partstech_api_key}',
                'Content-Type': 'application/json',
                'X-Location-Email': self.partstech_email
            }
            
            # Search for parts
            parts_payload = {
                'vehicle': {
                    'year': year,
                    'make': make,
                    'model': model
                },
                'repair_type': repair_type,
                'location': 'Jacksonville, FL'
            }
            
            parts_response = self.session.post(search_url, headers=headers, json=parts_payload, timeout=10)
            
            if parts_response.status_code == 200:
                parts_data = parts_response.json()
                
                # Get labor estimate
                labor_payload = parts_payload.copy()
                labor_response = self.session.post(labor_url, headers=headers, json=labor_payload, timeout=10)
                
                labor_data = {}
                if labor_response.status_code == 200:
                    labor_data = labor_response.json()
                
                return self.format_partstech_data(parts_data, labor_data, year, make, model, repair_type)
            
            else:
                print(f"PartsTech API error: {parts_response.status_code}")
                return None
                
        except Exception as e:
            print(f"PartsTech API error: {e}")
            return None
    
    def format_partstech_data(self, parts_data, labor_data, year, make, model, repair_type):
        """Format PartsTech API response into our standard format"""
        try:
            # Extract parts information (format depends on actual API response)
            parts = []
            total_parts_cost = 0
            
            # This structure will need adjustment based on actual PartsTech API response format
            if 'parts' in parts_data:
                for part in parts_data['parts'][:5]:  # Limit to top 5 parts
                    part_info = {
                        'name': part.get('description', 'Unknown Part'),
                        'price': float(part.get('price', 0)),
                        'part_number': part.get('part_number', ''),
                        'brand': part.get('brand', ''),
                        'availability': part.get('availability', 'Unknown')
                    }
                    parts.append(part_info)
                    total_parts_cost += part_info['price']
            
            # Extract labor information
            labor_time = 0
            labor_complexity = 'Unknown'
            
            if 'labor_estimate' in labor_data:
                labor_time = float(labor_data['labor_estimate'].get('hours', 0))
                labor_complexity = labor_data['labor_estimate'].get('complexity', 'Intermediate')
            
            return {
                'vehicle': {
                    'year': year,
                    'make': make,
                    'model': model
                },
                'repair_type': repair_type,
                'parts': parts,
                'total_parts_cost': round(total_parts_cost, 2),
                'labor_time': labor_time,
                'labor_complexity': labor_complexity,
                'data_source': 'partstech_api',
                'fetched_at': datetime.now().isoformat(),
                'api_success': True
            }
            
        except Exception as e:
            print(f"Error formatting PartsTech data: {e}")
            return None
    
    def get_gale_data(self, year, make, model, repair_type):
        """Access Gale automotive database for comprehensive vehicle data"""
        try:
            print(f"Accessing Gale database for {year} {make} {model} - {repair_type}")
            
            # Login to Gale database
            login_payload = {
                'username': self.gale_user,
                'password': self.gale_password
            }
            
            login_response = self.session.post(
                f"{self.gale_url}/login",
                data=login_payload,
                timeout=15
            )
            
            if login_response.status_code == 200:
                # Search for vehicle and repair information
                search_params = {
                    'year': year,
                    'make': make,
                    'model': model,
                    'repair': repair_type,
                    'format': 'json'  # Request JSON if available
                }
                
                search_response = self.session.get(
                    f"{self.gale_url}/search",
                    params=search_params,
                    timeout=15
                )
                
                if search_response.status_code == 200:
                    return self.parse_gale_response(search_response, year, make, model, repair_type)
                else:
                    print(f"Gale search failed: {search_response.status_code}")
                    return None
            else:
                print(f"Gale login failed: {login_response.status_code}")
                return None
                
        except Exception as e:
            print(f"Gale database error: {e}")
            return None
    
    def parse_gale_response(self, response, year, make, model, repair_type):
        """Parse Gale database response for automotive data"""
        try:
            content = response.text
            
            # Look for automotive data patterns in the response
            # This will need customization based on actual Gale response format
            
            # Basic pattern matching for common repair data
            labor_time = self.extract_labor_time_from_gale(content, repair_type)
            parts_info = self.extract_parts_info_from_gale(content, repair_type)
            
            if labor_time or parts_info:
                return {
                    'vehicle': {
                        'year': year,
                        'make': make,
                        'model': model
                    },
                    'repair_type': repair_type,
                    'parts': parts_info.get('parts', []),
                    'total_parts_cost': parts_info.get('total_cost', 0),
                    'labor_time': labor_time,
                    'labor_complexity': self.determine_complexity(labor_time),
                    'data_source': 'gale_database',
                    'fetched_at': datetime.now().isoformat(),
                    'content_length': len(content)
                }
            else:
                print("No relevant automotive data found in Gale response")
                return None
                
        except Exception as e:
            print(f"Error parsing Gale response: {e}")
            return None
    
    def extract_labor_time_from_gale(self, content, repair_type):
        """Extract labor time estimates from Gale database content"""
        # Pattern matching based on common automotive manual formats
        import re
        
        # Look for time patterns like "1.5 hours", "2.0 hrs", etc.
        time_patterns = [
            r'(\d+\.?\d*)\s*(?:hours?|hrs?|hr)',
            r'labor.*?(\d+\.?\d*)',
            r'time.*?(\d+\.?\d*)'
        ]
        
        for pattern in time_patterns:
            matches = re.findall(pattern, content.lower())
            if matches:
                try:
                    return float(matches[0])
                except:
                    continue
        
        return None
    
    def extract_parts_info_from_gale(self, content, repair_type):
        """Extract parts information from Gale database content"""
        # This would need customization based on actual Gale content format
        # For now, return basic structure
        
        return {
            'parts': [],
            'total_cost': 0
        }
    
    def determine_complexity(self, labor_time):
        """Determine repair complexity based on labor time"""
        if not labor_time:
            return 'Unknown'
        
        if labor_time <= 1.0:
            return 'Basic'
        elif labor_time <= 3.0:
            return 'Intermediate'  
        elif labor_time <= 6.0:
            return 'Advanced'
        else:
            return 'Expert'
    
    def get_enhanced_fallback_data(self, year, make, model, repair_type):
        """Enhanced fallback system with more intelligent estimates"""
        print(f"Using enhanced fallback for {year} {make} {model} - {repair_type}")
        
        # Import our existing fallback system
        sys.path.append(os.path.dirname(__file__))
        from charm_scraper import CharmScraper
        
        scraper = CharmScraper()
        data = scraper.generate_demo_data(year, make, model, enhanced=True)
        
        # Find the specific repair
        if repair_type in data['repairs']:
            repair_data = data['repairs'][repair_type]
            
            return {
                'vehicle': {
                    'year': year,
                    'make': make,
                    'model': model
                },
                'repair_type': repair_type,
                'parts': repair_data['parts'],
                'total_parts_cost': sum(part['price'] for part in repair_data['parts']),
                'labor_time': repair_data['labor_time'],
                'labor_complexity': repair_data['labor_complexity'],
                'data_source': 'enhanced_fallback',
                'fetched_at': datetime.now().isoformat()
            }
        
        return None
    
    def get_repair_data(self, year, make, model, repair_type):
        """Get repair data using the best available source"""
        
        # Determine data source priority for this vehicle year
        sources = self.determine_data_source(year)
        
        print(f"Data source priority for {year} {make} {model}: {sources}")
        
        # Try each data source in order of priority
        for source in sources:
            try:
                if source == 'partstech':
                    data = self.get_partstech_data(year, make, model, repair_type)
                elif source == 'gale':
                    data = self.get_gale_data(year, make, model, repair_type)
                elif source == 'charm':
                    # Use existing charm.li scraper
                    sys.path.append(os.path.dirname(__file__))
                    from charm_scraper import CharmScraper
                    scraper = CharmScraper()
                    charm_data = scraper.get_vehicle_data(year, make, model)
                    if charm_data and repair_type in charm_data.get('repairs', {}):
                        repair_info = charm_data['repairs'][repair_type]
                        data = {
                            'vehicle': charm_data['vehicle'],
                            'repair_type': repair_type,
                            'parts': repair_info['parts'],
                            'total_parts_cost': sum(part['price'] for part in repair_info['parts']),
                            'labor_time': repair_info['labor_time'],
                            'labor_complexity': repair_info['labor_complexity'],
                            'data_source': 'charm_li',
                            'fetched_at': datetime.now().isoformat()
                        }
                    else:
                        data = None
                elif source == 'fallback':
                    data = self.get_enhanced_fallback_data(year, make, model, repair_type)
                
                if data:
                    print(f"Successfully retrieved data from {source}")
                    return data
                else:
                    print(f"No data available from {source}")
                    
            except Exception as e:
                print(f"Error with {source}: {e}")
                continue
        
        print("All data sources failed")
        return None
    
    def batch_update_catalog(self, vehicle_list, repair_types):
        """Update the entire pricing catalog with multi-source data"""
        
        enhanced_data = []
        
        for vehicle in vehicle_list:
            print(f"\nProcessing {vehicle['year']} {vehicle['make']} {vehicle['model']}...")
            
            vehicle_repairs = {}
            
            for repair_type in repair_types:
                repair_data = self.get_repair_data(
                    vehicle['year'], 
                    vehicle['make'], 
                    vehicle['model'], 
                    repair_type
                )
                
                if repair_data:
                    vehicle_repairs[repair_type] = repair_data
                
                time.sleep(1)  # Be respectful to APIs
            
            if vehicle_repairs:
                enhanced_data.append({
                    'vehicle': vehicle,
                    'repairs': vehicle_repairs,
                    'updated_at': datetime.now().isoformat()
                })
        
        return enhanced_data


def main():
    """Test the multi-source data system"""
    
    manager = MultiSourceDataManager()
    
    # Test vehicles across different years to test all data sources
    test_vehicles = [
        {'year': 2023, 'make': 'Honda', 'model': 'Civic'},      # PartsTech
        {'year': 2018, 'make': 'Ford', 'model': 'F-150'},       # PartsTech  
        {'year': 2012, 'make': 'Toyota', 'model': 'Camry'},     # charm.li
        {'year': 2008, 'make': 'Chevrolet', 'model': 'Silverado'}, # charm.li
        {'year': 2020, 'make': 'BMW', 'model': '320i'},         # PartsTech/Gale
        {'year': 2005, 'make': 'Mercedes', 'model': 'C300'}     # charm.li/Gale
    ]
    
    test_repairs = [
        'Oil Change',
        'Brake Pads Replacement', 
        'Battery Replacement',
        'Alternator Replacement'
    ]
    
    print("Multi-Source Automotive Data Integration Test")
    print("=" * 60)
    
    # Test single repair lookup
    print("\n1. Testing single repair lookup...")
    test_data = manager.get_repair_data(2023, 'Honda', 'Civic', 'Oil Change')
    
    if test_data:
        print(f"✅ Success: {test_data['data_source']}")
        print(f"   Parts Cost: ${test_data['total_parts_cost']:.2f}")
        print(f"   Labor Time: {test_data['labor_time']} hours")
        print(f"   Complexity: {test_data['labor_complexity']}")
    else:
        print("❌ Failed to retrieve data")
    
    # Test batch update
    print("\n2. Testing batch catalog update...")
    batch_data = manager.batch_update_catalog(test_vehicles[:3], test_repairs[:2])
    
    print(f"✅ Batch update complete: {len(batch_data)} vehicles processed")
    
    # Save results
    output_file = 'multi_source_data.json'
    with open(output_file, 'w') as f:
        json.dump(batch_data, f, indent=2)
    
    print(f"📁 Results saved to {output_file}")
    
    # Show data source distribution
    sources = {}
    for vehicle_data in batch_data:
        for repair_name, repair_info in vehicle_data['repairs'].items():
            source = repair_info['data_source']
            sources[source] = sources.get(source, 0) + 1
    
    print(f"\n📊 Data Source Distribution:")
    for source, count in sources.items():
        print(f"   {source}: {count} repairs")


if __name__ == "__main__":
    main()