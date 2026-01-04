<?php
/**
 * Bootstrap - Auto-loads config based on domain
 * Include this at the top of any PHP file that needs config
 */

// Prevent double-loading
if (defined('CONFIG_LOADED')) {
    return $GLOBALS['site_config'];
}
define('CONFIG_LOADED', true);

// Get the domain from request
$domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$domain = preg_replace('/:\d+$/', '', $domain);   // Strip port
$domain = preg_replace('/^www\./', '', $domain);  // Strip www.
$domain = strtolower($domain);

// Config file path
$config_dir = __DIR__;
$config_file = "{$config_dir}/{$domain}.php";

// Fallback to default.php if domain config doesn't exist
if (!file_exists($config_file)) {
    $config_file = "{$config_dir}/default.php";
}

// Load config
if (file_exists($config_file)) {
    $config = require $config_file;
} else {
    // Emergency fallback - minimal config
    $config = [
        'site' => [
            'name' => 'Site',
            'domain' => $domain,
        ],
    ];
    error_log("WARNING: No config found for domain: {$domain}");
}

// Store globally for easy access
$GLOBALS['site_config'] = $config;

/**
 * Helper function to get config values
 * Usage: config('site.name') or config('crm.api_url')
 */
function config($key, $default = null) {
    $config = $GLOBALS['site_config'];
    $keys = explode('.', $key);

    foreach ($keys as $k) {
        if (!isset($config[$k])) {
            return $default;
        }
        $config = $config[$k];
    }

    return $config;
}

/**
 * Legacy support - define constants for backwards compatibility
 * This lets existing code work without modification
 */
function define_legacy_constants($config) {
    // CRM
    if (!empty($config['crm'])) {
        if (!defined('CRM_API_URL'))        define('CRM_API_URL', $config['crm']['api_url'] ?? '');
        if (!defined('CRM_LEADS_ENTITY_ID')) define('CRM_LEADS_ENTITY_ID', $config['crm']['entity_id'] ?? 26);
        if (!defined('CRM_USERNAME'))       define('CRM_USERNAME', $config['crm']['username'] ?? '');
        if (!defined('CRM_PASSWORD'))       define('CRM_PASSWORD', $config['crm']['password'] ?? '');
        if (!defined('CRM_API_KEY'))        define('CRM_API_KEY', $config['crm']['api_key'] ?? '');
        if (!defined('CRM_CREATED_BY_USER_ID')) define('CRM_CREATED_BY_USER_ID', $config['crm']['created_by'] ?? 2);
        if (!defined('CRM_FIELD_MAP'))      define('CRM_FIELD_MAP', $config['crm']['field_map'] ?? []);
    }

    // Phone/SignalWire
    if (!empty($config['phone'])) {
        if (!defined('SIGNALWIRE_PROJECT_ID'))   define('SIGNALWIRE_PROJECT_ID', $config['phone']['project_id'] ?? '');
        if (!defined('SIGNALWIRE_SPACE'))        define('SIGNALWIRE_SPACE', $config['phone']['space'] ?? '');
        if (!defined('SIGNALWIRE_API_TOKEN'))    define('SIGNALWIRE_API_TOKEN', $config['phone']['api_token'] ?? '');
        if (!defined('SIGNALWIRE_PHONE_NUMBER')) define('SIGNALWIRE_PHONE_NUMBER', $config['phone']['phone_number'] ?? '');
        if (!defined('MECHANIC_CELL_NUMBER'))    define('MECHANIC_CELL_NUMBER', $config['phone']['forward_to'] ?? '');
        if (!defined('TWILIO_FORWARD_TO'))       define('TWILIO_FORWARD_TO', $config['phone']['forward_to'] ?? '');
        if (!defined('VOICE_RECORDINGS_TOKEN'))  define('VOICE_RECORDINGS_TOKEN', $config['phone']['recordings_token'] ?? '');
    }

    // OpenAI
    if (!empty($config['openai'])) {
        if (!defined('OPENAI_API_KEY')) define('OPENAI_API_KEY', $config['openai']['api_key'] ?? '');
    }

    // EzLead Distribution (ezlead4u.com)
    if (!empty($config['ezlead'])) {
        if (!defined('EZLEAD_ENABLED'))   define('EZLEAD_ENABLED', $config['ezlead']['enabled'] ?? false);
        if (!defined('EZLEAD_BASE_URL'))  define('EZLEAD_BASE_URL', $config['ezlead']['base_url'] ?? '');
        if (!defined('EZLEAD_API_KEY'))   define('EZLEAD_API_KEY', $config['ezlead']['api_key'] ?? '');
        if (!defined('EZLEAD_TIMEOUT'))   define('EZLEAD_TIMEOUT', $config['ezlead']['timeout'] ?? 10);
        if (!defined('EZLEAD_LOG_FILE'))  define('EZLEAD_LOG_FILE', $config['ezlead']['log_file'] ?? null);
        if (!defined('EZLEAD_VERTICAL'))  define('EZLEAD_VERTICAL', $config['ezlead']['vertical'] ?? 'contractor');
    }
}

// Define legacy constants for backwards compatibility
define_legacy_constants($config);

return $config;
