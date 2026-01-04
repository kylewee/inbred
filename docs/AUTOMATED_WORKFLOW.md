# Automated Call Workflow System

## Overview
Your phone system now has intelligent automation that handles calls differently based on whether you answer or miss them.

---

## 📞 Workflow for ANSWERED Calls

### What Happens:
1. **Customer calls** → Forwarded to your cell (+19046634789)
2. **You answer** → Normal conversation happens
3. **Call is recorded** → Both sides captured
4. **AI processes recording** (once SignalWire enables transcription):
   - Transcribes conversation
   - Extracts customer data (name, vehicle, service needed)
   - Creates detailed lead in CRM
5. **AI generates estimate** automatically:
   - Analyzes service request
   - Calculates labor hours
   - Estimates parts cost
   - Provides price range
6. **You get approval text**:
   ```
   New estimate ready:

   Customer: John Smith
   Vehicle: 2015 Honda Civic
   Service: Oil change and brake inspection
   Estimate: $180.00
   Range: $150.00 - $220.00

   Reply YES to send to customer, NO to skip
   ```
7. **You decide**:
   - Reply **YES** → Estimate sent to customer automatically
   - Reply **NO** → Nothing sent, you handle it manually
   - No reply → Nothing sent (safe default)

### Key Benefits:
- ✅ Still capture all customer info even during normal conversation
- ✅ Get AI-generated estimates automatically
- ✅ You approve before customer sees anything
- ✅ Saves time on common services

---

## 📵 Workflow for MISSED Calls

### What Happens:
1. **Customer calls** → Forwarded to your cell
2. **You DON'T answer** → Goes to voicemail or times out
3. **System detects missed call** → Status = "no-answer"
4. **CRM lead created** → "Missed Caller XXXX"
5. **Automatic SMS sent** to customer:
   ```
   Thanks for calling Mechanic's of St. Augustine!
   Sorry we missed your call. We'll get back to
   you shortly. For faster service, text us your
   vehicle info and what you need done.
   ```
6. **Customer can text back** → You see their info
7. **You follow up** when available

### Key Benefits:
- ✅ Customers don't feel ignored
- ✅ Opens SMS conversation channel
- ✅ Captures leads you'd otherwise lose
- ✅ Professional automated response

---

## 🔧 Technical Implementation

### Files Modified:
- `voice/recording_callback.php` - Main webhook processor

### New Functions Added:

#### 1. `send_sms($to, $message, $context)`
Sends SMS via SignalWire API
- Works once brand "Mobilemechanic.best" is approved
- Returns status and message SID
- Logs all attempts

#### 2. `generate_auto_estimate($leadData, $context)`
Uses OpenAI to generate service estimates
- Analyzes vehicle year/make/model
- Interprets service description
- Calculates labor hours and parts
- Returns structured estimate with range

#### 3. `request_estimate_approval($estimateData, $mechanicPhone)`
Sends approval request to mechanic
- Formats estimate clearly
- Includes customer and vehicle info
- Asks for YES/NO reply

### Call Status Detection:
```php
// Missed calls
$failed = in_array($status, ['failed','busy','no-answer','canceled'], true);

// Answered calls
// Any other status (typically 'completed')
```

---

## ⚙️ Configuration

### Required Settings (api/.env.local.php):
```php
// SignalWire credentials
define('SIGNALWIRE_SPACE', 'mobilemechanic.signalwire.com');
define('TWILIO_ACCOUNT_SID', 'ce4806cb-ccb0-41e9-8bf1-7ea59536adfd');
define('TWILIO_AUTH_TOKEN', 'PT1c8cf22d1446d4d9daaf580a26ad92729e48a4a33beb769a');
define('TWILIO_SMS_FROM', '+19047066669');
define('TWILIO_FORWARD_TO', '+19046634789');  // Your cell - gets approval texts

// OpenAI for transcription and estimates
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));

// CRM integration
define('CRM_API_KEY', 'VMm87uzSFFyWAWCDzCXEK2AajBbHIOOIwtfhMWbA');
```

---

## 📊 Data Flow Diagram

### Answered Call:
```
Customer Call → You Answer
              ↓
         Record Conversation
              ↓
    [WAITING FOR SIGNALWIRE TRANSCRIPTION]
              ↓
      AI Extracts Customer Data
              ↓
         Create CRM Lead
              ↓
      AI Generates Estimate
              ↓
   Send Approval Text to You (+19046634789)
              ↓
        You Reply YES/NO
              ↓
     [If YES: Send to Customer]
```

