# 🎯 COMPREHENSIVE SYSTEM PLAN
**Mobile Mechanic Business Automation Platform**
**Date**: December 21, 2025
**Status**: Integration & Optimization Phase

---

## 📋 EXECUTIVE SUMMARY

This system automates a mobile mechanic business from initial customer contact through job completion, using:
- **SignalWire** for voice/SMS communication (replacing Twilio)
- **OpenAI Whisper + GPT** for call transcription and data extraction
- **Rukovoditel CRM** for lead and customer management
- **Go REST API** for modern backend services
- **PHP** for voice webhooks and web integrations
- **Python** for data scraping and AI features

**Current State**: Core systems working, needs integration and optimization
**Goal**: Fully automated quote-to-completion workflow with minimal manual intervention

---

## 🏗️ SYSTEM ARCHITECTURE

### Technology Stack

```
┌─────────────────────────────────────────────────────────┐
│                    CUSTOMER TOUCHPOINTS                   │
├─────────────────────────────────────────────────────────┤
│  Phone Calls (SignalWire) │ Web Forms │ SMS Messages    │
└────────────┬────────────────┴───────────┴────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│                   PROCESSING LAYER                        │
├─────────────────────────────────────────────────────────┤
│  Voice Webhooks (PHP)  │  Quote Handler (PHP)            │
│  OpenAI Transcription  │  Auto-Estimate Engine (PHP)     │
│  AI Data Extraction    │  Labor Time Lookup (Python)     │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│                     DATA LAYER                            │
├─────────────────────────────────────────────────────────┤
│  Rukovoditel CRM (MySQL)  │  Go API (PostgreSQL)         │
│  Recordings (Local FS)    │  Labor Times (JSON/DB)       │
└────────────┬────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│                  BUSINESS OPERATIONS                      │
├─────────────────────────────────────────────────────────┤
│  Lead Approval  │  Dispatch  │  Parts Orders  │  Jobs   │
└─────────────────────────────────────────────────────────┘
```

### Language & Framework Decisions

**PHP 8.3**:
- ✅ Voice/SMS webhooks (SignalWire compatibility)
- ✅ CRM integration (Rukovoditel is PHP-based)
- ✅ Web forms and quote handlers
- ✅ Admin interfaces with existing code

**Go**:
- ✅ Modern REST API for scalability
- ✅ Customer/vehicle/quote management
- ✅ JWT authentication
- ⚠️ Currently minimal usage - needs integration plan

**Python**:
- ✅ Data scraping (charm.li, PartsTech)
- ✅ ML/AI features (vehicle risk assessment)
- ✅ Data processing and integration
- ⚠️ Flask call-handling app exists but not integrated

**JavaScript/HTML**:
- ✅ Admin dashboard UI
- ✅ Customer-facing web forms

---

## 🎯 COMPLETE FEATURE INVENTORY

### ✅ WORKING FEATURES (Production Ready)

#### 1. Voice Call System
**Location**: `/voice/`
**Status**: ✅ Operational with SignalWire

**Features**:
- Incoming call handling with TwiML
- Call recording (MP3 format)
- Call forwarding to mechanic's cell
- Recording callback webhooks
- OpenAI Whisper transcription
- GPT-4 customer data extraction
- Auto-CRM lead creation
- SMS confirmations

**Integration Points**:
- SignalWire API (voice + recordings)
- OpenAI API (transcription + extraction)
- Rukovoditel CRM API
- Local filesystem (recording storage)

**Recent Fixes**:
- ✅ CRM API key corrected
- ✅ Local recording storage implemented
- ⚠️ Needs testing: transcription not triggering

#### 2. Auto-Estimate System
**Location**: `/scraper/auto_estimate.php`
**Status**: ✅ Production ready, needs voice integration

**Capabilities**:
- Keyword detection from transcripts (10+ repair types)
- Labor time lookup from charm_data.json
- Parts cost estimation with fallbacks
- Vehicle-specific pricing adjustments (luxury, truck, age)
- Multi-repair quotes
- Complexity-based labor rates

**Repair Types Supported**:
```
- Oil Change (0.5 hrs)
- Brake Pads (1.5 hrs)
- Battery Replacement (0.5 hrs)
- Alternator Replacement (2.0 hrs)
- Starter Replacement (2.3 hrs)
- Timing Belt (4.5 hrs)
- AC Recharge (1.0 hr)
- Engine Diagnostic (1.0 hr)
- Transmission Service (2.5 hrs)
- Spark Plugs (1.5 hrs)
```

