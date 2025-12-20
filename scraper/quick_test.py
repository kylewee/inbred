#!/usr/bin/env python3
"""
Quick test version of charm scraper that generates realistic data
without hitting charm.li repeatedly for development purposes
"""

import json
import sys
import os
sys.path.append(os.path.dirname(__file__))
from charm_scraper import CharmScraper

def main():
    scraper = CharmScraper()
    
    # Define common vehicles to generate data for
    common_vehicles = [
        {'year': 2020, 'make': 'Honda', 'model': 'Civic'},
        {'year': 2018, 'make': 'Ford', 'model': 'F-150'},
        {'year': 2019, 'make': 'Toyota', 'model': 'Camry'},
        {'year': 2017, 'make': 'Chevrolet', 'model': 'Silverado'},
        {'year': 2021, 'make': 'Nissan', 'model': 'Altima'},
        {'year': 2016, 'make': 'BMW', 'model': '320i'},
        {'year': 2015, 'make': 'Mercedes', 'model': 'C300'},
    ]
    
    print("Generating realistic demo data for development...")
    
    # Generate data quickly without network calls
    scraped_data = []
    for vehicle in common_vehicles:
        print(f"Generating data for {vehicle['year']} {vehicle['make']} {vehicle['model']}...")
        data = scraper.generate_demo_data(vehicle['year'], vehicle['make'], vehicle['model'], enhanced=True)
        scraped_data.append(data)
    
    # Save the data
    scraper.save_data(scraped_data, 'charm_data.json')
    
    print(f"Demo data generation complete! Created data for {len(scraped_data)} vehicles.")
    
    # Show a sample
    if scraped_data:
        sample = scraped_data[0]
        print(f"\nSample data for {sample['vehicle']['year']} {sample['vehicle']['make']} {sample['vehicle']['model']}:")
        for repair_name, repair_data in list(sample['repairs'].items())[:3]:
            parts_cost = sum(part['price'] for part in repair_data['parts'])
            print(f"  {repair_name}: ${parts_cost:.2f} parts + {repair_data['labor_time']}hrs labor")

if __name__ == "__main__":
    main()