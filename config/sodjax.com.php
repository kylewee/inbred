<?php
/**
 * sodjax.com - Sod Installation
 * Short domain - good for ads (paid thru 2029)
 */

return [
    'site' => [
        'name'        => 'Sod Jax',
        'tagline'     => 'Professional Sod Installation',
        'domain'      => 'sodjax.com',
        'phone'       => '',                      // TODO: Your phone number
        'email'       => 'info@sodjax.com',
        'address'     => 'Jacksonville, FL',
        'service_area'=> 'Jacksonville, FL and surrounding areas',
    ],

    'business' => [
        'type'        => 'landscaping',
        'category'    => 'Landscaping',
        'services'    => ['Sod Installation', 'Lawn Replacement', 'Grading', 'Irrigation'],
    ],

    'branding' => [
        'primary_color'   => '#16a34a',           // Green
        'secondary_color' => '#854d0e',           // Brown/earth
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
            'grass_type'  => 232,
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
        'title_template'    => '%page% | Sod Jax - Jacksonville Sod Installation',
        'meta_description'  => 'Professional sod installation in Jacksonville, FL. Free estimates. Same-week installation available.',
        'og_image'          => '/assets/og-image.jpg',
        'google_analytics'  => '',
        'google_tag_manager'=> '',
    ],

    // EzLead HQ (ezlead4u.com) - Lead Distribution
    'ezlead' => [
        'enabled'   => true,
        'base_url'  => 'https://ezlead4u.com',
        'api_key'   => 'ezl_DN2qTNd3sN27fK3I4rZwGF5c3v4es_qU12UcEr3_92M',
        'vertical'  => 'sod',
        'state'     => 'FL',
        'timeout'   => 10,
        'log_file'  => __DIR__ . '/../data/ezlead.log',
    ],

    'estimates' => [
        'enabled'       => true,
        'labor_rate'    => 0.85,                  // $ per sqft installed
        'min_charge'    => 500,                   // Minimum job
        'system_prompt' => 'You are an expert sod installation estimator in Jacksonville, FL.',
        'estimate_type' => 'landscaping',
        'input_fields'  => [
            ['name' => 'sqft', 'label' => 'Square Footage', 'type' => 'number', 'required' => true],
            ['name' => 'grass_type', 'label' => 'Grass Type', 'type' => 'select', 'required' => true, 'options' => ['St. Augustine (Floratam)', 'St. Augustine (Palmetto)', 'Bermuda', 'Zoysia', 'Bahia']],
            ['name' => 'soil_prep', 'label' => 'Needs Soil Prep/Grading?', 'type' => 'checkbox'],
            ['name' => 'removal', 'label' => 'Old Lawn Removal Needed?', 'type' => 'checkbox'],
            ['name' => 'address', 'label' => 'Property Address', 'type' => 'text', 'required' => true],
            ['name' => 'notes', 'label' => 'Additional Details', 'type' => 'textarea'],
        ],
        'prompts' => [
            'landscaping' => 'You are a sod installation estimator in Jacksonville FL. Job: {sqft} sqft of {grass_type} sod. Soil prep needed: {soil_prep}. Old lawn removal: {removal}. Respond with JSON: {"job_name": "", "material_cost": 0, "labor_cost": 0, "prep_cost": 0, "removal_cost": 0, "total": 0, "notes": ""}',
        ],
    ],

    'buyer_portal' => [
        'enabled'       => true,
        'price_per_lead'=> 3500,  // $35
        'free_leads'    => 3,
        'min_balance'   => 3500,  // $35
    ],
];