**Data Sources**:
- charm_data.json (27KB scraped labor times)
- Enhanced fallback system
- Vehicle-aware pricing rules

**Integration Status**: ⚠️ Called by recording_callback.php but needs verification

#### 3. Dispatch & Scheduling System
**Location**: `/admin/dispatch.php`
**Status**: ✅ Deployed and working

**Features**:
- Job scheduling with date/time
- Arrival window management
- Technician assignment
- Status tracking pipeline:
  - scheduled → confirmed → en route → on site → completed
- Lead integration (links to CRM)
- Prevents double-booking

**Database**: `dispatch_jobs` table in MySQL

#### 4. Lead Approval System
**Location**: `/admin/leads_approval.php`
**Status**: ✅ Working

**Workflow**:
1. Quote created (from call or web form)
2. Admin reviews in approval panel
3. Click "Approve" → triggers:
   - quote_approved flag set
   - approved_at timestamp
   - Auto-create parts order
   - Ready for dispatch

#### 5. Parts Ordering System
**Location**: `/admin/parts_orders.php`
**Status**: ✅ Deployed

**Lifecycle**: requested → ordered → received

**Features**:
- Supplier tracking (name, contact)
- Line items with part numbers
- Quantity and unit cost management
- Timestamp tracking for each status
- Auto-populated from approved quotes
- One order per lead (UNIQUE constraint)

**Database Tables**:
- `parts_orders` - Order header
- `parts_order_items` - Line items

#### 6. Customer Web Forms
**Location**: `/quote/`
**Status**: ✅ Live on website

**Forms**:
- Quote request form (index.html)
- Customer information collection
- Vehicle details (year/make/model/engine)
- Service description

**Processing**: `quote/quote_intake_handler.php`
- AI extraction from descriptions
- Auto-quote generation
- CRM lead creation
- SMS confirmation

#### 7. CRM Integration
**Location**: Multiple touchpoints
**Status**: ✅ Working (API key fixed Dec 21)

**Rukovoditel CRM Entity 26 (Leads)**:
```
Field 219: First Name
Field 220: Last Name
Field 227: Phone
Field 228: Stage
Field 229: Source (Phone/Web)
Field 230: Notes (transcript + recording URL)
Field 231: Vehicle Year
Field 232: Vehicle Make
Field 233: Vehicle Model
Field 234: Address
Field 235: Email
```

**API Credentials**:
- URL: `http://localhost:8080/crm/api/rest.php`
- Username: `kylewee2`
- Password: `rainonin`
- API Key: `VMm87uzSFFyWAWCDzCXEK2AajBbHIOOIwtfhMWbA`

**Integration Points**:
- Voice system (auto-lead creation)
- Web forms (quote submissions)
- Admin panels (read/update)

### 🔧 PARTIAL/NEEDS INTEGRATION

#### 8. Labor Time Scraper
**Location**: `/scraper/charm_scraper.py`
**Status**: ⚠️ Working but needs regular updates

**What It Does**:
- Scrapes charm.li for labor times
- Outputs to charm_data.json
- Integration script: integrate_data.py

**Usage**:
```bash
python3 scraper/charm_scraper.py
python3 scraper/integrate_data.py
```

**Status**: Data is 27KB and being used, needs scheduled updates

#### 9. PartsTech API Integration
**Location**: `/scraper/partstech_api.py`
**Status**: ⚠️ Credentials exist, needs endpoint configuration

**Credentials**:
- API Key: `c522bfbb64174741b59c3a4681db7558`
- Location: `sodjacksonville@gmail.com`

**Issue**: Endpoints returning 404, needs correct URLs from PartsTech

**When Fixed**: Real-time parts availability, accurate pricing, supplier inventory

#### 10. Multi-Source Data Manager
**Location**: `/scraper/multi_source_manager.py`
**Status**: ✅ Production ready, needs call integration

**Intelligent Routing**:
- 2014+: PartsTech → Gale → Enhanced Fallback
- 1990-2013: charm.li → Gale → Enhanced Fallback
- Pre-1990: Gale → Enhanced Fallback

