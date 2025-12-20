# Integrations Investigation Report
**Date**: December 6, 2025
**Purpose**: Consolidate scattered mechanic/repair system work for integration

---

## 🎯 CRITICAL FINDING: Labor Times Database EXISTS!

### Location: PostgreSQL Database Schema
**File**: `backend/db/migrations/001_init_schema.up.sql`

**Labor Hours Storage**:
```sql
CREATE TABLE quote_line_items (
    id BIGSERIAL PRIMARY KEY,
    quote_id BIGINT NOT NULL REFERENCES quotes(id),
    description TEXT NOT NULL,
    quantity NUMERIC(10,2) NOT NULL,
    unit_price BIGINT NOT NULL,  -- stored in cents
    labor_hours NUMERIC(6,2),    -- ⭐ THIS IS YOUR LABOR TIMES FIELD
    sort_order INTEGER NOT NULL DEFAULT 0
);
```

**Format**: Decimal hours (e.g., 2.50 = 2.5 hours)
**Already Implemented In**: Go backend API (`internal/domain/quotes/quotes.go`)

---

## 📁 Projects Found Across Directories

### 1. Call Handling Workflow (Python Flask)
**Location**: `/home/kylewee/code/call-handling-workflow/mechanic-test`

**What it does**:
- Flask web application
- Call management system
- Mechanic assignment/dispatch
- SQLite database with `calls` and `mechanics` tables

**Key Features**:
- Customer call tracking (name, phone, location, issue)
- Call status management
- Mechanic profiles with specialty/rating
- Priority system for calls

**Database Schema**:
```python
calls: id, customer_name, customer_phone, location, issue_description,
       status, priority, assigned_mechanic_id, created_at, updated_at

mechanics: id, name, phone, email, specialty, status, location, rating
```

**Integration Potential**: ⭐⭐⭐⭐ HIGH
- Could integrate with voice system for call logging
- Mechanic assignment automation
- Status tracking for CRM

---

### 2. Legacy Mobile Mechanic System (PHP/MySQL)
**Location**: `Mobile-mechanic/` subdirectory

**What it does**:
- Original 2018 PHP application
- MySQL database (mm.sql)
- Service request management
- Vehicle catalog system

**Database Tables**:
1. **servicerequest** - 5 sample records with:
   - Vehicle info (category, name, number)
   - Problem descriptions
   - Service codes (182, 107, 153, 200)
   - Status tracking
   - Completion dates
   - Service ratings

2. **vehicledescription** - Vehicle catalog:
   - Two-wheeler: Activa, Honda, Dio
   - Four-wheeler: SUV, Innova

3. **customer_reg** - Customer profiles
4. **mechanic_reg** - Mechanic profiles with license/Aadhar

**Sample Data Found**:
```
Service Request: Honda (KA25EW1368) - "oil leakage and starting problem"
Service Code: 182
Status: completed
Rating: 5
```

**Integration Potential**: ⭐⭐ MEDIUM
- Service codes could map to labor times
- Historical data for ML training
- Customer/vehicle data migration to PostgreSQL

---

### 3. Modern Backend API (Go/PostgreSQL)
**Location**: `backend/` directory (CURRENT SYSTEM)

**What it does**:
- RESTful API in Go
- PostgreSQL database
- JWT authentication
- Complete quote/customer/vehicle management

**Key Tables**:
1. **quotes** - Pricing proposals
   - Status: draft, sent, accepted, declined, converted
   - total_amount (stored in cents)
   - Links to customer & vehicle

2. **quote_line_items** - Individual repair items
   - description
   - quantity
   - unit_price
   - **labor_hours** ⭐
   - sort_order

3. **vehicles** - Complete vehicle specs
   - VIN, year, make, model, trim, engine, mileage
   - Customer relationship

4. **customers** - Customer profiles
   - Contact info, marketing opt-in
   - External ID for CRM integration

**Integration Potential**: ⭐⭐⭐⭐⭐ CRITICAL - THIS IS YOUR ACTIVE SYSTEM
- Already has labor_hours field built in
- Just needs population with actual labor time data
- API ready for SMS automation integration

---

### 4. Parts Ordering System (PHP Admin)
**Location**: `admin/parts_orders.php`

**What it does**:
- Parts order lifecycle management
- Supplier tracking
- Cost management

**Database Tables Created**:
1. **parts_orders**
   - lead_id reference
   - status: requested → ordered → received
   - supplier_name, supplier_contact
   - timestamp tracking
   - notes

2. **parts_order_items**
   - part_number
   - description
   - quantity (DECIMAL for fractional parts)
   - unit_cost
   - notes

**Integration Potential**: ⭐⭐⭐⭐ HIGH
- Auto-populate parts from repair quotes
- Integration with quote line items
- Supplier price tracking

---

### 5. Machine Learning Dataset
**Location**: `data/scania_component_x_summary.json`

