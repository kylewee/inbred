<?php
/**
 * Default configuration
 * Used when no domain-specific config exists
 * Also useful for local development (localhost)
 */

return [
    'site' => [
        'name'        => 'Lead Gen Template',
        'tagline'     => 'Professional Services',
        'domain'      => 'localhost',
        'phone'       => '+1234567890',
        'email'       => 'info@example.com',
        'address'     => 'Your City, ST',
        'service_area'=> 'Your Service Area',
    ],

    'business' => [
        'type'        => 'contractor',
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
        'enabled'       => false,                 // Disable CRM for default
        'api_url'       => '',
        'username'      => '',
        'password'      => '',
        'api_key'       => '',
        'entity_id'     => 26,
        'created_by'    => 2,
        'field_map'     => [],
    ],

    'ezlead' => [
        'enabled'       => false,                 // Enable ezlead4u.com distribution
        'base_url'      => 'http://localhost:8000',  // ezlead4u.com API URL
        'api_key'       => '',                    // API key from source registration
        'timeout'       => 10,                    // Request timeout in seconds
        'log_file'      => null,                  // Optional log file path
        'vertical'      => 'contractor',          // Default vertical for this site
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
        'api_key'       => getenv('OPENAI_API_KEY') ?: '',
        'model'         => 'gpt-4o-mini',
        'whisper_model' => 'whisper-1',
    ],

    'features' => [
        'voice_assistant'   => false,
        'call_recording'    => false,
        'transcription'     => false,
        'sms_estimates'     => false,
        'web_quotes'        => true,              // Web form always available
        'crm_integration'   => false,
        'ezlead_distribution' => false,           // Send leads to ezlead4u.com
    ],

    'seo' => [
        'title_template'    => '%page% | %site_name%',
        'meta_description'  => 'Professional services in your area. Contact us for a free quote.',
        'og_image'          => '/assets/og-image.jpg',
        'google_analytics'  => '',
        'google_tag_manager'=> '',
    ],
];
