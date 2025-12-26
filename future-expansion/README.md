# Feature Expansion - Collected Code From All Project Copies

This folder contains useful code gathered from duplicate project folders across your system.
Use this as a reference when adding features to the main working site.

## 📁 Folder Contents

### `python-call-system/`
**Source:** `/home/kylewee/code/call-handling-workflow/`  
**Date:** November 11, 2024  
**Language:** Python (Flask)

**Unique Features:**
- ✨ **Urgency scale 1-5** - DTMF input during call, prioritizes leads
- ✨ **SMS with edit link** - Customer can review/fix their info before service
- ✨ **/new quick form** - Short URL for roadside intake
- ✨ **/trust page** - Marketing page about ethics
- ✨ **/pricing page** - How mechanic shops charge

**Key Files:**
- `main.py` - Main Flask app with all routes
- `demonstrate.py` - Demo script
- `templates/` - HTML templates (customer_form, quick_intake, pricing, trust, confirmation)
- `database_schema.sql` - Database schema

---

### `clean-php-version/`
**Source:** `/home/kylewee/code/idk/projects/mechanicstaugustine.com/voice/`  
**Date:** December 10, 2024  
**Language:** PHP

**Why it's useful:**
- Cleaner, refactored code structure
- Proper PHP type hints
- Better function organization
- Single config file approach

**Note:** Missing some features from current version (dual provider support, DB fallback, email sending)

---

### `idk-voice/`
**Source:** `/home/kylewee/code/idk/idk/voice/`  
**Date:** November 9, 2024

Older voice webhook handlers. Check for any unique functionality.

---

### `inbred-voice/`
**Source:** `/home/kylewee/code/inbred/voice/`  
**Date:** December 18-19, 2024

The Docker-deployed version (most recent). Should match current working version.

---

### `nov-backup-voice/`
**Source:** `/home/kylewee/code/idk/backups/idk-11-05-25/projects/mechanicsaintaugustine.com/voice/`  
**Date:** November 4, 2024

Backup from November 5th. Compare with current version for any lost features.

---

### `api-versions/`
**Source:** `/home/kylewee/code/idk/idk/api/`

Alternative API implementations. Contains:
- `quote_intake.php`
- `quote.php`
- Other API handlers

---

### `sms-modules/`
**Source:** CRM plugins directory

Alternative SMS providers that might work without A2P registration:
- `clicksend/` - ClickSend SMS API
- `wamm_chat/` - WhatsApp messaging
- `wappi_pro/` - Another WhatsApp option

---

### `database-schemas/`
- `database_schema.sql` - From Python call-handling-workflow
- `setup_database.sql` - From Mobile-mechanic

---

### `data/`
- `price-catalog.json` - 40 repairs with V8/old car multipliers

---

## 🎯 Priority Features to Port

1. **Urgency Scale (1-5)** from `python-call-system/main.py`
   - Add DTMF gather after recording in PHP IVR
   - Store urgency level in CRM lead

2. **Customer Edit Link** from `python-call-system/`
   - SMS customer a link to review their extracted info
   - Let them correct before service

3. **Quick Roadside Form** `/new`
   - Simple mobile form for when customer is stranded

4. **Trust/Pricing Pages**
   - Marketing content already written in templates

---

## 🗄️ Original Locations (to archive later)

These folders can be moved to `~/code/ARCHIVE/` once features are merged:

```
/home/kylewee/code/idk/projects/mechanicsaintaugustine.com
/home/kylewee/code/idk/projects/mechanicstaugustine.com
/home/kylewee/code/idk/mechanicsaintaugustine.com
/home/kylewee/code/idk/projects/voice-system
/home/kylewee/code/idk/projects/mechanic-voice-system
/home/kylewee/code/call-handling-workflow
```
