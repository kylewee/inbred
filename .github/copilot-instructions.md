### Quick context
- Language: PHP (primary web handlers), some Go in `/backend/` and assorted scripts. The runtime entrypoints you will most often touch are under `voice/`, `api/`, `Mobile-mechanic/` and `crm/`.
- Primary purpose: a vehicle service / quote intake web app with phone/webhook handlers, CRM lead creation, and SMS flows. Much of the domain logic for incoming calls and recordings lives in `voice/recording_callback.php`.

### Big-picture architecture (short)
- Frontend/webroot: multiple public web endpoints in repo root and `website_7e9e6396/voice/` on the host. Webhook endpoints are under `voice/`.
- Telephony: Twilio libraries exist under `crm/plugins/ext/sms_modules/twilio/...`. Incoming call/recording flow: SignalWire/Twilio → webhooks in `voice/` → extract/transcribe → create CRM lead (REST or DB fallback) → optional quote forwarding.
- Persistence: CRM API integration (preferred) with DB fallback; migrations and DB code live under `backend/db` and `internal/database`.

### What to know before editing
- The central webhook handler is `voice/recording_callback.php` — it expects Twilio-like POST keys: `RecordingSid`, `RecordingUrl`, `TranscriptionText`, `From`, `To`, `CallSid`.
- For SignalWire compatibility we added an adapter `voice/signalwire_webhook.php` which remaps SignalWire fields (`call_id`, `recording_id`, `recording_url`, `caller`, `called`, `transcript`) into the Twilio keys and then includes `recording_callback.php`.
- Secrets: handlers look for `voice/.signalwire_secret` or the environment variable `SIGNALWIRE_WEBHOOK_SECRET` for webhook signing verification. Do NOT commit secrets.
- Logs: runtime logs are written to `voice/voice.log` and adapter debug to `voice/voice_signalwire_adapter.log` — tail these when debugging.

### Developer workflows & commands
- Run a quick local webserver for webhook tests:
  - `php -S 127.0.0.1:8000 -t /absolute/path/to/repo` and POST test payloads with `curl`.
- Common debug checks:
  - `tail -f voice/voice.log` — main webhook processing log
  - `tail -f voice/voice_signalwire_adapter.log` — adapter/signature debug
  - Search for telephony strings: `grep -R "RecordingSid\|TranscriptionText\|Twilio" -n .`
- When testing SignalWire live, ensure the public URL matches where files are hosted (some deployments use a subfolder `/website_*/`). If you get 404 from SignalWire, either update SignalWire resource URLs to the subpath or deploy files into the domain's document root.

### Project-specific patterns & conventions
- Prefer Twilio-like field names for webhook handlers. If introducing new providers, add a small adapter that normalizes provider-specific fields to the Twilio shape instead of changing the core handler.
- Logging: handlers append JSON blobs to `voice/voice.log` rather than using a centralized logger. Keep this pattern for quick troubleshooting unless extracting to a proper logger.
- Secret handling: prefer environment variables on the host; the code falls back to `voice/.signalwire_secret` if present. Document any added secrets in DEPLOYMENT.md but never commit them.

### Integration points to watch
- `voice/recording_callback.php` — the place to modify lead-creation logic, transcript handling, and auto-quoting.
- `voice/signalwire_webhook.php` — adapter example for mapping provider fields and signature verification.
- `crm/` — contains SMS and 3rd-party gateway clients (Twilio). If you add a new SMS provider, mirror the existing client layout.
- `backend/` and `internal/` — server-side services, migrations, and domain models; check `backend/README.md` for build steps when modifying server-side code.

### PR guidance for AI agents
- Keep changes minimal and focused: one behavioral change per PR. If updating webhook handling, include an integration test (manual curl script) and show sample log output in PR description.
- Preserve public endpoints and file locations unless the change includes coordinated deployment steps (update SignalWire resource URLs or host document root).
- Avoid committing secrets; add instructions to DEPLOYMENT.md for required env vars instead.

---
If any section is unclear or you want examples expanded (e.g., exact POST payloads used by SignalWire/Twilio or the CRM field mapping), tell me which area and I will iterate.