**Enhanced Fallback**:
- Luxury brands: +30% markup
- Trucks/SUVs: +25% markup
- Old vehicles: +20% markup
- Realistic parts costs
- Complexity-based labor

#### 11. Vehicle Risk Assessment
**Location**: `/data/scania_component_x_summary.json` + quote handler
**Status**: ⚠️ Data exists, needs integration

**Scania ML Data**: Component failure risk analysis

**Potential Use**: Warn customers about high-risk components during quote

#### 12. Go Backend API
**Location**: `/backend/`
**Status**: ✅ Working but underutilized

**Capabilities**:
- Customer CRUD
- Vehicle tracking (VIN, year, make, model, engine, mileage)
- Quote management
- Line items with labor hours
- JWT authentication
- PostgreSQL storage

**Integration Status**: ⚠️ Minimal usage - PHP handles most operations

**Consideration**: Migrate more features to Go for scalability?

#### 13. Python Call-Handling Flask App
**Location**: `/feature-expansion/python-call-system/`
**Status**: ⚠️ Built but not integrated

**Features**:
- Call/SMS logging
- Mechanic dispatch
- Priority tracking
- Status management

**Decision Needed**: Integrate or archive? (PHP system working)

---

## 🎯 RECOMMENDED SYSTEM FLOW (Target State)

### Customer Journey: Phone Call

```
1. Customer calls +19047066669 (SignalWire number)
   ↓
2. SignalWire → /voice/incoming.php
   - Answer with greeting
   - Forward to mechanic's cell (+19046634789)
   - Record entire conversation (MP3)
   ↓
3. Call ends → SignalWire webhook → /voice/recording_callback.php
   - Download MP3 from SignalWire
   - Save to /voice/recordings/{RecordingSid}.mp3
   - Send to OpenAI Whisper for transcription
   ↓
4. Transcription complete → AI Extraction
   - GPT-4 extracts: name, phone, vehicle, issue
   - Auto-estimate system analyzes transcript
   - Detects repair keywords
   - Looks up labor times (charm_data.json)
   - Calculates parts costs (multi-source)
   - Generates quote with line items
   ↓
5. Create CRM Lead (Entity 26)
   - Customer info (name, phone, address)
   - Vehicle details (year, make, model)
   - Notes: transcript + recording link
   - Auto-generated estimate
   ↓
6. Send SMS to Customer
   - "Thanks for calling! We received your request for [repair]."
   - "Estimated quote: $XXX (labor) + $XXX (parts) = $XXX total"
   - "Reply YES to approve or call us with questions"
   ↓
7. Admin Review (if needed)
   - /admin/leads_approval.php
   - Review quote, adjust if needed
   - Click "Approve"
   ↓
8. Quote Approved → Auto-Actions
   - Create parts order (parts_orders table)
   - Mark for dispatch (dispatch_jobs table)
   - Send SMS confirmation to customer
   ↓
9. Parts Ordered
   - /admin/parts_orders.php
   - Mark status: requested → ordered → received
   ↓
10. Dispatch Scheduled
    - /admin/dispatch.php
    - Assign technician
    - Set date/time and arrival window
    - Send appointment SMS to customer
    ↓
11. Job Completed
    - Update dispatch status: completed
    - Archive lead
    - Request review (future feature)
```

### Customer Journey: Web Form

```
1. Customer visits /quote/
   ↓
2. Fills out form:
   - Name, phone, email, address
   - Vehicle: year, make, model, engine
   - Service description
   ↓
3. Submit → /quote/quote_intake_handler.php
   - AI extracts repair type from description
   - Auto-estimate system generates quote
   - Create CRM lead
   - Send SMS confirmation
   ↓
4-11. Same as phone call flow (steps 7-11)
```

---

## 🚀 INTEGRATION PRIORITIES

### Phase 1: Critical Fixes (Immediate)
**Goal**: Ensure core voice system works end-to-end

1. **Debug Recording Storage** ⚠️ HIGH PRIORITY
   - Issue: Recordings not being saved despite code changes
   - Check: PHP-FPM picked up changes
   - Verify: File permissions on /voice/recordings/
   - Test: Make call and verify MP3 saved
   - **Owner**: Fix immediately

