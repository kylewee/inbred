# Session Log - Master Template Platform

## 2026-01-04 - EzLead4U Admin Hub & Outgoing Calls System

### What Was Done

1. **Cold Call Script & Email Sequence**
   - `docs/BUYER_COLD_CALL_SCRIPT.md` - Script for recruiting contractors
   - `docs/BUYER_EMAIL_SEQUENCE.md` - 5-email drip sequence

2. **Contractor Scraper**
   - `tools/contractor-scraper/scraper_free.py` - Free scraper (BBB, Yelp, YellowPages)
   - `tools/contractor-scraper/scraper.py` - Google Places API version
   - Tested: Found 15 contractors from BBB

3. **Admin Tools Migrated from Inbred**
   - Copied all admin tools to master-template:
     - `/admin/ab-testing/` - A/B test management
     - `/admin/analytics/` - Unified analytics
     - `/admin/flow/` - Customer journey (1-2-3-4-5)
     - `/admin/quotes/` - SMS quote system
     - `/admin/calls/` - Outgoing calls (NEW)
     - `/admin/dashboard.php` - Legacy dashboard
     - `/admin/leads_approval.php` - Lead QA
   - Libraries: ABTesting.php, Analytics.php, CallTracking.php, CustomerFlow/, QuoteSMS.php

4. **Admin Hub Created**
   - `/admin/index.php` - Central dashboard linking all tools
   - Stats row: Active Buyers, Total Leads, Revenue, Status
   - Organized by: Core Tools, Analytics & Testing, Operations

5. **Caddyfile Updated for ezlead4u.com**
   - `/admin/buyers/*` → PHP buyer admin
   - `/admin/*` → Admin tools
   - `/buyer/*` → Buyer portal
   - `/api/*` → PHP webhooks
   - `/voice/*` → Voice callbacks (SignalWire)
   - Everything else → Python FastAPI (localhost:8000)

6. **Outgoing Calls System Built**
   - `api/make-call.php` - API to initiate recorded calls via SignalWire
   - `voice/outgoing.php` - TwiML handler for outbound calls
   - `admin/calls/index.php` - Admin UI for making calls and viewing history
   - `voice/recording_processor.php` - Added `process_outgoing_recording()` function

7. **Fixed Call Flow - Calls YOU First**
   - Original: Called target first (wrong)
   - Fixed: Calls YOUR phone first, then connects to target
   - Agent number field added to admin UI
   - Says "Connecting you to 407-844-0231" when you answer

8. **Anti-Spam Protections**
   - 5-minute rate limit per phone number
   - Button disables after click
   - PRG pattern prevents form re-submission on refresh
   - Explicit `<Hangup>` in TwiML

9. **Recording System Fixed**
   - Added Basic Auth to SignalWire recording downloads
   - Recordings auto-save to `/voice/recordings/`
   - Database updated with local paths

10. **Password Updated**
    - Changed from `rainonin` to `Rain0nin` across all admin pages

### Files Created/Modified

