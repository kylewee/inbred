# Mechanicstaugustine.com - Complete System Architecture

**Last Updated**: December 6, 2025

This document shows how ALL components of the mobile mechanic platform connect and communicate.

---

## 🏗️ High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CUSTOMER ENTRY POINTS                         │
├─────────────────────────────────────────────────────────────────────┤
│  📞 Phone Call  │  💬 SMS  │  🌐 Website  │  📱 Mobile Portal      │
└────────┬────────┴─────┬────┴──────┬───────┴──────────┬──────────────┘
         │              │           │                  │
         ▼              ▼           ▼                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                       SIGNALWIRE PHONE SYSTEM                        │
│  • Voice Calls (Incoming/Outgoing)                                  │
│  • SMS Messages (10DLC Campaign)                                    │
│  • Call Recording                                                   │
│  • Webhooks to mechanicstaugustine.com                              │
└────────┬─────────────────────────────────────────────┬──────────────┘
         │                                             │
         ▼                                             ▼
┌──────────────────────────┐              ┌──────────────────────────┐
│   VOICE SYSTEM (PHP)     │              │   SMS SYSTEM (PHP)       │
│  voice/                  │              │  api/sms/                │
│  • incoming.php          │              │  • sms_incoming.log      │
│  • recording_callback    │              │  • sms_status.log        │
│  • call_status.php       │              │  quote/                  │
└────┬─────────────────────┘              │  • status_callback.php   │
     │                                    └──────────┬───────────────┘
     ▼                                               │
┌──────────────────────────┐                        │
│  OPENAI WHISPER API      │                        │
│  • Call Transcription    │                        │
│  • AI Processing         │                        │
└────┬─────────────────────┘                        │
     │                                               │
     ▼                                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      QUOTE SYSTEM (PHP)                              │
│  quote/                                                              │
│  • quote_intake_handler.php ← Processes customer requests           │
│  • quote_intake.php ← API endpoint                                  │
│  • index.html ← Customer form                                       │
│  • SMS_SETUP.md ← Configuration                                     │
└────────┬────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     RUKOVODITEL CRM (PHP + MySQL)                    │
│  crm/                                                                │
│  • Lead Management                                                   │
│  • Customer Database                                                 │
│  • Custom Fields (Vehicle info, Service history)                    │
│  • Workflow Automation                                               │
│  • Multi-user Access                                                 │
│  URL: https://crm.mechanicstaugustine.com                           │
└────────┬────────────────────────────────────────────────────────────┘
         │
         ├──────────────────────┬──────────────────────┐
         ▼                      ▼                      ▼
┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐
│  ADMIN DASHBOARD │   │ MOBILE MECHANIC  │   │   GO REST API    │
│  admin/          │   │    PORTAL        │   │   backend/       │
│  • Dispatch      │   │  Mobile-mechanic/│   │  • JWT Auth      │
│  • Leads         │   │  • Customers     │   │  • PostgreSQL    │
│  • Parts Orders  │   │  • Mechanics     │   │  • API Endpoints │
└──────────────────┘   │  • Vehicles      │   └──────────────────┘
                       │  • Appointments  │
                       └──────────────────┘
```

---

## 📊 Detailed Data Flow

### 1️⃣ Customer Calls In (Voice Flow)

```
Customer Dials → SignalWire Phone Number
         ↓
SignalWire receives call
         ↓
Webhook POST to: mechanicstaugustine.com/voice/incoming.php
         ↓
incoming.php:
  - Answers call
  - Plays greeting
  - Starts recording
  - Returns TwiML response
         ↓
Customer leaves message
         ↓
Call ends → SignalWire sends recording URL
         ↓
Webhook POST to: mechanicstaugustine.com/voice/recording_callback.php
         ↓
recording_callback.php:
  - Downloads recording file
  - Saves to voice/recordings/
  - Sends audio to OpenAI Whisper API
         ↓
OpenAI Whisper API returns transcription
         ↓
recording_callback.php:
  - Extracts customer info (name, phone, issue)
  - Creates lead in CRM via API
  - Sends confirmation SMS via SignalWire
         ↓
