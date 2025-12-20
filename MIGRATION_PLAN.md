# HostGator Migration Plan (December 2025)

## Overview
Moving from the current server to HostGator.
Strategy: **Fresh Install** of Rukovoditel CRM + Add-ons, then migrating data/configuration.

## Current Status (2025-12-11)
- **Backup Files**:
  - `backup_crm_20251211_060302.sql` (Database dump)
  - `backup_site_20251211_060322.tar.gz` (Site files)
  - `hostgator_restore.php` (Custom restore tool)
- **Location**: Uploaded to `public_html/tmp/` on HostGator server (`gator2117.hostgator.com`).
- **Connection Issue**:
  - User: `cpunccte`
  - Host: `gator2117.hostgator.com`
  - Password provided (`lWJ5CEne0HdcIucq`) failed for both SSH (port 2222) and FTP.
  - Action Required: Verify cPanel/FTP password.

## Execution Plan

### 1. Fresh CRM Installation
Since moving servers, a fresh install is preferred to ensure compatibility.
1.  **Download**: Latest Rukovoditel version.
2.  **Database**: Create new MySQL database in HostGator cPanel.
3.  **Install**: Run standard Rukovoditel installer.
4.  **Add-ons**:
    - Install **API Add-on** (Critical for SignalWire integration).
    - Install other required extensions.

### 2. Configuration Migration
After the fresh install, restore the custom configuration:

1.  **Restore Voice System**:
    - Copy `/voice/` directory from backup.
    - Copy `/api/` directory from backup.
    - Copy `/quote/` directory from backup.

2.  **Update Config Files**:
    - Edit `api/.env.local.php`:
      - Update `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` with new HostGator credentials.
      - Verify `CRM_API_URL` points to the new installation.
      - Update `CRM_API_KEY` with the key from the *new* fresh install.

3.  **Twilio/SignalWire Update**:
    - Update webhook URLs in SignalWire dashboard to point to the new HostGator domain/IP.

### 3. Data Migration (Optional)
If not starting 100% fresh with data:
1.  Use `hostgator_restore.php` (if access is fixed) or phpMyAdmin to import the `backup_crm_*.sql` dump.
2.  Be careful not to overwrite the new `app_entity_` tables if the schema has changed between versions, though a full restore usually works best if versions match.

### 4. Verification Checklist
- [ ] **Voice**: Call the business number, ensure it rings cell + records.
- [ ] **Transcription**: Verify call recording appears in CRM with transcription.
- [ ] **SMS**: Verify confirmation texts are sent.
- [ ] **Web Quote**: Test the quote form on the website.

## Critical Paths
- **`voice/incoming.php`**: Entry point for calls.
- **`voice/recording_callback.php`**: Logic hub (Transcription -> CRM).
- **`api/.env.local.php`**: Master config file containing all keys.
