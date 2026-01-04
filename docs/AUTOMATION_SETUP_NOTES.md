# 🔧 Mobile Mechanic Automation System - Setup Notes

**Last Updated:** December 16, 2024  
**Status:** System built, needs SignalWire webhook configuration to go live

---

## 📍 WHERE WE LEFT OFF

### ✅ COMPLETED:
1. IVR voicemail system - asks customers structured questions
2. Customer intake web form - generate estimates automatically
3. SMS approval flow - texts you when estimate is ready
4. Call recording with transcription
5. CRM lead creation from calls and forms

### ❌ STILL NEEDS TO BE DONE:
1. **Configure SignalWire webhooks** (see step-by-step below)
2. **Test with a real phone call** 
3. **Set up ngrok or public URL** for webhooks to work

---

## 🚀 HOW TO START THE SYSTEM (Baby Steps)

### Step 1: Open a Terminal
1. On your computer, press `Ctrl + Alt + T` to open a terminal
2. You should see a blinking cursor

### Step 2: Go to the Project Folder
Type this and press Enter:
```
cd /home/kylewee/code/mechanicstaugustine/mechanicsaintaugustine.com
```

### Step 3: Check if Docker is Running
Type this and press Enter:
```
docker ps
```
You should see containers named like `idk-php`, `idk-caddy`, `idk-db`

**If you see nothing or an error:**
```
docker compose up -d
```
Wait 30 seconds, then try `docker ps` again.

### Step 4: Start ngrok (Makes Your Computer Reachable from Internet)
Open a NEW terminal tab (Ctrl+Shift+T) and type:
```
ngrok http 8080
```
You will see something like:
```
Forwarding    https://abc123.ngrok-free.app -> http://localhost:8080
```
**COPY that https URL!** You need it for SignalWire.

### Step 5: Test the System is Working
Open your web browser and go to:
```
http://localhost:8080/quote/customer_form.php
```
You should see a dark blue form that says "Get a Quote"

---

## 📞 CONFIGURE SIGNALWIRE (One-Time Setup)

### Step 1: Log into SignalWire
1. Open browser: https://mobilemechanic.signalwire.com
2. Sign in with your account

### Step 2: Find Your Phone Number
1. Click "Phone Numbers" in the left menu
2. Find the number: **+1 904-706-6669**
3. Click on it to edit

### Step 3: Set the Voice Webhook
1. Find "Accept Incoming Calls As" → select "Voice Calls"
2. Find "When a Call Comes In" 
3. Paste your ngrok URL + `/voice/incoming.php`
   
   Example: `https://abc123.ngrok-free.app/voice/incoming.php`
   
4. Method: **POST**

### Step 4: Set the SMS Webhook  
1. Find "When a Message Comes In"
2. Paste your ngrok URL + `/voice/sms_estimate.php`
   
   Example: `https://abc123.ngrok-free.app/voice/sms_estimate.php`
   
3. Method: **POST**

### Step 5: Save
Click the blue "Save" button at the bottom.

---

## 🧪 HOW TO TEST

### Test 1: Customer Form
1. Go to: `http://localhost:8080/quote/customer_form.php`
2. Fill out the form with test info:
   - Name: Test Customer
   - Phone: 904-555-1234
   - Year: 2019
   - Make: Honda
   - Model: Civic
   - Problem: Need brakes and oil change
3. Click "Get My Estimate"
4. You should see an estimate appear
5. You should get a text on your phone asking to approve

### Test 2: Phone Call (IVR)
1. Call **904-706-6669** from any phone
2. You should hear "Please hold while I connect you"
3. Your cell phone (+1 904-663-4789) should ring
4. DON'T answer your cell
5. After 25 seconds, the caller hears the IVR:
   - "Hi! This is St. Augustine Mobile Mechanic..."
   - It asks for their name, year, make, model, engine, problem
6. After they answer all questions:
   - Recording is transcribed
   - Lead is created in CRM
   - You get approval text

### Test 3: Answer the Call
1. Call **904-706-6669**
2. Answer on your cell phone
3. Have a conversation
4. Hang up
5. The call is recorded and transcribed
6. Lead appears in CRM

---

## 📁 IMPORTANT FILES

| File | What It Does |
|------|--------------|
| `voice/incoming.php` | Handles incoming calls, forwards to your cell |
| `voice/dial_result.php` | Decides: answered? → done. Not answered? → IVR |
| `voice/ivr_intake.php` | Asks the 6 questions to customers |
| `voice/ivr_recording.php` | Processes recordings, creates leads |
| `voice/sms_estimate.php` | Handles YES/NO replies to estimate texts |
| `quote/customer_form.php` | Web form customers fill out |
| `api/.env.local.php` | All passwords and API keys (NEVER SHARE) |