Lead appears in CRM → Admin notified
```

### 2️⃣ Customer Submits Web Quote (Web Flow)

```
Customer visits: mechanicstaugustine.com
         ↓
Fills out quote form (quote/index.html)
         ↓
Form submits to: api/quote_intake.php
         ↓
quote_intake.php:
  - Validates form data
  - Forwards to quote/quote_intake_handler.php
         ↓
quote_intake_handler.php:
  - Creates lead in CRM (via Rukovoditel API)
  - Sends SMS confirmation to customer (SignalWire)
  - Logs request
         ↓
SignalWire sends SMS
         ↓
SMS status callback to: quote/status_callback.php
         ↓
status_callback.php:
  - Updates delivery status
  - Logs in api/sms_status.log
         ↓
Lead created in CRM → Admin Dashboard shows new lead
```

### 3️⃣ Customer Uses Mobile Portal (Portal Flow)

```
Customer visits: mechanicstaugustine.com/Mobile-mechanic/
         ↓
New Customer? → register.php → Creates account in MySQL
Existing? → login.php → Authenticates via database_connection.php
         ↓
Logged in → Customer Dashboard
         ↓
Customer Actions:
  • Add_vehicles.php → Adds vehicle to profile
  • servicerequest.php → Submits service request
  • cappointment.php → Books appointment
  • cprofile.php → Updates profile
         ↓
All actions write to MySQL database (Mobile-mechanic/DB/mm.sql schema)
         ↓
Service requests → Create entries in CRM
         ↓
Admin/Mechanic can view in:
  - CRM dashboard
  - Admin dashboard (admin/dispatch.php)
```

### 4️⃣ Admin/Mechanic Workflow (Operations Flow)

```
Admin logs into CRM: https://crm.mechanicstaugustine.com
         ↓
Views new leads from:
  - Phone calls (voice transcriptions)
  - Web quotes
  - Mobile portal requests
         ↓
Admin actions in CRM:
  - Assigns lead to mechanic
  - Updates status
  - Schedules appointment
  - Generates estimate
         ↓
Optional: Use admin dashboard (admin/)
  - admin/dispatch.php → Assign jobs
  - admin/leads_approval.php → Approve/reject leads
  - admin/parts_orders.php → Order parts
         ↓
Mechanic receives assignment
         ↓
Mechanic logs into Mobile-mechanic/mprofile.php
         ↓
Views assigned jobs via Mechanic_details.php
         ↓
Completes job → Updates status in portal
         ↓
System sends SMS to customer:
  - "Job complete" notification
  - Invoice/payment link
         ↓
Customer receives SMS via SignalWire
```

### 5️⃣ SMS Notification Flow (Automated)

```
Trigger Event:
  - New lead created
  - Appointment scheduled
  - Mechanic en route
  - Job complete
  - Payment reminder
         ↓
Application calls SignalWire Messaging API
         ↓
SignalWire sends SMS to customer
         ↓
SignalWire sends delivery receipt
         ↓
Webhook POST to: quote/status_callback.php
         ↓
status_callback.php logs status:
  - Sent / Delivered / Failed
  - Logged in api/sms_status.log
         ↓
Admin can view SMS delivery status in logs
```

---

## 🗄️ Database Architecture

### MySQL/MariaDB (CRM & Customer Data)

```
┌─────────────────────────────────────────┐
│        MySQL Database                    │
├─────────────────────────────────────────┤
│  Rukovoditel CRM Tables:                │
│    • entities (leads, customers)        │
│    • entities_fields (custom fields)    │
│    • users (CRM users)                  │
│    • workflows (automation)             │
│    • reports                            │
│                                         │
│  Mobile Mechanic Tables:                │
│    • customers (portal users)           │
│    • mechanics (mechanic profiles)      │
│    • vehicles (customer vehicles)       │
│    • service_requests                   │
│    • appointments                       │
└─────────────────────────────────────────┘
         ▲                        ▲
         │                        │
    ┌────┴────┐            ┌──────┴──────┐
    │   CRM   │            │   Portal    │
    │  (PHP)  │            │    (PHP)    │
    └─────────┘            └─────────────┘
