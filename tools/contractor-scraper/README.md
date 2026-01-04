# Contractor Scraper

Find potential lead buyers from Google Maps. Pulls name, phone, email, address, rating, and reviews for contractors in your target cities.

## Setup

### 1. Get Google API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or use existing)
3. Enable these APIs:
   - **Places API**
   - **Geocoding API**
4. Go to Credentials → Create Credentials → API Key
5. Copy your API key

### 2. Configure API Key

Option A - Environment variable:
```bash
export GOOGLE_PLACES_API_KEY=your_api_key_here
```

Option B - Config file:
```bash
cp config.example.json config.json
# Edit config.json and add your API key
```

### 3. Install Dependencies

```bash
pip install -r requirements.txt
```

## Usage

### Single City Search

```bash
# Search for sod contractors in Jacksonville
python scraper.py "sod installation" "Jacksonville, FL"

# Search for landscapers with 30 mile radius
python scraper.py "landscaping" "St Augustine, FL" --radius 30

# Search for lawn care
python scraper.py "lawn care service" "Orange Park, FL"
```

### Multi-City Search

```bash
# Use the included cities file
python scraper.py --file cities.txt --query "sod installation"

# Use your own city list
python scraper.py -f my_cities.txt -q "landscaping"
```

### Default Search (No Arguments)

```bash
# Searches default Florida cities for "sod installation"
python scraper.py
```

## Output

Results are saved to `output/` folder:

- `contractors_YYYYMMDD_HHMMSS.csv` - CSV for spreadsheets
- `contractors_YYYYMMDD_HHMMSS.json` - JSON for importing

### CSV Columns

| Column | Description |
|--------|-------------|
| name | Business name |
| phone | Formatted phone number |
| phone_clean | Phone digits only (for dialing) |
| email | Email if found on website |
| city | City extracted from address |
| address | Full address |
| rating | Google rating (1-5) |
| reviews | Number of Google reviews |
| website | Business website |
| types | Google business categories |
| scraped_at | Timestamp |

## Import to Buyer System

After scraping, import to your buyer database:

```bash
# View results
cat output/contractors_*.csv

# Import to SQLite buyer database (example)
sqlite3 /home/kylewee/code/master-template/data/prospects.db <<EOF
.mode csv
.import output/contractors_latest.csv prospects
EOF
```

## API Costs

Google Places API pricing (as of 2024):

| API Call | Cost per 1,000 |
|----------|----------------|
| Text Search | $32.00 |
| Place Details | $17.00 |
| Geocoding | $5.00 |

**Estimated cost per city:** ~$0.50-1.00 (depending on results)

**Free tier:** $200/month credit = ~200-400 cities free

## Tips

1. **Start small** - Test with 1-2 cities first
2. **Dedupe** - Script removes duplicates by phone number
3. **Check reviews** - High review count = established business
4. **Look for website** - Easier to find email
5. **Morning scrapes** - Run before calling so data is fresh

## Troubleshooting

### "API key not found"
- Set environment variable or create config.json

### "OVER_QUERY_LIMIT"
- You've hit rate limits, wait 24 hours
- Or enable billing on Google Cloud

### "REQUEST_DENIED"
- API key doesn't have Places API enabled
- Check Google Cloud Console → APIs & Services

### No results for a city
- Try broader search terms
- Increase radius with `--radius 50`
- City name might need state (e.g., "Jacksonville, FL" not "Jacksonville")

## Example Workflow

```bash
# 1. Scrape contractors
python scraper.py "sod installation" --file cities.txt

# 2. Review output
head -20 output/contractors_*.csv

# 3. Sort by reviews (best prospects first)
sort -t',' -k7 -rn output/contractors_*.csv | head -20

# 4. Start calling!
```
