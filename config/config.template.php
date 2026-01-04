<?php
/**
 * Site Configuration Template
 * Copy this file to [yourdomain.com].php and fill in the values
 */

return [
    // ===================
    // SITE IDENTITY
    // ===================
    'site' => [
        'name'        => 'Your Business Name',
        'tagline'     => 'Your tagline here',
        'domain'      => 'yourdomain.com',
        'phone'       => '+1234567890',           // Display phone
        'email'       => 'info@yourdomain.com',
        'address'     => '123 Main St, City, ST 12345',
        'service_area'=> 'Jacksonville, FL',
    ],

    // ===================
    // BUSINESS TYPE
    // ===================
    'business' => [
        'type'        => 'contractor',            // contractor, mechanic, landscaper, etc.
        'category'    => 'Landscaping',           // For schema markup
        'services'    => ['Sod Installation', 'Lawn Care', 'Irrigation'],
    ],

    // ===================
    // BRANDING
    // ===================
    'branding' => [
        'primary_color'   => '#2563eb',           // Blue
        'secondary_color' => '#10b981',           // Green
        'logo'            => '/assets/logo.png',
        'favicon'         => '/favicon.ico',
    ],

    // ===================
    // CRM (Rukovoditel)
    // ===================
    'crm' => [
        'enabled'       => true,
        'api_url'       => 'https://yourdomain.com/crm/api/rest.php',
        'username'      => '',
        'password'      => '',
        'api_key'       => '',
        'entity_id'     => 26,                    // Leads entity
        'created_by'    => 2,                     // User ID
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

    // ===================
    // PHONE SYSTEM
    // ===================
    'phone' => [
        'provider'      => 'signalwire',          // signalwire or twilio
        'project_id'    => '',
        'space'         => '',                    // SignalWire space URL
        'api_token'     => '',
        'phone_number'  => '',                    // Inbound number
        'forward_to'    => '',                    // Ring this number first
        'recordings_token' => '',                 // Token for recording access
    ],

    // ===================
    // AI / OPENAI
    // ===================
    'openai' => [
        'api_key'       => '',
        'model'         => 'gpt-4o-mini',         // For estimates/transcription
        'whisper_model' => 'whisper-1',           // For audio transcription
    ],

    // ===================
    // FEATURES
    // ===================
    'features' => [
        'voice_assistant'   => true,              // GPT picks up missed calls
        'call_recording'    => true,
        'transcription'     => true,
        'sms_estimates'     => true,
        'web_quotes'        => true,
        'crm_integration'   => true,
    ],

    // ===================
    // SEO
    // ===================
    'seo' => [
        'title_template'    => '%page% | %site_name%',
        'meta_description'  => 'Professional services in your area.',
        'og_image'          => '/assets/og-image.jpg',
        'google_analytics'  => '',                // GA4 ID
        'google_tag_manager'=> '',                // GTM ID
    ],

    // ===================
    // ESTIMATES (GPT-powered)
    // ===================
    'estimates' => [
        'enabled'       => true,
        'labor_rate'    => 75,                    // $ per hour (or per unit)
        'min_charge'    => 150,                   // Minimum job charge

        // GPT prompt context - customize for your business
        'system_prompt' => 'You are an expert estimator for a landscaping company.',

        // What the estimate is for (changes GPT prompt)
        'estimate_type' => 'landscaping',         // mechanic, landscaping, roofing, plumbing, etc.

        // Input fields for the estimate form (business-specific)
        'input_fields' => [
            // Example for landscaping:
            ['name' => 'sqft', 'label' => 'Square Footage', 'type' => 'number', 'required' => true],
            ['name' => 'grass_type', 'label' => 'Grass Type', 'type' => 'select', 'options' => ['St. Augustine', 'Bermuda', 'Zoysia', 'Bahia']],
            ['name' => 'soil_prep', 'label' => 'Needs Soil Prep?', 'type' => 'checkbox'],
            ['name' => 'removal', 'label' => 'Old Lawn Removal?', 'type' => 'checkbox'],
            ['name' => 'notes', 'label' => 'Additional Details', 'type' => 'textarea'],

            // Example for mechanic (comment out above, use these):
            // ['name' => 'year', 'label' => 'Year', 'type' => 'text', 'required' => true],
            // ['name' => 'make', 'label' => 'Make', 'type' => 'text', 'required' => true],
            // ['name' => 'model', 'label' => 'Model', 'type' => 'text', 'required' => true],
            // ['name' => 'problem', 'label' => 'What needs fixed?', 'type' => 'textarea', 'required' => true],
        ],

        // GPT prompt templates per business type
        'prompts' => [
            'mechanic' => 'You are an automotive repair estimator. Vehicle: {year} {make} {model}. Problem: {problem}. Give labor hours, parts cost range, and total estimate.',
            'landscaping' => 'You are a landscaping estimator. Job: {sqft} sqft of {grass_type} sod installation. Soil prep: {soil_prep}. Old lawn removal: {removal}. Give material cost, labor cost, and total estimate.',
            'roofing' => 'You are a roofing estimator. Job: {sqft} sqft {roof_type} roof. Give material cost, labor cost, and total estimate.',
            'plumbing' => 'You are a plumbing estimator. Job: {service_type}. Give parts cost, labor cost, and total estimate.',
        ],
    ],
];
