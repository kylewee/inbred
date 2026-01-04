#!/usr/bin/env python3
"""
Contractor Scraper - Find potential lead buyers from Google Maps

Usage:
    python scraper.py "sod installation" "Jacksonville, FL"
    python scraper.py "landscaping" "St Augustine, FL" --radius 20
    python scraper.py --file cities.txt --query "sod installation"
"""

import argparse
import csv
import json
import os
import re
import sys
import time
from datetime import datetime
from pathlib import Path

try:
    import requests
except ImportError:
    print("Installing requests...")
    os.system("pip install requests")
    import requests

# Load API key from environment or config file
def get_api_key():
    # Check environment variable first
    api_key = os.environ.get('GOOGLE_PLACES_API_KEY')
    if api_key:
        return api_key

    # Check config file
    config_path = Path(__file__).parent / 'config.json'
    if config_path.exists():
        with open(config_path) as f:
            config = json.load(f)
            return config.get('google_places_api_key')

    return None


class ContractorScraper:
    def __init__(self, api_key: str):
        self.api_key = api_key
        self.base_url = "https://maps.googleapis.com/maps/api/place"
        self.results = []

    def search_places(self, query: str, location: str, radius_miles: int = 25) -> list:
        """Search Google Places for contractors"""

        # Convert miles to meters
        radius_meters = radius_miles * 1609

        # First, geocode the location
        geocode_url = f"https://maps.googleapis.com/maps/api/geocode/json"
        geo_params = {
            'address': location,
            'key': self.api_key
        }

        geo_response = requests.get(geocode_url, params=geo_params)
        geo_data = geo_response.json()

        if geo_data['status'] != 'OK':
            print(f"  Could not geocode location: {location}")
            return []

        lat = geo_data['results'][0]['geometry']['location']['lat']
        lng = geo_data['results'][0]['geometry']['location']['lng']

        # Search for places
        search_url = f"{self.base_url}/textsearch/json"
        params = {
            'query': f"{query} in {location}",
            'location': f"{lat},{lng}",
            'radius': radius_meters,
            'key': self.api_key
        }

        all_results = []
        next_page_token = None
        page = 1

        while True:
            if next_page_token:
                params['pagetoken'] = next_page_token
                time.sleep(2)  # Required delay for page tokens

            response = requests.get(search_url, params=params)
            data = response.json()

            if data['status'] not in ['OK', 'ZERO_RESULTS']:
                print(f"  API Error: {data.get('status')} - {data.get('error_message', '')}")
                break

            results = data.get('results', [])
            print(f"  Page {page}: Found {len(results)} results")

            for place in results:
                contractor = self.extract_place_data(place)
                if contractor:
                    all_results.append(contractor)

            next_page_token = data.get('next_page_token')
            if not next_page_token:
                break

            page += 1
            if page > 3:  # Max 60 results (3 pages of 20)
                break

        return all_results

    def extract_place_data(self, place: dict) -> dict:
        """Extract relevant data from a place result"""

        place_id = place.get('place_id')

        # Get detailed info (includes phone, website)
        details = self.get_place_details(place_id)

        if not details:
            return None

        # Extract phone number
        phone = details.get('formatted_phone_number', '')
        phone_clean = re.sub(r'[^\d]', '', phone)

        # Skip if no phone (can't contact them)
        if not phone_clean:
            return None

        return {
            'name': place.get('name', ''),
            'phone': phone,
            'phone_clean': phone_clean,
            'address': place.get('formatted_address', ''),
            'city': self.extract_city(place.get('formatted_address', '')),
            'rating': place.get('rating', 0),
            'reviews': place.get('user_ratings_total', 0),
            'website': details.get('website', ''),
            'email': self.extract_email_from_website(details.get('website', '')),
            'place_id': place_id,
            'types': ', '.join(place.get('types', [])),
            'scraped_at': datetime.now().isoformat()
        }

    def get_place_details(self, place_id: str) -> dict:
        """Get detailed place information"""

        url = f"{self.base_url}/details/json"
        params = {
            'place_id': place_id,
            'fields': 'formatted_phone_number,website,opening_hours',
            'key': self.api_key
        }

        response = requests.get(url, params=params)
        data = response.json()

        if data['status'] == 'OK':
            return data.get('result', {})
        return {}

    def extract_city(self, address: str) -> str:
        """Extract city from address string"""
        parts = address.split(',')
        if len(parts) >= 2:
            return parts[-3].strip() if len(parts) >= 3 else parts[0].strip()
        return ''

    def extract_email_from_website(self, website: str) -> str:
        """Try to find email on website (basic scrape)"""
        if not website:
            return ''

        try:
            headers = {'User-Agent': 'Mozilla/5.0'}
            response = requests.get(website, headers=headers, timeout=5)

            # Find email patterns
            emails = re.findall(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', response.text)

            # Filter out common non-business emails
            ignore = ['example.com', 'email.com', 'domain.com', 'yoursite.com', 'sentry.io']
            emails = [e for e in emails if not any(x in e.lower() for x in ignore)]

            return emails[0] if emails else ''
        except:
            return ''

    def save_to_csv(self, results: list, filename: str):
        """Save results to CSV file"""

        if not results:
            print("No results to save")
            return

        output_dir = Path(__file__).parent / 'output'
        output_dir.mkdir(exist_ok=True)

        filepath = output_dir / filename

        fieldnames = ['name', 'phone', 'phone_clean', 'email', 'city', 'address',
                      'rating', 'reviews', 'website', 'types', 'scraped_at']

        with open(filepath, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
            writer.writeheader()
            writer.writerows(results)

        print(f"\nSaved {len(results)} contractors to: {filepath}")
        return filepath

    def save_to_json(self, results: list, filename: str):
        """Save results to JSON file"""

        output_dir = Path(__file__).parent / 'output'
        output_dir.mkdir(exist_ok=True)

        filepath = output_dir / filename

        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2)

        print(f"Saved {len(results)} contractors to: {filepath}")
        return filepath


def main():
    parser = argparse.ArgumentParser(description='Scrape contractors from Google Maps')
    parser.add_argument('query', nargs='?', default='sod installation',
                        help='Search query (e.g., "sod installation", "landscaping")')
    parser.add_argument('location', nargs='?',
                        help='City/location to search (e.g., "Jacksonville, FL")')
    parser.add_argument('--file', '-f', help='File with list of cities (one per line)')
    parser.add_argument('--radius', '-r', type=int, default=25,
                        help='Search radius in miles (default: 25)')
    parser.add_argument('--output', '-o', default='contractors',
                        help='Output filename prefix (default: contractors)')

    args = parser.parse_args()

    # Get API key
    api_key = get_api_key()
    if not api_key:
        print("ERROR: Google Places API key not found!")
        print("\nTo set up:")
        print("1. Get an API key from: https://console.cloud.google.com/apis/credentials")
        print("2. Enable 'Places API' and 'Geocoding API'")
        print("3. Either:")
        print("   - Set environment variable: export GOOGLE_PLACES_API_KEY=your_key")
        print("   - Or create config.json with: {\"google_places_api_key\": \"your_key\"}")
        sys.exit(1)

    scraper = ContractorScraper(api_key)
    all_results = []

    # Determine cities to search
    cities = []
    if args.file:
        with open(args.file) as f:
            cities = [line.strip() for line in f if line.strip()]
    elif args.location:
        cities = [args.location]
    else:
        # Default Florida cities
        cities = [
            "Jacksonville, FL",
            "St Augustine, FL",
            "Orange Park, FL",
            "Fleming Island, FL",
            "Ponte Vedra, FL",
            "Neptune Beach, FL",
            "Atlantic Beach, FL",
            "Fernandina Beach, FL",
            "Middleburg, FL",
            "Green Cove Springs, FL"
        ]
        print(f"No location specified, using default Florida cities")

    print(f"\nSearching for: {args.query}")
    print(f"Cities: {len(cities)}")
    print(f"Radius: {args.radius} miles\n")
    print("-" * 50)

    for city in cities:
        print(f"\n[{city}]")
        results = scraper.search_places(args.query, city, args.radius)

        # Add city to results for tracking
        for r in results:
            r['search_city'] = city

        all_results.extend(results)

        # Rate limiting
        time.sleep(0.5)

    # Deduplicate by phone number
    seen_phones = set()
    unique_results = []
    for r in all_results:
        if r['phone_clean'] not in seen_phones:
            seen_phones.add(r['phone_clean'])
            unique_results.append(r)

    print("\n" + "-" * 50)
    print(f"\nTotal found: {len(all_results)}")
    print(f"Unique (by phone): {len(unique_results)}")

    # Save results
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    csv_file = scraper.save_to_csv(unique_results, f"{args.output}_{timestamp}.csv")
    scraper.save_to_json(unique_results, f"{args.output}_{timestamp}.json")

    # Print summary
    if unique_results:
        print("\n" + "=" * 50)
        print("TOP 10 BY REVIEWS:")
        print("=" * 50)
        sorted_results = sorted(unique_results, key=lambda x: x['reviews'], reverse=True)[:10]
        for r in sorted_results:
            print(f"  {r['name'][:30]:<30} | {r['phone']:<14} | {r['reviews']} reviews")


if __name__ == '__main__':
    main()
