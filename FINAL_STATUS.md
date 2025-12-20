# Migration Status Report - December 11, 2025

## Migration Summary
Successfully migrated **mechanicstaugustine.com** to HostGator.

## Configuration Details
- **Server:** HostGator (gator2117.hostgator.com)
- **Filesystem Path:** `~/public_html/website_7e9e6396/`
- **Database:** `cpunccte_mechanic`
- **DB User:** `cpunccte_kylewee`
- **Backup Source:** 
  - Site: `backup_site_20251211_060322.tar.gz`
  - Data: `backup_crm_20251211_060302.sql`

## Key Files
The following files on the server were updated with new credentials:
1.  `api/.env.local.php` (Master configuration)
2.  `crm/config/database.php` (CRM connection)
3.  `voice/incoming.php` (Uploaded latest working version)
4.  `voice/recording_callback.php` (Uploaded latest working version)

## Post-Migration Checklist
1.  **DNS**: Point A record to HostGator IP.
2.  **SSL**: Run "AutoSSL" in cPanel if site shows "Not Secure".
3.  **Cron Jobs**: You may need to re-setup cron jobs in cPanel for the CRM background tasks (e.g., email sending).
    - Command: `/usr/local/bin/php /home/cpunccte/public_html/website_7e9e6396/crm/cron/cron.php` (Verify path in CRM docs).

## Recent Fixes & Changes (14:30 EST)
### 1. Transcription System
- **Issue:** HostGator lacks `ffmpeg`, preventing audio conversion for OpenAI Whisper.
- **Fix:** Switched to **SignalWire Native Transcription**.
- **Changes:**
    - `voice/incoming.php`: Added `recordingTranscribe="true"` to Dial verb.
    - `voice/recording_callback.php`: Updated to check for `TranscriptionText` payload.

### 2. CRM Access Issues
- **Issue:** `/crm` not loading (404/500).
- **Fixes Applied:**
    - Uploaded standard `.htaccess` to `crm/` directory.
    - Cleared CRM cache (`crm/cache/*`).

### 3. Diagnostics
- Created `voice/voice.log` for debugging call flows.
- Pending verification of `/admin` and `/crm` access.

## Troubleshooting
If you see "Database Connection Error":
- Check `api/.env.local.php` and `crm/config/database.php`.
- Ensure `cpunccte_kylewee` user has "All Privileges" on `cpunccte_mechanic` database in cPanel.

If Calls Fail:
- Check `voice/voice.log` (if writable).
- Ensure `voice/` folder permissions are 755.