| File | Change |
|------|--------|
| `admin/index.php` | NEW - Admin hub |
| `admin/calls/index.php` | NEW - Outgoing calls UI |
| `api/make-call.php` | NEW - Call initiation API |
| `voice/outgoing.php` | NEW - Outbound call TwiML |
| `voice/recording_processor.php` | Added outgoing recording handler |
| `/etc/caddy/Caddyfile` | Added /voice/* route for ezlead4u.com |
| `docs/BUYER_COLD_CALL_SCRIPT.md` | NEW |
| `docs/BUYER_EMAIL_SEQUENCE.md` | NEW |
| `tools/contractor-scraper/*` | NEW - Scraper tools |

### Key Config

```
Outgoing Calls:
- SignalWire Number: +19047066669
- Default Agent: +19042175152 (Kyle's cell)
- Rate Limit: 5 minutes per number
- Recordings: /voice/recordings/*.mp3

Admin Password: Rain0nin
Admin Hub: https://ezlead4u.com/admin/
```

### Domain Status

| Domain | Redirect | Status |
|--------|----------|--------|
| wesodjax.com | → sodjax.com | ✓ Working (301) |
| www.wesodjax.com | → sodjax.com | ✗ Loop (Cloudflare issue) |
| landimpressions.com | → jacksonvillesod.com | ✓ Working |

**Fix for www.wesodjax.com:** In Cloudflare, either turn off proxy (orange→gray) or add redirect rule.

### Next Steps

1. Fix www.wesodjax.com redirect in Cloudflare
2. Test outgoing calls during normal hours
3. Recruit first contractor buyer
4. Wait for rankings to improve before running scraper

---

## 2025-12-31 (Part 2) - Voice System Testing & Critical Discovery

### Critical Finding: Wrong Codebase Being Served

**Caddy is serving from `/home/kylewee/code/inbred/` NOT `/home/kylewee/code/master-template/`!**

All our edits to master-template are NOT live. The production site is still running the old code.

```bash
# Current Caddy config shows:
mechanicstaugustine.com {
    root * /home/kylewee/code/inbred  # ← WRONG!
    # Should be: /home/kylewee/code/master-template
}
```

### What Was Done

1. **Tested Voice System**
   - Calls to +1 904-706-6669 are failing immediately (status: "failed", duration: 1s)
   - Root cause: Caddy serving old code from `/code/inbred/`
   - Old code uses `Polly.Matthew` voice (not supported by SignalWire)
   - Old code tries to forward calls instead of voicemail

2. **Verified Estimate System Works**
   - Tested GPT extraction + estimate generation directly via PHP CLI
   - Successfully extracts: name, phone, address, email, year, make, model, problem
   - Successfully generates estimates with labor hours, parts costs, totals
   - Example: "2018 Honda Accord brakes grinding" → Brake Pad and Rotor Replacement, $192.50 - $292.50

3. **Fixed webhook_bootstrap.php**
   - Added session file lookup for recording callbacks
   - Recording callbacks don't get "To" field from SignalWire
   - Now checks session file for "called" number to detect domain

4. **Verified CRM Ingestion Working**
   - 4 test leads created in CRM:
     - ID 1: Cinderella Stevenson (test)
     - ID 2: Kyle (test)
     - ID 3: Random audio
     - ID 4: "Universal" test (no customer data)
   - All marked with `crm_ok: true`

### Files Modified

| File | Change |
|------|--------|
| `config/webhook_bootstrap.php` | Added session file lookup for domain detection |

### The Problem

```
EDITING:     /home/kylewee/code/master-template/voice/incoming.php
CADDY SERVES: /home/kylewee/code/inbred/voice/incoming.php
```

These are completely different files with different code!

### Next Step Required

**Update Caddy to point to master-template:**
```bash
sudo nano /etc/caddy/Caddyfile
# Change: root * /home/kylewee/code/inbred
# To:     root * /home/kylewee/code/master-template
sudo systemctl reload caddy
```

Or copy working files to inbred (less clean).

---

## 2025-12-31 - Buyer Portal + Lead Gen Business Setup

### What Was Done

1. **Buyer Portal System Built**
   - `buyer/init_db.php` - SQLite database (buyers, leads, transactions, campaigns, sessions)
   - `buyer/BuyerAuth.php` - Authentication helper
   - `buyer/LeadDistributor.php` - Auto-distributes leads to eligible buyers
   - `buyer/login.php` - Buyer login page
   - `buyer/index.php` - Dashboard with stats and lead list
   - `buyer/admin/index.php` - Admin panel to create buyers

2. **Business Model Finalized**
   - **Pricing tiers:**
     - Basic: $25/lead (shared)
     - Priority: $35/lead (first in line, shared)
     - Exclusive: $90/lead (only them, pre-screened)
   - 3 free test leads to start
   - $35 minimum balance (auto-pause if below)
   - No contracts, turn on/off anytime

3. **Documentation Created**
   - `docs/BUYER_ACQUISITION.md` - How to recruit contractors without cold calling
   - `docs/LAUNCH_PLAN.md` - Step-by-step plan to get 3 leads/day
   - `docs/DOMAINS.md` - Full domain portfolio with priorities
   - `docs/DOMAIN_MIGRATION.md` - How to properly migrate domains

4. **Scripts Created**
   - `scripts/find_hungry_contractors.py` - Finds contractors on Google page 2-3 (the hungry ones)
   - Creative outreach scripts included (don't sound like cold callers)

5. **Domain Strategy Finalized**

   **SOD (Priority 1 - Jacksonville):**
   - sodjacksonvillefl.com (main, standalone) - aged 2008, 1.63k impressions
   - wesodjax.com (main) + sodjax.com redirecting
   - landimpressions.com (separate site, fresh content) - aged 2007

   **Full Portfolio (18 domains):**
   - 7 sod domains
   - 3 welding domains (5k+ impressions combined)
   - 3 mobile mechanic domains
   - Septic, drainage, handyman, general lead gen

6. **Site Configs Created**
   - `config/sodjacksonvillefl.com.php`
   - `config/wesodjax.com.php` - updated with buyer portal
   - `config/landimpressions.com.php` - updated with buyer portal

7. **Landing Page Created**
   - `sites/sodjacksonvillefl.com/index.html` - Clean, mobile-responsive
   - Form posts to `/api/form-submit.php`
   - Trust signals ("17+ years")
   - Service areas

### Key Decisions

| Decision | Reason |
|----------|--------|
| $35/lead default | Good margin, not greedy |
| 3 free leads | Let them prove value before paying |
| Minimum balance $35 | Auto-pause, no chasing payments |
| Multiple domains per niche | Dominate search results like the big lead companies |
| Aged domains are weapons | 17-18 years of trust with Google |

### Competition Analysis

- Competitor (sodcompanyjacksonville.com) dominates with 4+ domains on page 1
- Same strategy: multiple sites, same buyer pool behind the scenes
- Our advantage: aged domains (2007, 2008) vs their newer ones

### The Math

```
PPC: $3/click → 1 in 5 converts → $15 cost per lead
Sell for: $35
Profit: $20/lead

3 leads/day = $60/day profit = $1,800/month
Once organic kicks in = 100% profit
```

### Files Created/Modified

| File | Purpose |
|------|---------|
| `buyer/init_db.php` | Database schema |
| `buyer/BuyerAuth.php` | Authentication |
| `buyer/LeadDistributor.php` | Lead distribution |
| `buyer/login.php` | Login page |
| `buyer/index.php` | Dashboard |
| `buyer/admin/index.php` | Admin panel |
| `docs/BUYER_ACQUISITION.md` | Contractor recruitment guide |
| `docs/LAUNCH_PLAN.md` | 3 leads/day plan |
| `docs/DOMAINS.md` | Domain portfolio |
| `scripts/find_hungry_contractors.py` | Contractor finder |
| `config/sodjacksonvillefl.com.php` | Site config |
| `sites/sodjacksonvillefl.com/index.html` | Landing page |
| `snippets/sod-quote-form.html` | Drop-in form snippet |

### Next Steps

1. Deploy sodjacksonvillefl.com landing page
2. Set up form handler and CRM connection
3. Set up 301 redirects (sodjax.com → wesodjax.com)
4. Find first contractor buyer using hungry contractor script
5. Turn on Google Ads ($10-20/day)
6. Build out wesodjax.com and landimpressions.com

### Philosophy

*"I'll never be greedy. Ever."*

- Quality leads > quantity
- Happy contractors = recurring revenue
- One guy who gives a shit > corporation that doesn't
- 3 leads/day is all you need

---

## 2025-12-30 (Part 2) - Voice System Rebuilt & Working

### What Was Done

Built a clean, simple voice system from scratch:

1. **Simple Voicemail Approach** - Single recording, GPT extraction
   - Friendly greeting prompts caller to leave info
   - Records up to 2 minutes
   - Whisper transcribes → GPT extracts name/phone/address/email → CRM lead

2. **Files Created**
   ```
   voice/
   ├── incoming.php           # Greeting + record voicemail
   ├── recording_processor.php # Transcribe + extract + CRM
   ├── sessions/              # Call session data
   └── recordings/            # Downloaded recordings
   ```

3. **Call Flow**
   ```
   Call → "Hey there! Thanks for calling..."
       → Beep → Record up to 2 min
       → Whisper transcribe
       → GPT extracts: name, phone, address, email
       → CRM lead created
   ```

### Test Results

- Answered call (Cinderella test): ✓ CRM lead created
- Voicemail call (Kyle test): ✓ CRM lead created
- GPT extraction working for name, phone, address from natural speech

### Key Decisions

- **No call forwarding** - All calls go straight to voicemail
- **Single recording** - Simpler than multi-step IVR prompts
- **Friendly greeting** - Warm tone to encourage leaving info

### Files Reference

| File | Purpose |
|------|---------|
| `voice/incoming.php` | Greeting + Record voicemail |
| `voice/recording_processor.php` | Download, transcribe, extract, CRM |
| `voice/dial_result.php` | (Not used - no forwarding) |
| `voice/missed_call.php` | (Not used - simplified to single recording) |

---

## 2025-12-30 - Architecture Redesign

### Decision: Split Voice System

After extensive testing, the live GPT conversation approach was unreliable:
- GPT didn't consistently return JSON for data extraction
- `collected` data stayed empty across turns
- Calls would hang up prematurely
- API timeouts caused failures

**New Architecture:**

1. **Voice Prompts** - Collect structured data reliably
   - "Please say your name" → record → transcribe
   - "What's your address?" → record → transcribe
   - Phone from caller ID

2. **Optional GPT Module** - For complex discussions
   - "Press 1 to describe your vehicle issue"
   - GPT handles the unstructured conversation
   - Extraction happens AFTER conversation ends

3. **Two Template Variants:**
   - **Basic template:** Voice prompts → CRM (landscaping, plumbing, roofing)
   - **Auto estimation template:** Voice prompts → GPT vehicle discussion → estimate (mechanic)

### Files Changed

| File | Change |
|------|--------|
| `voice/gpt_assistant.php` | Renamed to `gpt_assistant.old.php` |
| `voice/gpt_assistant.php` | To be rebuilt with IVR approach |

### What Was Tested (and failed)

- GPT live conversation with inline JSON extraction
- Multiple JSON extraction methods (3 fallback regex patterns)
- 4 hang-up safeguards (question mark, goodbye words, confusion phrases, minimum info)
- Fallback text parsing for vehicle info

All approaches had reliability issues. Fresh rebuild is the right call.

### CRM Status

- Cleared all test leads (379 deleted)
- Reset auto-increment to 1
- Ready for fresh data

### Next Steps

1. Build voice prompt handler (collect name, address)
2. Add optional GPT vehicle discussion (press 1)
3. Add auto estimation for mechanic template
4. Test end-to-end

---

## 2025-12-29 (Part 6) - Phone Cleanup & GPT Testing

### Phone Number Cleanup
- Replaced ALL 904-217-5152 → 904-706-6669 (all formats)
- ~30 files cleaned (PHP, HTML, MD, backup files)

### GPT Voice Assistant Testing
- ✓ Missed call redirect WORKING
- ✓ GPT answers and converses
- ✓ CRM leads created
- ⚠️ `collected` data not populating (GPT not returning JSON)

### Fixes Applied
- Stronger JSON prompt with "CRITICAL" instruction and example
- Recording retry: 3s + 5s delay if first download fails
- Gather timeouts: 4s→6s, 3s→5s

### Phone Setup
```
Main: 904-706-6669 (SignalWire) → Forwards to: 904-663-4789
```

---

## 2025-12-29 (Part 5) - Production Deployment

### What Was Done

1. **Fixed MySQL/MariaDB temp file error**
   - Issue: `/tmp` symlinked to `/home/tmp`, MariaDB couldn't write temp files
   - Fix: Created `/var/lib/mysql-tmp` and configured MariaDB to use it
   ```bash
   sudo mkdir -p /var/lib/mysql-tmp
   sudo chown mysql:mysql /var/lib/mysql-tmp
   echo -e "[mysqld]\ntmpdir = /var/lib/mysql-tmp" | sudo tee /etc/mysql/mariadb.conf.d/99-tmpdir.cnf
   sudo systemctl restart mariadb
   ```

2. **Cleared CRM login lockout**
   ```sql
   DELETE FROM app_login_attempt WHERE user_ip='127.0.0.1';
   ```

3. **Switched production to master-template**
   - Updated Caddyfile: `/code/inbred` → `/code/master-template`
   - Reloaded Caddy

4. **Fixed 500 error - file permissions**
   - Config files had `rw-------` (owner only)
   - PHP-FPM (www-data) couldn't read them
   - Fix: `chmod 644` on all PHP files

### Commands for Future Reference

```bash
# Fix file permissions for web server
find /home/kylewee/code/master-template -name "*.php" -type f -exec chmod 644 {} \;

# Restart MariaDB
sudo systemctl restart mariadb

# Clear CRM login lockout
mysql -u kylewee -prainonin rukovoditel -e "DELETE FROM app_login_attempt;"

# Check Caddy config
sudo cat /etc/caddy/Caddyfile

# Reload Caddy
sudo systemctl reload caddy
```

### Production Status

| Component | Status | Notes |
|-----------|--------|-------|
| Landing page | ✓ Working | https://mechanicstaugustine.com |
| CRM | ✓ Working | Login: kylewee2 / rainonin |
| Voice webhooks | ✓ Working | Domain detection active |
| Recording | ✓ Working | Saves to master-template/voice/recordings/ |
| Transcription | ✓ Working | OpenAI Whisper |
| MySQL | ✓ Working | Using /var/lib/mysql-tmp |

---

## 2025-12-29 (Part 4) - Domain Detection Fix Implemented

### What Was Done

Created `config/webhook_bootstrap.php` - smart domain detection for SignalWire webhooks:
- Maps phone numbers to domains (e.g., +19047066669 → mechanicstaugustine.com)
- Sets HTTP_HOST before loading regular bootstrap
- Falls back gracefully when number unknown

**Files Updated:**
| File | Change |
|------|--------|
| `config/webhook_bootstrap.php` | NEW - Phone-to-domain mapping |
| `voice/incoming.php` | Use webhook_bootstrap |
| `voice/dial_result.php` | Use webhook_bootstrap |
| `voice/recording_callback.php` | Use webhook_bootstrap |
| `voice/gpt_assistant.php` | Use webhook_bootstrap |
| `voice/swaig_functions.php` | Use webhook_bootstrap |

**Phone-to-Domain Mapping:**
```php
$PHONE_TO_DOMAIN = [
    '+19047066669' => 'mechanicstaugustine.com',
    // Add more as needed:
    // '+19041234567' => 'wesodjax.com',
];
```

**To add new sites:** Edit `config/webhook_bootstrap.php` and add the phone number mapping.

### Test Results

```
HTTP_HOST: mechanicstaugustine.com
Config loaded: yes
Site name: EZ Mobile Mechanic
SignalWire Project ID: ce4806cb-ccb0-41e9-8bf1-7ea59536adfd
OpenAI API Key set: yes
```

### Next Steps

1. Update Caddy to point to master-template
2. Test with real SignalWire call
3. Add phone numbers for wesodjax.com and landimpressions.com when configured

---

## 2025-12-29 (Part 3) - Transition Notes & Issue Analysis

### Critical Issues Found

#### 1. Production Using Old Codebase
**Evidence:** Logs reference `/home/kylewee/code/inbred/voice/recordings/` not `/home/kylewee/code/master-template/`

**To Fix:**
```bash
# Check current Caddy config
sudo cat /etc/caddy/Caddyfile | grep -A5 "mechanicstaugustine"

# Update root path from /home/kylewee/code/inbred to /home/kylewee/code/master-template
sudo nano /etc/caddy/Caddyfile

# Validate and reload
caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy
```

---

#### 2. Recording Downloads Failing - "no_twilio_creds"
**Root Cause:** SignalWire webhooks come with different HTTP_HOST than expected, causing wrong config to load → legacy constants not defined.

**Evidence from logs:**
```json
"recording_download_error": {"ok": false, "error": "no_twilio_creds"}
```

**How bootstrap.php domain detection works:**
```php
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';  // Webhook might not have correct HOST
$config_file = "{$config_dir}/{$domain}.php";    // Loads wrong config!
```

**Fix Options:**

Option A - **Force domain in webhook URLs** (Recommended):
```php
// In recording_callback.php, before bootstrap:
$_SERVER['HTTP_HOST'] = 'mechanicstaugustine.com';
require_once __DIR__ . '/../config/bootstrap.php';
```

Option B - **Add domain hint parameter**:
```
recordingStatusCallback="https://site.com/voice/recording_callback.php?site=mechanicstaugustine.com"
```
Then in recording_callback.php:
```php
if (!empty($_GET['site'])) {
    $_SERVER['HTTP_HOST'] = $_GET['site'];
}
```

Option C - **Auto-detect from SignalWire To field**:
```php
// SignalWire includes the To number - lookup config by phone number
$toNumber = $_POST['To'] ?? $_POST['Called'] ?? '';
// Match phone to site config
```

---

#### 3. GPT Redirect Not Working After Dial Timeout
**Evidence:**
- `dial_result.php` IS called (logs confirm `dial_result_entry`)
- TwiML Redirect IS generated correctly
- But SignalWire doesn't follow the redirect

**Confirmed TwiML output:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Redirect method="POST">https://mechanicstaugustine.com/voice/gpt_assistant.php</Redirect>
</Response>
```

**Possible causes:**
1. **Same domain detection issue** - If bootstrap fails, `config('features.voice_assistant')` returns null, not true
2. **SignalWire LAML quirk** - May need absolute URL or specific verb format
3. **Call already terminated** - Recording callback might be ending the call before redirect processed

**Debug steps:**
```bash
# Watch real-time logs during test call
tail -f /home/kylewee/code/master-template/voice/voice.log | python3 -m json.tool

# Check if gpt_assistant.php is accessible
curl -X POST "https://mechanicstaugustine.com/voice/gpt_assistant.php" \
  -d "CallSid=TEST123&From=+19041234567"
```

**Fix:** Same as issue #2 - force HTTP_HOST before bootstrap.

---

#### 4. SMS Blocked by 10DLC (External - No Code Fix)
**Status:** SignalWire brands stuck "pending" for 3+ weeks
- `Mobilemechanic.best` - pending since Dec 4
- `We Sod Jax` - pending since Dec 9

**Action Required:**
1. Contact SignalWire support - ask why brands pending so long
2. Try "Sole Proprietor" brand type - may approve faster
3. **Alternative:** Consider Twilio's "Low Volume Standard" (<1000 msgs/month)

---

### Files That Need Domain Fix

These files load bootstrap.php and rely on correct domain detection:

| File | Used By |
|------|---------|
| `voice/incoming.php` | SignalWire call webhooks |
| `voice/dial_result.php` | SignalWire dial action callback |
| `voice/recording_callback.php` | SignalWire recording ready |
| `voice/gpt_assistant.php` | AI voice assistant |
| `api/form-submit.php` | Web form POST |

**Recommended fix - Add to each voice file:**
```php
<?php
// Force correct domain for SignalWire webhooks
// SignalWire may send requests with different HTTP_HOST
if (empty($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] === 'localhost') {
    // Detect from To number or hardcode
    $_SERVER['HTTP_HOST'] = 'mechanicstaugustine.com';
}
require_once __DIR__ . '/../config/bootstrap.php';
```

---

### GPT Assistant - What's Working

From `gpt_assistant.log`:
- Successfully handles conversations when called directly
- Extracts vehicle info (year, make, model, problem)
- Creates CRM leads
- Rate limiting working

Example successful flow:
```
Turn 0: "Hey there! What's going on with your ride?"
Turn 1: Customer: "my brakes are squeaking on my 2018 Honda Accord"
        AI: "Gotcha! So, it's a 2018 Honda Accord with squeaky brakes..."
```

---

### Migration Checklist

Before going live with master-template:

- [ ] Update Caddy to point to master-template
- [ ] Add domain-forcing code to voice files
- [ ] Test webhook with actual SignalWire call
- [ ] Verify recording download works
- [ ] Verify GPT redirect works on no-answer
- [ ] Test CRM lead creation
- [ ] Fill in real credentials for wesodjax.com and landimpressions.com
- [ ] Set up SignalWire phone numbers for new sites

---

### Quick Test Commands

```bash
# Syntax check all PHP files
find /home/kylewee/code/master-template -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax"

# Test incoming call handler
curl -X POST "http://localhost:8888/voice/incoming.php" \
  -H "Host: mechanicstaugustine.com" \
  -d "From=+19041234567&To=+19047066669&CallSid=TEST123"

# Test dial result with no-answer
curl -X POST "http://localhost:8888/voice/dial_result.php" \
  -H "Host: mechanicstaugustine.com" \
  -d "DialCallStatus=no-answer&From=+19041234567&CallSid=TEST123"

# Test GPT assistant directly
curl -X POST "http://localhost:8888/voice/gpt_assistant.php" \
  -H "Host: mechanicstaugustine.com" \
  -d "CallSid=TEST123&From=+19041234567"
```

---

## 2025-12-29 (Part 2) - Cleanup & Automation

### What We Did

1. **Updated CLAUDE.md** (`/init`)
   - Rewrote for multi-site template architecture
   - Added config loading flow diagram
   - Removed outdated Go backend references

2. **Created Auto-Start PHP Dev Server**
   - Systemd user service: `~/.config/systemd/user/php-dev-server.service`
   - Starts on boot, restarts on crash
   - Manage with: `systemctl --user [start|stop|status] php-dev-server`

3. **Committed Template System to Git**
   - `5bc11690` - Add multi-site template system (24 files, +3558/-1887)
   - `d237f125` - Update gitignore and add workflows
   - `a3a1bf81` - Remove desktop shortcut, update gitignore

4. **Updated .gitignore**
   - Excludes: `*.log`, `data/*.db`, `voice/recordings/*.mp3`, `voice/sessions/`
   - Excludes: `api/.env.local.php` (credentials)
   - Excludes: `*.desktop`, temp session logs

### Commands Reference

```bash
# PHP dev server (auto-starts on boot)
systemctl --user status php-dev-server
systemctl --user restart php-dev-server

# Push commits to remote
git push
```

---

## 2025-12-29 - Multi-Site Template System Created

### What We Did

1. **Created Config-Driven Multi-Site Architecture**
   - `config/bootstrap.php` - Auto-detects domain from HTTP_HOST and loads matching config
   - Strips port numbers and www prefix for clean domain matching
   - Falls back to `default.php` if no domain-specific config exists
   - Backwards-compatible: defines legacy constants for existing code

2. **Created Site Configuration Templates**
   - `config/config.template.php` - Full template with all options
   - `config/default.php` - Generic fallback config
   - `config/mechanicstaugustine.com.php` - Mobile mechanic config
   - `config/wesodjax.com.php` - Sod/landscaping config
   - `config/landimpressions.com.php` - General landscaping config

3. **Updated Voice System for Multi-Site**
   - `voice/incoming.php` - Dynamic greeting based on business type
   - `voice/dial_result.php` - Uses config for forwarding and callbacks
   - `voice/gpt_assistant.php` - Completely rewritten with business-type-aware prompts
   - `voice/swaig_functions.php` - Config-driven estimate generation

4. **Created Config-Driven Landing Page**
   - `index.php` - Full landing page template with:
     - LocalBusiness Schema markup
     - Open Graph tags
     - Dynamic services grid from config
     - Dynamic form fields from `estimates.input_fields` config
     - CSS variables from branding config

5. **Fixed Form Submission**
   - Created `api/form-submit.php` - Handles standard POST form data
   - Shows thank you page with business branding
   - Creates CRM lead if enabled in config
   - Logs submissions to `data/form_submissions.log`

6. **Created Deployment Tools**
   - `deploy-site.sh` - Script to create new site configs
   - `README.md` - Full documentation
   - `Caddyfile.template` - Multi-domain Caddy configuration

### Issues Fixed

| Issue | Fix |
|-------|-----|
| Port in HTTP_HOST breaking domain detection | Added regex to strip `:8888` from domain |
| Form returning "Invalid JSON" | Created new form-submit.php that handles POST data |

### Files Created/Modified

| File | Status | Purpose |
|------|--------|---------|
| `config/bootstrap.php` | NEW | Domain-based config auto-loader |
| `config/config.template.php` | NEW | Full config template |
| `config/default.php` | NEW | Fallback config |
| `config/mechanicstaugustine.com.php` | NEW | Mechanic site config |
| `config/wesodjax.com.php` | NEW | Sod/landscaping config |
| `config/landimpressions.com.php` | NEW | Landscaping config |
| `voice/incoming.php` | MODIFIED | Uses bootstrap, dynamic greeting |
| `voice/dial_result.php` | MODIFIED | Uses bootstrap, config-driven |
| `voice/gpt_assistant.php` | MODIFIED | Full rewrite for multi-business |
| `voice/swaig_functions.php` | MODIFIED | Config-driven estimates |
| `api/service-complete.php` | MODIFIED | Uses bootstrap |
| `api/form-submit.php` | NEW | POST form handler with thank you page |
| `index.php` | NEW | Config-driven landing page |
| `deploy-site.sh` | NEW | Site deployment script |
| `README.md` | NEW | Full documentation |
| `Caddyfile.template` | NEW | Multi-domain Caddy config |

### Config Structure

```php
return [
    'site' => [
        'name' => 'Business Name',
        'tagline' => 'Professional Services',
        'phone' => '+1234567890',
        'domain' => 'example.com',
        'service_area' => 'Jacksonville, FL',
    ],
    'business' => [
        'type' => 'landscaping',  // mechanic, landscaping, roofing, plumbing
        'category' => 'Lawn & Landscaping',
        'services' => ['Sod Installation', 'Lawn Care', ...],
    ],
    'branding' => [
        'primary_color' => '#22c55e',
        'secondary_color' => '#84cc16',
    ],
    'estimates' => [
        'enabled' => true,
        'input_fields' => [...],  // Dynamic form fields
        'gpt_prompt' => '...',    // Business-specific GPT prompt
    ],
    'phone' => [...],  // SignalWire/Twilio config
    'crm' => [...],    // CRM config with field mappings
    'openai' => [...], // OpenAI API key
];
```

### How to Test Locally

```bash
# Add domain to hosts file
echo "127.0.0.1 wesodjax.com" | sudo tee -a /etc/hosts

# Start PHP dev server
php -S 0.0.0.0:8888 -t /home/kylewee/code/master-template

# Visit http://wesodjax.com:8888
```

### Next Steps

1. **Fill in real credentials** in site configs (SignalWire, OpenAI, CRM)
2. **Set up Caddy** for production multi-domain routing
3. **Test phone system** with actual SignalWire webhooks
4. **Deploy to production** for wesodjax.com and landimpressions.com

---

## 2025-12-27 - CRM Workflow Automation + GPT Debugging

### What We Did

1. **Full CRM Pipeline Setup**
   - Converted stage field (228) from text input to dropdown
   - Created 11 color-coded pipeline stages:
     - New Lead (blue), Callback Needed (red), Quote Sent (orange)
     - Quote Viewed (purple), Quote Approved (green), Scheduled (teal)
     - In Progress (orange), Completed (green), Review Requested (purple)
     - Closed Won (green), Closed Lost (gray)
   - Updated all 322 existing leads to "New Lead" stage

2. **Created CRMHelper.php**
   - Centralized CRM API functions
   - `updateStage()` - Update lead pipeline stage
   - `addComment()` - Add activity log entry
   - `getLeadByPhone()` - Find existing leads
   - `transitionStage()` - Update + comment in one call

3. **Automatic Stage Transitions**
   - `recording_callback.php` → Sets "New Lead" + logs call as comment
   - `dial_result.php` → Sets "Callback Needed" for missed calls
   - `QuoteSMS.php` → Sets "Quote Sent/Viewed/Approved" stages
   - `service-complete.php` → Sets "Completed" stage

4. **Fixed GPT Assistant Access**
   - **Root cause**: File permissions were `rw-------` (owner only)
   - Web server (www-data) couldn't read `gpt_assistant.php`
   - Fixed with `chmod 644` on all voice PHP files
   - Fixed sessions directory permissions (`chmod 777`)
   - Created `gpt_assistant.log` with proper permissions

5. **Updated Call Flow**
   - Dial timeout: 5s → 18s (more time to answer)
   - Added greeting: "Thanks for calling EZ Mobile Mechanic. Let me get Kyle on the line."
   - Added `ringTone="us"` for standard US ring tone

6. **Created CRM Setup Guide**
   - `docs/CRM_WORKFLOW_SETUP.md` - Full guide for:
     - Kanban board setup
     - SMS rules configuration
     - Email notifications
     - Process automation
     - Reports and analytics

### Issues Found

| Issue | Status |
|-------|--------|
| GPT assistant not being called after dial timeout | DEBUGGING |
| `dial_result.php` returns Redirect TwiML but call hangs up | INVESTIGATING |
| SignalWire not following Redirect to `gpt_assistant.php` | INVESTIGATING |

### Key Observations

- `dial_result.php` IS being called (logs show `dial_result_entry`)
- `DialCallStatus` = "no-answer" (correct)
- TwiML Redirect is generated correctly
- But SignalWire hangs up instead of following redirect
- GPT assistant works when called directly (tested via curl)

### Files Changed

| File | Change |
|------|--------|
| `lib/CRMHelper.php` | NEW - Centralized CRM API functions |
| `lib/QuoteSMS.php` | Added CRM stage updates on quote actions |
| `lib/WorkflowEngine.php` | Created then deleted (using CRMHelper instead) |
| `voice/recording_callback.php` | Added stage="New Lead" + comment logging |
| `voice/dial_result.php` | Added missed call detection, early logging, TwiML logging |
| `voice/incoming.php` | Timeout 5→18s, added greeting |
| `api/service-complete.php` | Added CRM stage update |
| `docs/CRM_WORKFLOW_SETUP.md` | NEW - CRM configuration guide |

---

## 2025-12-26 (Part 3) - Estimate Page + SMS Investigation

### What We Did

1. **get-estimate.php - Engine Field Updated**
   - Changed from dropdown (V6, V8, etc.) to text input
   - Now accepts liter displacement: "3.0L", "2.5L Turbo", "5.7L Hemi"
   - More accurate for AI labor estimates

2. **SignalWire SMS Investigation**
   - **Problem:** SMS not sending - "From must belong to an active campaign"
   - **Root cause:** 10DLC registration required, brands stuck in "pending"
   - Two brands registered but pending for 3+ weeks (abnormal):
     - `Mobilemechanic.best` - pending since Dec 4
     - `We Sod Jax` - pending since Dec 9
   - No campaigns created yet (can't create until brand approved)
   - API credentials verified working (old token good, new one invalid)

3. **Toll-Free Alternative Checked**
   - SignalWire toll-free numbers don't have SMS enabled
   - Not a viable workaround

4. **Cisco 7925 Discussion**
   - WiFi VoIP phone - voice only, no SMS capability
   - Could be used as shop/office handset via SIP
   - Not useful for SMS problem

### Issues Outstanding

| Issue | Status | Action Needed |
|-------|--------|---------------|
| SMS blocked by 10DLC | **Blocking** | Contact SignalWire - brands stuck pending 3 weeks |
| Toll-free SMS | Not available | SignalWire TF numbers don't have SMS |

### Next Steps for SMS

1. **Contact SignalWire support** - Ask why brands pending so long
2. **Try Sole Proprietor brand** - May approve faster than Private Profit
3. **Alternative:** Twilio has "Low Volume Standard" for <1000 msgs/month

---

## 2025-12-26 (Part 2) - GPT Integration Complete

### What We Did

1. **Fixed OpenAI API Key**
   - Old key was invalid (401 error)
   - User created new service account key in OpenAI dashboard
   - Updated `api/.env.local.php` with new key

2. **GPT-Powered Estimates - WORKING**
   - Updated `voice/swaig_functions.php` with GPT-4o-mini estimate function
   - Removed old keyword-based `auto_estimate_from_transcript()` dependency
   - Tested: 96 Geo Metro clutch = $652.50 (labor 4.5hrs + parts $225)
   - Cost: ~$0.0001 per estimate (basically free)

3. **Replaced Sarah with GPT Voice Assistant**
   - Decision: Use one GPT brain for everything (voice, estimates, SMS)
   - Created `voice/gpt_assistant.php` - full conversational AI
   - Cool, chill personality ("Alright", "I got you", "No worries")
   - Male voice (Polly.Matthew)
   - Collects: name, vehicle (year/make/model), problem
   - Texts estimate to customer (not verbal)
   - Creates CRM lead automatically

4. **Updated Call Flow**
   - `voice/dial_result.php` now redirects to GPT assistant (not voicemail)
   - Switched phone +19047066669 from Sarah to LAML webhooks
   - Flow: Call → Try mechanic (18s) → No answer → GPT picks up

### Decisions Made

| Decision | Reason |
|----------|--------|
| One GPT for everything | Same AI, same billing, full control |
| GPT-4o-mini model | Dirt cheap (~$0.10/month for 1000 estimates) |
| Text estimates, don't say them | Control, paper trail, rate limiting possible |
| Service account for OpenAI | Separate billing, not tied to personal account |

### Files Changed

| File | Change |
|------|--------|
| `voice/swaig_functions.php` | GPT-powered `get_estimate` function |
| `voice/gpt_assistant.php` | NEW - Full GPT voice assistant |
| `voice/dial_result.php` | Redirects to GPT instead of voicemail |
| `voice/recording_callback.php` | Commented out old scraper require |
| `api/.env.local.php` | New OpenAI API key |

---

## 2025-12-26 (Part 1) - Cloudflare Security + Cleanup

### What We Did

1. **Cloudflare Security Setup**
   - Changed SSL mode: `full` → `strict`
   - Changed Min TLS: `1.0` → `1.2`
   - Enabled WAF managed rules
   - Created custom rule to allow `/voice/` webhooks

2. **Codebase Cleanup**
   - Organized docs into `docs/` folder (19 files)
   - Organized tests into `tests/` folder
   - Deleted old backups, scripts (227MB freed)
   - Renamed `feature-expansion/` → `future-expansion/`
   - Archived old scraper to `archive/old-scraper-system/`

3. **SignalWire AI Agent (Sarah) - Now Replaced**
   - Was working but replaced with GPT system
   - Agent still exists if needed: `c1ddbedc-7a12-4d54-87c1-cd79ced4077e`

---

## Current Status Summary

### What's Working
- GPT-powered estimate generation (GPT-4o-mini)
- CRM lead creation and pipeline tracking
- Call recording and transcription
- Stage automation (New Lead, Callback Needed, etc.)
- Web estimate form (get-estimate.php)

### What's Broken/Blocked
| Issue | Impact | Priority |
|-------|--------|----------|
| SMS blocked by 10DLC | Can't send estimate texts | HIGH |
| GPT redirect not working | Missed calls go nowhere | HIGH |
| Recording download ("no_twilio_creds") | Transcription fails | MEDIUM |

### Pending Investigation
- Why SignalWire ignores Redirect TwiML in `dial_result.php`
- Why SignalWire brands stuck pending for 3+ weeks

---

## Key Config Reference

```php
// SignalWire credentials (api/.env.local.php)
SIGNALWIRE_PROJECT_ID: ce4806cb-ccb0-41e9-8bf1-7ea59536adfd
SIGNALWIRE_SPACE: mobilemechanic.signalwire.com
SIGNALWIRE_PHONE_NUMBER: +19047066669

// CRM Entity 26 Fields
first_name: 219, last_name: 220, phone: 227, email: 235
year: 231, make: 232, model: 233, notes: 230, stage: 228
```

---

## How to Continue

```bash
# Check latest logs after a test call
tail -5 /home/kylewee/code/inbred/voice/voice.log | python3 -m json.tool

# Look for twiml_response in the log
grep "twiml_response" /home/kylewee/code/inbred/voice/voice.log | tail -1
```

Tell Claude:
> "Read SESSION_LOG.md - we're debugging why GPT assistant doesn't pick up after dial timeout"

---
*Last updated: 2025-12-29*
