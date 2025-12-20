# Mechanicstaugustine.com - Complete Project Inventory

**Last Updated**: December 6, 2025
**Project Size**: 419 MB, 17,670+ files
**Status**: Active Production System

---

## Project Overview

Full-stack mobile mechanic service platform with integrated phone system, CRM, quote management, and AI-powered call transcription.

---

## Core Components

### 1. Backend API (Go)
**Location**: `backend/`

- **Main Server**: `cmd/api/main.go`
- **Database Seeder**: `cmd/seed/main.go`
- **Storage Layer**: `internal/storage/` (PostgreSQL + in-memory)
- **Authentication**: `internal/auth/` (JWT token-based)
- **HTTP API**: `internal/httpapi/`
- **Migrations**: `internal/database/`
- **Dependencies**: `go.mod`, `go.sum`

**Database**: PostgreSQL

### 2. Voice & Recording System (PHP)
**Location**: `voice/`

- `incoming.php` - Inbound call handler
- `recording_callback.php` - Process call recordings (78 KB, actively used)
- `call_status.php` - Call status tracking
- `ci_callback.php` - Integration callbacks
- `voice.log` - Active system logs
- `recordings/` - Stored voice files

**Features**:
- Twilio/SignalWire integration
- OpenAI Whisper transcription
- Call recording storage

### 3. Quote System (PHP)
**Location**: `quote/` and `api/`

- `quote_intake_handler.php` - Main quote processor
- `quote_intake.php` - Quote API endpoint
- `status_callback.php` - Twilio status callbacks
- `index.html` - Customer quote form
- `SMS_SETUP.md` - SMS integration docs

**Features**:
- Customer quote requests
- SMS notifications
- Status tracking

### 4. CRM System (Rukovoditel v3.6.2)
**Location**: `crm/`

- Full Rukovoditel CRM installation
- 55+ module directories
- Multi-language support
- Custom field mappings for mechanic business
- `config/` - CRM configuration
- `modules/` - CRM functionality
- `plugins/` - Plugin system
- `uploads/` - User files (owned by www-data)
- `backups/` - CRM backup directory
- `log/` - System logs

**Database**: MySQL/MariaDB

**URL**: https://crm.mechanicstaugustine.com

### 5. Mobile Mechanic Portal (PHP)
**Location**: `Mobile-mechanic/`

**Customer Features**:
- `login.php`, `register.php` - Customer authentication
- `Add_vehicles.php` - Vehicle management
- `servicerequest.php` - Service request submission
- `cappointment.php` - Appointment scheduling
- `cprofile.php` - Customer profile

**Mechanic Features**:
- `mregister.php` - Mechanic registration
- `mprofile.php` - Mechanic profile
- `maction.php` - Mechanic actions
- `Mechanic_details.php` - Mechanic info

**Database**:
- `database_connection.php` - DB connection
- `DB/mm.sql` - Database schema

**Assets**:
- `CSS/` - Stylesheets (Bootstrap, AOS, custom)
- `JS/` - JavaScript libraries (jQuery, Bootstrap, custom)
- `images/`, `fonts/` - Media assets

### 6. Admin Dashboard (PHP)
**Location**: `admin/`

- `dispatch.php` - Dispatch management
- `leads_approval.php` - Lead approval workflow
- `parts_orders.php` - Parts inventory
- `parts_order_export.php` - Export functionality

### 7. SignalWire Integration
**Location**: `signalwire/`

- `README.md` - Integration documentation
- `email-to-signalwire.txt` - Email templates
- **Brand**: Mobilemechanic.best (ff9797fe-a05e-4131-a6cf-6f5d2ca7bf33)
- **Status**: Active (migrated from Twilio Dec 5, 2025)

**Features**:
- 10DLC messaging campaigns
- Voice call handling
- SMS notifications

### 8. API Endpoints
**Location**: `api/`

- `quote_intake.php` - Quote processing
- `sms/` - SMS handling
- `sms_incoming.log` - SMS activity logs
- `sms_status.log` - SMS status tracking
- `.env.local.php` - Local environment config

---

## Documentation Files

- `README.md` (7.4K) - Main project documentation
- `MASTER_CONFIG_DOCUMENT.md` (11K) - Complete configuration reference
- `SERVICE_STATUS.md` (2.4K) - Service status tracking
- `DEPLOYMENT.md` (12K) - Deployment guide
- `TESTING_GUIDE.md` (11K) - Testing procedures
- `SESSION_SUMMARY.md` (14K) - Development session notes
- `ai-instructions.md` (8.5K) - AI assistant instructions
- `CONFIGURATION_MASTER_REFERENCE.md` - Alternate config reference

### Documentation Subdirectory
**Location**: `docs/`

- `project_blueprint.md` - Architecture overview
- `requirements.md` - System requirements
- `runbook.md` - Operational guide
- `api_outline.md` - API specification
- `erd.md` - Entity relationship diagram

---

## Configuration & Infrastructure

### Web Server
- `Caddyfile` - Production Caddy config
- `Caddyfile.dev` - Development Caddy config
- **Server**: Caddy v2.10.2
- **PHP**: PHP 8.3 FPM

### SSL Certificates (Let's Encrypt)
**Location**: `~/.local/share/caddy/certificates/`

- `mechanicstaugustine.com.key` - Main domain SSL key
- `mechanicstaugustine.com.crt` - Main domain SSL cert
- `crm.mechanicstaugustine.com.key` - CRM subdomain SSL key
- `crm.mechanicstaugustine.com.crt` - CRM subdomain SSL cert

