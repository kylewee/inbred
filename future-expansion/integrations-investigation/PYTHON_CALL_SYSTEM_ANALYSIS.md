# Python Flask Call Handling System Analysis
**Location**: `/home/kylewee/code/call-handling-workflow/mechanic-test`
**Date Analyzed**: December 6, 2025

---

## 📋 System Overview

**Technology Stack**:
- Flask (Python web framework)
- SQLAlchemy (ORM)
- SQLite database
- Flask-WTF (forms)
- REST API

**Purpose**: Call management and mechanic dispatch system

---

## 🔧 Features

### 1. Call Management
- Track customer service requests
- Fields: customer_name, customer_phone, location, issue_description
- Status tracking: pending → assigned → in_progress → completed
- Priority levels: low, normal, high, urgent
- Timestamps: created_at, updated_at

### 2. Mechanic Management
- Mechanic roster with profiles
- Fields: name, phone, email, specialty, status, location, rating
- Unique email constraint

### 3. Dashboard & UI
- Web interface for viewing calls
- Recent calls display (last 10)
- Create new call forms
- View all mechanics

### 4. REST API
- `GET /api/calls` - JSON endpoint for calls list
- Integration-ready design

---

## 🗄️ Database Schema

### calls table
```sql
CREATE TABLE calls (
    id INTEGER NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    location VARCHAR(200) NOT NULL,
    issue_description TEXT NOT NULL,
    status VARCHAR(20),
    priority VARCHAR(10),
    assigned_mechanic_id INTEGER,
    created_at DATETIME,
    updated_at DATETIME,
    PRIMARY KEY (id),
    FOREIGN KEY(assigned_mechanic_id) REFERENCES mechanics (id)
)
```

### mechanics table
```sql
CREATE TABLE mechanics (
    id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(120) NOT NULL,
    specialty VARCHAR(100),
    status VARCHAR(20),
    location VARCHAR(200),
    rating FLOAT,
    PRIMARY KEY (id),
    UNIQUE (email)
)
```

---

## ✅ Integration Potential: ⭐⭐⭐⭐ HIGH

### What This Could Do For You

1. **SMS Call Logging**
   - Every SMS conversation becomes a "call" record
   - Track customer interactions automatically
   - Customer phone → customer_phone field
   - Issue description from AI parsing

2. **Voice Call Integration**
   - Log Twilio/SignalWire voice calls
   - Store transcriptions in issue_description
   - Link to recording URLs
   - Track call status from incoming → completed

3. **Dispatch Automation**
   - Assign jobs to mechanics automatically
   - Track mechanic availability via status field
   - Location-based assignment (mechanic.location vs call.location)
   - Priority-based queue

4. **CRM Bridge**
   - Import calls into Rukovoditel CRM as leads
   - customer_name/phone → CRM lead
   - issue_description → CRM notes
   - created_at → lead created date

---

## 💡 How to Integrate with Current System

### Option 1: Standalone Service (Microservice)
- Run Flask app on separate port (e.g., :5000)
- Current system sends calls via API
- Example:
  ```bash
  curl -X POST http://localhost:5000/api/calls \
    -H "Content-Type: application/json" \
    -d '{
      "customer_name": "John Doe",
      "customer_phone": "+19045726121",
      "location": "123 Main St",
      "issue_description": "2013 Hyundai Sonata starter replacement",
      "priority": "normal"
    }'
  ```

### Option 2: Merge into Go Backend
- Recreate tables in PostgreSQL
- Port Python models to Go structs
- Add endpoints to existing Go API
- Unified system

### Option 3: Database Sharing
- Keep Flask app separate
- Point it at PostgreSQL instead of SQLite
- Both systems share same database
- Flask for UI, Go for API

---

## 🚀 Use Cases for Your Business

### Automated SMS Flow
```
1. Customer texts: "Need starter replacement on 2013 Sonata"
2. AI creates call record:
   - customer_phone: extracted from SMS
   - issue_description: "starter replacement on 2013 Sonata"
   - status: "pending"
   - priority: "normal"
3. System auto-assigns to you (mechanic_id: 1)
4. Status updates: pending → assigned → in_progress → completed
5. Send status SMS: "Your repair is in progress"
```

### Voice Call Tracking
```
1. Customer calls business line
2. SignalWire webhook triggers
3. Create call record with CallSid
4. Store transcription when available
5. You can view all calls in dashboard
6. Filter by status, priority, date
```

### Multi-Mechanic Expansion
When you hire help:
```
mechanics table:
- id: 1, name: "Kyle", specialty: "General", rating: 5.0
- id: 2, name: "Helper", specialty: "Oil changes", rating: 4.5

Auto-assign based on:
- Specialty (oil change → Helper)
- Location (closest mechanic)
- Rating (higher rated for urgent)
- Availability (status: available/busy/off)
```

---

## 📊 Current State

**Status**: Functional but unused
**Database**: Empty (instance/mechanic_calls.db has schema but no data)
**Last Modified**: November 17, 2025
**Dependencies**: All in requirements.txt

---

## 🎯 Recommendation: **INTEGRATE**

### Why Keep It:
1. ✅ Well-structured code (clean Flask patterns)
2. ✅ Ready-to-use call tracking
3. ✅ Status workflow (pending → completed)
4. ✅ Priority system for urgent calls
5. ✅ API-ready for automation
6. ✅ Mechanic assignment (future-proof for expansion)

### Integration Plan:

**Phase 1: Quick Win (1-2 hours)**
- Add POST endpoint for creating calls via API
- Integrate with voice/recording_callback.php
- Log every call automatically
- View calls in Flask dashboard

**Phase 2: SMS Integration (2-3 hours)**
- Connect SignalWire SMS webhook
- Parse customer info → create call
- AI extracts issue_description
- Auto-update status

**Phase 3: Database Migration (3-4 hours)**
- Move from SQLite to PostgreSQL
- Share database with Go backend
- Unified data model

**Phase 4: CRM Sync (2-3 hours)**
- Create calls → auto-create CRM leads
- Bidirectional sync
- Completed calls → closed leads

---

## 🔨 Next Steps

1. **Test the Flask app**:
   ```bash
   cd /home/kylewee/code/call-handling-workflow/mechanic-test
   source .venv/bin/activate
   python app.py
   # Visit http://localhost:5000
   ```

2. **Add API endpoint** for creating calls programmatically

3. **Connect to voice system**:
   - Modify voice/recording_callback.php
   - POST call data to Flask API
   - Track all voice interactions

4. **Decision Point**:
   - Keep separate (microservice) OR
   - Merge into Go backend OR
   - Database sharing approach

---

## 📁 Files to Copy/Reference

If integrating, these files are useful:
- `models/call.py` - Call data model
- `models/mechanic.py` - Mechanic data model
- `app.py` - Flask routes and logic
- `templates/` - UI templates (if want web dashboard)

---

## Verdict: ✅ **KEEPER** - Integrate for call tracking automation

This system solves your SMS/voice call logging problem and sets you up for future expansion.
