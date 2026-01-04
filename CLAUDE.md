# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

Multi-site lead generation platform. One codebase serves multiple domains (mechanic, landscaping, roofing, etc.) with domain-based config loading.

**Core integrations:**
- **SignalWire/Twilio** - VoIP calls, SMS via 10DLC
- **OpenAI** - Whisper transcription, GPT-4 estimates
- **Rukovoditel CRM** - Lead management (MySQL, entity-based)
- **Caddy** - Multi-domain HTTPS routing to PHP-FPM

## Multi-Site Architecture

```
Request (sodjax.com) → Caddy → index.php
                                    ↓
                         config/bootstrap.php
                                    ↓
                         config/sodjax.com.php  ← domain-specific config
                                    ↓
                         Site renders with correct branding
```

**How config loading works:**
1. `bootstrap.php` extracts domain from `HTTP_HOST`, strips port and `www.`
2. Loads `config/{domain}.php` if it exists, else `config/default.php`
3. Defines legacy constants (CRM_*, SIGNALWIRE_*, OPENAI_*) for backwards compatibility
4. Provides `config('key.subkey', $default)` helper function

**To add a new site:**
```bash
./deploy-site.sh newdomain.com "Business Name" landscaping
# Edit config/newdomain.com.php with credentials
# Add domain to Caddyfile, reload Caddy
```

## Critical Policies

**NEVER delete configuration lines** - Comment them out. Preserves rollback context.

**After config changes**: `sudo systemctl reload php8.3-fpm`

**Telephony pattern**: All webhook handlers expect Twilio-like POST keys. SignalWire fields are normalized via `voice/signalwire_webhook.php`.

## Caddy Configuration

Caddyfile location: `/etc/caddy/Caddyfile`

**ezlead4u.com routing:**
- `/admin/buyers/*` → PHP buyer admin
- `/admin/*` → Admin tools (hub, calls, analytics, etc.)
- `/buyer/*` → Buyer portal
- `/api/*` → PHP webhooks
- `/voice/*` → SignalWire callbacks
- Everything else → Python FastAPI (localhost:8000)

```bash
# Validate and reload Caddy (admin API disabled, must restart)
caddy validate --config /etc/caddy/Caddyfile
sudo systemctl restart caddy
```

## Essential Commands

```bash
# Local testing (test domain via /etc/hosts)
php -S 0.0.0.0:8888 -t /home/kylewee/code/master-template

# Syntax check
php -l voice/recording_callback.php

# Reload after config changes
sudo systemctl reload php8.3-fpm

# View voice logs (JSON format)
tail -f voice/voice.log | python3 -m json.tool

# Validate Caddy config
caddy validate --config Caddyfile
sudo systemctl reload caddy

# Test SignalWire connectivity
php test_signalwire_setup.php

# Test complete workflow
php test_workflow.php
```

## Voice Call Flow

### Current Flow (Voicemail Only)
```
Customer Call → SignalWire → voice/incoming.php
       ↓
Friendly greeting: "Hey there! Thanks for calling..."
       ↓
Record voicemail (up to 2 min)
       ↓
voice/recording_processor.php
       ↓
Whisper transcribe → GPT extract (name, phone, address, email)
       ↓
[Mechanic only] If vehicle info present → Generate estimate
       ↓
CRM Lead created (with estimate in notes if generated)
```

### Optional: Forward First, Then Voicemail
```
# In incoming.php, if config('phone.forward_to') is set:
Customer Call → Try owner (20s) → No answer → Voicemail
```

