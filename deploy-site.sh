#!/bin/bash
#
# Deploy New Site
# Creates a new site config from template
#
# Usage: ./deploy-site.sh domain.com "Business Name" "landscaping"
#

set -e

DOMAIN="$1"
BUSINESS_NAME="$2"
BUSINESS_TYPE="${3:-contractor}"

if [ -z "$DOMAIN" ] || [ -z "$BUSINESS_NAME" ]; then
    echo "Usage: ./deploy-site.sh domain.com \"Business Name\" [business_type]"
    echo ""
    echo "Examples:"
    echo "  ./deploy-site.sh wesodjax.com \"We Sod Jax\" landscaping"
    echo "  ./deploy-site.sh myplumber.com \"My Plumber\" plumbing"
    echo "  ./deploy-site.sh fixmycar.com \"Fix My Car\" mechanic"
    echo ""
    echo "Business types: mechanic, landscaping, roofing, plumbing, contractor"
    exit 1
fi

CONFIG_DIR="$(dirname "$0")/config"
CONFIG_FILE="${CONFIG_DIR}/${DOMAIN}.php"

if [ -f "$CONFIG_FILE" ]; then
    echo "Config already exists: $CONFIG_FILE"
    echo "Edit it manually or delete and re-run."
    exit 1
fi

echo "Creating config for: $DOMAIN"
echo "Business: $BUSINESS_NAME ($BUSINESS_TYPE)"
echo ""

# Create config from template
cat > "$CONFIG_FILE" << PHPCONFIG
<?php
/**
 * ${DOMAIN} - ${BUSINESS_NAME}
 * Generated: $(date '+%Y-%m-%d')
 * TODO: Fill in credentials
 */

return [
    'site' => [
        'name'        => '${BUSINESS_NAME}',
        'tagline'     => 'Professional Services',
        'domain'      => '${DOMAIN}',
        'phone'       => '',                      // TODO: Your phone number
        'email'       => 'info@${DOMAIN}',
        'address'     => '',
        'service_area'=> 'Your City, FL',
    ],

    'business' => [
        'type'        => '${BUSINESS_TYPE}',
        'category'    => 'Home Services',
        'services'    => ['Service 1', 'Service 2', 'Service 3'],
    ],

    'branding' => [
        'primary_color'   => '#2563eb',
        'secondary_color' => '#10b981',
        'logo'            => '/assets/logo.png',
        'favicon'         => '/favicon.ico',
    ],

    'crm' => [
        'enabled'       => false,                 // TODO: Enable after setup
        'api_url'       => '',
        'username'      => '',
        'password'      => '',
        'api_key'       => '',
        'entity_id'     => 26,
        'created_by'    => 2,
        'field_map'     => [
            'first_name'  => 219,
            'last_name'   => 220,
            'phone'       => 227,
            'stage'       => 228,
            'source'      => 229,
            'notes'       => 230,
        ],
    ],

    'phone' => [
        'provider'      => 'signalwire',
        'project_id'    => '',
        'space'         => '',
        'api_token'     => '',
        'phone_number'  => '',
        'forward_to'    => '',
        'recordings_token' => '',
    ],

    'openai' => [
        'api_key'       => '',
        'model'         => 'gpt-4o-mini',
        'whisper_model' => 'whisper-1',
    ],

    'features' => [
        'voice_assistant'   => false,             // Enable after phone setup
        'call_recording'    => false,
        'transcription'     => false,
        'sms_estimates'     => false,
        'web_quotes'        => true,
        'crm_integration'   => false,
    ],

    'seo' => [
        'title_template'    => '%page% | ${BUSINESS_NAME}',
        'meta_description'  => 'Professional services from ${BUSINESS_NAME}. Contact us today.',
        'og_image'          => '/assets/og-image.jpg',
        'google_analytics'  => '',
        'google_tag_manager'=> '',
    ],

    'estimates' => [
        'enabled'       => true,
        'labor_rate'    => 75,
        'min_charge'    => 150,
        'system_prompt' => 'You are a professional estimator.',
        'estimate_type' => '${BUSINESS_TYPE}',
        'input_fields'  => [
            ['name' => 'service_type', 'label' => 'Service Needed', 'type' => 'text', 'required' => true],
            ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'required' => true],
            ['name' => 'notes', 'label' => 'Details', 'type' => 'textarea'],
        ],
        'prompts' => [
            '${BUSINESS_TYPE}' => 'You are a ${BUSINESS_TYPE} estimator. Job: {service_type}. Details: {notes}. Respond with JSON: {"job_name": "", "total": 0, "notes": ""}',
        ],
    ],
];
PHPCONFIG

echo "Created: $CONFIG_FILE"
echo ""
echo "Next steps:"
echo "1. Edit $CONFIG_FILE and fill in:"
echo "   - Phone number"
echo "   - Service area"
echo "   - Services list"
echo "   - OpenAI API key (if using estimates)"
echo "   - SignalWire credentials (if using phone system)"
echo "   - CRM credentials (if using CRM)"
echo ""
echo "2. Add domain to Caddyfile:"
echo "   ${DOMAIN}, www.${DOMAIN},"
echo ""
echo "3. Reload Caddy:"
echo "   sudo caddy reload --config Caddyfile"
echo ""
echo "Done!"
