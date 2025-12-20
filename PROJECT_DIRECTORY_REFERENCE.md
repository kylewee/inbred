# Project Directory Reference - UPDATED 2025-12-11
# =====================================
# MAIN PROJECT DIRECTORY: /home/kylewee/code/idk/projects/mechanicstaugustine.com/
# =====================================

## Key Files Locations:
- Main web files: /home/kylewee/code/idk/projects/mechanicstaugustine.com/
- Voice files: /home/kylewee/code/idk/projects/mechanicstaugustine.com/voice/
- API config: /home/kylewee/code/idk/projects/mechanicstaugustine.com/api/.env.local.php
- Caddyfile: /home/kylewee/code/idk/projects/mechanicstaugustine.com/Caddyfile
- Health check: /home/kylewee/code/idk/projects/mechanicstaugustine.com/health.php

## SignalWire Setup (CURRENT):
- Phone Number: +1904706669 (SignalWire number)
- Script: "Mobile Mechanic Handler" (SWML Script)
- Webhook: https://mechanicstaugustine.com/voice/incoming.php
- Forward to: +19046634789 (your cell)

## Current Issue (2025-12-11 1:35 AM):
- SignalWire calls webhook but POST data is empty (from: null, to: null)
- Added debug logging to incoming.php to capture raw POST data
- Need to see what SignalWire is actually sending

## Call Flow:
1. Customer calls +19042175152 (Google Voice)
2. Google Voice forwards to +1904706669 (SignalWire)
3. SignalWire runs SWML script at incoming.php
4. Script should answer, play greeting, forward to +19046634789
5. Recording should be enabled, callback to recording_callback.php
6. OpenAI should transcribe, extract data, create CRM lead

## WORKING FILES:
- incoming.php: SWML JSON format (not XML/TwiML)
- recording_callback.php: Processes recordings with OpenAI
- Caddyfile: Points to correct directory

## NEVER CONFUSE THESE:
- WRONG: /home/kylewee/mechanicsaintaugustine.com/site/ (OLD LOCATION)
- RIGHT: /home/kylewee/code/idk/projects/mechanicstaugustine.com/ (CURRENT LOCATION)

## COMMANDS:
- Reload services: ./scripts/reload-services.sh
- Test webhook: curl -X POST https://mechanicstaugustine.com/voice/incoming.php
- Check logs: tail -f /home/kylewee/code/idk/projects/mechanicstaugustine.com/voice/voice.log
- Debug SignalWire: Check raw POST data in voice.log

## NEXT STEPS:
1. Call +1904706669 and check voice.log for raw POST data
2. Fix SignalWire data format if needed
3. Test call forwarding to +19046634789