**What it contains**:
- 23,550 training vehicle records
- 2,272 repair incidents (9.6% repair rate)
- Repair time statistics:
  - Mean: 240.3 units
  - Median: 218.2 units
- Broken down by component categories

**Integration Potential**: ⭐⭐⭐ MEDIUM
- Train ML model for labor time prediction
- Predict repair times based on vehicle/issue
- Improve quote accuracy

---

## 🔍 Additional Scattered Directories Found

From directory search:
- `/home/kylewee/code/idk/mechanicsaintaugustine.com` (another copy?)
- `/home/kylewee/code/idk/backups/idk-11-05-25/projects/mechanicsaintaugustine.com` (backup)
- Trash: `/home/kylewee/.local/share/Trash/files/mechanicstaugustine.com`

**Action Needed**: Investigate these for any unique code not in current project

---

## 💡 Integration Opportunities for SMS Automation

### Immediate Wins (Use Existing Infrastructure):

1. **Labor Hours Already Stored** ⭐ CRITICAL
   - You already have `quote_line_items.labor_hours` in PostgreSQL
   - Just need to populate it with actual labor time data
   - Can query it via Go API

2. **Vehicle Info Extraction**
   - Customer says: "2013 Hyundai Sonata 2.4"
   - AI parses and stores in `vehicles` table
   - Query: `SELECT * FROM vehicles WHERE year=2013 AND make='Hyundai' AND model='Sonata'`

3. **Quote Generation Flow**
   ```
   1. Customer requests repair (via SMS)
   2. AI extracts: vehicle info + issue description
   3. Lookup labor_hours from database (by vehicle + repair type)
   4. Calculate: labor_hours × $150/hr + parts estimate
   5. Create quote in database via Go API
   6. Send SMS with quote back to customer
   ```

4. **Call Handling Integration**
   - Import Python Flask call system
   - Log all SMS conversations as "calls"
   - Track status, priority, assignment

5. **Parts Integration**
   - When quote accepted, auto-create parts_order
   - Send to supplier
   - Track delivery status

---

## 🚀 Recommended Next Steps

### Phase 1: Data Consolidation (URGENT)
1. ✅ Found labor_hours schema - already exists!
2. Need to populate with actual repair time data
3. Build lookup table: repair_type → vehicle_make/model → labor_hours

### Phase 2: Create Labor Time Lookup
**Two Options**:

**Option A: Manual Data Entry** (Quick Start)
- Create CSV with common repairs:
  ```
  repair_type,make,model,year_start,year_end,labor_hours
  "Starter Replacement","Hyundai","Sonata",2011,2015,2.3
  "Oil Change","*","*",*,*,0.5
  "Brake Pads Front","Honda","Civic",2010,2015,1.5
  ```

**Option B: API Integration** (Better Long-term)
- Check if Mitchell1/AllData has API access
- Scrape RepairPal for labor times
- Build lookup service

### Phase 3: SMS Automation
1. Integrate with SignalWire (once ported)
2. AI parses incoming SMS for:
   - Vehicle: year, make, model, engine
   - Issue: "starter replacement", "oil change", etc.
3. Query labor_hours from database
4. Generate quote automatically
5. Send back to customer

### Phase 4: Consolidate Systems
1. Migrate useful parts from Python Flask call system
2. Deprecate old PHP MySQL system
3. Keep Go/PostgreSQL as single source of truth

---

## 📋 Files to Review with User

**Must Review**:
- [ ] `/code/call-handling-workflow/mechanic-test/` - Keep or integrate?
- [ ] `Mobile-mechanic/DB/mm.sql` - Migrate service codes?
- [ ] `data/scania_component_x_summary.json` - Use for ML?
- [ ] `admin/parts_orders.php` - Integrate with quotes?

**Investigate**:
- [ ] `/code/idk/mechanicsaintaugustine.com` vs current directory - same or different?
- [ ] Backup directory - any unique code?

---

## ⚠️ Issues Found

1. **Labor Times Exist But Empty**
   - Schema has labor_hours field
   - No actual data populated yet
   - Need lookup table/API integration

2. **Scattered Projects**
   - 3 different mechanic systems
   - 2 databases (MySQL + PostgreSQL)
   - Duplicate directories

3. **No Labor Time Source**
   - No Mitchell1/AllData subscription
   - Need alternative data source
   - Could start with manual common repairs

---

## 🎯 Answer to Original Question

**You asked**: "I need to look up how many hours that specific car pays for starter replacement"

**Answer**: You already have the infrastructure! Just need the data:

1. **Schema exists**: `quote_line_items.labor_hours`
2. **API exists**: Go backend can query it
3. **Missing**: Actual labor time data for specific vehicles/repairs

**For your current customer (2013 Hyundai Sonata 2.4 starter)**:
- Typical industry standard: 2.0-2.5 hours
- Your rate: $150/hr
- Labor: $300-375
- Parts: ~$150-200
- Total: ~$450-575

**Solution**: Build a simple lookup table to automate this!
