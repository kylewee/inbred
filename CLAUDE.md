# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is a mobile mechanic service platform integrating:
- **SignalWire phone system** (VoIP calls, SMS messaging via 10DLC)
- **OpenAI Whisper** (call transcription and AI customer data extraction)
- **Rukovoditel CRM** (lead and customer management)
- **Go REST API backend** (modern API with JWT auth)
- **PHP web services** (voice webhooks, quote intake, admin tools)

## Critical Configuration Policy

**NEVER delete configuration lines** - Always comment them out with `//` or `#` instead. This preserves historical context and makes rollback easier.

**Always update master documentation** when changing configuration:
- `MASTER_CONFIG_DOCUMENT.md` - Single source of truth for all configuration
- `SERVICE_STATUS.md` - Track service status changes
- `TRANSCRIPTION_SYSTEM_STATUS.md` - Voice system status

## Common Development Commands

### PHP Voice & Web System

```bash
# Test voice workflow
php test_workflow.php

# Test SignalWire setup
php test_signalwire_setup.php

# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# Reload PHP-FPM after config changes (ALWAYS after .env.local.php edits)
sudo systemctl reload php8.3-fpm

# Restart if reload doesn't work
sudo systemctl restart php8.3-fpm

# Check PHP configuration
php -i | grep -E "display_errors|error_reporting"
php -l voice/recording_callback.php  # Syntax check
```

### Current Phone System Configuration (2025-12-10)

**SignalWire Setup:**
- **Space**: mobilemechanic.signalwire.com
- **Project ID**: 106de0be-255e-4fe3-9072-88bf1d5cdfc2
- **Business Number**: +19042175152 (Google Voice)
- **Forwarding Chain**: +19042175152 → +19046634789 (direct cell)

**SignalWire Call Handler Configuration:**
- **Call Handler Script**: https://mechanicstaugustine.com/voice/incoming.php
- **Status Change Webhook**: https://mechanicstaugustine.com/voice/recording_callback.php
- **Hangup Handler**: https://mechanicstaugustine.com/voice/hangup.php

**SignalWire Call Handler Configuration:**
- **Call Handler Script**: https://mechanicstaugustine.com/voice/incoming.php
- **Status Change Webhook**: https://mechanicstaugustine.com/voice/recording_callback.php
- **Hangup Handler**: https://mechanicstaugustine.com/voice/hangup.php

**SignalWire Call Handler Configuration:**
- **Call Handler Script**: https://mechanicstaugustine.com/voice/incoming.php
- **Status Change Webhook**: https://mechanicstaugustine.com/voice/recording_callback.php
- **Hangup Handler**: https://mechanicstaugustine.com/voice/hangup.php
- **Recording**: All calls recorded (record="true")
- **Forwarding**: To NaturalWire (+1904706669) then to cell (+19046634789)
- **CRM Integration**: kylewee2 / R0ckS0l!d (admin credentials)
- **Recording**: All calls recorded via SignalWire
- **CRM Integration**: kylewee2 / R0ckS0l!d (admin credentials)

**Call Flow:**
1. Customer calls +19042175152
2. Google Voice forwards to +19046634789 (direct cell)
3. SignalWire records entire conversation
4. AI transcribes and extracts customer data
5. CRM lead created automatically
6. SMS confirmation sent

**Key Files:**
- `/voice/incoming.php` - Call handling TwiML
- `/voice/recording_callback.php` - Recording processing and CRM integration
- `/voice/hangup.php` - Call termination
- `/api/.env.local.php` - Configuration file
- `/.env` - Environment variables

### Web Server (Caddy)

```bash
# Check status
sudo systemctl status caddy

# Reload configuration (after Caddyfile changes)
sudo systemctl reload caddy

# Restart Caddy
sudo systemctl restart caddy

# View logs
sudo journalctl -u caddy -f

# Validate Caddyfile
caddy validate --config Caddyfile
```

### Go Backend API

