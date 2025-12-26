# Mechanics St. Augustine - Complete Configuration Reference

This document contains ALL configuration information found scattered throughout the project files. Keep this updated as the single source of truth for system configuration.

## 📁 Project Structure Overview

```
mechanicsaintaugustine.com/
├── api/                    # Go backend API
├── voice/                  # Twilio phone system & webhooks
├── crm/                    # Rukovoditel CRM system
├── quote/                  # Quote request system
├── backend/                # Go API backend (alternative structure?)
├── admin/                  # PHP admin tools
├── Mobile-mechanic/        # Mobile mechanic demo files
├── Caddyfile              # Web server configuration
└── index.html             # Main website
```

---

## 🔧 Environment Configuration Files

### Primary Environment File: `api/.env.local.php`

**Location**: `/api/.env.local.php`

```php
<?php

// === TWILIO CONFIGURATION ===
define('TWILIO_FORWARD_TO', getenv('TWILIO_FORWARD_TO') ?: '+19046634789');  // Fallback phone
define('TWILIO_ACCOUNT_SID', getenv('TWILIO_ACCOUNT_SID') ?: '');
define('TWILIO_AUTH_TOKEN', getenv('TWILIO_AUTH_TOKEN') ?: '');
define('TWILIO_SMS_FROM', getenv('TWILIO_SMS_FROM') ?: '');

// === CRM CONFIGURATION ===
const CRM_API_URL = 'https://mechanicstaugustine.com/crm/api/rest.php';
define('CRM_API_KEY', getenv('CRM_API_KEY') ?: '');
const CRM_LEADS_ENTITY_ID = 26;
const CRM_USERNAME = 'kylewee2'; 
define('CRM_PASSWORD', getenv('CRM_PASSWORD') ?: '');
define('CRM_CREATED_BY_USER_ID', 1); // kylewee2's user ID

// === CRM FIELD MAPPING ===
// Maps extracted customer data to CRM field IDs
const CRM_FIELD_MAP = [
  'name'        => 0,    // Combined name (0 = disabled)
  'first_name'  => 219,  // First Name field ID in CRM
  'last_name'   => 220,  // Last Name field ID in CRM  
  'phone'       => 227,  // Phone field ID in CRM
  'email'       => 235,  // Email field ID in CRM
  'address'     => 234,  // Address field ID in CRM
  'year'        => 231,  // Vehicle year field ID
  'make'        => 232,  // Vehicle make field ID
  'model'       => 233,  // Vehicle model field ID
  'engine_size' => 0,    // Engine size (0 = disabled)
  'notes'       => 230,  // Notes field ID (textarea_wysiwyg)
];

// === NOTIFICATION CONFIGURATION ===
const QUOTE_NOTIFICATION_EMAILS = ['sodjacksonville@gmail.com'];
const QUOTE_NOTIFICATION_EMAIL_FROM = '';
const QUOTE_STATUS_WEBHOOK = '';
const QUOTE_WORKFLOW_ADMIN_TOKEN = 'admin-token-change-me';
const QUOTE_CONFIRMATION_BASE_URL = 'https://mechanicstaugustine.com';
const QUOTE_REVIEW_LINK = 'https://g.page/r/Cc7RMechReview';

// === STATUS CALLBACK CONFIGURATION ===
const STATUS_CALLBACK_TOKEN = '';
const STATUS_CALLBACK_EMAILS = ['sodjacksonville@gmail.com'];
const STATUS_CALLBACK_EMAIL_FROM = '';

// === OPENAI CONFIGURATION ===
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');

// === SECURITY TOKENS ===
const VOICE_RECORDINGS_TOKEN = 'msarec-2b7c9f1a5d4e';

// === TRANSCRIPTION SETTINGS ===
const TWILIO_TRANSCRIBE_ENABLED = true;  // Auto-transcribe recordings up to 2 minutes
```

### Backup Configuration: `api/.env.local.php.bak`

**Location**: `/api/.env.local.php.bak`  
**Note**: Simpler version with minimal CRM field mapping