### Missed Call:
```
Customer Call → No Answer (60 second timeout)
              ↓
      System Detects "no-answer"
              ↓
    Create "Missed Caller" Lead
              ↓
   Send Automatic SMS to Customer
              ↓
    Customer Can Text Back
              ↓
       You Follow Up
```

---

## 🚨 Current Status

### ✅ Working Now:
- Call forwarding to your cell
- Call recording
- CRM lead creation
- Missed call detection
- **NEW:** SMS sending function (ready for brand approval)
- **NEW:** Auto-estimate generation
- **NEW:** Approval request workflow

### ⏳ Waiting On:
1. **SignalWire Native Transcription** (emailed support)
   - Required for: AI data extraction, estimate generation
   - Status: Ticket submitted
   - Impact: Auto-estimates won't work until this is enabled

2. **SMS Brand Approval** (submitted Dec 4, 2025)
   - Brand: "Mobilemechanic.best"
   - Required for: Missed call SMS automation
   - Status: Pending approval
   - Impact: SMS code is ready but won't send until approved

---

## 🧪 Testing the System

### Test Answered Call Workflow:
**NOTE:** Won't fully work until SignalWire enables transcription

1. Call +19047066669
2. Answer the call
3. Say realistic info:
   - "Hi, I'm John Smith"
   - "I have a 2015 Honda Civic"
   - "Needs an oil change"
4. Hang up
5. **Expected** (once transcription works):
   - Lead created with full customer data
   - Estimate generated automatically
   - Approval text sent to +19046634789
   - You reply YES or NO

### Test Missed Call Workflow:
**NOTE:** SMS won't send until brand approved

1. Call +19047066669
2. Don't answer - let it timeout (60 seconds)
3. Hang up after hearing voicemail
4. **Current behavior:**
   - Lead created as "Missed Caller XXXX"
   - SMS sending attempted
   - Error logged (brand not approved yet)
5. **Expected** (once brand approved):
   - Lead created
   - SMS sent to customer automatically
   - Customer can text back

---

## 📝 Estimate Pricing Reference

The AI uses these typical mobile mechanic rates:

### Labor Rate: $80-120/hour

### Common Services:
| Service | Labor Hours | Parts Cost | Total Estimate |
|---------|-------------|------------|----------------|
| Oil change | 0.5-1 hour | $30-60 | $70-180 |
| Brake pads | 1-2 hours | $80-200 | $160-440 |
| Battery | 0.5 hour | $100-200 | $140-260 |
| Alternator | 1-2 hours | $150-400 | $230-640 |
| Starter | 1-2 hours | $150-350 | $230-590 |
| Timing belt | 3-5 hours | $100-300 | $340-900 |

The AI adjusts based on:
- Vehicle year/make/model (older = harder)
- Complexity described
- Parts availability
- Market rates

---

## 🔍 Monitoring & Logs

### Voice Log:
```bash
tail -f /home/kylewee/code/idk/projects/mechanicstaugustine.com/voice/voice.log
```

### Look for:
- `"event":"dial_action"` - Call status (completed/no-answer)
- `"auto_sms"` - SMS sending results
- `"auto_estimate"` - Estimate generation results
- `"estimate_approval_request"` - Approval text sending

### System Logs:
```bash
# SMS attempts
grep "VOICE_SMS" /var/log/apache2/error.log

# Estimate generation
grep "VOICE_ESTIMATE" /var/log/apache2/error.log
```

---

## 🔐 Security Notes

1. **SMS Content**: Professional, generic message (no pricing)
2. **Estimate Approval**: You MUST approve before customer sees anything
3. **No Automation Without Approval**: Customer only gets estimate if you reply YES
4. **Missed Call SMS**: Automatic but safe (just acknowledgment)
5. **All Actions Logged**: Full audit trail in voice.log

---

## 📞 Phone Numbers Reference

- **Public Number:** +19047066669 (Google Voice)
- **System Number:** +19047066669 (SignalWire)
- **Your Cell:** +19046634789 (receives calls + approval texts)

---

## 🚀 Next Steps

1. **Wait for SignalWire to enable transcription** → Auto-estimates will work
2. **Wait for SMS brand approval** → Missed call SMS will send
3. **Test the workflows** once both are enabled
4. **Adjust estimate pricing** if needed (edit the AI prompt)
5. **Customize SMS message** if you want different wording

---

**Last Updated:** December 8, 2025
**Status:** Code complete, waiting for service approvals
**System Version:** 2.0 with intelligent automation
