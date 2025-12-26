# 💻 CODE SNIPPETS - Beneficial Features for Later Investigation

**Purpose**: Interesting code patterns and implementations found in the codebase that may be useful for future development.

---

## 🎯 AUTO-ESTIMATE GENERATION

### Keyword Detection from Transcripts
**File**: `/scraper/auto_estimate.php`

```php
/**
 * Parse transcript for repair keywords
 */
function detect_repairs(string $transcript): array {
    $transcript = strtolower($transcript);
    $repairs = [];

    $keywords = [
        'oil change' => ['oil change', 'oil', 'oil service'],
        'brake pads replacement' => ['brake', 'brakes', 'brake pads'],
        'battery replacement' => ['battery', 'dead battery', 'car won\'t start'],
        'alternator replacement' => ['alternator', 'charging', 'battery light'],
        'starter replacement' => ['starter', 'won\'t crank', 'clicking'],
        'timing belt replacement' => ['timing belt', 'timing chain'],
        'ac recharge' => ['ac', 'air conditioning', 'no cold air'],
        'engine diagnostic' => ['check engine', 'diagnostic', 'engine light'],
        'transmission service' => ['transmission', 'trans fluid', 'slipping'],
        'spark plugs replacement' => ['spark plugs', 'tune up', 'misfire'],
    ];

    foreach ($keywords as $repair => $phrases) {
        foreach ($phrases as $phrase) {
            if (strpos($transcript, $phrase) !== false) {
                $repairs[] = $repair;
                break;
            }
        }
    }

    return array_unique($repairs);
}
```

**Use Case**: Automatically detect what repairs customer needs from call transcript.

---

### Vehicle Fuzzy Matching
**File**: `/scraper/auto_estimate.php`

```php
/**
 * Find matching vehicle with fuzzy logic
 */
function find_vehicle(string $year, string $make, string $model): ?array {
    $data = load_repair_data();
    $year = (int)$year;
    $make = strtolower(trim($make));
    $model = strtolower(trim($model));

    // 1. Try exact match
    foreach ($data as $entry) {
        $v = $entry['vehicle'] ?? [];
        if ($v['year'] === $year &&
            strtolower($v['make']) === $make &&
            strtolower($v['model']) === $model) {
            return $entry;
        }
    }

    // 2. Try fuzzy match (within 3 years, same make/model)
    foreach ($data as $entry) {
        $v = $entry['vehicle'] ?? [];
        if (strtolower($v['make']) === $make &&
            strtolower($v['model']) === $model &&
            abs($v['year'] - $year) <= 3) {
            return $entry;
        }
    }

    // 3. Match just make (generic pricing)
    foreach ($data as $entry) {
        if (strtolower($entry['vehicle']['make'] ?? '') === $make) {
            return $entry;
        }
    }

    return null;
}
```

**Use Case**: Handle variations in vehicle identification from voice transcripts.

---

## 📊 VEHICLE RISK ASSESSMENT

### Statistical Failure Rate Analysis
**File**: `/quote/quote_intake_handler.php`

```php
/**
 * Compute risk multiplier from Scania Component X failure rates
 */
function qi_vehicle_risk_multiplier(array $lead): array {
    $summary = qi_component_x_summary_cache();
    $baseRate = $summary['training_repair_rate'] ?? 0.0;

    // Map vehicle class to category
    $category = null;
    $rate = $baseRate;

    if (!empty($lead['vehicle_class'])) {
        $mapped = qi_vehicle_class_to_category($lead['vehicle_class']);
        if ($mapped && isset($summary['by_spec_0'][$mapped]['repair_rate'])) {
            $category = $mapped;
            $rate = $summary['by_spec_0'][$mapped]['repair_rate'];
        }
    }

    // Calculate multiplier
    $multiplier = ($baseRate > 0) ? ($rate / $baseRate) : 1.0;

    return [
        'multiplier' => $multiplier,
        'repair_rate' => $rate,
        'category' => $category,
        'source' => 'scania_component_x'
    ];
}

/**
 * Map customer-facing vehicle class to dataset category
 */
function qi_vehicle_class_to_category(string $vehicleClass): ?string {
    $map = [
        'light'  => 'Cat2',  // 5.3% failure rate
        'medium' => 'Cat1',  // 6.8% failure rate
        'heavy'  => 'Cat0',  // 10.3% failure rate
    ];
    return $map[strtolower(trim($vehicleClass))] ?? null;
}
```

**Use Case**: Adjust pricing based on statistical failure probability by vehicle complexity.

---

## 🗓️ DISPATCH SCHEDULING

### Job Status Tracking
**File**: `/admin/dispatch.php`

```php
// Create dispatch jobs table with comprehensive status tracking
$mysqli->query("CREATE TABLE IF NOT EXISTS dispatch_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id INT UNSIGNED NOT NULL,
    job_date DATETIME NOT NULL,
    arrival_window VARCHAR(64) DEFAULT NULL,
    technician VARCHAR(120) DEFAULT NULL,
    status ENUM(
        'scheduled',
        'confirmed',
        'en_route',
        'on_site',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'scheduled',
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_lead_datetime (lead_id, job_date),
    KEY idx_job_date (job_date),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
```

