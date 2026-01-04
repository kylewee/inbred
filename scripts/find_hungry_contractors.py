#!/usr/bin/env python3
"""
Find Hungry Contractors - Page 2-3 of Google
The ones who NEED leads because they're not ranking well.

Usage:
    python3 find_hungry_contractors.py

Edit the LOCATIONS and SEARCH_TERM variables below.
"""

import requests
import time
import json
import csv
from datetime import datetime

# ============================================================
# CONFIGURE THESE
# ============================================================

# Pick ONE or add your own
SEARCH_TERMS = [
    # Home Services
    "landscaper",
    "lawn care",
    "tree service",
    "pressure washing",
    "pool cleaning",
    "house cleaning",
    "maid service",
    "handyman",
    "junk removal",
    "moving company",

    # Auto
    "mobile mechanic",
    "auto repair",
    "tow truck",
    "mobile detailing",
    "windshield repair",

    # Trades
    "plumber",
    "electrician",
    "hvac",
    "roofer",
    "painter",
    "carpet cleaner",
    "pest control",
    "locksmith",

    # Other
    "dog groomer",
    "pet sitter",
    "photographer",
    "dj services",
    "catering",
]

# >>> PICK WHICH ONES TO SEARCH <<<
ACTIVE_SEARCHES = [
    "landscaper",
    "lawn care",
    "tree service",
    # "mobile mechanic",
    # Add more from list above...
]

LOCATIONS = [
    # Add your zip codes or city names here
    "32080",           # St. Augustine Beach
    "32084",           # St. Augustine
    "32095",           # St. Augustine area
    "32259",           # St. Johns
    "Jacksonville FL",
    "Palm Coast FL",
    "Ponte Vedra FL",
    # Add more...
]

# Which pages to scrape (page 1 = results 0-9, page 2 = 10-19, etc)
START_PAGE = 2  # Start at page 2
END_PAGE = 3    # End at page 3

OUTPUT_FILE = "hungry_contractors.csv"

# ============================================================
# SCRIPT - Don't edit below unless you know what you're doing
# ============================================================

def search_google(query, start=0):
    """
    Search Google and return results.
    Uses SerpAPI or falls back to a simple scrape.

    For production, get a SerpAPI key: https://serpapi.com
    """

    # Option 1: Use SerpAPI (recommended - $50/mo for 5000 searches)
    SERPAPI_KEY = ""  # Add your key here

    if SERPAPI_KEY:
        url = "https://serpapi.com/search"
        params = {
            "q": query,
            "start": start,
            "num": 10,
            "api_key": SERPAPI_KEY,
        }
        resp = requests.get(url, params=params)
        data = resp.json()

        results = []
        for r in data.get("organic_results", []):
            results.append({
                "title": r.get("title", ""),
                "link": r.get("link", ""),
                "snippet": r.get("snippet", ""),
            })

        # Also get local pack results (Google Maps listings)
        for r in data.get("local_results", {}).get("places", []):
            results.append({
                "title": r.get("title", ""),
                "link": r.get("website", ""),
                "phone": r.get("phone", ""),
                "address": r.get("address", ""),
                "rating": r.get("rating", ""),
                "reviews": r.get("reviews", ""),
                "type": "local_pack",
            })

        return results

    # Option 2: Manual approach - just generate the search URLs
    # (Google blocks automated scraping, so we generate URLs for manual checking)
    else:
        search_url = f"https://www.google.com/search?q={requests.utils.quote(query)}&start={start}"
        return [{"manual_url": search_url, "start": start}]


def find_contractors():
    """Main function to find contractors in all locations."""

    all_results = []

    for search_term in ACTIVE_SEARCHES:
        for location in LOCATIONS:
            query = f"{search_term} {location}"
            print(f"\n🔍 Searching: {query}")

            for page in range(START_PAGE, END_PAGE + 1):
                start = (page - 1) * 10  # Google uses 0-indexed start
                print(f"   Page {page} (start={start})...")

                results = search_google(query, start)

                for r in results:
                    r["search_query"] = query
                    r["search_term"] = search_term
                    r["location"] = location
                    r["page"] = page
                    all_results.append(r)

                # Be nice to Google
                time.sleep(2)

    return all_results


def save_results(results):
    """Save results to CSV."""

    if not results:
        print("No results to save.")
        return

    # Get all unique keys
    keys = set()
    for r in results:
        keys.update(r.keys())
    keys = sorted(list(keys))

    with open(OUTPUT_FILE, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=keys)
        writer.writeheader()
        writer.writerows(results)

    print(f"\n✅ Saved {len(results)} results to {OUTPUT_FILE}")


