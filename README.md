# Master Template

Multi-site lead generation platform. One codebase, many domains.

## Quick Start

```bash
# Create new site config
./deploy-site.sh sodjax.com "Sod Jax" landscaping

# Edit config with your credentials
nano config/sodjax.com.php

# Add to Caddyfile and reload
sudo caddy reload --config Caddyfile
```

## How It Works

1. **All domains point to same codebase** via Caddy
2. **PHP detects domain** and loads appropriate config
3. **Config controls everything**: branding, services, phone, CRM, estimates

```
Request → Caddy → master-template/index.php
                  ↓
                  config/bootstrap.php → config/[domain].php
                  ↓
                  Site renders with correct branding/content
```

## Directory Structure

```
master-template/
├── config/                    # Site configurations
│   ├── bootstrap.php          # Auto-loads config by domain
│   ├── config.template.php    # Copy for new sites
│   ├── default.php            # Fallback config
│   ├── mechanicstaugustine.com.php
│   ├── sodjacksonvillefl.com.php
│   ├── sodjax.com.php
│   └── jacksonvillesod.com.php
├── voice/                     # Phone system
│   ├── incoming.php           # Inbound call handler
│   ├── dial_result.php        # Post-dial routing
│   ├── gpt_assistant.php      # AI voice assistant
│   ├── recording_callback.php # Transcription + CRM
│   └── swaig_functions.php    # AI functions (estimates)
├── api/                       # Backend APIs
│   ├── quote_intake.php       # Web form handler
│   ├── service-complete.php   # Mark job complete
│   └── quote-approve.php      # Quote approval
├── lib/                       # Helpers
│   ├── CRMHelper.php          # CRM integration
│   └── QuoteSMS.php           # SMS quote system
├── index.php                  # Landing page (config-driven)
├── deploy-site.sh             # New site setup script
├── Caddyfile.template         # Multi-domain Caddy config
└── data/                      # SQLite databases
```

## Config Reference

```php
return [
    'site' => [
        'name'        => 'Business Name',
        'tagline'     => 'Your tagline',
        'domain'      => 'yourdomain.com',
        'phone'       => '+1234567890',
        'email'       => 'info@domain.com',
        'service_area'=> 'Jacksonville, FL',
    ],

    'business' => [
        'type'        => 'landscaping',  // mechanic, landscaping, roofing, plumbing
        'category'    => 'Landscaping',
        'services'    => ['Sod Installation', 'Lawn Care'],
    ],

    'branding' => [
        'primary_color'   => '#16a34a',
        'secondary_color' => '#854d0e',
    ],

    'crm' => [
        'enabled'       => true,
        'api_url'       => 'https://domain.com/crm/api/rest.php',
        // ... credentials
    ],

    'phone' => [
        'provider'      => 'signalwire',
        'project_id'    => '...',
        'phone_number'  => '+1234567890',
        'forward_to'    => '+1234567890',
    ],

    'openai' => [
        'api_key'       => 'sk-...',
    ],

    'features' => [
        'voice_assistant'   => true,
        'sms_estimates'     => true,
        'crm_integration'   => true,
    ],

    'estimates' => [
        'enabled'       => true,
        'labor_rate'    => 75,
        'estimate_type' => 'landscaping',
        'input_fields'  => [
            ['name' => 'sqft', 'label' => 'Square Footage', 'type' => 'number'],
            // ...
        ],
        'prompts' => [
            'landscaping' => 'You are a sod estimator. ...',
        ],
    ],
];
```

## Adding a New Site

1. **Create config**
   ```bash
   ./deploy-site.sh newdomain.com "Business Name" landscaping
   ```

2. **Edit config** - Fill in credentials:
   - Phone number and service area
   - SignalWire credentials (for phone)
   - OpenAI key (for estimates)
   - CRM credentials (optional)

3. **Update Caddyfile**
   ```
   newdomain.com, www.newdomain.com,
   ```

4. **Reload Caddy**
   ```bash
   sudo caddy reload --config Caddyfile
   ```

## Business Types

| Type | Collects | Estimate Fields |
|------|----------|-----------------|
| `mechanic` | year, make, model, problem | Labor hours, parts cost |
| `landscaping` | sqft, grass type, address | Material, labor, total |
| `roofing` | sqft, roof type, address | Material, labor, total |
| `plumbing` | service type, address | Parts, labor, total |

## Features

### Voice System (SignalWire)
- Inbound call handling
- Auto-forward to your cell
- GPT assistant picks up if you miss
- Call recording + transcription
- Auto-creates CRM leads

### GPT Estimates
- Voice: "What's it cost to replace brakes?"
- Web form: Fill out, get instant quote
- SMS: Texts estimate to customer
- Rate limiting: 5 per day per phone

### CRM Integration (Rukovoditel)
- Auto-creates leads from calls/forms
- Pipeline stage tracking
- Activity logging

## Commands

```bash
# Test PHP syntax
php -l index.php

# View voice logs
tail -f voice/voice.log | python3 -m json.tool

# Reload PHP after config changes
sudo systemctl reload php8.3-fpm

# Validate Caddyfile
caddy validate --config Caddyfile
```

## Environment

- PHP 8.3
- Caddy web server
- SQLite (quotes, rate limits)
- MySQL (CRM)
- SignalWire/Twilio (phone)
- OpenAI (GPT-4o-mini)

## Active Sites

| Domain | Business | Type |
|--------|----------|------|
| mechanicstaugustine.com | EZ Mobile Mechanic | mechanic |
| sodjacksonvillefl.com | Sod Jacksonville FL | landscaping |
| sodjax.com | Sod Jax | landscaping |
| jacksonvillesod.com | Jacksonville Sod | landscaping |