2. **Verify Auto-Estimate Integration**
   - Confirm recording_callback.php calls auto_estimate.php
   - Test with transcript containing repair keywords
   - Verify estimate appears in CRM notes
   - **Owner**: Test after #1 fixed

3. **Fix Recording URL in CRM**
   - Current: Proxy URL (recording_callback.php?action=download)
   - Target: Direct link to /voice/recordings/{sid}.mp3
   - Requires: Caddy config to serve /voice/recordings/
   - **Owner**: After #1 verified

4. **Test Complete Call Flow**
   - Make test call with repair keywords
   - Verify: recording saved, transcription works, estimate generated
   - Check: CRM lead has all data
   - Confirm: SMS sent to customer
   - **Owner**: Integration test after #1-3

### Phase 2: Enhanced Admin Dashboard (This Weekend)
**Goal**: Unified monitoring and control panel

**New Admin Dashboard** (`/admin/index.php` with auth):

**Sections**:

1. **System Health Monitor**
   ```bash
   # Latest call status
   tail -1 voice/voice.log | jq '.recording_saved, .crm_result, .auto_estimate'

   # Recent CRM leads (last 5)
   mysql ... "SELECT id, field_219, field_227, FROM_UNIXTIME(date_added) FROM app_entity_26 ORDER BY id DESC LIMIT 5"

   # Recordings count
   ls voice/recordings/ | wc -l

   # Disk usage
   du -sh voice/recordings/
   ```

2. **Real-Time Call Log Viewer**
   - Parse voice/voice.log
   - Display in table: timestamp, from, duration, status
   - Link to recording playback
   - Show transcript

3. **Quote Pipeline Dashboard**
   - Pending approval (quote_approved = 0)
   - Approved quotes (quote_approved = 1)
   - Parts ordered status
   - Dispatch scheduled

4. **Quick Actions**
   - Test incoming call
   - View latest recordings
   - Approve pending quotes
   - Create dispatch job
   - Order parts

5. **System Status Checks**
   - PHP-FPM status
   - Caddy status
   - MySQL status
   - Disk space
   - OpenAI API connectivity
   - SignalWire API connectivity
   - CRM API connectivity

**Authentication**: Basic HTTP auth (kylewee / rainonin)

**Implementation**:
- Convert index.html to index.php
- Add session-based auth
- Use PHP to execute system commands
- Real-time data from database
- AJAX for live updates (optional)

### Phase 3: Data Quality & Automation (Next Week)

1. **Labor Time Database Expansion**
   - Schedule weekly charm_scraper.py runs
   - Add more vehicle makes/models
   - Integrate Chilton data if available
   - Build admin UI to manually add labor times

2. **PartsTech API Fix**
   - Contact PartsTech for correct API endpoints
   - Test authentication
   - Integrate real-time parts pricing
   - Fallback to current system if unavailable

3. **Duplicate Detection**
   - Check if customer already exists (by phone)
   - Link new calls to existing customers
   - Update existing lead vs create new

4. **SMS Conversation Handler**
   - Respond to customer SMS replies
   - Handle "YES" for quote approval
   - Answer basic questions with AI
   - Escalate complex questions to mechanic

### Phase 4: Advanced Features (Future)

1. **Customer Portal**
   - View quote status
   - Approve/decline online
   - Track job progress
   - Payment integration

2. **Inventory Management**
   - Track common parts in stock
   - Auto-reorder when low
   - Integration with parts suppliers

3. **Technician Mobile App**
   - View scheduled jobs
   - Update job status
   - Upload completion photos
   - Time tracking

4. **Analytics Dashboard**
   - Revenue tracking
   - Call→Quote→Job conversion rates
   - Average job value
   - Customer satisfaction scores

5. **Review Automation**
   - Auto-request Google reviews after job
   - Monitor review responses
   - Thank customers for positive reviews

---

## 📊 DATABASE SCHEMA CONSIDERATIONS

### Current State: Dual Database

**MySQL (Rukovoditel CRM)**:
- Leads (app_entity_26)
- Parts orders (parts_orders, parts_order_items)
- Dispatch jobs (dispatch_jobs)

**PostgreSQL (Go API)**:
- Customers
- Vehicles
- Quotes
- Quote line items

**Issue**: Data duplication and sync complexity

### Recommendation: Gradual Migration

**Option A: Unify on MySQL**
- Pro: CRM tightly integrated
- Pro: Less migration work
- Con: MySQL less performant for API