```bash
cd backend

# Run locally (development)
export DATABASE_URL="postgres://ezm:ezm@localhost:5432/ezm?sslmode=disable"
export JWT_SECRET="dev-secret"
go run ./cmd/api

# Start with Docker Compose (includes PostgreSQL + Adminer)
make docker-up

# Enable pgcrypto extension (required once)
docker exec -it ezm-db psql -U ezm -d ezm -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;"

# Run with Postgres backend
make run DATA_BACKEND=postgres DATABASE_URL="postgres://ezm:ezm@localhost:5432/ezm?sslmode=disable"

# Run tests
go test ./...

# Run integration tests (requires running Postgres)
export TEST_DATABASE_URL="postgres://ezm:ezm@localhost:5432/ezm?sslmode=disable"
make test-integration

# Seed sample data
make seed

# Stop Docker services
make docker-down

# Database migrations
export DATABASE_URL="postgres://user:pass@localhost:5432/ezm?sslmode=disable"
scripts/db/migrate.sh up
```

### Database Operations

**CRM Database (MySQL/MariaDB):**
```bash
# Connect to CRM database
mysql -u kylewee -p rukovoditel

# Check leads table
mysql -u kylewee -p rukovoditel -e "SELECT id, field_219, field_227, date_added FROM app_entity_26 ORDER BY id DESC LIMIT 10;"

# Database info
mysql -u kylewee -p -e "SHOW DATABASES;"
mysql -u kylewee -p rukovoditel -e "SHOW TABLES;"
```

**API Database (PostgreSQL):**
```bash
# Connect via Docker
docker exec -it ezm-db psql -U ezm -d ezm

# Or locally
psql -U ezm -d ezm

# Check tables
\dt

# View schema
\d customers
\d vehicles
\d quotes
```

### Log Monitoring

```bash
# Voice system logs (JSON format)
tail -f voice/voice.log

# PHP-FPM error log
sudo tail -f /var/log/php8.3-fpm.log

# Caddy logs
sudo journalctl -u caddy -f

# MySQL logs
sudo tail -f /var/log/mysql/error.log

# Check for recent errors in voice system
grep -i "error" voice/voice.log | tail -20
```

## High-Level Architecture

### Voice Call Flow (Primary Business Logic)

```
Customer Call → SignalWire → voice/incoming.php (TwiML)
    ↓
Call Forwarded + Recorded (MP3 format, ~138KB)
    ↓
SignalWire Webhook → voice/recording_callback.php
    ↓
Download MP3 → OpenAI Whisper API (transcription)
    ↓
OpenAI GPT-4 (extract: name, phone, vehicle, issue)
    ↓
Create CRM Lead (Rukovoditel API, entity 26)
    ↓
Send Confirmation SMS (SignalWire)
```

**Critical Implementation Details:**
- Recording format: **MUST be MP3** (not WAV) - SignalWire's 8kHz WAV is incompatible with OpenAI
- Download recordings with `.mp3` extension in TWO locations:
  - `fetch_twilio_recording_mp3()` function (line ~1882)
  - Direct URL download (line ~2155)
- Recording URLs require token authentication: `?token=VOICE_RECORDINGS_TOKEN`
- Missed call detection: Check `DialCallDuration === 0` (not just status)

### Web Quote Flow

```
Website Form → api/quote_intake.php
    ↓
quote/quote_intake_handler.php (validation)
    ↓
Create CRM Lead (direct API call)
    ↓
SMS Confirmation → quote/status_callback.php (delivery status)
```

### CRM Integration Points

**Rukovoditel CRM v3.6.2** (PHP + MySQL):
- Database: `rukovoditel`
- Leads Entity: ID `26` (table: `app_entity_26`)
- API URL: `https://mechanicstaugustine.com/crm/api/rest.php`
- Authentication: Username `kylewee2` + API key

**Field Mappings** (in `api/.env.local.php`):
```php
const CRM_FIELD_MAP = [
  'first_name'  => 219,
  'last_name'   => 220,
  'phone'       => 227,
  'email'       => 235,
  'address'     => 234,
  'year'        => 231,  // Vehicle year
  'make'        => 232,  // Vehicle make
  'model'       => 233,  // Vehicle model
  'notes'       => 230,  // Includes call transcript
];
```

### SignalWire Phone System

**Configuration** (in `api/.env.local.php`):
- Space: `mobilemechanic.signalwire.com`
- Project ID: `ce4806cb-ccb0-41e9-8bf1-7ea59536adfd` (acts as Account SID)
- Business Number: `+19042175152`
- Forward To: `+19046634789` (direct cell - no Google Voice "press 1")

**Webhook Endpoints:**
- Incoming calls: `POST /voice/incoming.php`
- Recording status: `POST /voice/recording_callback.php`
- Call status: `POST /voice/call_status.php`
- SMS status: `POST /quote/status_callback.php`

