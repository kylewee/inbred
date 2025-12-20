# CRM Telephony & SMS Integration

## Summary

Integrated SignalWire phone system with Rukovoditel CRM for:
1. **Call Logging** - Incoming calls automatically logged to CRM call history
2. **SMS Module** - SignalWire SMS module created for CRM (pending A2P registration)

---

## 1. Telephony Call Logging

### How It Works

When a call comes in through SignalWire:
1. SignalWire calls `incoming.php` → direct dial to Kyle's cell
2. Recording is captured during the call
3. On call end, `recording_callback.php`:
   - Creates a CRM Lead (entity 25)
   - Logs call to CRM Telephony History (`app_ext_call_history` table)

### Configuration

**CRM Telephony API Key** (already set in CRM settings):
```
mech4n1c5A1d3c7f9b2e8a4d6c0f3b5a7e9d1c3f5
```

**Endpoint used:**
```
http://idk-caddy:80/crm/index.php?module=ext/telephony/save_call
  &key=API_KEY
  &phone=PHONE_NUMBER
  &date_added=UNIX_TIMESTAMP
  &direction=inbound|outbound
  &duration=SECONDS
```

### Files Modified

- `voice/telephony_log.php` (NEW) - Helper function for call logging
- `voice/recording_callback.php` - Added include and call to log telephony

### Viewing Call History

In CRM, go to: **Extension → Call History**

Or via URL: http://localhost:8080/crm/index.php?module=ext/call_history/view

---

## 2. SMS Module - SignalWire

### Module Location
```
crm/plugins/ext/sms_modules/signalwire/
├── signalwire.php      # Main module code
└── languages/
    └── english.php     # Language strings
```

### Installation Steps

1. Go to CRM: **Extension → Modules → SMS Modules**
2. Click **Install Module**
3. Find "SignalWire" in the list (marked as "International")
4. Click **Install**
5. Configure with your SignalWire credentials:
   - **Space**: mobilemechanic.signalwire.com
   - **Project ID**: ce4806cb-ccb0-41e9-8bf1-7ea59536adfd
   - **API Token**: (your token from SignalWire dashboard)
   - **From Number**: +19047066669

### ⚠️ IMPORTANT: A2P Registration Required

SignalWire currently has A2P (Application-to-Person) SMS blocked because:
- A2P requires verified business entity
- You have a Sole Proprietorship (requires LLC)

**Options:**
1. Register LLC and complete A2P 10DLC registration
2. Use P2P (Person-to-Person) if volume is low (<~200/day)
3. Use toll-free number (faster approval, ~$15/mo)

### SMS Sending Rules

After installing the module, set up rules:
1. Go to: **Extension → Modules → SMS Modules → Sending SMS Rules**
2. Create rule for when to send SMS (e.g., new lead created)
3. Select SignalWire module
4. Select entity (Leads)
5. Configure message template using [Field IDs]

Example template:
```
Hi [first_name], thanks for calling Mobile Mechanic St Augustine! 
We'll be in touch shortly about your [year] [make] [model].
```

---

## 3. Telephony Module (Click-to-Call)

The CRM also supports click-to-call functionality.

### Setup

1. Go to: **Extension → Modules → Telephony Modules**
2. Install "Link for Calls"
3. Configure the prefix to use your SIP client or softphone URL scheme

For SignalWire, the endpoint for incoming call tracking:
```
http://localhost:8080/crm/index.php?module=ext/telephony/save_call
  &key=mech4n1c5A1d3c7f9b2e8a4d6c0f3b5a7e9d1c3f5
  &phone=%NUMBER%
  &date_added=%TIMESTAMP%
  &direction=%DIRECTION%
  &duration=%DURATION%
```

---

## Quick Reference

| Component | URL/Location |
|-----------|-------------|
| Call History | http://localhost:8080/crm/index.php?module=ext/call_history/view |
| SMS Modules | http://localhost:8080/crm/index.php?module=ext/modules/sms |
| Telephony Modules | http://localhost:8080/crm/index.php?module=ext/modules/telephony |
| API Key Setting | http://localhost:8080/crm/index.php?module=configuration/index |

---

## Phone Numbers

- **SignalWire**: +1 904-706-6669
- **Google Voice** (forwards to SW): +1 904-217-5152
- **Kyle's Cell**: +1 904-663-4789

---

*Last updated: December 18, 2025*