### Environment & Secrets
- `.env.example` - Environment template
- `.gitignore` - Git ignore rules
- Environment variables for:
  - Twilio/SignalWire credentials
  - OpenAI API keys
  - Database connections
  - CRM API tokens

### Version Control
**Location**: `.git/`

- **Repository**: git@github.com:kylewee/mechanicsaintaugustine.com.git
- **Branch**: main
- **Feature Branches**: claude, copilot, signalwire-migration
- **Recent Commits**:
  - Migrate to SignalWire (Dec 5, 2025)
  - Improve AI call transcription
  - Sanitize secrets
  - Remove hardcoded API keys

### CI/CD
**Location**: `.github/workflows/`

- `deploy.yml` - Deployment workflow
- `ci.yml` - Continuous integration
- `.eslintrc.json` - ESLint config
- `.prettierrc` - Code formatting

---

## Scripts & Utilities

**Location**: `scripts/`

- Python and shell scripts for automation
- Database utilities
- Deployment helpers

**Root Scripts**:
- `create_ci_files.sh` - CI/CD setup
- `setup_repo.sh` - Repository initialization

---

## Static Assets & Data

- `data/` - Data files
- `lib/` - Shared libraries
- `price-catalog.json` - Service pricing
- `favicon.ico` - Site icon
- `index.html` - Main landing page
- `robots.txt` - SEO configuration
- `web.config` - Web server config

---

## Technology Stack

### Backend
- **Go**: 1.19+ (REST API)
- **PHP**: 8.3 (Voice, CRM, Portal)
- **Python**: 3.12.3 (Voice processing support)

### Databases
- **PostgreSQL**: 16.10 (Go API backend)
- **MySQL/MariaDB**: 10.11.13 (CRM, customer data)

### Web Server
- **Caddy**: v2.10.2 (Automatic HTTPS, PHP-FPM)

### External Services
- **SignalWire**: Phone & SMS (migrated Dec 5, 2025)
- **OpenAI**: Call transcription (Whisper)
- **Let's Encrypt**: SSL certificates

### Frontend
- **Bootstrap**: 4.x
- **jQuery**: 3.3.1
- **AOS**: Animation library
- Custom CSS & JavaScript

---

## Domains & URLs

- **Main**: https://mechanicstaugustine.com
- **CRM**: https://crm.mechanicstaugustine.com
- **WWW**: https://www.mechanicstaugustine.com

---

## Companion Projects

These support projects are located in `/home/kylewee/code/idk/projects/`:

### voice-system
- Python Flask-based voice infrastructure
- Core voice interaction framework
- 10 MB, active

### mechanic-voice-system
- Specialized voice handling
- Python-based
- 4 MB, active

### test-productivity
- Testing and verification tools
- VERIFICATION_REPORT.md
- 50 MB (with venv)

### demo-build
- Demo and deployment testing
- 4 MB

---

## Backups

### Database Backup
**Location**: `/home/kylewee/mysql_backup_20251201_014514.sql`
- **Size**: 300 MB
- **Date**: December 1, 2025
- **Scope**: Complete MySQL database dump

### Project Backup
**Location**: `/home/kylewee/code/idk/backups/idk-11-05-25/`
- **Date**: November 5, 2025
- **Scope**: Full project snapshot

---

## Recent Activity

**Last Modified**: December 5, 2025

**Recent Changes**:
1. SignalWire migration (Dec 5) - Replaced Twilio integration
2. AI transcription improvements (Dec 2)
3. Database backup (Dec 1)
4. Security improvements - removed hardcoded secrets
5. Campaign registration for 10DLC messaging

**Active Logs**:
- `voice/voice.log` - Voice system activity
- `api/sms_incoming.log` - SMS traffic
- `api/sms_status.log` - SMS delivery status
- `crm/log/` - CRM system logs

---

## Next Steps / TODO

1. **SignalWire Campaign** - Complete 10DLC campaign registration
2. **Port-In** - Complete number port from previous carrier
3. **Vanity Number** - Acquire 904-MECHANIC or similar
4. **Integration Testing** - Test full call-to-quote workflow
5. **Documentation** - Update API docs with SignalWire endpoints

---

## Key Features Summary

✅ **Phone System**: Voice calls, recordings, AI transcription
✅ **SMS System**: Appointment reminders, quote notifications
✅ **CRM Integration**: Rukovoditel for lead management
✅ **Quote System**: Online quote requests with SMS follow-up
✅ **Customer Portal**: Vehicle tracking, service requests
✅ **Mechanic Portal**: Profile management, job tracking
✅ **Admin Dashboard**: Dispatch, leads, parts management
✅ **SSL Security**: Automatic HTTPS for all domains
✅ **Git Tracked**: Full version control with GitHub
✅ **CI/CD**: Automated testing and deployment
✅ **Multi-Database**: PostgreSQL + MySQL
✅ **API Backend**: RESTful Go API with JWT auth

---

## File Statistics

- **Total Files**: 17,670+
- **Total Size**: 419 MB
- **Languages**: PHP, Go, Python, JavaScript, HTML, CSS, SQL
- **Go Files**: Backend API services
- **PHP Files**: 100+ across modules
- **Python Files**: Voice system components
- **JavaScript Files**: Frontend and CRM UI

---

*This inventory represents the complete, consolidated mechanicstaugustine.com project as of December 6, 2025. All scattered work has been identified and consolidated into this main project directory.*