**SMS System:**
- 10DLC Campaign registered (brand approval required for production SMS)
- SMS logs: `api/sms_incoming.log`, `api/sms_status.log`

### Go Backend Architecture

**Clean Architecture Pattern:**
```
cmd/api/main.go                  # Entry point
internal/
├── config/                      # Environment-based configuration
├── httpapi/                     # HTTP handlers and routing
├── auth/                        # JWT authentication
├── storage/
│   ├── memory/                  # In-memory repositories (dev)
│   └── postgres/                # PostgreSQL repositories (prod)
└── domain/                      # Business logic (models)
```

**Data Backend Modes:**
- `DATA_BACKEND=memory` - In-memory stores (development, no database required)
- `DATA_BACKEND=postgres` - PostgreSQL backend (production)

**Key Features:**
- JWT authentication with refresh tokens
- Structured logging (`slog` with request timing)
- Graceful shutdown
- Automatic schema migrations (`.up.sql` files in `db/migrations/`)
- Health check: `/healthz`, ping: `/v1/ping`

## Key Files and Their Purpose

### Configuration Files

**`api/.env.local.php`** - Primary configuration (PHP):
- SignalWire credentials
- OpenAI API key (for transcription)
- CRM credentials and field mappings
- Database credentials
- Security tokens

**`crm/config/database.php`** - CRM database connection:
- MySQL credentials for Rukovoditel CRM
- Used by CRM and voice system