```php
<?php
const CRM_API_URL = 'https://mechanicstaugustine.com/crm/api/rest.php';
const CRM_API_KEY = 'REDACTED_CRM_API_KEY';
const CRM_LEADS_ENTITY_ID = 26;
const CRM_FIELD_MAP = [
  'first_name' => 219,  // First Name field ID
  'last_name'  => 220,  // Last Name field ID  
  'name'       => 219,  // fallback: full name goes into First Name
];
```

---

## 🗄️ Database Configuration

### CRM Database Config: `crm/config/database.php`

**Location**: `/crm/config/database.php`

```php
<?php
// Rukovoditel CRM Database Connection
define('DB_SERVER', 'localhost');
define('DB_SERVER_USERNAME', 'kylewee');
define('DB_SERVER_PASSWORD', 'rainonin');
define('DB_SERVER_PORT', '');		
define('DB_DATABASE', 'rukovoditel');
```

### Go Backend Database Config: `backend/internal/config/config.go`

**Location**: `/backend/internal/config/config.go`

Environment variables expected:
- `DATABASE_URL` - Full PostgreSQL connection string
- `DATABASE_DRIVER` - Database driver (default: "postgres")
- `DB_MAX_OPEN_CONNS` - Max open connections (default: 10)
- `DB_MAX_IDLE_CONNS` - Max idle connections (default: 5)
- `DB_CONN_MAX_LIFETIME` - Connection max lifetime (default: 1 hour)
- `DB_CONN_MAX_IDLE_TIME` - Connection max idle time (default: 30 minutes)

### Docker Database Config: `backend/docker-compose.yml`

**Location**: `/backend/docker-compose.yml`

```yaml
services:
  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ezm
      POSTGRES_USER: ezm  
      POSTGRES_PASSWORD: ezm
    ports:
      - "5432:5432"
```

---

## 🌐 Web Server Configuration

### Caddy Configuration: `Caddyfile`

**Location**: `/Caddyfile`

```
{
    email sodjacksonville@gmail.com
    admin off
}

mechanicstaugustine.com, www.mechanicstaugustine.com {
    root * /home/kylewee/code/idk/projects/mechanicsaintaugustine.com
    
    # PHP processing
    php_fastcgi unix//run/php/php8.3-fpm.sock
    
    # Serve static files
    file_server
}

localhost:8092 {
    root * /home/kylewee/code/idk/projects/mechanicsaintaugustine.com
    
    # PHP processing  
    php_fastcgi unix//run/php/php8.3-fpm.sock
    
    # Serve static files
    file_server
}
```

**Key Points**:
- Uses PHP 8.3 FPM
- Serves from the corrected project directory (was pointing to non-existent `/home/kylewee/mechanicsaintaugustine.com/site`)
- Handles both production domain and localhost:8092
- TLS certificates managed by Caddy/Let's Encrypt

---

## 📞 Phone System Configuration

### Voice System Components

**Location**: `/voice/` directory

**Key Files**:
- `recording_callback.php` - Main webhook processor  
- `incoming.php` - Handles incoming calls
- `ci_callback.php` - AI transcription webhook
- `test_webhook.php` - Test webhook simulator

**Configuration Sources**:
1. Loads from `../api/.env.local.php` (primary)
2. Loads from `../crm/config/database.php` (for database fallback)

**Phone Numbers Referenced**:
- `+19046634789` - Primary fallback/forwarding number
- `+19042175152` - Business line (appears in logs)

**Twilio Webhooks Expected**:
- Recording callbacks: `POST /voice/recording_callback.php`
- Call status: `POST /voice/call_status.php` 
- Incoming calls: `POST /voice/incoming.php`

---

## 🔐 Security & API Keys

### Required Environment Variables

**Twilio**:
- `TWILIO_ACCOUNT_SID` - Twilio Account SID
- `TWILIO_AUTH_TOKEN` - Twilio Auth Token  
- `TWILIO_SMS_FROM` - SMS sending number

**OpenAI**:
- `OPENAI_API_KEY` - For AI transcription and customer data extraction

