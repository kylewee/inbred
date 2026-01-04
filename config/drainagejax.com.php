<?php
/**
 * drainagejax.com - Yard Drainage Jacksonville
 */

return [
    'site' => [
        'name'        => 'Drainage Jax',
        'tagline'     => 'Yard Drainage Solutions',
        'domain'      => 'drainagejax.com',
        'phone'       => '',  // Forms only for now
        'email'       => 'info@drainagejax.com',
        'address'     => 'Jacksonville, FL',
        'service_area'=> 'Jacksonville, FL and surrounding areas',
    ],

    'business' => [
        'type'        => 'landscaping',
        'category'    => 'Yard Drainage',
        'services'    => ['French Drain Installation', 'Yard Leveling', 'Grading', 'Drainage Control'],
    ],

    'branding' => [
        'primary_color'   => '#1e40af',  // Blue
        'secondary_color' => '#15803d',  // Green
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
            'service_type'=> 232,
            'address'     => 234,
            'email'       => 235,
        ],
    ],

    'features' => [
        'voice_assistant'   => false,
        'call_recording'    => false,
        'transcription'     => false,
        'sms_estimates'     => false,
        'web_quotes'        => true,
        'crm_integration'   => true,
    ],

    'seo' => [
        'title_template'    => '%page% | Yard Drainage Jacksonville FL',
        'meta_description'  => 'Yard drainage solutions in Jacksonville FL. French drain installation, yard leveling, grading. Free estimates.',
        'og_image'          => '/assets/og-image.jpg',
    ],

    // EzLead HQ (ezlead4u.com) - Lead Distribution
    'ezlead' => [
        'enabled'   => true,
        'base_url'  => 'https://ezlead4u.com',
        'api_key'   => 'ezl_CK9ZYKf5XJdvS5Do-X1eGVPaXT3Jm_t0iQhOgMIRiWA',
        'vertical'  => 'drainage',
        'state'     => 'FL',
        'timeout'   => 10,
        'log_file'  => __DIR__ . '/../data/ezlead.log',
    ],
];