---

## 🔑 IMPORTANT NUMBERS & CREDENTIALS

### Phone Numbers
- **Business Line (SignalWire):** +1 904-706-6669
- **Your Cell Phone:** +1 904-663-4789

### SignalWire Account
- **Space:** mobilemechanic.signalwire.com
- **Project ID:** ce4806cb-ccb0-41e9-8bf1-7ea59536adfd
- **API Token:** (in api/.env.local.php - keep secret!)

### CRM Login
- **URL:** http://localhost:8080/crm/
- **Username:** kylewee2
- **Password:** (in api/.env.local.php)

---

## 🆘 TROUBLESHOOTING

### Problem: Form shows errors or blank page
**Fix:** Restart PHP container
```
docker restart idk-php
```

### Problem: Calls don't work / SignalWire errors
**Fix:** Check ngrok is running and URL is correct in SignalWire

### Problem: No transcription happening
**Fix:** Check OpenAI API key is valid in `api/.env.local.php`

### Problem: Texts not sending
**Fix:** Check SignalWire has credits and phone number is correct

### Problem: "Container not found"
**Fix:** Start Docker
```
cd /home/kylewee/code/mechanicstaugustine/mechanicsaintaugustine.com
docker compose up -d
```

---

## 📝 THE 6 IVR QUESTIONS (What Callers Hear)

1. "Hi! This is St. Augustine Mobile Mechanic. I'm not available right now, but I'd love to help you. Let me get some information. First, **what's your name?**"

2. "Great! **What year is your vehicle?**"

3. "Got it. **What's the make?** Like Honda, Ford, or Toyota?"

4. "And **what's the model?** Like Civic, F-150, or Camry?"

5. "Almost done. **What's the engine size** if you know it? You can say something like 2 point 4 liter or V6. If you don't know, just say skip."

6. "Last question. **Briefly describe what's wrong** or what service you need."

---

## 🔄 DAILY STARTUP CHECKLIST

Every time you restart your computer:

- [ ] Open terminal
- [ ] Run: `cd /home/kylewee/code/mechanicstaugustine/mechanicsaintaugustine.com`
- [ ] Run: `docker compose up -d`
- [ ] Wait 30 seconds
- [ ] Open new terminal tab
- [ ] Run: `ngrok http 8080`
- [ ] Copy the https URL
- [ ] Go to SignalWire → Phone Numbers → Update webhook URLs with new ngrok URL
- [ ] Test: http://localhost:8080/quote/customer_form.php

---

## 💡 QUICK COMMAND REFERENCE

```bash
# Go to project folder
cd /home/kylewee/code/mechanicstaugustine/mechanicsaintaugustine.com

# Start everything
docker compose up -d

# Stop everything
docker compose down

# Restart just PHP (fixes most issues)
docker restart idk-php

# Start ngrok tunnel
ngrok http 8080

# Check logs for errors
docker logs idk-php --tail 50

# Check voice webhook logs
tail -50 /home/kylewee/code/inbred/voice/voice.log
```

---

## 📱 CUSTOMER FORM LINK

**To text customers the quote form:**

Send them: `https://[YOUR-NGROK-URL]/quote/customer_form.php`

Example: `https://abc123.ngrok-free.app/quote/customer_form.php`

(Replace with your actual ngrok URL)

---

## 🎯 WHAT HAPPENS WHEN (Flow Summary)

### When Someone CALLS 904-706-6669:
```
Call comes in
    ↓
"Please hold while I connect you"
    ↓
Your cell phone rings (25 seconds)
    ↓
┌─────────────────┬──────────────────────┐
│ You ANSWER      │ You DON'T answer     │
├─────────────────┼──────────────────────┤
│ Call recorded   │ IVR asks 6 questions │
│ Transcribed     │ Answers recorded     │
│ Lead created    │ Transcribed          │
│ Done!           │ Lead created         │
│                 │ You get approval SMS │
└─────────────────┴──────────────────────┘
```

### When Customer Fills FORM:
```
Customer fills form
    ↓
Estimate calculated automatically
    ↓
Lead created in CRM
    ↓
You get SMS: "New estimate for [Name], $XXX. Reply YES to send"
    ↓
┌────────────────┬────────────────┐
│ You reply YES  │ You reply NO   │
├────────────────┼────────────────┤
│ Customer gets  │ Nothing sent   │
│ estimate SMS   │ to customer    │
└────────────────┴────────────────┘
```

---

**END OF NOTES**

*Last edited by AI Assistant on December 16, 2024*
