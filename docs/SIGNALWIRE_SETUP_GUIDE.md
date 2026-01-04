# SignalWire Setup Guide

## Current Status ✅

**Phone System:** WORKING
**Call Recording:** WORKING
**OpenAI Integration:** CONFIGURED (environment variable found)

## What You Need To Complete

### 1. Get Your SignalWire API Token

**Steps:**
1. Go to https://mobilemechanic.signalwire.com (your SignalWire space)
2. Log in to your dashboard
3. Navigate to: **Settings → API** or **Credentials**
4. Find your **API Token** (also called Auth Token or Project Token)
5. Copy the token (it looks like: `PT...` followed by random characters)

**Your SignalWire Details:**
- **Space:** mobilemechanic.signalwire.com
- **Project ID:** ce4806cb-ccb0-41e9-8bf1-7ea59536adfd
- **Phone Number:** +19047066669
- **API Token:** ⚠️ NEEDS TO BE ADDED (see steps above)

### 2. Update Configuration

**Option A: Set Environment Variable (Recommended)**
```bash
export TWILIO_AUTH_TOKEN="YOUR_SIGNALWIRE_API_TOKEN_HERE"
```

Add this to your `~/.bashrc` or `~/.profile` to make it permanent:
```bash
echo 'export TWILIO_AUTH_TOKEN="YOUR_TOKEN_HERE"' >> ~/.bashrc
source ~/.bashrc
```

**Option B: Update Config File Directly**
Edit: `/home/kylewee/code/idk/projects/mechanicstaugustine.com/api/.env.local.php`

Change line 12:
```php
define('TWILIO_AUTH_TOKEN', getenv('TWILIO_AUTH_TOKEN') ?: 'YOUR_SIGNALWIRE_API_TOKEN_HERE');
```

Replace `YOUR_SIGNALWIRE_API_TOKEN_HERE` with your actual token.

### 3. CRM Configuration Status

**CRM Database:** ✅ Connected
**CRM User:** kylewee (ID: 1)
**Leads Entity:** 26 (table: app_entity_26)
**CRM API:** ⚠️ Needs API Key

**To Get CRM API Key:**
1. Log in to your CRM: https://mechanicstaugustine.com/crm/
2. Go to: **Configuration → Integrations → API**
3. Generate or copy your API key
4. Set it as environment variable:
   ```bash
   export CRM_API_KEY="YOUR_CRM_API_KEY_HERE"
   ```

**Alternative:** Update line 15 in `/home/kylewee/code/idk/projects/mechanicstaugustine.com/api/.env.local.php`:
```php
define('CRM_API_KEY', getenv('CRM_API_KEY') ?: 'YOUR_ACTUAL_CRM_API_KEY');
```

## Testing Your Setup

Once you've added both tokens, test with:

1. **Call your SignalWire number:** +1 (904) 706-6669
2. **Check the call log:**
   ```bash
   tail -f /home/kylewee/code/idk/projects/mechanicstaugustine.com/voice/voice.log
   ```
3. **Check recordings page:**
   https://mechanicstaugustine.com/voice/recording_callback.php?action=recordings&token=msarec-2b7c9f1a5d4e

## What's Already Working

✅ **OpenAI API Key:** Configured via environment variable
✅ **SignalWire Space:** mobilemechanic.signalwire.com
✅ **SignalWire Project ID:** ce4806cb-ccb0-41e9-8bf1-7ea59536adfd
✅ **Phone Number:** +19047066669
✅ **Call Forwarding:** Calls forward to +19047066669
✅ **Call Recording:** All calls are being recorded
✅ **CRM Database:** Connected (MySQL: rukovoditel)
✅ **CRM Field Mappings:** Configured for leads entity 26

## What Needs Configuration

⚠️ **SignalWire API Token:** Required for downloading recordings
⚠️ **CRM API Key:** Required for creating leads via REST API

## Troubleshooting

### "401 Unauthorized" when downloading recordings
- **Cause:** Missing or incorrect SignalWire API Token
- **Fix:** Add your SignalWire API Token (see step 1 above)

### "CRM config missing" errors
- **Cause:** Missing CRM_API_KEY
- **Fix:** Add your CRM API Key (see step 3 above)

### Transcriptions not working
- **Status:** OpenAI API key is already set ✅
- **Check:** Verify with `echo $OPENAI_API_KEY`

## Quick Reference

**Configuration File:**
`/home/kylewee/code/idk/projects/mechanicstaugustine.com/api/.env.local.php`

**Call Logs:**
`/home/kylewee/code/idk/projects/mechanicstaugustine.com/voice/voice.log`

**Recordings Page:**
https://mechanicstaugustine.com/voice/recording_callback.php?action=recordings&token=msarec-2b7c9f1a5d4e

**CRM Login:**
https://mechanicstaugustine.com/crm/

**SignalWire Dashboard:**
https://mobilemechanic.signalwire.com

## Next Steps

1. [ ] Get SignalWire API Token from dashboard
2. [ ] Add token to environment or config file
3. [ ] Get CRM API Key from CRM settings
4. [ ] Add CRM API key to environment or config file
5. [ ] Test a phone call
6. [ ] Verify recording downloads work
7. [ ] Verify leads are created in CRM