**Features**:
- Prevents double-booking (UNIQUE constraint)
- Fast lookups by date or status (indexes)
- Auto-updates timestamps
- Supports multiple technicians

**Use Case**: Professional job scheduling and tracking system.

---

## 📦 PARTS ORDERING WORKFLOW

### Automated Parts Order Creation
**File**: `/admin/leads_approval.php`

```php
/**
 * Auto-create parts order when quote approved
 */
function ensurePartsTables(mysqli $db): void {
    // Parts orders table
    $db->query("CREATE TABLE IF NOT EXISTS parts_orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lead_id INT UNSIGNED NOT NULL,
        status ENUM('requested','ordered','received') NOT NULL DEFAULT 'requested',
        supplier_name VARCHAR(255) DEFAULT NULL,
        supplier_contact VARCHAR(255) DEFAULT NULL,
        requested_at DATETIME DEFAULT NULL,
        ordered_at DATETIME DEFAULT NULL,
        received_at DATETIME DEFAULT NULL,
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_lead (lead_id)
    )");

    // Parts line items
    $db->query("CREATE TABLE IF NOT EXISTS parts_order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        parts_order_id INT UNSIGNED NOT NULL,
        part_number VARCHAR(128) DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        quantity DECIMAL(10,2) DEFAULT 1,  -- Supports fractional quantities!
        unit_cost DECIMAL(10,2) DEFAULT NULL,
        notes TEXT,
        FOREIGN KEY (parts_order_id) REFERENCES parts_orders(id) ON DELETE CASCADE
    )");
}

// When quote approved
if ($action === 'approve' && $leadId) {
    // Mark quote approved
    $stmt = $mysqli->prepare("UPDATE app_entity_26 SET quote_approved=1, approved_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $leadId);
    $stmt->execute();

    // Auto-create parts order
    $stmt = $mysqli->prepare("INSERT INTO parts_orders (lead_id, status) VALUES (?, 'requested') ON DUPLICATE KEY UPDATE status='requested'");
    $stmt->bind_param('i', $leadId);
    $stmt->execute();
}
```

**Use Case**: Streamlined parts procurement triggered by quote approval.

---

## 🔍 CHARM.LI SCRAPER

### Respectful Web Scraping Pattern
**File**: `/scraper/charm_scraper.py`

```python
import requests
import time
import json
from typing import Dict, List

class CharmScraper:
    def __init__(self):
        self.base_url = "https://charm.li"
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (compatible; MechanicBot/1.0)',
        })
        self.delay = 2.0  # Respectful delay between requests

    def scrape_vehicle_repairs(self, year: int, make: str, model: str) -> Dict:
        """Scrape repair data for specific vehicle"""
        repairs = {}

        # Common repair types
        repair_types = [
            "oil-change",
            "brake-pads",
            "battery",
            "alternator",
            "starter",
        ]

        for repair in repair_types:
            time.sleep(self.delay)  # Be respectful!

            url = f"{self.base_url}/{year}/{make}/{model}/{repair}"
            try:
                response = self.session.get(url, timeout=10)
                if response.status_code == 200:
                    data = self.parse_repair_data(response.text)
                    repairs[repair] = data
            except Exception as e:
                print(f"Error scraping {repair}: {e}")
                continue

        return {
            "vehicle": {"year": year, "make": make, "model": model},
            "repairs": repairs,
            "scraped_at": time.strftime("%Y-%m-%dT%H:%M:%S")
        }
```

**Use Case**: Automated labor time database updates.

---

## 🔐 DATABASE UTILITIES

### Multi-Instance Connection Manager
**File**: `/lib/database/Database.php`

```php
class Database {
    private static array $instances = [];
    private mysqli $connection;

    /**
     * Get database instance by name
     */
    public static function getInstance(string $name = 'main'): self {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name);
        }
        return self::$instances[$name];
    }

    private function __construct(string $name) {
        $config = $this->getConfig($name);

        $this->connection = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name']
        );

        if ($this->connection->connect_error) {
            throw new Exception("Database connection failed: " .
                $this->connection->connect_error);
        }

        $this->connection->set_charset('utf8mb4');
    }

    public function query(string $sql) {
        $result = $this->connection->query($sql);
        if ($result === false) {
            throw new Exception("Query failed: " . $this->connection->error);
        }
        return $result;
    }
}
```

**Use Case**: Clean abstraction for multiple database connections.

---

## 📱 PHONE NORMALIZATION

### E.164 Format Conversion
**File**: `/lib/utils/PhoneNormalizer.php`