```

### PostgreSQL (API Backend)

```
┌─────────────────────────────────────────┐
│      PostgreSQL Database                │
├─────────────────────────────────────────┤
│  Go API Tables:                         │
│    • users (API users, JWT tokens)      │
│    • sessions                           │
│    • api_logs                           │
│    • integrations                       │
└─────────────────────────────────────────┘
         ▲
         │
    ┌────┴────┐
    │ Go API  │
    │ Backend │
    └─────────┘
```

---

## 🔐 Authentication & Security

### Customer Authentication
```
Mobile Portal (Mobile-mechanic/)
         ↓
login.php checks credentials
         ↓
database_connection.php queries MySQL
         ↓
Session created in PHP
         ↓
Customer accesses protected pages
```

### Admin/CRM Authentication
```
CRM Login (crm.mechanicstaugustine.com)
         ↓
Rukovoditel authentication system
         ↓
Multi-user roles:
  - Admin (full access)
  - Manager (dispatch, leads)
  - Mechanic (assigned jobs only)
```

### API Authentication
```
API Request to backend/
         ↓
JWT token required in Authorization header
         ↓
internal/auth/ validates token
         ↓
Access granted/denied
```

---

## 🌐 Web Server & Routing (Caddy)

### Caddyfile Configuration

```
mechanicstaugustine.com {
    root * /home/kylewee/code/idk/projects/mechanicstaugustine.com

    # PHP-FPM for voice, quote, portal
    php_fastcgi unix//run/php/php8.3-fpm.sock

    # Static files
    file_server

    # Automatic HTTPS (Let's Encrypt)
    tls {
        protocols tls1.2 tls1.3
    }
}

crm.mechanicstaugustine.com {
    root * /home/kylewee/code/idk/projects/mechanicstaugustine.com/crm

    # PHP-FPM for CRM
    php_fastcgi unix//run/php/php8.3-fpm.sock

    # Automatic HTTPS
    tls {
        protocols tls1.2 tls1.3
    }
}
```

### Request Routing

```
HTTPS Request arrives at Caddy
         ↓
