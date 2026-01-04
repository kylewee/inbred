<?php
/**
 * sodjacksonvillefl.com - Sod Installation Jacksonville
 * AGED DOMAIN: Registered 2008 (17 years old)
 * Migrated from landimpressions.com
 */

return [
    'site' => [
        'name'        => 'Sod Jacksonville FL',
        'tagline'     => 'Professional Sod Installation',
        'domain'      => 'sodjacksonvillefl.com',
        'phone'       => '+19049258873',          // 904-925-TURF
        'email'       => 'info@sodjacksonvillefl.com',
        'address'     => 'Jacksonville, FL',
        'service_area'=> 'Jacksonville, FL and surrounding areas',
    ],

    'business' => [
        'type'        => 'landscaping',
        'category'    => 'Sod Installation',
        'services'    => ['Sod Installation', 'Lawn Replacement', 'New Lawn', 'Sod Delivery'],
    ],

    'branding' => [
        'primary_color'   => '#15803d',           // Forest green
        'secondary_color' => '#0369a1',           // Sky blue
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
        'phone_number'  => '+19049258873',        // 904-925-TURF
        'forward_to'    => '+19046634789',
        'recordings_token' => 'sodrec-2b7c9f1a5d4e',
    ],

    'openai' => [
        'api_key'       => getenv('OPENAI_API_KEY') ?: '',
        'model'         => 'gpt-4o-mini',
        'whisper_model' => 'whisper-1',
    ],

    'features' => [
        'voice_assistant'   => true,
        'call_recording'    => true,
        'transcription'     => true,
        'sms_estimates'     => true,
        'web_quotes'        => true,
        'crm_integration'   => true,
    ],

    'seo' => [
        'title_template'    => '%page% | Sod Installation Jacksonville FL',
        'meta_description'  => 'Professional sod installation in Jacksonville, FL. New lawns, lawn replacement, sod delivery. Call for a free quote.',
        'og_image'          => '/assets/og-image.jpg',
        'google_analytics'  => '',
        'google_tag_manager'=> '',
    ],

    // EzLead HQ (ezlead4u.com) - Lead Distribution
    'ezlead' => [
        'enabled'   => true,
        'base_url'  => 'https://ezlead4u.com',
        'api_key'   => 'ezl_t2Aj5PLT9ySH3FxjV-cP4SejbO16-D6YUxQ94Z9JTFM',
        'vertical'  => 'sod',
        'state'     => 'FL',
        'timeout'   => 10,
        'log_file'  => __DIR__ . '/../data/ezlead.log',
    ],

    'estimates' => [
        'enabled'       => true,
        'labor_rate'    => 75,                    // $ per hour
        'min_charge'    => 350,
        'system_prompt' => 'You are a sod installation estimator in Jacksonville, FL.',
        'estimate_type' => 'sod',
        'input_fields'  => [
            ['name' => 'grass_type', 'label' => 'Grass Type', 'type' => 'select', 'required' => true, 'options' => ['St. Augustine (Floratam)', 'St. Augustine (Bitter Blue)', 'St. Augustine (Palmetto/Shade)', 'Zoysia (Empire)', 'Bermuda (419 Tifton)', 'Centipede', 'Bahia']],
            ['name' => 'sqft', 'label' => 'Approximate Square Footage', 'type' => 'number', 'required' => true],
            ['name' => 'address', 'label' => 'Property Address', 'type' => 'text', 'required' => true],
            ['name' => 'notes', 'label' => 'Additional Details', 'type' => 'textarea'],
        ],
        // Pricing based on landimpressions.com rates
        'pricing_table' => [
            'st_augustine' => [
                'pallet_sqft' => 500,
                'per_pallet' => [
                    1 => 867, 2 => 1435, 3 => 1807, 4 => 2200, 5 => 2541,
                    6 => 2860, 7 => 3206, 8 => 3531, 9 => 3877, 10 => 4202,
                ],
                'includes' => 'soil sampling, nutrient application, weed treatment, 4-6 inch tilling, delivery, installation',
            ],
            'zoysia' => [
                'pallet_sqft' => 450,
                'note' => 'Contact for pricing',
            ],
        ],
        'prompts' => [
            'sod' => 'You are a sod installation estimator for Sod Jacksonville FL. Grass type: {grass_type}. Area: {sqft} sqft. Details: {notes}. Use these installed prices: St Augustine ~$1.73/sqft (includes soil prep, tilling, delivery, install). Respond with JSON: {"job_name": "", "material_cost": 0, "labor_cost": 0, "total": 0, "timeframe": "", "notes": ""}',
        ],
    ],

    // 301 redirect from old domain
    'redirect_from' => [
        'landimpressions.com',
        'www.landimpressions.com',
    ],
];
