# Voice System Session Log

## Overview

Voice handling for multi-site lead generation platform.

**Call Flow:**
```
Incoming Call → incoming.php → Forward to owner
       ↓
Answered → recording_callback.php → Whisper → CRM Lead
       ↓
Missed → dial_result.php → ivr_intake.php → Collect info → CRM Lead
```

## Files

| File | Purpose | Status |
|------|---------|--------|
| `incoming.php` | Entry point, forwards to owner | Working |
| `dial_result.php` | Routes answered/missed calls | Needs update |
| `recording_callback.php` | Transcribe answered calls, create lead | Working |
| `ivr_intake.php` | Voice prompts for missed calls | Needs config update |
| `ivr_recording.php` | Process recordings, create lead | Needs config update |
| `signalwire_webhook.php` | SignalWire field adapter | Working |
| `hangup.php` | Simple hangup TwiML | Working |

## To Do

1. Update ivr_intake.php to use bootstrap.php config system
2. Update ivr_recording.php to use bootstrap.php config system
3. Update dial_result.php to route missed calls to ivr_intake.php
4. Replace old auto_estimate with GPT-based estimation
5. Test full flow

## Config System

All files should use:
```php
require_once __DIR__ . '/../config/webhook_bootstrap.php';
$siteName = config('site.name');
```

---

## Session History

### 2025-12-30 - Fresh Start

- Created clean voice folder from working pre-GPT files
- Base files: ivr_intake.php, ivr_recording.php (structured prompts, reliable)
- Goal: Structured data collection + optional GPT for estimates
