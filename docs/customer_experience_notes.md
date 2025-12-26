 huh huh# Customer Experience Notes
## Mechanic St Augustine - Role-Play Observations

---

## 🎯 Goal
Make every aspect of car repair as painless as possible for ALL parties involved.

---

## 🧰 WHAT WE HAVE (Inventory)

### MOMMY (CRM - Pre-built)
- Kanban boards (drag jobs between stages)
- Calendar scheduling
- Templates huh
- Public forms (customer fills out → lead created)
- Email notifications
-  huh-Stripe payments
            - huhpaypal - 
- REST API
- Twilio SMS module
- Track changes/audit log
- User management
- Reports/charts
- File attachments
- WhatsApp/Telegram
- Mail integration

### DADDY (SignalWire/Twilio)
- Phone calls
- SMS sending
- Call recording
- Voicemail
- Transcription
- Phone number

### BABY (What we built)
- Missed call → auto-text customer with form link
- Missed call → text YOU notification
- IVR flow
- Quote intake form
- Price catalog (40 repairs with V8/old car multipliers)
- Webhook handlers
- dial_result.php
- recording_callback.php
- Cloudflare tunnel setup
- Dictation toggle script (voice-to-text)
- Admin dashboard

---

## 🔌 CONNECTIONS NEEDED
1. Missed call → create lead in CRM (baby talks to mommy)
2. Quote form → create lead in CRM (baby talks to mommy)
3. CRM sends SMS via Twilio (mommy talks to daddy)

---

## 📋 Role-Play Sessions

### Session 1: Kyle as Customer, AI as Mechanic
**Date:** December 17, 2025

#### Step 1: Customer realizes something is wrong
- **Scenario:** Car making a weird noise
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 2: Customer searches for a mechanic
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 3: Customer contacts the shop
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 4: Describing the problem
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 5: Getting a quote/estimate
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 6: Scheduling the repair
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 7: Dropping off the car
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 8: Waiting for updates
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 9: Approval for additional work
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 10: Picking up the car
- **Pain Points:** 
- **Improvement Ideas:** 

#### Step 11: After service
- **Pain Points:** 
- **Improvement Ideas:** 

---

### Session 2: AI as Customer, Kyle as Mechanic
**Date:** (To be completed)

*(Notes will be added here)*

---

## 💡 Key Takeaways

### Top Pain Points Identified
1. Kyle doesn't call people back - needs automation
2. Billing/invoicing is a pain - needs automation
3. Sometimes doesn't want to talk to people - voicemail + auto-text handles it
4. Price estimates could be wrong if not careful

### Top Improvement Ideas
1. Auto follow-up texts after calls
2. Auto reminders for jobs/callbacks  
3. "On my way" auto-text with ETA
4. If busy/booked, auto-text: "Can come tomorrow, reply YES to confirm"
5. Automated billing/invoicing (CRM templates can do this)

### Quick Wins (Easy to Implement)
- Missed call → auto-text with form link ✅ DONE
- Missed call → notify Kyle ✅ DONE
- Price catalog for quick estimates ✅ DONE (40 repairs)

### Big Projects (Worth the Effort)
- Connect phone system to CRM (auto-create leads)
- Auto-generate invoices from job data
- "On my way" button that texts customer ETA
- Review estimates before sending (human check on auto-quotes)

---

## 🔄 THE PIPELINE (Automated Job Flow)

**Goal: Touch it once, it flows through to Bahamas 🏝️**

```
ESTIMATE → ACCEPTED → SCHEDULED → JOB DONE → INVOICE → PAID → RECEIPT → 🏝️
```

### Stage Transitions (Auto-Push)

| From | To | Trigger | Auto-Action |
|------|-----|---------|-------------|
| Estimate | Accepted | Customer says YES | → Create job, add to calendar |
| Accepted | Scheduled | Pick date/time | → Text customer confirmation |
| Scheduled | Job Done | Mark complete | → Generate invoice from estimate |
| Invoice | Paid | Payment received | → Send receipt (email/text) |
| Paid | Done | Receipt sent | → Archive, maybe ask for review |

### What CRM Already Has
- ✅ Kanban board (drag jobs through stages)
- ✅ Email templates (for receipts)
- ✅ SMS templates (via Twilio module)
- ✅ Calendar integration
- ✅ Invoice/Quote templates

### What We Need to Build
- [ ] Webhook on stage change → trigger next action
- [ ] "Customer accepted" button → auto-schedule
- [ ] "Job complete" button → auto-generate invoice
- [ ] Payment confirmation → auto-send receipt
- [ ] All SMS/email templates ready to go 

---

## ✅ Action Items
| Item | Priority | Status |
|------|----------|--------|
|      |          |        |

---

*Last Updated: December 17, 2025*
