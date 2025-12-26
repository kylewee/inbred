#!/usr/bin/env python3
"""
Charm.li Site Explorer
Helps understand the structure of charm.li for accurate scraping
"""

import requests
from urllib.parse import urljoin, urlparse
import time
import json

class CharmExplorer:
    def __init__(self):
        self.base_url = "https://charm.li"
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        self.discovered_urls = set()
        self.site_structure = {}
    
    def check_site_availability(self):
        """Check if charm.li is accessible"""
        try:
            response = self.session.get(self.base_url, timeout=10)
            print(f"Site Status: {response.status_code}")
            print(f"Response Headers: {dict(response.headers)}")
            
            if response.status_code == 200:
                print("✓ Site is accessible")
                return True, response
            else:
                print(f"✗ Site returned status {response.status_code}")
                return False, response
        except requests.exceptions.RequestException as e:
            print(f"✗ Failed to connect to charm.li: {e}")
            return False, None
    
    def analyze_homepage(self, response):
        """Analyze the homepage to understand site structure"""
        content = response.text
        
        # Look for common patterns
        analysis = {
            'forms_found': content.count('<form'),
            'search_inputs': content.count('type="search"') + content.count('name="search"'),
            'vehicle_selectors': 0,
            'links_count': content.count('<a href='),
            'javascript_files': content.count('.js"'),
            'api_endpoints': []
        }
        
        # Look for vehicle-related terms
        vehicle_terms = ['year', 'make', 'model', 'vehicle', 'car', 'truck']
        for term in vehicle_terms:
            if term.lower() in content.lower():
                analysis['vehicle_selectors'] += content.lower().count(term.lower())
        
        print("Homepage Analysis:")
        for key, value in analysis.items():
            print(f"  {key}: {value}")
        
        return analysis
    
    def find_search_functionality(self):
        """Try to find search or lookup functionality"""
        search_endpoints = [
            '/search',
            '/lookup',
            '/parts',
            '/labor',
            '/estimate',
            '/quote',
            '/api/search',
            '/api/parts',
            '/vehicle'
        ]
        
        available_endpoints = []
        
        for endpoint in search_endpoints:
            try:
                url = urljoin(self.base_url, endpoint)
                response = self.session.head(url, timeout=5)
                if response.status_code in [200, 301, 302]:
                    available_endpoints.append(endpoint)
                    print(f"✓ Found endpoint: {endpoint} (Status: {response.status_code})")
                time.sleep(1)  # Be respectful
            except:
                continue
        
        return available_endpoints
    
    def test_search_parameters(self):
        """Test common search parameters"""
        test_params = [
            {'year': '2020', 'make': 'honda', 'model': 'civic'},
            {'vehicle': 'honda civic 2020'},
            {'q': 'oil change honda civic'},
            {'search': 'brake pads'}
        ]
        
        search_url = urljoin(self.base_url, '/search')
        
        for params in test_params:
            try:
                print(f"Testing parameters: {params}")
                response = self.session.get(search_url, params=params, timeout=10)
                print(f"  Status: {response.status_code}")
                print(f"  Content length: {len(response.text)}")
                
                if response.status_code == 200 and len(response.text) > 1000:
                    print(f"  ✓ Successful response with content")
                    # Save a sample response for analysis
                    with open(f'sample_response_{hash(str(params))}.html', 'w') as f:
                        f.write(response.text[:5000])  # First 5KB
                
                time.sleep(2)  # Be respectful
                
            except Exception as e:
                print(f"  ✗ Failed: {e}")
    
    def explore_api_endpoints(self):
        """Look for API endpoints"""
        api_paths = [
            '/api/',
            '/api/v1/',
            '/api/parts',
            '/api/labor', 
            '/api/search',
            '/rest/',
            '/graphql'
        ]
        
        api_endpoints = []
        
        for path in api_paths:
            try:
                url = urljoin(self.base_url, path)
                response = self.session.get(url, timeout=5)
                
                if response.status_code == 200:
                    content_type = response.headers.get('content-type', '')
                    if 'json' in content_type:
                        api_endpoints.append({
                            'url': url,
                            'status': response.status_code,
                            'content_type': content_type,
                            'response_size': len(response.text)
                        })
                        print(f"✓ Found API endpoint: {url}")
                
                time.sleep(1)
                
            except:
                continue
        
        return api_endpoints
    
    def generate_scraping_strategy(self, analysis_results):
        """Generate a scraping strategy based on findings"""
        strategy = {
            'approach': 'unknown',
            'entry_points': [],
            'parameters': {},
            'challenges': [],
            'recommendations': []
        }
        
        # Determine approach based on findings
        if analysis_results.get('api_endpoints'):
            strategy['approach'] = 'api'
            strategy['entry_points'] = [ep['url'] for ep in analysis_results['api_endpoints']]
            strategy['recommendations'].append("Use API endpoints for direct data access")
        
        elif analysis_results.get('search_endpoints'):
            strategy['approach'] = 'web_scraping'
            strategy['entry_points'] = analysis_results['search_endpoints']
            strategy['recommendations'].append("Scrape search results pages")
        
        else:
            strategy['approach'] = 'exploratory'
            strategy['challenges'].append("No clear search functionality found")
            strategy['recommendations'].append("Manual exploration needed")
        
        return strategy
    
    def save_exploration_results(self, results):
        """Save exploration results to file"""
        with open('charm_exploration_results.json', 'w') as f:
            json.dump(results, f, indent=2)
        print("Exploration results saved to charm_exploration_results.json")


def main():
    explorer = CharmExplorer()
    
    print("Exploring charm.li structure...")
    print("=" * 50)
    
    # Check if site is accessible
    accessible, response = explorer.check_site_availability()
    
    if not accessible:
        print("Cannot proceed - site is not accessible")
        return
    
    results = {'site_accessible': True}
    
    # Analyze homepage
    print("\n1. Analyzing Homepage...")
    homepage_analysis = explorer.analyze_homepage(response)
    results['homepage_analysis'] = homepage_analysis
    
    # Find search functionality
    print("\n2. Looking for Search Endpoints...")
    search_endpoints = explorer.find_search_functionality()
    results['search_endpoints'] = search_endpoints
    
    # Look for API endpoints
    print("\n3. Exploring API Endpoints...")
    api_endpoints = explorer.explore_api_endpoints()
    results['api_endpoints'] = api_endpoints
    
    # Test search parameters if endpoints found
    if search_endpoints:
        print("\n4. Testing Search Parameters...")
        explorer.test_search_parameters()
    
    # Generate strategy
    print("\n5. Generating Scraping Strategy...")
    strategy = explorer.generate_scraping_strategy(results)
    results['strategy'] = strategy
    
    print("\nScraping Strategy:")
    print(f"  Approach: {strategy['approach']}")
    print(f"  Entry Points: {strategy['entry_points']}")
    print("  Recommendations:")
    for rec in strategy['recommendations']:
        print(f"    - {rec}")
    
    # Save results
    explorer.save_exploration_results(results)
    
    print(f"\nExploration complete!")


if __name__ == "__main__":
    main()