**Option B: Unify on PostgreSQL**
- Pro: Better performance for Go API
- Pro: Modern features (JSONB, etc)
- Con: Major migration of CRM data

**Option C: Keep Dual (Current)**
- Pro: No migration needed
- Pro: Each system optimized for use case
- Con: Sync complexity
- Mitigation: Use Go API as "source of truth", sync to CRM

**Recommendation**: **Option C** for now, revisit when scaling

---

## 🔐 SECURITY CONSIDERATIONS

### Current Status
✅ API keys in .env.local.php (gitignored)
✅ CRM password in environment
✅ Recording token protection
⚠️ Admin pages not password protected
⚠️ No rate limiting on webhooks
⚠️ Database passwords sometimes hardcoded

### Immediate Actions
1. Add HTTP Basic Auth to all /admin/* pages
2. Add rate limiting to voice webhooks (prevent abuse)
3. Move all passwords to environment variables
4. Rotate SignalWire API token (if exposed)
5. Enable HTTPS-only for all webhooks
6. Add webhook signature verification (SignalWire)

---

## 🎯 SUCCESS METRICS

### System Performance
- Call→Lead creation time: < 2 minutes
- Quote accuracy: > 90% (vs manual)
- Recording storage: 100% success rate
- Transcription accuracy: > 95%

### Business Metrics
- Manual intervention required: < 10% of calls
- Quote approval rate: > 60%
- Parts ordering automation: > 80%
- Customer satisfaction: > 4.5/5

---

## 📝 TECHNICAL DEBT TO ADDRESS

1. **Flask Python app** - Archive or integrate?
2. **Go API underutilization** - Expand usage or simplify to PHP?
3. **Recording proxy vs direct** - Migrate fully to direct file links
4. **Hardcoded values** - Move to environment variables
5. **Test coverage** - Add automated tests for critical paths
6. **Documentation** - API docs, webhook specs, database schema docs
7. **Error handling** - Better error logging and alerts
8. **Monitoring** - Add uptime monitoring and alerting

---

## 🚦 DECISION POINTS

### 1. Language Strategy

**Current**: PHP (voice/web), Go (API), Python (scraping)

**Options**:
- A: Consolidate to PHP (simplest, but less scalable)
- B: Migrate to Go (most scalable, most work)
- C: Keep hybrid (current state, manageable)

**Recommendation**: **C** - Keep hybrid, expand Go usage gradually

### 2. CRM Strategy

**Current**: Rukovoditel (PHP-based)

**Options**:
- A: Keep Rukovoditel (known entity, working)
- B: Migrate to custom Go UI (more control, more work)
- C: Use third-party CRM (Pipedrive, HubSpot)

**Recommendation**: **A** - Keep Rukovoditel for now, it works well

### 3. Transcription Strategy

**Current**: OpenAI Whisper API

**Options**:
- A: Keep OpenAI (best quality, API cost)
- B: Self-hosted Whisper (one-time cost, maintenance)
- C: AssemblyAI or similar (alternative API)

**Recommendation**: **A** - OpenAI quality is excellent, cost reasonable

---

## 📅 IMPLEMENTATION TIMELINE

### Week 1 (Dec 21-27)
- ✅ Fix CRM API key
- ⚠️ Fix recording storage
- ⚠️ Build enhanced admin dashboard
- ⚠️ Add password protection
- ⚠️ Test complete call flow

### Week 2 (Dec 28-Jan 3)
- Schedule charm_scraper.py weekly
- Fix PartsTech API endpoints
- Add duplicate customer detection
- Implement basic SMS auto-responses

### Week 3 (Jan 4-10)
- Customer portal (quote status view)
- Inventory tracking basics
- Analytics dashboard v1

### Month 2+
- Mobile app for technicians
- Payment integration
- Review automation
- Advanced analytics

---

## 🎓 LEARNING RESOURCES

- **SignalWire Docs**: https://developer.signalwire.com
- **Rukovoditel API**: https://docs.rukovoditel.net
- **OpenAI Whisper**: https://platform.openai.com/docs/guides/speech-to-text
- **Go Backend Patterns**: https://github.com/golang-standards/project-layout

---

**Next Steps**: Review this plan, prioritize features, and execute Phase 1 critical fixes.
