# CRM Workflow Setup Guide

This guide documents how to configure Rukovoditel CRM for the mobile mechanic lead pipeline.

## Pipeline Stages

The following stages are configured in the CRM (field_228):

| Stage | Color | Description |
|-------|-------|-------------|
| New Lead | Blue (#3498db) | Fresh lead from call/web |
| Callback Needed | Red (#e74c3c) | Missed call, needs callback |
| Quote Sent | Orange (#f39c12) | Quote SMS sent to customer |
| Quote Viewed | Purple (#9b59b6) | Customer clicked quote link |
| Quote Approved | Green (#27ae60) | Customer approved quote |
| Scheduled | Teal (#1abc9c) | Service appointment scheduled |
| In Progress | Orange (#e67e22) | Mechanic working on vehicle |
| Completed | Green (#2ecc71) | Service finished |
| Review Requested | Purple (#8e44ad) | Follow-up sent for review |
| Closed Won | Green (#27ae60) | Deal completed successfully |
| Closed Lost | Gray (#95a5a6) | Lead lost/cancelled |

---

## 1. Set Up Kanban Board View

1. Log into CRM: https://mechanicstaugustine.com/crm/
2. Go to **Leads** entity (Entity 26)
3. Click **Reports** → **Add Report**
4. Configure:
   - **Name**: Lead Pipeline
   - **Report Type**: Kanban Board
   - **Group By Field**: Stage (field_228)
   - **Card Title**: [First Name] [Last Name]
   - **Card Subtitle**: [Make] [Model] - [Phone]
5. Save and set as default view

---

## 2. Configure SMS Rules

### SMS Rule 1: New Lead Confirmation
1. Go to **Extension** → **Modules** → **SMS Modules**
2. Click **Sending SMS Rules** → **Add Rule**
3. Configure:
   - **Name**: New Lead Confirmation
   - **Entity**: Leads (26)
   - **SMS Module**: SignalWire
   - **Action Type**: On Insert (new record)
   - **Send To**: Phone field (field_227)
   - **Message Template**:
     ```
     Thanks for contacting EZ Mobile Mechanic! We received your request and will call you shortly.

     Questions? Call (904) 706-6669
     ```

### SMS Rule 2: Quote Approved Confirmation
1. Add new rule:
   - **Name**: Quote Approved
   - **Action Type**: On Update
   - **Monitor Field**: Stage (field_228)
   - **Monitor Values**: Quote Approved (72)
   - **Message Template**:
     ```
     Your quote has been approved! We'll contact you within 2 hours to schedule your service.

     EZ Mobile Mechanic (904) 706-6669
     ```

### SMS Rule 3: Scheduled Reminder
1. Add new rule:
   - **Name**: Service Scheduled
   - **Action Type**: On Update
   - **Monitor Field**: Stage (field_228)
   - **Monitor Values**: Scheduled (73)
   - **Message Template**:
     ```
     Your service is scheduled! Our mechanic will arrive at the confirmed time.

     Questions? (904) 706-6669
     ```

---

## 3. Configure Email Notifications

### Email Rule 1: New Lead Alert
1. Go to **Extension** → **Modules** → **Email Notification Rules**
2. Click **Add Rule**
3. Configure:
   - **Name**: New Lead Alert
   - **Entity**: Leads (26)
   - **Action Type**: On Insert
   - **Send To**: Admin user(s)
   - **Subject**: New Lead: [First Name] [Last Name] - [Phone]
   - **Body Template**:
     ```html
     <h2>New Lead Received</h2>
     <p><strong>Name:</strong> [First Name] [Last Name]</p>
     <p><strong>Phone:</strong> [Phone]</p>
     <p><strong>Vehicle:</strong> [Year] [Make] [Model]</p>
     <p><strong>Issue:</strong> [Notes]</p>
     <p><a href="https://mechanicstaugustine.com/crm/index.php?module=items/items&path=26&id=[ID]">View in CRM</a></p>
     ```

### Email Rule 2: Callback Needed Alert
1. Add new rule:
   - **Name**: Callback Needed
   - **Action Type**: On Update
   - **Monitor Field**: Stage (field_228)
   - **Monitor Values**: Callback Needed (69)
   - **Subject**: URGENT: Callback Needed - [First Name] [Last Name]
   - **Body**: Include phone and reason

---

## 4. Configure Process Automation

### Process 1: Auto-Create Task on Quote Approved
1. Go to **Extension** → **Modules** → **Processes**
2. Click **Add Process**
3. Configure:
   - **Name**: Create Scheduling Task
   - **Entity**: Leads (26)
   - **Button Position**: Run After Update
   - **Is Active**: Yes
4. Add **Filter**:
   - Field: Stage (228)
   - Condition: Equals
   - Value: Quote Approved (72)
5. Add **Action**:
   - Type: Insert Item (Tasks entity if you have one, or create related comment)
   - Fields: Set task title "Schedule service for [First Name]", due date = tomorrow

### Process 2: Auto-Stage Progression
1. Add process:
   - **Name**: Mark Review Requested After Completion
   - **Button Position**: Run After Update
   - **Filter**: Stage = Completed (75) AND completed more than 24 hours ago
   - **Action**: Set Stage to Review Requested (76)

---

## 5. Set Up Recurring Tasks

### Daily Follow-up Check
1. Go to **Extension** → **Modules** → **Recurring Tasks**
2. Add task:
   - **Name**: Check Pending Quotes
   - **Entity**: Leads (26)
   - **Repeat**: Daily at 10:00 AM
   - **Filter**: Stage = Quote Sent AND more than 24 hours old
   - **Action**: Send reminder SMS or create task

---

## 6. Configure Report for Analytics

### Lead Source Report
1. Go to **Reports** → **Add Report**
2. Configure:
   - **Type**: Pivot Table
   - **Rows**: Source (field_229)
   - **Columns**: Stage (field_228)
   - **Values**: Count of records
3. Save as "Lead Source Analysis"

### Conversion Funnel
1. Add report:
   - **Type**: Funnel Chart
   - **Stages**: New Lead → Quote Sent → Quote Approved → Scheduled → Completed
   - **Group By**: Stage (field_228)

---

## 7. SignalWire SMS Module Configuration

If not already installed:

1. Go to **Extension** → **Modules** → **SMS Modules**
2. Click **Install Module** → Find **SignalWire**
3. Configure credentials:
   - **Space**: mobilemechanic.signalwire.com
   - **Project ID**: (from SignalWire dashboard)
   - **API Token**: (from SignalWire dashboard)
   - **From Number**: +19047066669

---

## Code Integration Points

The following PHP files automatically update CRM:

| File | Trigger | CRM Action |
|------|---------|------------|
| `voice/recording_callback.php` | New call | Create lead (stage: New Lead), add comment |
| `voice/dial_result.php` | Missed call | Update stage to Callback Needed |
| `lib/QuoteSMS.php` | Quote sent | Update stage to Quote Sent |
| `lib/QuoteSMS.php` | Quote viewed | Update stage to Quote Viewed |
| `lib/QuoteSMS.php` | Quote approved | Update stage to Quote Approved |
| `api/service-complete.php` | Service done | Update stage to Completed |

All stage transitions also add a comment to the lead's activity log.

---

## Testing the Workflow

1. **Test New Lead Flow**:
   ```bash
   # Simulate a call - check CRM for new lead with stage "New Lead"
   curl -X POST https://mechanicstaugustine.com/voice/recording_callback.php \
     -d "From=+19045551234" \
     -d "RecordingUrl=https://example.com/test.mp3"
   ```

2. **Test Quote Flow**:
   - New lead should appear in CRM
   - When quote is sent, stage changes to "Quote Sent"
   - Comment shows quote details

3. **Test Kanban**:
   - View Kanban board
   - Drag leads between stages
   - Verify stage field updates

---

## Maintenance

- **Clear old logs**: `truncate voice/voice.log`
- **Check SMS delivery**: Review `api/sms_status.log`
- **Monitor failed API calls**: Check `voice/voice.log` for `crm_comment` failures
