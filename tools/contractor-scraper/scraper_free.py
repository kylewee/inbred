#!/usr/bin/env python3
"""
Free Contractor Scraper - No API key required

Uses web scraping to find contractors. Slower but free.

Usage:
    python scraper_free.py "sod installation" "Jacksonville FL"
    python scraper_free.py "landscaping" "St Augustine FL"
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
from urllib.parse import quote_plus

try:
    import requests
except ImportError:
    os.system("pip install requests")
    import requests

try:
    from bs4 import BeautifulSoup
except ImportError:
    os.system("pip install beautifulsoup4")
    from bs4 import BeautifulSoup


class FreeContractorScraper:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
        })
        self.results = []

    def search_yelp(self, query: str, location: str) -> list:
        """Scrape Yelp for contractors"""
        results = []

        location_encoded = quote_plus(location)
        query_encoded = quote_plus(query)

        url = f"https://www.yelp.com/search?find_desc={query_encoded}&find_loc={location_encoded}"

        try:
            print(f"  Searching Yelp...")
            response = self.session.get(url, timeout=15)

            if response.status_code != 200:
                print(f"  Yelp returned status {response.status_code}")
                return results

            soup = BeautifulSoup(response.text, 'html.parser')

            # Find business cards
            businesses = soup.select('[data-testid="serp-ia-card"]')

            if not businesses:
                # Try alternative selector
                businesses = soup.select('.container__09f24__mpR8_ a[href*="/biz/"]')

            print(f"  Found {len(businesses)} Yelp results")

            for biz in businesses[:20]:  # Limit to first 20
                try:
                    # Extract name
                    name_elem = biz.select_one('a[href*="/biz/"]')
                    if not name_elem:
                        continue

                    name = name_elem.get_text(strip=True)
                    link = name_elem.get('href', '')

                    if '/biz/' not in link:
                        continue

                    # Get rating
                    rating = 0
                    rating_elem = biz.select_one('[aria-label*="star rating"]')
                    if rating_elem:
                        match = re.search(r'([\d.]+)\s*star', rating_elem.get('aria-label', ''))
                        if match:
                            rating = float(match.group(1))

                    # Get review count
                    reviews = 0
                    review_elem = biz.select_one('span:-soup-contains("reviews")')
                    if review_elem:
                        match = re.search(r'(\d+)', review_elem.get_text())
                        if match:
                            reviews = int(match.group(1))

                    results.append({
                        'name': name,
                        'phone': '',  # Need to visit detail page
                        'phone_clean': '',
                        'address': '',
                        'city': location,
                        'rating': rating,
                        'reviews': reviews,
                        'website': '',
                        'email': '',
                        'source': 'yelp',
                        'yelp_url': f"https://www.yelp.com{link}" if link.startswith('/') else link,
                        'scraped_at': datetime.now().isoformat()
                    })

                except Exception as e:
                    continue

        except Exception as e:
            print(f"  Yelp error: {e}")

        return results

    def search_yellowpages(self, query: str, location: str) -> list:
        """Scrape YellowPages for contractors"""
        results = []

        location_encoded = quote_plus(location.replace(',', '').replace(' ', '-').lower())
        query_encoded = quote_plus(query)

        url = f"https://www.yellowpages.com/search?search_terms={query_encoded}&geo_location_terms={location_encoded}"

        try:
            print(f"  Searching YellowPages...")
            response = self.session.get(url, timeout=15)

            if response.status_code != 200:
                print(f"  YellowPages returned status {response.status_code}")
                return results

            soup = BeautifulSoup(response.text, 'html.parser')

            # Find business listings
            businesses = soup.select('.result')

            print(f"  Found {len(businesses)} YellowPages results")

            for biz in businesses[:20]:
                try:
                    # Name
                    name_elem = biz.select_one('.business-name')
                    if not name_elem:
                        continue
                    name = name_elem.get_text(strip=True)

                    # Phone
                    phone = ''
                    phone_elem = biz.select_one('.phones')
                    if phone_elem:
                        phone = phone_elem.get_text(strip=True)

                    phone_clean = re.sub(r'[^\d]', '', phone)

                    # Skip if no phone
                    if not phone_clean:
                        continue

                    # Address
                    address = ''
                    addr_elem = biz.select_one('.street-address')
                    if addr_elem:
                        address = addr_elem.get_text(strip=True)

                    locality_elem = biz.select_one('.locality')
                    if locality_elem:
                        address += ', ' + locality_elem.get_text(strip=True)

                    # Website
                    website = ''
                    web_elem = biz.select_one('a.track-visit-website')
                    if web_elem:
                        website = web_elem.get('href', '')

                    # Rating
                    rating = 0
                    rating_elem = biz.select_one('.rating')
                    if rating_elem:
                        classes = rating_elem.get('class', [])
                        for c in classes:
                            if c.startswith('result-rating-'):
                                try:
                                    rating = float(c.replace('result-rating-', '').replace('-', '.'))
                                except:
                                    pass

                    results.append({
                        'name': name,
                        'phone': phone,
                        'phone_clean': phone_clean,
                        'address': address,
                        'city': location,
                        'rating': rating,
                        'reviews': 0,
                        'website': website,
                        'email': '',
                        'source': 'yellowpages',
                        'scraped_at': datetime.now().isoformat()
                    })

                except Exception as e:
                    continue

        except Exception as e:
            print(f"  YellowPages error: {e}")

        return results

    def search_bbb(self, query: str, location: str) -> list:
        """Scrape Better Business Bureau"""
        results = []

        # BBB uses different location format
        state_abbrev = ''
        match = re.search(r',\s*([A-Z]{2})', location)
        if match:
            state_abbrev = match.group(1).lower()

        city_name = location.split(',')[0].strip().lower().replace(' ', '-')

        query_encoded = quote_plus(query)
        url = f"https://www.bbb.org/search?find_country=USA&find_text={query_encoded}&find_loc={city_name}%2C%20{state_abbrev.upper()}&page=1"

        try:
            print(f"  Searching BBB...")
            response = self.session.get(url, timeout=15)

            if response.status_code != 200:
                print(f"  BBB returned status {response.status_code}")
                return results

            soup = BeautifulSoup(response.text, 'html.parser')

            # Find business listings
            businesses = soup.select('[data-testid="search-result"]')

            if not businesses:
                businesses = soup.select('.result-card')

            print(f"  Found {len(businesses)} BBB results")

            for biz in businesses[:15]:
                try:
                    # Name
                    name_elem = biz.select_one('h3, .result-name')
                    if not name_elem:
                        continue
                    name = name_elem.get_text(strip=True)

                    # Phone
                    phone = ''
                    phone_elem = biz.select_one('a[href^="tel:"]')
                    if phone_elem:
                        phone = phone_elem.get_text(strip=True)

                    phone_clean = re.sub(r'[^\d]', '', phone)

                    # Address
                    address = ''
                    addr_elem = biz.select_one('.result-address, address')
                    if addr_elem:
                        address = addr_elem.get_text(strip=True)

                    results.append({
                        'name': name,
                        'phone': phone,
                        'phone_clean': phone_clean,
                        'address': address,
                        'city': location,
                        'rating': 0,
                        'reviews': 0,
                        'website': '',
                        'email': '',
                        'source': 'bbb',
                        'scraped_at': datetime.now().isoformat()
                    })

                except Exception as e:
                    continue

        except Exception as e:
            print(f"  BBB error: {e}")

        return results

    def search_all(self, query: str, location: str) -> list:
        """Search all sources"""
        all_results = []

        # Search each source with delays
        all_results.extend(self.search_yellowpages(query, location))
        time.sleep(2)

        all_results.extend(self.search_yelp(query, location))
        time.sleep(2)

        all_results.extend(self.search_bbb(query, location))

        return all_results

    def save_to_csv(self, results: list, filename: str):
        """Save results to CSV"""
        if not results:
            print("No results to save")
            return None

        output_dir = Path(__file__).parent / 'output'
        output_dir.mkdir(exist_ok=True)

        filepath = output_dir / filename

        fieldnames = ['name', 'phone', 'phone_clean', 'email', 'city', 'address',
                      'rating', 'reviews', 'website', 'source', 'scraped_at']

        with open(filepath, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
            writer.writeheader()
            writer.writerows(results)

        print(f"\nSaved {len(results)} contractors to: {filepath}")
        return filepath


def main():
    parser = argparse.ArgumentParser(description='Free contractor scraper (no API key)')
    parser.add_argument('query', nargs='?', default='sod installation',
                        help='Search query')
    parser.add_argument('location', nargs='?', default='Jacksonville, FL',
                        help='City/location')
    parser.add_argument('--file', '-f', help='File with list of cities')
    parser.add_argument('--output', '-o', default='contractors_free',
                        help='Output filename prefix')

    args = parser.parse_args()

    scraper = FreeContractorScraper()
    all_results = []

    # Determine cities
    cities = []
    if args.file:
        with open(args.file) as f:
            cities = [line.strip() for line in f if line.strip()]
    else:
        cities = [args.location]

    print(f"\nSearching for: {args.query}")
    print(f"Cities: {len(cities)}")
    print("-" * 50)

    for city in cities:
        print(f"\n[{city}]")
        results = scraper.search_all(args.query, city)

        for r in results:
            r['search_city'] = city

        all_results.extend(results)
        time.sleep(3)  # Be nice to servers

    # Deduplicate by phone
    seen_phones = set()
    unique_results = []
    for r in all_results:
        if r['phone_clean'] and r['phone_clean'] not in seen_phones:
            seen_phones.add(r['phone_clean'])
            unique_results.append(r)
        elif not r['phone_clean']:
            # Include even without phone (might get from Yelp detail page)
            unique_results.append(r)

    print("\n" + "-" * 50)
    print(f"Total found: {len(all_results)}")
    print(f"With phone: {len([r for r in unique_results if r['phone_clean']])}")

    # Save
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    scraper.save_to_csv(unique_results, f"{args.output}_{timestamp}.csv")

    # Print summary
    if unique_results:
        print("\n" + "=" * 50)
        print("CONTRACTORS WITH PHONE NUMBERS:")
        print("=" * 50)
        has_phone = [r for r in unique_results if r['phone_clean']][:15]
        for r in has_phone:
            print(f"  {r['name'][:35]:<35} | {r['phone']:<14} | {r['source']}")


if __name__ == '__main__':
    main()
