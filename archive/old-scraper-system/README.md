# Charm.li Scraper for Mechanic Saint Augustine

This directory contains the web scraping system for integrating real-time parts costs and labor times from charm.li into the Mechanic Saint Augustine pricing system.

## Files

### `charm_scraper.py`
Main scraping script that extracts parts and labor data from charm.li for common vehicle repairs.

**Features:**
- Scrapes parts costs and labor times for multiple vehicle makes/models
- Respectful scraping with delays to avoid server overload
- Structured data output with repair categories
- Error handling and data validation

**Usage:**
```bash
python3 charm_scraper.py
```

### `integrate_data.py`
Data integration utility that merges scraped charm.li data with the existing price-catalog.json.

**Features:**
- Calculates average parts costs across vehicle models
- Integrates labor time estimates from charm.li
- Creates enhanced pricing catalog with real-time data
- Generates PHP API endpoint for enhanced pricing

**Usage:**
```bash
python3 integrate_data.py
```

### Generated Files

- `charm_data.json` - Raw scraped data from charm.li
- `../price-catalog-enhanced.json` - Enhanced price catalog with integrated data
- `../api/enhanced_pricing.php` - API endpoint for enhanced pricing

## Integration Workflow

1. **Scrape Data**: Run `charm_scraper.py` to collect current parts/labor data
2. **Integrate**: Run `integrate_data.py` to merge with existing pricing
3. **Update Website**: Enhanced data automatically available through API

## Data Structure

### Scraped Data Format
```json
{
  "vehicle": {"year": 2020, "make": "Honda", "model": "Civic"},
  "repairs": {
    "Oil Change": {
      "parts": [
        {"name": "Oil Filter", "price": 12.99, "part_number": "PF123"}
      ],
      "labor_time": 0.5,
      "labor_complexity": "Basic"
    }
  },
  "scraped_at": "2024-01-15T10:30:00"
}
```

### Enhanced Catalog Format
```json
{
  "name": "Oil Change",
  "base_price": 45.00,
  "charm_parts_cost": 37.98,
  "charm_labor_time": 0.5,
  "pricing_source": "charm.li + calculated",
  "last_updated": "2024-01-15T10:30:00"
}
```

## Configuration

The scraper can be customized by modifying:

- **Vehicle List**: Edit `common_vehicles` in `charm_scraper.py`
- **Repair Types**: Modify `get_common_repairs()` method
- **Labor Rate**: Adjust `labor_rate` in `integrate_data.py` 
- **Multipliers**: Update multiplier values for V8/old cars

## Error Handling

- Network timeouts and retry logic
- Graceful degradation if charm.li is unavailable
- Fallback to static pricing when enhanced data missing
- Comprehensive logging for debugging

## Scheduling

For production use, consider scheduling regular scraping:

```bash
# Add to crontab for daily updates
0 6 * * * cd /path/to/scraper && python3 charm_scraper.py && python3 integrate_data.py
```

## Dependencies

- Python 3.6+
- `requests` library for HTTP requests
- Standard library modules: `json`, `time`, `datetime`, `os`

Install dependencies:
```bash
pip3 install requests
```