<?php
/**
 * CRM Helper - Shared functions for interacting with Rukovoditel CRM
 *
 * Provides:
 * - Stage updates
 * - Comment logging
 * - Lead queries
 */

class CRMHelper {
    // Stage Choice IDs (from app_fields_choices where fields_id=228)
    const STAGE_NEW_LEAD = '68';
    const STAGE_CALLBACK_NEEDED = '69';
    const STAGE_QUOTE_SENT = '70';
    const STAGE_QUOTE_VIEWED = '71';
    const STAGE_QUOTE_APPROVED = '72';
    const STAGE_SCHEDULED = '73';
    const STAGE_IN_PROGRESS = '74';
    const STAGE_COMPLETED = '75';
    const STAGE_REVIEW_REQUESTED = '76';
    const STAGE_CLOSED_WON = '77';
    const STAGE_CLOSED_LOST = '78';

    private static $config = null;

    /**
     * Load CRM configuration
     */
    private static function loadConfig(): void {
        if (self::$config !== null) return;

        // Try bootstrap first (defines legacy constants)
        $bootstrapPath = __DIR__ . '/../config/bootstrap.php';
        if (file_exists($bootstrapPath) && !defined('CRM_API_URL')) {
            require_once $bootstrapPath;
        }

        // Fallback to old config if bootstrap not available
        if (!defined('CRM_API_URL')) {
            $envPath = __DIR__ . '/../api/.env.local.php';
            if (file_exists($envPath)) {
                require_once $envPath;
            }
        }

        self::$config = [
            'api_url' => defined('CRM_API_URL') ? CRM_API_URL : null,
            'api_key' => defined('CRM_API_KEY') ? CRM_API_KEY : null,
            'username' => defined('CRM_USERNAME') ? CRM_USERNAME : null,
            'password' => defined('CRM_PASSWORD') ? CRM_PASSWORD : null,
            'entity_id' => defined('CRM_LEADS_ENTITY_ID') ? CRM_LEADS_ENTITY_ID : 26,
            'stage_field_id' => defined('CRM_FIELD_MAP') && isset(CRM_FIELD_MAP['stage']) ? CRM_FIELD_MAP['stage'] : 228,
        ];
    }

    /**
     * Make API request to CRM
     */
    private static function apiRequest(array $post): array {
        self::loadConfig();

        if (!self::$config['api_url'] || !self::$config['api_key']) {
            return ['success' => false, 'error' => 'CRM not configured'];
        }

        $post['key'] = self::$config['api_key'];
        if (self::$config['username']) {
            $post['username'] = self::$config['username'];
        }
        if (self::$config['password']) {
            $post['password'] = self::$config['password'];
        }

        $ch = curl_init(self::$config['api_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error, 'http_code' => $httpCode];
        }

        $result = json_decode($response, true);
        return [
            'success' => $httpCode === 200 && isset($result['status']) && $result['status'] === 'success',
            'http_code' => $httpCode,
            'response' => $result
        ];
    }

    /**
     * Update lead stage
     *
     * @param int $leadId CRM lead ID
     * @param string $stageChoiceId Stage choice ID (use class constants)
     * @return array Result with success status
     */
    public static function updateStage(int $leadId, string $stageChoiceId): array {
        self::loadConfig();

        return self::apiRequest([
            'action' => 'update',
            'entity_id' => self::$config['entity_id'],
            'id' => $leadId,
            'field_' . self::$config['stage_field_id'] => $stageChoiceId,
        ]);
    }

    /**
     * Add comment/activity to lead
     *
     * @param int $leadId CRM lead ID
     * @param string $description Comment text
     * @return array Result with success status
     */
    public static function addComment(int $leadId, string $description): array {
        self::loadConfig();

        return self::apiRequest([
            'action' => 'insert_comment',
            'entity_id' => self::$config['entity_id'],
            'item_id' => $leadId,
            'comment_description' => $description,
        ]);
    }

    /**
     * Get lead by ID
     *
     * @param int $leadId CRM lead ID
     * @return array|null Lead data or null if not found
     */
    public static function getLead(int $leadId): ?array {
        self::loadConfig();

        $result = self::apiRequest([
            'action' => 'select',
            'entity_id' => self::$config['entity_id'],
            'filters' => json_encode([
                ['field' => 'id', 'operator' => '=', 'value' => $leadId]
            ]),
        ]);

        if ($result['success'] && !empty($result['response']['data'][0])) {
            return $result['response']['data'][0];
        }

        return null;
    }

    /**
     * Get lead by phone number
     *
     * @param string $phone Phone number
     * @return array|null Lead data or null if not found
     */
    public static function getLeadByPhone(string $phone): ?array {
        self::loadConfig();

        $phoneFieldId = defined('CRM_FIELD_MAP') && isset(CRM_FIELD_MAP['phone']) ? CRM_FIELD_MAP['phone'] : 227;

        $result = self::apiRequest([
            'action' => 'select',
            'entity_id' => self::$config['entity_id'],
            'filters' => json_encode([
                ['field' => 'field_' . $phoneFieldId, 'operator' => 'like', 'value' => '%' . preg_replace('/[^\d]/', '', $phone) . '%']
            ]),
        ]);

        if ($result['success'] && !empty($result['response']['data'][0])) {
            return $result['response']['data'][0];
        }

        return null;
    }

    /**
     * Update lead stage and add comment in one call
     *
     * @param int $leadId CRM lead ID
     * @param string $stageChoiceId Stage choice ID
     * @param string $comment Comment to add
     * @return array Combined result
     */
    public static function transitionStage(int $leadId, string $stageChoiceId, string $comment = ''): array {
        $results = [];

        // Update stage
        $results['stage_update'] = self::updateStage($leadId, $stageChoiceId);

        // Add comment if provided
        if ($comment) {
            $results['comment'] = self::addComment($leadId, $comment);
        }

        $results['success'] = $results['stage_update']['success'];
        return $results;
    }

    /**
     * Get stage name from choice ID
     */
    public static function getStageName(string $choiceId): string {
        $stages = [
            self::STAGE_NEW_LEAD => 'New Lead',
            self::STAGE_CALLBACK_NEEDED => 'Callback Needed',
            self::STAGE_QUOTE_SENT => 'Quote Sent',
            self::STAGE_QUOTE_VIEWED => 'Quote Viewed',
            self::STAGE_QUOTE_APPROVED => 'Quote Approved',
            self::STAGE_SCHEDULED => 'Scheduled',
            self::STAGE_IN_PROGRESS => 'In Progress',
            self::STAGE_COMPLETED => 'Completed',
            self::STAGE_REVIEW_REQUESTED => 'Review Requested',
            self::STAGE_CLOSED_WON => 'Closed Won',
            self::STAGE_CLOSED_LOST => 'Closed Lost',
        ];

        return $stages[$choiceId] ?? 'Unknown';
    }
}