**CRM**:
- `CRM_API_KEY` - API key for CRM access
- `CRM_PASSWORD` - Password for user 'kylewee2'

**Backend API**:
- `JWT_SECRET` - Required for Go backend (no default)

### Access Tokens

**Voice Recordings Access**:
- Token: `msarec-2b7c9f1a5d4e`
- URL: `/voice/recording_callback.php?action=recordings&token=msarec-2b7c9f1a5d4e`

**Admin Workflow**:
- Token: `admin-token-change-me` (should be changed)

---

## 📧 Email & Notifications

### Email Addresses Used

**Primary Contact**: `sodjacksonville@gmail.com`

**Used For**:
- Quote notifications (`QUOTE_NOTIFICATION_EMAILS`)
- Status callback notifications (`STATUS_CALLBACK_EMAILS`)
- General system notifications

### Links & URLs

**Business Links**:
- Main site: `https://mechanicstaugustine.com`
- CRM API: `https://mechanicstaugustine.com/crm/api/rest.php`
- Review link: `https://g.page/r/Cc7RMechReview`

---

## 🔧 Go Backend API Configuration

### Environment Variables Expected

**Location**: `/backend/internal/config/config.go`

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | development | Application environment |
| `HTTP_PORT` | 8080 | HTTP server port |
| `DATA_BACKEND` | memory | Data storage backend (memory/postgres) |
| `DATABASE_URL` | - | PostgreSQL connection string |
| `JWT_SECRET` | - | **Required** JWT signing secret |
| `JWT_EXPIRY` | 24h | JWT token expiry time |
| `REFRESH_TOKEN_TTL` | 30d | Refresh token TTL |
| `REDIS_URL` | - | Redis connection string |

### Defaults

```go
defaultHTTPPort = 8080
defaultShutdownTimeout = 10s 
defaultReadHeaderTimeout = 5s
defaultDataBackend = "memory"
defaultDatabaseDriver = "postgres"
defaultJWTExpiry = 24h
defaultRefreshTokenTTL = 30d
```

---

## 🏥 System Health & Monitoring

### Log Files

**Voice System Logs**:
- `voice/voice.log` - All phone system activity
- Format: JSON lines with timestamp, IP, and event data

**Test Endpoints**:
- `/voice/test_webhook.php` - Webhook simulator
- `/backend/health` - Go API health check (likely)

---

## 🚨 Critical Configuration Notes

### Issues Found & Fixed

1. **Caddyfile Path Issue**: 
   - ❌ Was pointing to `/home/kylewee/mechanicsaintaugustine.com/site` 
   - ✅ Fixed to `/home/kylewee/code/idk/projects/mechanicsaintaugustine.com`

2. **Database Credentials**: 
   - Username: `kylewee`
   - Password: `rainonin` (hardcoded - consider using env vars)
   - Database: `rukovoditel`

3. **CRM Field IDs**:
   - Field IDs are specific to the CRM instance
   - Currently mapped to Rukovoditel field numbers
   - Changes to CRM structure require field mapping updates

### Security Considerations

1. **Hardcoded Credentials**: Database password is hardcoded in `crm/config/database.php`
2. **API Keys**: Should be set via environment variables, not hardcoded
3. **Tokens**: Admin token should be changed from default
4. **Phone Numbers**: Personal phone numbers exposed in config

### Commented Code Policy

As requested: **DO NOT delete any configuration lines** - comment them out with `//` instead for future reference.

---

## 📝 Configuration Checklist

When setting up the system, ensure:

- [ ] All environment variables are set
- [ ] Database credentials are correct  
- [ ] Twilio webhooks point to correct URLs
- [ ] CRM field mappings match your CRM setup
- [ ] Caddy/web server points to correct directory
- [ ] Phone numbers are updated for your use case
- [ ] SSL certificates are configured
- [ ] File permissions allow log writing
- [ ] PHP 8.3 FPM is configured and running

---

## 📞 Support Contacts

**Technical Issues**: Check `voice.log` for phone system issues  
**CRM Issues**: Verify field mappings in `.env.local.php`  
**Web Server Issues**: Check Caddy logs and file permissions