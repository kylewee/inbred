#!/usr/bin/env python3
"""
Gale Automotive Database Integration
Accesses comprehensive automotive repair data for all vehicle years
"""

import requests
from bs4 import BeautifulSoup
import json
import re
import time
from datetime import datetime

class GaleAutomotiveAPI:
    def __init__(self, username="nclivemdcp", password="nclive001"):
        self.username = username
        self.password = password
        self.base_url = "https://link.gale.com/apps/CHLL"
        self.session = requests.Session()
        self.logged_in = False
        
        # Set headers to mimic a real browser
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate',
            'DNT': '1',
            'Connection': 'keep-alive'
        })
        
        # Common automotive repair patterns to search for
        self.repair_patterns = {
            'Oil Change': ['oil change', 'engine oil', 'oil filter', 'lubrication'],
            'Brake Pads Replacement': ['brake pad', 'brake service', 'disc brake', 'brake system'],
            'Battery Replacement': ['battery', 'electrical system', 'starting system'],
            'Alternator Replacement': ['alternator', 'charging system', 'electrical'],
            'Starter Replacement': ['starter', 'starting system', 'cranking'],
            'Timing Belt': ['timing belt', 'timing chain', 'valve timing'],
            'AC Recharge': ['air conditioning', 'a/c service', 'refrigerant'],
            'Engine Diagnostic': ['engine diagnostic', 'trouble codes', 'check engine'],
            'Transmission Service': ['transmission', 'automatic transmission', 'transmission fluid'],
            'Spark Plugs Replacement': ['spark plug', 'ignition system', 'tune up']
        }
    
    def login(self):
        """Login to Gale database"""
        try:
            print("Logging into Gale database...")
            
            # Get login page first
            login_page = self.session.get(self.base_url, timeout=15)
            
            if login_page.status_code != 200:
                print(f"Failed to access login page: {login_page.status_code}")
                return False
            
            # Parse login form to find required fields
            soup = BeautifulSoup(login_page.content, 'html.parser')
            
            # Look for login form
            login_form = soup.find('form', {'id': 'loginForm'}) or soup.find('form', class_='login')
            
            if not login_form:
                # Try alternative login approach
                print("Trying alternative login method...")
                return self.alternative_login()
            
            # Prepare login data
            login_data = {
                'username': self.username,
                'password': self.password
            }
            
            # Add any hidden fields from the form
            for input_field in login_form.find_all('input', type='hidden'):
                name = input_field.get('name')
                value = input_field.get('value', '')
                if name:
                    login_data[name] = value
            
            # Submit login
            login_response = self.session.post(
                login_form.get('action', self.base_url),
                data=login_data,
                timeout=15
            )
            
            if 'logout' in login_response.text.lower() or 'dashboard' in login_response.text.lower():
                print("✅ Successfully logged into Gale database")
                self.logged_in = True
                return True
            else:
                print("❌ Login failed - checking response...")
                print(f"Response status: {login_response.status_code}")
                return False
                
        except Exception as e:
            print(f"Login error: {e}")
            return False
    
    def alternative_login(self):
        """Alternative login method if standard form login fails"""
        try:
            # Some Gale databases use different authentication methods
            auth_url = f"{self.base_url}/authenticate"
            
            auth_data = {
                'user': self.username,
                'pass': self.password,
                'submit': 'Login'
            }
            
            auth_response = self.session.post(auth_url, data=auth_data, timeout=15)
            
            if auth_response.status_code == 200:
                print("✅ Alternative login successful")
                self.logged_in = True
                return True
            else:
                print(f"❌ Alternative login failed: {auth_response.status_code}")
                return False
                
        except Exception as e:
            print(f"Alternative login error: {e}")
            return False
    
    def search_repair_data(self, year, make, model, repair_type):
        """Search Gale database for specific repair information"""
        
        if not self.logged_in:
            if not self.login():
                return None
        
        try:
            print(f"Searching Gale for {year} {make} {model} - {repair_type}")
            
            # Get search terms for this repair type
            search_terms = self.repair_patterns.get(repair_type, [repair_type.lower()])
            
            # Try different search approaches
            for search_term in search_terms:
                
                # Construct search query
                query = f"{year} {make} {model} {search_term}"
                
                search_params = {
                    'q': query,
                    'searchType': 'basic',
                    'userGroupName': 'automotive',
                    'docType': 'repair'
                }
                
                search_url = f"{self.base_url}/search"
                search_response = self.session.get(search_url, params=search_params, timeout=15)
                
                if search_response.status_code == 200:
                    # Parse search results
                    parsed_data = self.parse_search_results(
                        search_response.text, year, make, model, repair_type
                    )
                    
                    if parsed_data:
                        return parsed_data
                
                time.sleep(1)  # Be respectful
            
            print("No relevant data found in Gale database")
            return None
            
        except Exception as e:
            print(f"Gale search error: {e}")
            return None
    
    def parse_search_results(self, html_content, year, make, model, repair_type):
        """Parse Gale search results for automotive repair data"""
        
        try:
            soup = BeautifulSoup(html_content, 'html.parser')
            
            # Look for repair-related content
            repair_data = {
                'vehicle': {
                    'year': int(year),
                    'make': make,
                    'model': model
                },
                'repair_type': repair_type,
                'labor_time': None,
                'complexity': 'Unknown',
                'parts_info': [],
                'data_source': 'gale_database',
                'found_content': False,
                'fetched_at': datetime.now().isoformat()
            }
            
            # Extract labor time information
            labor_time = self.extract_labor_time(html_content, repair_type)
            if labor_time:
                repair_data['labor_time'] = labor_time
                repair_data['found_content'] = True
            
            # Extract parts information
            parts_info = self.extract_parts_info(html_content, repair_type)
            if parts_info:
                repair_data['parts_info'] = parts_info
                repair_data['found_content'] = True
            
            # Extract complexity information
            complexity = self.extract_complexity_info(html_content, repair_type)
            if complexity:
                repair_data['complexity'] = complexity
            
            return repair_data if repair_data['found_content'] else None
            
        except Exception as e:
            print(f"Error parsing Gale results: {e}")
            return None
    
    def extract_labor_time(self, content, repair_type):
        """Extract labor time estimates from Gale content"""
        
        # Common patterns for labor time in automotive manuals
        time_patterns = [
            r'(\d+\.?\d*)\s*(?:hours?|hrs?|hr)\s*(?:labor|work)',
            r'labor.*?(\d+\.?\d*)\s*(?:hours?|hrs?|hr)',
            r'time.*?(\d+\.?\d*)\s*(?:hours?|hrs?|hr)',
            r'(\d+\.?\d*)\s*hr\b',
            r'estimate.*?(\d+\.?\d*)\s*(?:hours?|hrs?)',
        ]
        
        content_lower = content.lower()
        
        for pattern in time_patterns:
            matches = re.findall(pattern, content_lower)
            if matches:
                try:
                    # Return the first reasonable match (0.1 to 20 hours)
                    time_value = float(matches[0])
                    if 0.1 <= time_value <= 20.0:
                        return time_value
                except ValueError:
                    continue
        
        return None
    
    def extract_parts_info(self, content, repair_type):
        """Extract parts information from Gale content"""
        
        parts_info = []
        content_lower = content.lower()
        
        # Common part name patterns
        part_patterns = {
            'oil change': ['filter', 'gasket', 'oil'],
            'brake': ['pad', 'rotor', 'caliper', 'fluid'],
            'battery': ['battery', 'terminal', 'cable'],
            'alternator': ['alternator', 'belt', 'pulley'],
            'starter': ['starter', 'solenoid', 'relay'],
            'timing': ['belt', 'chain', 'tensioner', 'guide'],
            'spark': ['plug', 'wire', 'coil', 'boot']
        }
        
        repair_key = repair_type.lower().split()[0]  # Get first word
        
        if repair_key in part_patterns:
            for part_name in part_patterns[repair_key]:
                if part_name in content_lower:
                    parts_info.append({
                        'name': part_name.title(),
                        'mentioned': True,
                        'source': 'gale_text_analysis'
                    })
        
        return parts_info
    
    def extract_complexity_info(self, content, repair_type):
        """Extract repair complexity from Gale content"""
        
        content_lower = content.lower()
        
        # Complexity indicators
        if any(word in content_lower for word in ['complex', 'difficult', 'advanced', 'expert']):
            return 'Advanced'
        elif any(word in content_lower for word in ['intermediate', 'moderate', 'standard']):
            return 'Intermediate'
        elif any(word in content_lower for word in ['simple', 'basic', 'easy', 'routine']):
            return 'Basic'
        
        # Default based on typical repair complexity
        complexity_map = {
            'oil change': 'Basic',
            'battery replacement': 'Basic',
            'brake pads replacement': 'Intermediate',
            'alternator replacement': 'Advanced',
            'starter replacement': 'Intermediate',
            'timing belt': 'Advanced',
            'ac recharge': 'Intermediate',
            'engine diagnostic': 'Intermediate',
            'transmission service': 'Advanced',
            'spark plugs replacement': 'Intermediate'
        }
        
        return complexity_map.get(repair_type.lower(), 'Intermediate')
    
    def test_database_access(self):
        """Test access to Gale automotive database"""
        
        print("Testing Gale Automotive Database Access")
        print("=" * 45)
        
        # Test login
        if not self.login():
            print("❌ Cannot access Gale database - login failed")
            return False
        
        # Test search functionality
        test_searches = [
            {'year': 2018, 'make': 'Honda', 'model': 'Civic', 'repair': 'Oil Change'},
            {'year': 2015, 'make': 'Ford', 'model': 'F-150', 'repair': 'Brake Pads Replacement'},
        ]
        
        results = []
        
        for test in test_searches:
            print(f"\nTesting: {test['year']} {test['make']} {test['model']} - {test['repair']}")
            
            data = self.search_repair_data(
                test['year'], test['make'], test['model'], test['repair']
            )
            
            if data:
                print(f"✅ Found data - Labor: {data.get('labor_time', 'Unknown')}hrs")
                results.append(data)
            else:
                print("❌ No data found")
            
            time.sleep(2)  # Be respectful to the database
        
        return results


def main():
    """Test Gale database integration"""
    
    gale = GaleAutomotiveAPI()
    
    print("Gale Automotive Database Integration Test")
    print("=" * 50)
    print(f"Username: {gale.username}")
    print(f"Database URL: {gale.base_url}")
    print()
    
    # Test database access
    results = gale.test_database_access()
    
    if results:
        # Save results
        output_file = 'gale_test_results.json'
        with open(output_file, 'w') as f:
            json.dump(results, f, indent=2)
        
        print(f"\n📁 Results saved to {output_file}")
        print(f"📊 Found data for {len(results)} repairs")
    else:
        print("\n⚠️  No data retrieved - using fallback system")


if __name__ == "__main__":
    main()