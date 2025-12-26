<?php
/**
 * MERGED BEST CONFIGURATION
 * Entity 26 field mappings from idk/idk + SignalWire credentials from inbred
 */

// CRM API Configuration
define('CRM_API_URL', 'https://mechanicstaugustine.com/crm/api/rest.php');
define('CRM_LEADS_ENTITY_ID', 26);
define('CRM_USERNAME', 'kylewee2');
define('CRM_PASSWORD', 'rainonin');
define('CRM_API_KEY', 'VMm87uzSFFyWAWCDzCXEK2AajBbHIOOIwtfhMWbA');
define('CRM_CREATED_BY_USER_ID', 2);

// CRM Field Mapping - ENTITY 26 VERIFIED CORRECT
define('CRM_FIELD_MAP', [
    'first_name'  => 219,  // First Name
    'last_name'   => 220,  // Last Name
    'phone'       => 227,  // Phone
    'stage'       => 228,  // stage
    'source'      => 229,  // source
    'notes'       => 230,  // notes (textarea) - contains recording URL + transcript
    'year'        => 231,  // year
    'make'        => 232,  // Make
    'model'       => 233,  // model
    'address'     => 234,  // Address
    'email'       => 235,  // Email
]);

// SignalWire Configuration (current production)
define('SIGNALWIRE_PROJECT_ID', 'ce4806cb-ccb0-41e9-8bf1-7ea59536adfd');
define('SIGNALWIRE_SPACE', 'mobilemechanic.signalwire.com');
define('SIGNALWIRE_API_TOKEN', 'PT1c8cf22d1446d4d9daaf580a26ad92729e48a4a33beb769a');
define('SIGNALWIRE_PHONE_NUMBER', '+19047066669');
define('MECHANIC_CELL_NUMBER', '+19046634789');
define('TWILIO_FORWARD_TO', '+19046634789');

// OpenAI for transcription
define('OPENAI_API_KEY', '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');

// Recording access token
define('VOICE_RECORDINGS_TOKEN', 'msarec-2b7c9f1a5d4e');