def generate_manual_urls():
    """
    Generate Google search URLs for manual checking.
    Use this if you don't have SerpAPI.
    """

    print("\n" + "="*60)
    print("MANUAL SEARCH URLs")
    print("Open these in your browser, check page 2-3 results")
    print("="*60 + "\n")

    urls = []

    for search_term in ACTIVE_SEARCHES:
        print(f"\n--- {search_term.upper()} ---\n")

        for location in LOCATIONS:
            query = f"{search_term} {location}"

            for page in range(START_PAGE, END_PAGE + 1):
                start = (page - 1) * 10
                url = f"https://www.google.com/search?q={requests.utils.quote(query)}&start={start}"

                print(f"📍 {search_term} | {location} | Page {page}:")
                print(f"   {url}\n")

                urls.append({
                    "search_term": search_term,
                    "location": location,
                    "page": page,
                    "url": url,
                    "query": query,
                })

    # Save URLs to file
    with open("search_urls.txt", "w") as f:
        for u in urls:
            f.write(f"{u['search_term']} | {u['location']} | Page {u['page']}\n")
            f.write(f"{u['url']}\n\n")

    print(f"✅ URLs saved to search_urls.txt")

    return urls


def print_outreach_template():
    """Print creative outreach scripts that don't sound like cold callers."""

    print("\n" + "="*60)
    print("CREATIVE OUTREACH SCRIPTS")
    print("(Don't sound like every other lead gen caller)")
    print("="*60)
    print("""

🎯 THE GOLDEN RULE: Never pitch. Just offer work.

============================================================
OPENING LINES THAT DON'T GET HUNG UP ON
============================================================

❌ WRONG (sounds like a cold caller):
"Hi, I'm calling about lead generation services..."
"Would you like more customers?"
"I have a great opportunity for your business..."

✅ RIGHT (sounds like a customer):

FOR LANDSCAPERS:
"Hey, my neighbor just moved and their yard is a disaster.
 I told them I'd ask around - you guys do cleanups?"

"Quick question - I've got a rental property on [Street] that
 needs monthly maintenance. You taking new accounts?"

"Hey, weird question - know anyone who does tree removal?
 My buddy's got one leaning toward his house."

FOR MECHANICS:
"Hey, my sister's car won't start - sounds like the starter.
 You guys mobile? She's stuck at [Location]."

"Quick one - got a buddy with a 2012 F-150, brakes grinding bad.
 You do trucks?"

FOR PLUMBERS:
"Hey, my tenant's got a leak under the sink.
 You available today or tomorrow?"

FOR HANDYMAN:
"Got a weird one - need someone to mount a TV and fix a
 wobbly ceiling fan. You do small jobs like that?"

============================================================
AFTER THEY SAY YES
============================================================

Give them the lead info. Let them close the job.

THEN (after they've done the work):

"Hey, how'd that job go? ... Good, good.
 Listen, I get calls like that pretty often.
 Want me to send them your way when they come in?"

[If they ask about cost]:
"First 3 are free - test it out, see if they're good leads.
 After that, $35 per lead. Turn it on or off whenever you want.
 No contracts, no minimums. Just pay for what you use."

============================================================
TEXT/EMAIL VERSION
============================================================

SUBJECT: Quick job in [Location]?

"Hey [Name] - got someone in [area] who needs [service].
 Sounds like a quick one. You available?

 Let me know and I'll send their info."

---

FOLLOW UP (after job done):

"Hey, they said you did good work. I get a few of
 these a month - want me to keep you in mind?"

============================================================
KEY MINDSET
============================================================

You're not SELLING leads.
You're OFFERING work.

Big difference.

One sounds like: "Buy my product"
Other sounds like: "Got a job for you"

Contractors HATE salespeople.
Contractors LOVE work.

Be the second one.
""")


if __name__ == "__main__":
    print("🎣 HUNGRY CONTRACTOR FINDER")
    print("   Finding page 2-3 contractors who NEED leads\n")

    # Check if SerpAPI key is set
    SERPAPI_KEY = ""  # Set above

    if not SERPAPI_KEY:
        print("⚠️  No SerpAPI key - generating manual search URLs instead")
        print("   (Get a key at serpapi.com for automated scraping)\n")
        generate_manual_urls()
    else:
        results = find_contractors()
        save_results(results)

    print_outreach_template()

    print("\n🎣 Happy fishing!")