**Key details:**
- Recording format MUST be MP3 (SignalWire's 8kHz WAV incompatible with OpenAI)
- Use `voice="man"` or `voice="woman"` (NOT `Polly.*` - causes failures)
- Estimate auto-generated when transcript contains year/make/model/problem
- Session files store call state in `voice/sessions/`

## Template Variants

| Template | Voice Flow | Use Case |
|----------|------------|----------|
| **Basic** | Voice prompts → record → transcribe → CRM | landscaping, plumbing, roofing |
| **Auto Estimation** | Voice prompts → GPT vehicle discussion → estimate | mechanic, auto repair |

The estimation module is optional based on `business.type` in config.

## Key Files

| File | Purpose |
|------|---------|
| `config/bootstrap.php` | Domain detection, config loading, `config()` helper |
| `config/webhook_bootstrap.php` | For webhooks: detects domain from phone number or session file |
| `config/config.template.php` | Full config template - copy for new sites |
| `index.php` | Config-driven landing page with dynamic form fields |
| `api/form-submit.php` | Web form POST handler → CRM lead → thank you page |
| `voice/incoming.php` | TwiML: greeting + record voicemail (optional: try forward first) |
| `voice/recording_processor.php` | Download recording, transcribe, extract, estimate, CRM lead |
| `voice/dial_result.php` | Handles answered/missed call routing (if forwarding enabled) |
| `lib/CRMHelper.php` | Centralized CRM API functions |
| `lib/QuoteSMS.php` | SMS quote system with rate limiting |
| `deploy-site.sh` | Script to create new site config |

## Config Structure

```php
// config/domain.com.php
return [
    'site' => ['name', 'tagline', 'phone', 'domain', 'service_area'],
    'business' => ['type', 'category', 'services'],  // type: mechanic, landscaping, roofing, plumbing
    'branding' => ['primary_color', 'secondary_color'],
    'crm' => ['api_url', 'entity_id', 'field_map' => [...]],
    'phone' => ['provider', 'project_id', 'space', 'api_token', 'forward_to'],
    'openai' => ['api_key'],
    'estimates' => ['enabled', 'labor_rate', 'input_fields' => [...], 'prompts' => [...]],
];
```

**Access config values:**
```php
require_once __DIR__ . '/../config/bootstrap.php';
$siteName = config('site.name', 'Default Name');
$laborRate = config('estimates.labor_rate', 75);
```

## CRM Integration (Rukovoditel)

- **Leads Entity**: ID `26` (table: `app_entity_26`)
- **Field mappings**: Defined per-site in `config/{domain}.php` → `crm.field_map`
- **Common fields**: first_name(219), last_name(220), phone(227), email(235), notes(230), stage(228)

```bash
# Query recent leads
mysql -u kylewee -p rukovoditel -e "SELECT id, field_219, field_227 FROM app_entity_26 ORDER BY id DESC LIMIT 10;"
```

## Webhook Field Mapping

Central handlers expect Twilio-like POST keys:
- `RecordingSid`, `RecordingUrl`, `TranscriptionText`, `From`, `To`, `CallSid`

SignalWire adapter (`voice/signalwire_webhook.php`) maps:
- `call_id` → `CallSid`
- `recording_id` → `RecordingSid`
- `caller` → `From`

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Voice changes not working | Caddy serves from `/code/inbred/` not `/code/master-template/` - update Caddyfile |
| Calls failing immediately | Check webhook returns valid TwiML, avoid `Polly.*` voices (use `man` or `woman`) |
| Config not loading | Check domain matches filename, strip `www.` and port |
| Recording callback wrong config | webhook_bootstrap.php checks session file for domain if no "To" field |
| Transcription failing | Verify OpenAI key is direct string (not `getenv()`), reload PHP-FPM |
| Recording forbidden | Add `?token=VOICE_RECORDINGS_TOKEN` to URL |
| CRM lead not created | Check field mappings match your Rukovoditel installation |
| Changes not taking effect | `sudo systemctl reload php8.3-fpm` (opcache) |

## SQLite Databases

Local storage in `data/`:
- `form_submissions.log` - Web form submissions
- `quotes.db` - Quote management
- `rate_limits.db` - SMS rate limiting
- `call_tracking.db` - Call analytics

## Session Continuity

**Start of session:** Read `SESSION_LOG.md` for context and recent changes.

**End of session:** Update `SESSION_LOG.md` with what was done.

## Admin Hub (ezlead4u.com)

Central admin dashboard at `https://ezlead4u.com/admin/`

**Password:** `Rain0nin`

| Tool | URL | Purpose |
|------|-----|---------|
| Admin Hub | `/admin/` | Central dashboard |
| Buyer Management | `/admin/buyers/` | Create buyers, credits, campaigns |
| Outgoing Calls | `/admin/calls/` | Make recorded calls |
| A/B Testing | `/admin/ab-testing/` | Landing page experiments |
| Analytics | `/admin/analytics/` | Unified dashboard |
| Customer Flow | `/admin/flow/` | 1-2-3-4-5 journey |
| Quotes | `/admin/quotes/` | SMS quote system |

### Outgoing Calls System

Makes recorded outbound calls via SignalWire. Calls YOUR phone first, then connects to target.

**Files:**
- `api/make-call.php` - API endpoint
- `voice/outgoing.php` - TwiML handler
- `admin/calls/index.php` - Admin UI
- `data/calls.db` - Call history (SQLite)
- `data/call_rate_limits.json` - Rate limiting

**Flow:**
1. Enter target number in admin
2. Click "Call" → YOUR phone rings
3. Answer → "Connecting you to 407-844-0231"
4. Target's phone rings
5. Call recorded, saved to `/voice/recordings/`

**Rate Limit:** 5 minutes per number (prevents spam)

```bash
# Test outgoing call API
curl -X POST https://ezlead4u.com/api/make-call.php \
  -H "Content-Type: application/json" \
  -d '{"to": "+14075551234", "agent": "+19042175152"}'
```

## Buyer Portal

Lead distribution system for selling leads to contractors.

**Location:** `buyer/`

| File | Purpose |
|------|---------|
| `buyer/init_db.php` | Initialize SQLite database |
| `buyer/BuyerAuth.php` | Authentication (login, sessions) |
| `buyer/LeadDistributor.php` | Auto-distribute leads to eligible buyers |
| `buyer/login.php` | Buyer login page |
| `buyer/index.php` | Buyer dashboard |
| `buyer/admin/index.php` | Admin panel (create buyers, add credit) |

**Pricing model:**
- $35/lead default (configurable per buyer)
- 3 free test leads to start
- $35 minimum balance (auto-pause if below)
- Shared leads (unless paying $90 for exclusive)

**Database:** `data/buyers.db` (SQLite)

```bash
# Initialize buyer database
php buyer/init_db.php

# Create test buyer
php buyer/create_test_buyer.php
```

## Domain Portfolio

**Priority 1 - SOD (Jacksonville):**
- sodjacksonvillefl.com - aged 2008, 1.63k impressions (primary)
- sodjax.com - short domain, good for ads (paid thru 2029)
- jacksonvillesod.com - 993 impressions

**Priority 2 - WELDING:**
- weldingjacksonville.com - 2.68k impressions
- weldingjax.com - 1.84k impressions
- welderfl.com - 906 impressions

**Priority 3 - OTHER:**
- mechanicstaugustine.com - mobile mechanic (live)
- septictankjacksonville.com - 1.09k impressions
- drainagejax.com

See `docs/DOMAINS.md` for full portfolio.

## Active Sites

| Domain | Business Type | Status |
|--------|---------------|--------|
| mechanicstaugustine.com | mechanic | Live |
| sodjacksonvillefl.com | sod | Live |
| sodjax.com | sod | Live |
| jacksonvillesod.com | sod | Live |

## Contractor Scraper

Tools for finding contractors to recruit as lead buyers.

**Location:** `tools/contractor-scraper/`

| File | Purpose |
|------|---------|
| `scraper_free.py` | Free scraper (BBB, Yelp, YellowPages) |
| `scraper.py` | Google Places API version |
| `cities.txt` | Target cities (18 Florida cities) |

```bash
cd tools/contractor-scraper
pip install -r requirements.txt
python scraper_free.py --keyword "sod installation" --city "Jacksonville, FL"
```

## Key Documentation

| File | Purpose |
|------|---------|
| `SESSION_LOG.md` | What was done each session |
| `docs/BUYER_ACQUISITION.md` | How to recruit contractors |
| `docs/BUYER_COLD_CALL_SCRIPT.md` | Cold call script |
| `docs/BUYER_EMAIL_SEQUENCE.md` | 5-email drip sequence |
| `docs/LAUNCH_PLAN.md` | Step-by-step to 3 leads/day |
| `docs/DOMAINS.md` | Full domain portfolio |
| `docs/DOMAIN_MIGRATION.md` | How to migrate domains properly |