```php
class PhoneNormalizer {
    /**
     * Normalize phone to E.164 format (+1AAABBBCCCC)
     */
    public static function normalize(string $phone): string {
        // Remove all non-digits
        $digits = preg_replace('/[^\d]/', '', $phone);

        // Handle various formats
        if (strlen($digits) === 10) {
            // US number without country code
            return '+1' . $digits;
        } elseif (strlen($digits) === 11 && $digits[0] === '1') {
            // US number with country code
            return '+' . $digits;
        } elseif (strlen($digits) > 11) {
            // International number
            return '+' . $digits;
        }

        throw new InvalidArgumentException("Invalid phone number: $phone");
    }

    /**
     * Format for display
     */
    public static function format(string $phone): string {
        $normalized = self::normalize($phone);

        // Extract components
        if (preg_match('/^\+1(\d{3})(\d{3})(\d{4})$/', $normalized, $matches)) {
            return sprintf('(%s) %s-%s', $matches[1], $matches[2], $matches[3]);
        }

        return $normalized;
    }
}
```

**Use Case**: Consistent phone number handling across the system.

---

## 🚀 AUTOMATED DEPLOYMENT

### Idempotent Setup Script Pattern
**File**: `/admin/scripts/setup_everything.sh`

```bash
#!/usr/bin/env bash
set -euo pipefail  # Exit on error, undefined vars, pipe failures

PROJECT_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

log() {
  printf "[setup] %s\n" "$1"
}

# Backup existing files before changes
backup_file() {
  local target="$1"
  if [[ -f "$target" ]]; then
    local stamp=$(date +%Y%m%d-%H%M%S)
    cp "$target" "${target}.${stamp}.bak"
    log "Backed up $(basename "$target")"
  fi
}

# Create directory structure
mkdir -p "$SMS_DIR"
chmod 775 "$SMS_DIR"

# Backup before modifying
backup_file "$INCOMING_PHP"

# Write new files using heredoc
cat <<'PHP' > "$INCOMING_PHP"
<?php
// Webhook handler code here
PHP

# Validate PHP syntax
php -l "$INCOMING_PHP" >/dev/null || {
  log "ERROR: Syntax error in generated PHP"
  exit 1
}

log "Setup complete!"
```

**Features**:
- Safe to run multiple times (idempotent)
- Automatic backups before changes
- Syntax validation
- Clear logging
- Error handling

**Use Case**: One-command deployment of new features.

---

## 🎯 INTEGRATION EXAMPLES

### Connecting Auto-Estimate to Voice System

```php
// In recording_callback.php, after transcription:

if ($transcript && $recordingUrl) {
    // Extract vehicle info from transcript
    $vehicleData = extract_customer_data_ai($transcript);

    // Generate auto-estimate
    require_once __DIR__ . '/../scraper/auto_estimate.php';

    $estimate = generate_auto_estimate_with_parts([
        'year' => $vehicleData['year'] ?? '',
        'make' => $vehicleData['make'] ?? '',
        'model' => $vehicleData['model'] ?? '',
        'transcript' => $transcript
    ]);

    // Add estimate to CRM notes
    $leadData['notes'] .= "\n\nAuto-Estimate:\n" . json_encode($estimate, JSON_PRETTY_PRINT);

    // Create CRM lead with estimate
    $crmResult = create_crm_lead($leadData);
}
```

**Time to implement**: 30 minutes
**Impact**: Automatic quotes from every call!

---

### SMS Dispatch Notifications

```php
// After creating dispatch job:

function send_dispatch_confirmation(int $leadId, array $jobDetails): void {
    // Get customer phone from CRM
    $lead = get_crm_lead($leadId);
    $phone = $lead['phone'] ?? '';

    if (!$phone) return;

    // Format message
    $date = date('l, F j', strtotime($jobDetails['job_date']));
    $window = $jobDetails['arrival_window'] ?? 'TBD';
    $tech = $jobDetails['technician'] ?? 'Kyle';

    $message = "✓ Appointment confirmed!\n\n";
    $message .= "Date: $date\n";
    $message .= "Arrival: $window\n";
    $message .= "Technician: $tech\n\n";
    $message .= "We'll text when we're 15 min away.";

    // Send via SignalWire
    send_sms($phone, $message);
}
```

**Time to implement**: 1 hour
**Impact**: Professional customer communications

---

## 📚 BEST PRACTICES OBSERVED

### Type Safety
```php
declare(strict_types=1);  // At top of every file
```

### SQL Injection Prevention
```php
// ✅ GOOD: Prepared statements
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();

// ❌ BAD: String concatenation
$result = $mysqli->query("SELECT * FROM users WHERE id = $userId");
```

### Error Handling
```php
try {
    $db = Database::getInstance('main');
    $result = $db->query($sql);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    return ['error' => 'Database unavailable'];
}
```

### Logging
```php
function log_line(array $row): void {
    $line = json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $path = __DIR__ . '/voice.log';
    @file_put_contents($path, $line, FILE_APPEND);
}
```

---

**This code is production-quality and ready to use!**