Caddy terminates SSL (Let's Encrypt cert)
         ↓
Routes to appropriate handler:
  ┌─────────────────────────────────────────┐
  │ .php files → PHP-FPM (PHP 8.3)         │
  │ /api/* → PHP endpoints                 │
  │ /voice/* → PHP voice handlers          │
  │ /quote/* → PHP quote system            │
  │ Static files → Served directly         │
  │ /backend/* → Go API (if proxied)       │
  └─────────────────────────────────────────┘
         ↓
Response returned to client
```

---

## 🔌 External Integrations

### SignalWire (Phone & SMS)
```
┌─────────────────────────────────────────┐
│         SignalWire Cloud                │
├─────────────────────────────────────────┤
│  Phone Number: (To be ported)           │
│  Brand: Mobilemechanic.best             │
│  Campaign: Customer Service             │
│  10DLC Registered                       │
└─────────────────────────────────────────┘
         ↕ API Calls
┌─────────────────────────────────────────┐
│    mechanicstaugustine.com              │
│  • Webhook endpoints (voice/, quote/)   │
│  • API client calls (send SMS)          │
└─────────────────────────────────────────┘

Webhook Endpoints:
  POST /voice/incoming.php ← Incoming calls
  POST /voice/recording_callback.php ← Recordings
  POST /voice/call_status.php ← Call status
  POST /quote/status_callback.php ← SMS status
```

### OpenAI (Transcription)
```
┌─────────────────────────────────────────┐
│         OpenAI Whisper API              │
├─────────────────────────────────────────┤
│  Model: whisper-1                       │
│  Input: Audio file (from SignalWire)   │
│  Output: Text transcription             │
└─────────────────────────────────────────┘
         ↕ HTTPS API
┌─────────────────────────────────────────┐
│  voice/recording_callback.php           │
│  • Sends audio file                     │
│  • Receives transcription               │
│  • Parses customer info                 │
└─────────────────────────────────────────┘
```

### Let's Encrypt (SSL)
```
┌─────────────────────────────────────────┐
│         Let's Encrypt                   │
├─────────────────────────────────────────┤
│  ACME Protocol                          │
│  Automatic certificate renewal          │
└─────────────────────────────────────────┘
         ↕
┌─────────────────────────────────────────┐
│         Caddy Web Server                │
│  • Automatic cert requests              │
│  • Stores in ~/.local/share/caddy/      │
│  • Auto-renews every 90 days            │
└─────────────────────────────────────────┘

Certificates:
  mechanicstaugustine.com.crt
  crm.mechanicstaugustine.com.crt
```

---

## 🔄 Complete Customer Journey Example

### Scenario: Customer Needs Oil Change

```
1. INITIAL CONTACT (Choice A: Phone)
   Customer calls → SignalWire → voice/incoming.php
   ↓
   Recording created → OpenAI transcribes
   ↓
   Lead created in CRM with transcription
   ↓
   SMS sent: "Thanks for calling! We'll contact you soon."

1. INITIAL CONTACT (Choice B: Website)
   Customer visits website → quote/index.html
   ↓
   Fills form: Name, Phone, Vehicle, Issue (Oil Change)
   ↓
   Submits → api/quote_intake.php → quote_intake_handler.php
   ↓
   Lead created in CRM
   ↓
   SMS sent: "Quote request received! Ref #12345"

2. ADMIN REVIEW
   Admin opens CRM → https://crm.mechanicstaugustine.com
   ↓
   Sees new lead: "John Doe - Oil Change - 2015 Honda Civic"
   ↓
   Reviews customer info, vehicle details
   ↓
   Assigns to Mechanic: "Mike"
   ↓
   Schedules appointment: Dec 11, 10:30 AM at Twincourt Trail

3. APPOINTMENT CONFIRMATION
   CRM workflow triggers → Calls SignalWire API
   ↓
   SMS sent to customer:
   "Hi John, your oil change is scheduled for Dec 11 at 10:30 AM.
    Mike will arrive at Twincourt Trail. Reply STOP to opt-out."

4. DAY OF SERVICE
   Mechanic Mike logs in → Mobile-mechanic/mprofile.php
   ↓
   Views assigned job
   ↓
   Starts driving → Updates status to "En Route"
   ↓
   System sends SMS: "Mike is on his way! ETA: 15 minutes."
   ↓
   Arrives → Performs oil change
   ↓
   Completes job → Updates in portal
   ↓
   System sends SMS: "Service complete! Total: $49.99. Pay at [link]"

5. POST-SERVICE
   Customer clicks payment link → Payment processed
   ↓
   Receipt generated in CRM
   ↓
   SMS sent: "Thanks for choosing us! Receipt: [link]"
   ↓
   Follow-up email (if configured)
```

---

## 📁 File Structure Map

```
mechanicstaugustine.com/
│
├── voice/                    ← Voice call handling
│   ├── incoming.php         ← Incoming calls webhook
│   ├── recording_callback.php ← Process recordings
│   ├── call_status.php      ← Call status tracking
│   ├── recordings/          ← Stored audio files
│   └── voice.log           ← Activity logs
│
├── api/                      ← API endpoints
│   ├── quote_intake.php     ← Quote API
│   ├── sms/                 ← SMS handling
│   ├── sms_incoming.log     ← SMS logs
│   └── sms_status.log       ← SMS delivery status
│
├── quote/                    ← Quote system
│   ├── quote_intake_handler.php ← Main processor
│   ├── status_callback.php  ← SMS callbacks
│   ├── index.html          ← Customer form
│   └── SMS_SETUP.md        ← SMS docs
│
├── crm/                      ← Rukovoditel CRM
│   ├── config/             ← CRM config
│   ├── modules/            ← CRM modules (55+)
│   ├── uploads/            ← User uploads
│   ├── backups/            ← CRM backups
│   └── log/                ← CRM logs
│
├── Mobile-mechanic/          ← Customer & Mechanic Portal
│   ├── login.php           ← Authentication
│   ├── register.php        ← New accounts
│   ├── Add_vehicles.php    ← Vehicle management
│   ├── servicerequest.php  ← Service requests
│   ├── cappointment.php    ← Appointments
│   ├── mprofile.php        ← Mechanic portal
│   ├── database_connection.php ← DB config
│   ├── DB/mm.sql           ← Database schema
│   ├── CSS/                ← Stylesheets
│   └── JS/                 ← JavaScript
│
├── admin/                    ← Admin dashboard
│   ├── dispatch.php        ← Job dispatch
│   ├── leads_approval.php  ← Lead management
│   └── parts_orders.php    ← Parts inventory
│
├── backend/                  ← Go API
│   ├── cmd/api/main.go     ← Main server
│   ├── internal/auth/      ← JWT authentication
│   ├── internal/httpapi/   ← HTTP endpoints
│   └── internal/storage/   ← Data layer
│
├── signalwire/               ← Phone integration
│   └── README.md           ← SignalWire docs
│
├── docs/                     ← Documentation
│   ├── project_blueprint.md
│   ├── api_outline.md
│   └── runbook.md
│
├── Caddyfile                 ← Web server config
├── .env.example              ← Environment template
├── PROJECT_INVENTORY.md      ← Component inventory
└── SYSTEM_ARCHITECTURE.md    ← This file!
```

---

## 🔧 Technology Stack Summary

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Voice System** | PHP 8.3, SignalWire, OpenAI | Call handling, recording, transcription |
| **SMS System** | PHP 8.3, SignalWire 10DLC | Appointment reminders, notifications |
| **Quote System** | PHP 8.3 | Web quote intake, form processing |
| **CRM** | Rukovoditel (PHP), MySQL | Lead/customer management |
| **Customer Portal** | PHP 8.3, MySQL, Bootstrap | Customer self-service |
| **Mechanic Portal** | PHP 8.3, MySQL | Job management for mechanics |
| **Admin Dashboard** | PHP 8.3 | Operations management |
| **API Backend** | Go 1.19+, PostgreSQL | REST API, authentication |
| **Web Server** | Caddy 2.10.2 | HTTPS, PHP-FPM, routing |
| **Database (CRM)** | MySQL/MariaDB 10.11 | Customer & CRM data |
| **Database (API)** | PostgreSQL 16.10 | API backend data |
| **SSL/TLS** | Let's Encrypt (auto) | HTTPS certificates |
| **Phone/SMS** | SignalWire | Voice calls, SMS messaging |
| **AI** | OpenAI Whisper | Call transcription |
| **Version Control** | Git, GitHub | Code repository |
| **CI/CD** | GitHub Actions | Automated deployment |

---

## 🚀 Deployment Flow

```
Developer commits code → Git repository
         ↓
Git push → GitHub
         ↓
GitHub Actions CI/CD (.github/workflows/)
         ↓
Automated tests run
         ↓
If tests pass → Deploy to server
         ↓
Server: /home/kylewee/code/idk/projects/mechanicstaugustine.com
         ↓
Caddy serves updated code
         ↓
Services automatically reload
```

---

## 📊 Monitoring & Logs

### Log Files
```
voice/voice.log              ← Voice system activity
api/sms_incoming.log         ← Incoming SMS
api/sms_status.log           ← SMS delivery status
crm/log/                     ← CRM system logs
backend/logs/                ← Go API logs (if configured)
```

### System Health Checks
```
health.php                   ← System health endpoint
GET https://mechanicstaugustine.com/health.php
Returns: System status, database connectivity, service availability
```

---

## 🔄 Data Synchronization

### CRM ↔ Portal Sync
```
Mobile Portal creates service request
         ↓
Writes to MySQL (Mobile-mechanic tables)
         ↓
Trigger/cron job syncs to CRM
         ↓
CRM entities table updated
         ↓
Admin sees request in both:
  - CRM dashboard
  - admin/dispatch.php
```

### Voice → CRM Sync
```
Call recording transcribed
         ↓
recording_callback.php parses customer info
         ↓
Direct API call to Rukovoditel
         ↓
Lead created in CRM entities table
         ↓
Immediately visible to admin
```

---

## 🎯 Key Integration Points

### 1. SignalWire → Voice System
- **Webhook**: POST /voice/incoming.php
- **Webhook**: POST /voice/recording_callback.php
- **Webhook**: POST /voice/call_status.php

### 2. SignalWire → SMS System
- **Webhook**: POST /quote/status_callback.php
- **API Call**: Send SMS (from any PHP script)

### 3. Voice System → OpenAI
- **API Call**: POST https://api.openai.com/v1/audio/transcriptions
- **Input**: Audio file from SignalWire
- **Output**: Text transcription

### 4. Quote System → CRM
- **API Call**: Rukovoditel REST API
- **Endpoint**: /api/v1/entities
- **Method**: Create lead entity

### 5. Portal → MySQL
- **Direct Connection**: database_connection.php
- **Operations**: CRUD on customers, vehicles, service_requests

### 6. CRM → MySQL
- **Internal**: Rukovoditel ORM
- **Tables**: entities, entities_fields, users, workflows

### 7. Backend API → PostgreSQL
- **Connection**: internal/storage/
- **ORM**: Go database/sql or GORM
- **Operations**: User auth, sessions, API logs

---

## 🔐 Environment Variables Required

```bash
# SignalWire
SIGNALWIRE_PROJECT_ID=xxx
SIGNALWIRE_AUTH_TOKEN=xxx
SIGNALWIRE_PHONE_NUMBER=xxx
SIGNALWIRE_SPACE_URL=xxx

# OpenAI
OPENAI_API_KEY=sk-xxx

# CRM
CRM_API_URL=https://crm.mechanicstaugustine.com/api/v1
CRM_API_TOKEN=xxx

# Database (MySQL)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mechanicstaugustine
DB_USER=xxx
DB_PASS=xxx

# Database (PostgreSQL)
PG_HOST=localhost
PG_PORT=5432
PG_NAME=mechanic_api
PG_USER=xxx
PG_PASS=xxx

# JWT
JWT_SECRET=xxx
JWT_EXPIRY=24h

# App
APP_ENV=production
APP_URL=https://mechanicstaugustine.com
```

---

## 🎬 Quick Start Guide

### Start All Services
```bash
# Start Caddy web server
sudo systemctl start caddy

# Start PHP-FPM
sudo systemctl start php8.3-fpm

# Start MySQL
sudo systemctl start mysql

# Start PostgreSQL
sudo systemctl start postgresql

# Start Go API (if separate)
cd backend && go run cmd/api/main.go
```

### Check Service Status
```bash
sudo systemctl status caddy
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
sudo systemctl status postgresql
```

### View Logs
```bash
# Caddy logs
sudo journalctl -u caddy -f

# PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log

# Application logs
tail -f voice/voice.log
tail -f api/sms_status.log
```

---

## 📞 Testing the System

### Test Voice Flow
```bash
1. Call SignalWire number
2. Leave voicemail
3. Check voice/recordings/ for audio file
4. Check voice/voice.log for processing log
5. Check CRM for new lead
6. Check phone for confirmation SMS
```

### Test Web Quote Flow
```bash
1. Visit https://mechanicstaugustine.com/quote/
2. Fill out form
3. Submit
4. Check api/sms_status.log
5. Check CRM for new lead
6. Check phone for confirmation SMS
```

### Test Portal Flow
```bash
1. Visit https://mechanicstaugustine.com/Mobile-mechanic/
2. Register new account
3. Add vehicle
4. Submit service request
5. Check MySQL database
6. Check CRM for synced data
```

---

## 🎯 Critical Success Paths

### Path 1: Customer gets quote within 24 hours
```
Call/Web Request → Lead in CRM → Admin assigns → Quote sent → SMS notification
```

### Path 2: Appointment scheduled and confirmed
```
Quote accepted → Schedule in CRM → Mechanic assigned → SMS confirmation → Service completed
```

### Path 3: Emergency service dispatched
```
Urgent call → Voice transcription → Priority lead → Immediate dispatch → SMS with ETA
```

---

*This architecture document maps the complete integration of all system components. Every webhook, API call, database query, and user interaction flows through these connections to deliver a seamless mobile mechanic service experience.*
