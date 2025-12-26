# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

Mobile mechanic service platform integrating:
- **SignalWire/Twilio** - VoIP calls, SMS via 10DLC
- **OpenAI Whisper** - Call transcription and AI data extraction
- **Rukovoditel CRM** - Lead management (MySQL, entity-based)
- **Go REST API** - JWT auth, PostgreSQL (in `backend/`)
- **PHP webhooks** - Voice handlers, quote intake, customer flow (in `voice/`, `api/`, `quote/`, `lib/`)

Runtime entrypoints are under `voice/`, `api/`, `quote/`, `crm/`, and `cron/`.

## Critical Policies

**NEVER delete configuration lines** - Always comment them out instead. This preserves rollback context.

**After config changes**: Always reload PHP-FPM (`sudo systemctl reload php8.3-fpm`)

**Telephony pattern**: Prefer Twilio-like field names for webhook handlers. If adding new providers, create adapters that normalize to Twilio shape (see `voice/signalwire_webhook.php`).

**Secrets**: Use environment variables; code falls back to `voice/.signalwire_secret` if present. Never commit secrets.

## Essential Commands

```bash
# PHP voice system
php test_workflow.php                    # Test complete workflow
php test_signalwire_setup.php            # Test SignalWire connectivity
php -l voice/recording_callback.php      # Syntax check
sudo systemctl reload php8.3-fpm         # Reload after config changes

# Local PHP dev server for webhook tests
php -S 127.0.0.1:8000 -t .

# Web server
sudo systemctl reload caddy              # Reload Caddy
caddy validate --config Caddyfile        # Validate config

# Go backend (in backend/)
make docker-up                           # Start PostgreSQL + Adminer
make run DATA_BACKEND=postgres DATABASE_URL="postgres://ezm:ezm@localhost:5432/ezm?sslmode=disable"
go run ./cmd/api                         # Run API directly
go test ./...                            # Run tests
make test-integration                    # Integration tests (requires Postgres)
make seed                                # Seed sample data
make docker-down                         # Stop Docker services

# Logs
tail -f voice/voice.log                  # Voice system logs (JSON)
tail -f voice/voice_signalwire_adapter.log  # Adapter debug
sudo journalctl -u caddy -f              # Caddy logs

# PHP tests (in tests/)
php tests/test_flow.php                  # Test voice/SMS flow
php tests/test_callback.php              # Test recording callback
```

## Architecture

### Voice Call Flow

```
Customer Call → SignalWire → voice/incoming.php (TwiML)
       ↓
Call Forwarded + Recorded (MP3)
       ↓
SignalWire Webhook → voice/recording_callback.php
       ↓
Download MP3 → OpenAI Whisper (transcription)
       ↓
GPT-4 (extract: name, phone, vehicle, issue)
       ↓
Create CRM Lead (Rukovoditel API, entity 26)
       ↓
Send Confirmation SMS
```

### Key Implementation Details

- **Recording format**: MUST be MP3 (not WAV) - SignalWire's 8kHz WAV is incompatible with OpenAI
- **Recording URLs**: Require token authentication (`?token=VOICE_RECORDINGS_TOKEN`)
- **Missed call detection**: Check `DialCallDuration === 0` (not just status)
- **SignalWire adapter**: `voice/signalwire_webhook.php` remaps SignalWire fields to Twilio-like keys

### Webhook Field Mapping

The central handler `voice/recording_callback.php` expects Twilio-like POST keys:
- `RecordingSid`, `RecordingUrl`, `TranscriptionText`, `From`, `To`, `CallSid`

SignalWire adapter (`voice/signalwire_webhook.php`) maps:
- `call_id` → `CallSid`
- `recording_id` → `RecordingSid`
- `recording_url` → `RecordingUrl`
- `caller` → `From`
- `called` → `To`

### CRM Integration

- **Database**: `rukovoditel` (MySQL)
- **Leads Entity**: ID `26` (table: `app_entity_26`)
- **API**: `https://mechanicstaugustine.com/crm/api/rest.php`
- **Field mappings in**: `api/.env.local.php` (CRM_FIELD_MAP constant)

Key fields: first_name(219), last_name(220), phone(227), email(235), year(231), make(232), model(233), notes(230)

### Go Backend (`backend/`)

Clean architecture with switchable storage:
- `DATA_BACKEND=memory` - In-memory (development)
- `DATA_BACKEND=postgres` - PostgreSQL (production)

Endpoints: `/healthz`, `/v1/ping`, `/v1/customers`, `/v1/vehicles`, `/v1/quotes`, `/v1/auth/*`

Migrations auto-run from `db/migrations/*.up.sql` at startup.

## Key Files

| File | Purpose |
|------|---------|
| `voice/recording_callback.php` | Main webhook: download recording, transcribe, extract data, create CRM lead, send SMS |
| `voice/incoming.php` | TwiML handler: greeting, call forwarding, recording setup |
| `voice/signalwire_webhook.php` | Adapter: normalizes SignalWire fields to Twilio format |
| `voice/call_router.php` | AI agent integration, call routing logic |
| `voice/sms_estimate.php` | SMS estimate responses, rate limiting |
| `api/.env.local.php` | Primary config: SignalWire creds, OpenAI key, CRM field mappings |
| `lib/CustomerFlow/` | Customer journey tracking, post-service flow |
| `cron/followups.php` | Automated follow-up scheduling |
| `crm/config/database.php` | CRM database connection |
| `Caddyfile` | Web server: PHP-FPM integration, HTTPS |
| `test_workflow.php` | Complete workflow tester |

## Database Access

```bash
# CRM (MySQL)
mysql -u kylewee -p rukovoditel
mysql -u kylewee -p rukovoditel -e "SELECT id, field_219, field_227 FROM app_entity_26 ORDER BY id DESC LIMIT 10;"

# Go API (PostgreSQL via Docker)
docker exec -it ezm-db psql -U ezm -d ezm
```

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Transcription failing | Verify OpenAI key in `api/.env.local.php` is direct string (not `getenv()`), reload PHP-FPM |
| Audio format error | Ensure using MP3 format in download URLs (`.mp3` extension) |
| Recording forbidden | Add token to URL: `?token=VOICE_RECORDINGS_TOKEN` |
| CRM lead not created | Check CRM credentials and field mappings match installation |
| Changes not taking effect | `sudo systemctl reload php8.3-fpm` (opcache) |
| Calls marked missed incorrectly | Check `DialCallDuration > 0` for answered calls |

## PR Guidelines

- Keep changes minimal and focused: one behavioral change per PR
- If updating webhook handling, include sample log output in PR description
- Preserve public endpoint URLs unless coordinating with SignalWire resource URL updates
- Logging: handlers append JSON to `voice/voice.log` - keep this pattern for quick troubleshooting

## SQLite Databases

Several features use SQLite for local data storage:
- `data/ab_testing.db` - A/B test variants and results
- `data/customer_flow.db` - Customer journey tracking
- `data/quotes.db` - Quote management
- `data/service_flow.db` - Service workflow state

## Documentation References

- `docs/MASTER_CONFIG_DOCUMENT.md` - Complete configuration reference
- `docs/SERVICE_STATUS.md` - Current service status
- `docs/TRANSCRIPTION_SYSTEM_STATUS.md` - Voice system status
- `docs/SYSTEM_ARCHITECTURE.md` - Complete system architecture diagrams
- `voice/TELEPHONY_SETUP.md` - CRM telephony integration
- `backend/README.md` - Go API documentation