**`Caddyfile`** - Web server configuration:
- PHP-FPM integration (PHP 8.3)
- Domain routing
- Automatic HTTPS (Let's Encrypt)

**`backend/.env` / environment variables** - Go API configuration:
- Database connection strings
- JWT secrets
- Server ports and timeouts

### Core Voice System

**`voice/recording_callback.php`** (~100KB, 2200+ lines):
- Main webhook handler for call recordings
- Downloads MP3 from SignalWire
- Calls OpenAI Whisper API for transcription
- Extracts customer data with GPT-4
- Creates CRM leads via API
- Sends confirmation SMS
- Handles missed call detection
- Token-authenticated recording downloads

**Critical functions:**
- `fetch_twilio_recording_mp3()` - Downloads recording as MP3
- `whisper_transcribe_bytes()` - OpenAI Whisper transcription
- `extract_lead_from_transcript()` - GPT-4 data extraction
- `create_crm_lead()` - Rukovoditel CRM integration
- `lookup_labor_time()` - Labor time estimation (Chilton data)
- `generate_auto_estimate_with_parts()` - Parts pricing (PartTech API)

**`voice/incoming.php`** - TwiML handler for incoming calls:
- Answers calls with greeting
- Forwards to mechanic's cell
- Enables call recording (MP3 format)
- Sets recording callback URL
- 20-second timeout before voicemail

### Quote System

**`quote/quote_intake_handler.php`** - Web quote processor:
- Validates form submissions
- Creates CRM leads
- Sends SMS confirmations
- Email notifications

**`api/quote_intake.php`** - Quote API endpoint:
- JSON payload validation
- Forwards to handler
- Returns structured responses

### Testing & Development

**`test_workflow.php`** - Complete workflow tester:
- Simulates incoming call
- Tests AI extraction
- Tests labor lookup
- Tests estimate generation
- Tests SMS approval
- Tests CRM integration
- Reports pain points

**`test_signalwire_setup.php`** - SignalWire connectivity test:
- Validates credentials
- Tests API access
- Checks webhook configuration

**`health.php`** - System health check:
- Database connectivity
- Environment variables
- File permissions
- PHP version/extensions
- Returns JSON status

## Development Workflow

### When Adding Voice System Features

1. **Read existing code first:**
   ```bash
   # Always check current implementation
   grep -n "function_name" voice/recording_callback.php
   ```

2. **Test with workflow script:**
   ```bash
   php test_workflow.php
   ```

3. **Check logs immediately:**
   ```bash
   tail -f voice/voice.log
   ```

4. **After config changes, ALWAYS reload PHP-FPM:**
   ```bash
   sudo systemctl reload php8.3-fpm
   # If that doesn't work:
   sudo systemctl restart php8.3-fpm
   ```

5. **Verify in CRM:**
   ```bash
   mysql -u kylewee -p rukovoditel -e "SELECT id, field_219, field_230 FROM app_entity_26 ORDER BY id DESC LIMIT 5;"
   ```

### When Modifying Configuration

1. **Edit config file** (e.g., `api/.env.local.php`)

2. **Update master documentation:**
   ```bash
   nano MASTER_CONFIG_DOCUMENT.md
   # Document what changed and why
   ```

3. **Update service status if applicable:**
   ```bash
   nano SERVICE_STATUS.md
   ```

4. **Reload services:**
   ```bash
   sudo systemctl reload php8.3-fpm
   sudo systemctl reload caddy
   ```

5. **Test the change:**
   ```bash
   php test_workflow.php
   # or make actual test call
   ```

### When Working with CRM

**CRM Field IDs are specific to this installation** - changing CRM structure requires updating `CRM_FIELD_MAP`.

**To find field IDs:**
1. Log into CRM: `https://mechanicstaugustine.com/crm/`
2. Navigate to Entities → Configure → Fields
3. Note field IDs for mapping

**To create new CRM entity (e.g., Estimates):**
1. CRM Admin → Entities → Create New Entity
2. Add required fields
3. Note entity ID and field IDs
4. Update configuration in `api/.env.local.php`
5. Create API integration functions

### When Debugging Transcription Issues

**Common issues and fixes:**

1. **Transcription not running:**
   - Check OpenAI API key: `grep OPENAI_API_KEY api/.env.local.php`
   - Verify key is NOT wrapped in `getenv()` - use direct string
   - Reload PHP-FPM: `sudo systemctl reload php8.3-fpm`

2. **"Access denied" on recordings:**
   - Check token parameter in URLs
   - Verify `VOICE_RECORDINGS_TOKEN` is defined
   - Look for token in URL construction (lines ~2072-2077)

3. **"Audio format not supported":**
   - Verify using MP3 format (not WAV)
   - Check THREE locations in `recording_callback.php`:
     - Line ~1882: `.mp3` in fetch function
     - Line ~2155: `.mp3` in direct download
     - Line ~2172: `.mp3` in filename parameter

4. **Calls marked as "Missed" when answered:**
   - Check `DialCallDuration` logic (line ~1764)
   - Should only mark missed if status failed AND duration === 0

### PHP Opcache Issues

PHP opcache can cache stale code. If changes don't take effect:

```bash
# Try reload first
sudo systemctl reload php8.3-fpm

# Then restart
sudo systemctl restart php8.3-fpm

# If still broken, check opcache settings
php -i | grep opcache

# Nuclear option: reinstall PHP-FPM
sudo apt-get remove --purge php8.3-fpm
sudo apt-get install php8.3-fpm
sudo systemctl start php8.3-fpm
```

## Security Considerations

### API Keys and Secrets

**Never commit sensitive data to git:**
- OpenAI API keys
- SignalWire tokens
- CRM passwords
- Database credentials
- JWT secrets

**Current approach:**
- PHP: Keys in `api/.env.local.php` (gitignored)
- Go: Environment variables
- Fallback values use `getenv()` with defaults

**Tokens to change before production:**
- `QUOTE_WORKFLOW_ADMIN_TOKEN` (currently: `admin-token-change-me`)
- `STATUS_CALLBACK_TOKEN` (currently empty)

### Database Security

**CRM database** (`crm/config/database.php`):
- Currently has hardcoded password
- Consider migrating to environment variables

**SQL Injection Protection:**
- CRM uses Rukovoditel's built-in ORM
- Custom queries use PDO prepared statements
- Never concatenate user input into SQL

### Phone Number Privacy

Personal phone number (`+19046634789`) is exposed in:
- Configuration files
- Call forwarding logic
- CRM records

Consider using environment variables for sensitive phone numbers.

## Testing Strategy

### Manual Testing

```bash
# 1. Test complete workflow
php test_workflow.php

# 2. Check health endpoint
curl https://mechanicstaugustine.com/health.php

# 3. Make real test call
# Call: +19042175152
# Leave voicemail with: Name, phone, vehicle, issue
# Check CRM for new lead

# 4. Test web quote
curl -X POST https://mechanicstaugustine.com/api/quote_intake.php \
  -H 'Content-Type: application/json' \
  -d '{"name":"Test User","phone":"555-1234",...}'
```

### Integration Testing

**Voice System:**
1. Make test call
2. Check `voice/voice.log` for webhook receipt
3. Verify recording downloaded
4. Check transcription in CRM lead
5. Confirm SMS sent (check phone)

**Go API:**
```bash
cd backend
# Start Postgres
make docker-up
# Run integration tests
make test-integration
```

## Troubleshooting Guide

### "Recording URL forbidden"
- Add token to URL: `&token=msarec-2b7c9f1a5d4e`
- Check token authentication in download handler

### "Transcription failing"
- Verify OpenAI API key is set
- Ensure MP3 format (not WAV)
- Check `voice.log` for API errors
- Restart PHP-FPM

### "CRM lead not created"
- Check CRM credentials
- Verify field mappings match CRM
- Test CRM API manually with curl
- Check MySQL is running

### "SMS not sending"
- Verify SignalWire brand approval status
- Check SMS logs: `api/sms_status.log`
- Test SignalWire API with `test_signalwire_setup.php`

### "Calls marked as missed"
- Check `DialCallDuration` field
- Verify duration > 0 for answered calls
- Review webhook data in `voice.log`

### "Changes not taking effect"
- Reload PHP-FPM: `sudo systemctl reload php8.3-fpm`
- Check PHP syntax: `php -l voice/recording_callback.php`
- Clear opcache (restart PHP-FPM)
- Verify file permissions

## Project Documentation

**Must-read documents:**
- `MASTER_CONFIG_DOCUMENT.md` - Complete configuration reference
- `SYSTEM_ARCHITECTURE.md` - System design and data flows
- `TRANSCRIPTION_SYSTEM_STATUS.md` - Voice system status and recent fixes
- `SERVICE_STATUS.md` - Current service status tracking
- `DEPLOYMENT.md` - Production deployment guide
- `TESTING_GUIDE.md` - Testing procedures

**Backend documentation:**
- `backend/README.md` - Go API guide
- `backend/db/migrations/` - Database schema

**Integration docs:**
- `signalwire/README.md` - Phone system setup
- `quote/SMS_SETUP.md` - SMS configuration

## Service Management

### Required Services

```bash
# Check all services
sudo systemctl status caddy php8.3-fpm mysql

# Start all services
sudo systemctl start caddy php8.3-fpm mysql

# Enable auto-start on boot
sudo systemctl enable caddy php8.3-fpm mysql
```

### Restart Order (after major changes)

```bash
# 1. Database first
sudo systemctl restart mysql

# 2. PHP-FPM (processes PHP)
sudo systemctl restart php8.3-fpm

# 3. Web server last
sudo systemctl reload caddy
```

## Performance Notes

### Recording Optimization

- **MP3 format**: 87.5% smaller than WAV (138KB vs 1106KB)
- **Transcription time**: ~5-10 seconds typical
- **No ffmpeg conversion needed**: OpenAI Whisper accepts MP3 natively

### CRM Performance

- **Direct API calls**: Faster than database sync
- **Field caching**: CRM field IDs are constants
- **Minimal validation**: Trust internal data

## Future Development

### Planned Features (from todo list)

1. **CRM Estimates Entity:**
   - Create new entity in Rukovoditel
   - Fields: Labor, Parts, Total, Status, Approval Token
   - Integration with quote generation

2. **SMS Approval Workflow:**
   - Token-based approve/decline links
   - Webhook handler for responses
   - Status tracking in CRM

3. **Outgoing Call Recording:**
   - Record mechanic→customer calls
   - Link to CRM activity log
   - Transcription optional

4. **Labor Time Lookup:**
   - Chilton/Mitchell data integration
   - Automatic estimate generation
   - Parts pricing via PartTech API

### Technical Debt

- Migrate CRM database password to environment variable
- Add rate limiting to API endpoints
- Implement request caching (Redis)
- Add automated backup system
- Create monitoring/alerting (uptime, errors)

## Getting Help

### Log Analysis

```bash
# Find recent errors
grep -i error voice/voice.log | tail -20

# Watch logs in real-time
tail -f voice/voice.log | grep -E "error|warning"

# Check PHP errors
sudo tail -f /var/log/php8.3-fpm.log | grep -i "error\|warning"
```

### Health Checks

```bash
# System health
curl https://mechanicstaugustine.com/health.php | jq

# Test voice webhook
curl -X POST https://mechanicstaugustine.com/voice/incoming.php

# Backend API health
curl http://localhost:8080/healthz
```

### Support Resources

- SignalWire Documentation: https://developer.signalwire.com
- OpenAI API Docs: https://platform.openai.com/docs
- Rukovoditel CRM: https://www.rukovoditel.net
- Caddy Docs: https://caddyserver.com/docs
