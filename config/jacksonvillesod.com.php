<?php
/**
 * jacksonvillesod.com - Sod Installation
 * 993 impressions - good SEO potential
 */

return [
    'site' => [
        'name'        => 'Jacksonville Sod',
        'tagline'     => 'Transform Your Landscape',
        'domain'      => 'jacksonvillesod.com',
        'phone'       => '',                      // TODO
        'email'       => 'info@jacksonvillesod.com',
        'address'     => 'Jacksonville, FL',
        'service_area'=> 'Jacksonville, FL and surrounding areas',
    ],

    'business' => [
        'type'        => 'landscaping',
        'category'    => 'Landscaping & Sod',
        'services'    => ['Sod Installation', 'Drainage Solutions', 'Landscape Design', 'Irrigation'],
    ],

    'branding' => [
        'primary_color'   => '#15803d',
        'secondary_color' => '#0369a1',
        'logo'            => '/assets/logo.png',
        'favicon'         => '/favicon.ico',
    ],

    'crm' => [
        'enabled'       => true,
        'api_url'       => 'https://mechanicstaugustine.com/crm/api/rest.php',
        'username'      => 'kylewee2',
        'password'      => 'rainonin',
        'api_key'       => 'VMm87uzSFFyWAWCDzCXEK2AajBbHIOOIwtfhMWbA',
        'entity_id'     => 26,
        'created_by'    => 2,
        'field_map'     => [
            'first_name'  => 219,
            'last_name'   => 220,
            'phone'       => 227,
            'stage'       => 228,
            'source'      => 229,
            'notes'       => 230,
            'sqft'        => 231,
            'service_type'=> 232,
            'address'     => 234,
            'email'       => 235,
        ],
    ],

    'phone' => [
        'provider'      => 'signalwire',
        'project_id'    => 'ce4806cb-ccb0-41e9-8bf1-7ea59536adfd',
        'space'         => 'mobilemechanic.signalwire.com',
        'api_token'     => 'PT1c8cf22d1446d4d9daaf580a26ad92729e48a4a33beb769a',
        'phone_number'  => '+19049258873',        // Shared sod number (904-925-TURF)
        'forward_to'    => '+19046634789',
    ],

    'openai' => [
        'api_key'       => getenv('OPENAI_API_KEY') ?: '',
        'model'         => 'gpt-4o-mini',
    ],

    'features' => [
        'voice_assistant'   => true,
        'sms_estimates'     => true,
        'web_quotes'        => true,
        'buyer_portal'      => true,
    ],

    'seo' => [
        'title_template'    => '%page% | Jacksonville Sod Installation',
        'meta_description'  => 'Professional landscaping, sod installation, and drainage solutions in Jacksonville FL.',
    ],

    // EzLead HQ (ezlead4u.com) - Lead Distribution
    'ezlead' => [
        'enabled'   => true,
        'base_url'  => 'https://ezlead4u.com',
        'api_key'   => 'ezl_HyQnN8ruPmkJp1MzDoDJMq-bH2Piy1tFxZaahMUv6TQ',
        'vertical'  => 'sod',
        'state'     => 'FL',
        'timeout'   => 10,
        'log_file'  => __DIR__ . '/../data/ezlead.log',
    ],

    'estimates' => [
        'enabled'       => true,
        'labor_rate'    => 75,
        'min_charge'    => 350,
        'pricing' => [
            'st_augustine_per_sqft' => 1.73,
            'zoysia_per_sqft' => 2.00,
            'french_drain_per_ft' => 45,
            'grading_per_sqft' => 0.75,
        ],
    ],

    'buyer_portal' => [
        'enabled'       => true,
        'price_per_lead'=> 3500,  // $35
        'free_leads'    => 3,
        'min_balance'   => 3500,  // $35
    ],
];
