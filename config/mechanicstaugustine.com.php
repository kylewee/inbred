<?php
/**
 * mechanicstaugustine.com - Mobile Mechanic
 * Original site config (reference)
 */

return [
    'site' => [
        'name'        => 'EZ Mobile Mechanic',
        'tagline'     => 'We Come To You',
        'domain'      => 'mechanicstaugustine.com',
        'phone'       => '+19047066669',
        'email'       => 'info@mechanicstaugustine.com',
        'address'     => 'St. Augustine, FL',
        'service_area'=> 'St. Augustine, FL and surrounding areas',
    ],

    'business' => [
        'type'        => 'mechanic',
        'category'    => 'Auto Repair',
        'services'    => ['Mobile Mechanic', 'Brake Repair', 'Oil Change', 'Diagnostics'],
    ],

    'branding' => [
        'primary_color'   => '#1e40af',
        'secondary_color' => '#f59e0b',
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
            'year'        => 231,
            'make'        => 232,
            'model'       => 233,
            'address'     => 234,
            'email'       => 235,
        ],
    ],

    'phone' => [
        'provider'      => 'signalwire',
        'project_id'    => 'ce4806cb-ccb0-41e9-8bf1-7ea59536adfd',
        'space'         => 'mobilemechanic.signalwire.com',
        'api_token'     => 'PT1c8cf22d1446d4d9daaf580a26ad92729e48a4a33beb769a',
        'phone_number'  => '+19047066669',
        'forward_to'    => '+19046634789',
        'recordings_token' => 'msarec-2b7c9f1a5d4e',
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
        'title_template'    => '%page% | EZ Mobile Mechanic St. Augustine',
        'meta_description'  => 'Mobile mechanic serving St. Augustine, FL. We come to you for brake repair, oil changes, and diagnostics.',
        'og_image'          => '/assets/og-image.jpg',
        'google_analytics'  => '',
        'google_tag_manager'=> '',
    ],

    'estimates' => [
        'enabled'       => true,
        'labor_rate'    => 95,
        'min_charge'    => 150,
        'system_prompt' => 'You are an expert mobile mechanic estimator in St. Augustine, FL.',
        'estimate_type' => 'mechanic',
        'input_fields'  => [
            ['name' => 'year', 'label' => 'Year', 'type' => 'text', 'required' => true],
            ['name' => 'make', 'label' => 'Make', 'type' => 'text', 'required' => true],
            ['name' => 'model', 'label' => 'Model', 'type' => 'text', 'required' => true],
            ['name' => 'engine', 'label' => 'Engine (e.g., 3.5L V6)', 'type' => 'text'],
            ['name' => 'problem', 'label' => 'What needs fixed?', 'type' => 'textarea', 'required' => true],
        ],
        'prompts' => [
            'mechanic' => 'You are an automotive repair estimator. Vehicle: {year} {make} {model} {engine}. Problem: {problem}. Respond with JSON: {"repair_name": "", "labor_hours": 0, "parts_cost_low": 0, "parts_cost_high": 0, "notes": ""}',
        ],
    ],
];
