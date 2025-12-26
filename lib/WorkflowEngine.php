<?php
/**
 * WorkflowEngine - Central trigger processor for lead stage transitions
 *
 * Stages: New Lead → Quote Sent → Quote Viewed → Quote Approved → Scheduled → Completed → Review Requested
 * Also handles: Callback Needed (missed calls), Quote Expired
 */

class WorkflowEngine {
    private $db;
    private $crmFieldMap;
    private $stageFieldId;

    // Stage constants
    const STAGE_NEW_LEAD = 'New Lead';
    const STAGE_CALLBACK_NEEDED = 'Callback Needed';
    const STAGE_QUOTE_SENT = 'Quote Sent';
    const STAGE_QUOTE_VIEWED = 'Quote Viewed';
    const STAGE_QUOTE_APPROVED = 'Quote Approved';
    const STAGE_SCHEDULED = 'Scheduled';
    const STAGE_COMPLETED = 'Completed';
    const STAGE_REVIEW_REQUESTED = 'Review Requested';
    const STAGE_QUOTE_EXPIRED = 'Quote Expired';

    // Valid stage transitions
    private $validTransitions = [
        self::STAGE_NEW_LEAD => [self::STAGE_QUOTE_SENT, self::STAGE_CALLBACK_NEEDED, self::STAGE_SCHEDULED],
        self::STAGE_CALLBACK_NEEDED => [self::STAGE_NEW_LEAD, self::STAGE_QUOTE_SENT],
        self::STAGE_QUOTE_SENT => [self::STAGE_QUOTE_VIEWED, self::STAGE_QUOTE_APPROVED, self::STAGE_QUOTE_EXPIRED],
        self::STAGE_QUOTE_VIEWED => [self::STAGE_QUOTE_APPROVED, self::STAGE_QUOTE_EXPIRED],
        self::STAGE_QUOTE_APPROVED => [self::STAGE_SCHEDULED],
        self::STAGE_SCHEDULED => [self::STAGE_COMPLETED],
        self::STAGE_COMPLETED => [self::STAGE_REVIEW_REQUESTED],
        self::STAGE_QUOTE_EXPIRED => [self::STAGE_QUOTE_SENT, self::STAGE_NEW_LEAD],
    ];

    public function __construct() {
        $this->initDB();
        $this->loadCRMConfig();
    }

    private function initDB() {
        $dbPath = __DIR__ . '/../data/workflow.db';
        $this->db = new SQLite3($dbPath);

        // Workflow transitions log
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS transitions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lead_id INTEGER,
                phone TEXT,
                from_stage TEXT,
                to_stage TEXT,
                trigger_source TEXT,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Pending triggers (time-based)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pending_triggers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lead_id INTEGER,
                phone TEXT,
                trigger_type TEXT,
                trigger_at TEXT,
                executed_at TEXT,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create indexes
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_pending_trigger_at ON pending_triggers(trigger_at)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_pending_executed ON pending_triggers(executed_at)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_transitions_lead ON transitions(lead_id)");
    }

    private function loadCRMConfig() {
        $envPath = __DIR__ . '/../api/.env.local.php';
        if (file_exists($envPath) && !defined('CRM_FIELD_MAP')) {
            require_once $envPath;
        }

        $this->crmFieldMap = defined('CRM_FIELD_MAP') ? CRM_FIELD_MAP : [];
        $this->stageFieldId = $this->crmFieldMap['stage'] ?? 228;
    }

    /**
     * Transition a lead to a new stage
     *
     * @param int|null $leadId CRM lead ID
     * @param string $toStage Target stage
     * @param string $triggerSource What triggered this (e.g., 'recording_callback', 'quote_sms', 'cron')
     * @param array $metadata Additional context
     * @return array Result with success status
     */
    public function transition(?int $leadId, string $toStage, string $triggerSource, array $metadata = []): array {
        $phone = $metadata['phone'] ?? null;
        $fromStage = $metadata['from_stage'] ?? null;

        // Get current stage if we have a lead ID and no from_stage provided
        if ($leadId && !$fromStage) {
            $fromStage = $this->getCurrentStage($leadId);
        }

        // Log the transition
        $this->logTransition($leadId, $phone, $fromStage, $toStage, $triggerSource, $metadata);

        // Update CRM if we have a lead ID
        $crmResult = null;
        if ($leadId) {
            $crmResult = $this->updateCRMStage($leadId, $toStage);
        }

        // Schedule any follow-up triggers
        $this->scheduleFollowUpTriggers($leadId, $phone, $toStage, $metadata);

        // Execute immediate actions based on stage
        $actions = $this->executeStageActions($leadId, $phone, $toStage, $metadata);

        return [
            'success' => true,
            'lead_id' => $leadId,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'crm_updated' => $crmResult['success'] ?? false,
            'actions' => $actions
        ];
    }

    /**
     * Log transition to database
     */
    private function logTransition(?int $leadId, ?string $phone, ?string $fromStage, string $toStage, string $triggerSource, array $metadata): void {
        $stmt = $this->db->prepare("
            INSERT INTO transitions (lead_id, phone, from_stage, to_stage, trigger_source, metadata, created_at)
            VALUES (:lead_id, :phone, :from_stage, :to_stage, :trigger_source, :metadata, :created_at)
        ");

        $stmt->bindValue(':lead_id', $leadId, SQLITE3_INTEGER);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':from_stage', $fromStage, SQLITE3_TEXT);
        $stmt->bindValue(':to_stage', $toStage, SQLITE3_TEXT);
        $stmt->bindValue(':trigger_source', $triggerSource, SQLITE3_TEXT);
        $stmt->bindValue(':metadata', json_encode($metadata), SQLITE3_TEXT);
        $stmt->bindValue(':created_at', date('c'), SQLITE3_TEXT);
        $stmt->execute();
    }

    /**
     * Update CRM lead stage field
     */
    private function updateCRMStage(int $leadId, string $stage): array {
        if (!defined('CRM_API_URL') || !defined('CRM_API_KEY')) {
            return ['success' => false, 'error' => 'CRM not configured'];
        }

        $fieldId = $this->stageFieldId;

        // Try REST API first
        $post = [
            'key' => CRM_API_KEY,
            'action' => 'update',
            'entity_id' => CRM_LEADS_ENTITY_ID ?? 26,
            'id' => $leadId,
            "field_{$fieldId}" => $stage
        ];

        $ch = curl_init(CRM_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] === 'success') {
                return ['success' => true, 'method' => 'api'];
            }
        }

        // Fallback to direct DB update
        return $this->updateCRMStageDB($leadId, $stage);
    }

    /**
     * Direct DB update fallback
     */
    private function updateCRMStageDB(int $leadId, string $stage): array {
        $mysqli = $this->getCRMConnection();
        if (!$mysqli) {
            return ['success' => false, 'error' => 'DB connection failed'];
        }

        $entityId = CRM_LEADS_ENTITY_ID ?? 26;
        $table = "app_entity_{$entityId}";
        $fieldCol = "field_{$this->stageFieldId}";

        $stmt = $mysqli->prepare("UPDATE `{$table}` SET `{$fieldCol}` = ? WHERE id = ?");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Prepare failed'];
        }

        $stmt->bind_param('si', $stage, $leadId);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $mysqli->close();

        return [
            'success' => $result && $affected > 0,
            'method' => 'db',
            'affected' => $affected
        ];
    }

    /**
     * Get CRM MySQL connection
     */
    private function getCRMConnection(): ?mysqli {
        $configPath = __DIR__ . '/../crm/config/database.php';
        if (!file_exists($configPath)) {
            return null;
        }

        $cfg = @include $configPath;
        if (!is_array($cfg)) {
            // Try to parse it differently
            $content = file_get_contents($configPath);
            preg_match('/\$db_server\s*=\s*[\'"]([^\'"]+)/', $content, $m1);
            preg_match('/\$db_name\s*=\s*[\'"]([^\'"]+)/', $content, $m2);
            preg_match('/\$db_user\s*=\s*[\'"]([^\'"]+)/', $content, $m3);
            preg_match('/\$db_password\s*=\s*[\'"]([^\'"]+)/', $content, $m4);

            if ($m1 && $m2 && $m3) {
                $mysqli = @new mysqli($m1[1], $m3[1], $m4[1] ?? '', $m2[1]);
                return $mysqli->connect_error ? null : $mysqli;
            }
            return null;
        }

        $mysqli = @new mysqli(
            $cfg['server'] ?? 'localhost',
            $cfg['user'] ?? '',
            $cfg['password'] ?? '',
            $cfg['name'] ?? ''
        );

        return $mysqli->connect_error ? null : $mysqli;
    }

    /**
     * Get current stage for a lead
     */
    public function getCurrentStage(int $leadId): ?string {
        $mysqli = $this->getCRMConnection();
        if (!$mysqli) {
            return null;
        }

        $entityId = CRM_LEADS_ENTITY_ID ?? 26;
        $table = "app_entity_{$entityId}";
        $fieldCol = "field_{$this->stageFieldId}";

        $stmt = $mysqli->prepare("SELECT `{$fieldCol}` FROM `{$table}` WHERE id = ?");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $leadId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $mysqli->close();

        return $row[$fieldCol] ?? null;
    }

    /**
     * Schedule time-based follow-up triggers
     */
    private function scheduleFollowUpTriggers(?int $leadId, ?string $phone, string $stage, array $metadata): void {
        switch ($stage) {
            case self::STAGE_QUOTE_SENT:
                // Schedule quote expiration reminder for 24 hours
                $this->scheduleTrigger($leadId, $phone, 'quote_reminder', '+24 hours', $metadata);
                break;

            case self::STAGE_COMPLETED:
                // Schedule follow-up + review request for 24 hours after completion
                $this->scheduleTrigger($leadId, $phone, 'followup_review', '+24 hours', $metadata);
                break;

            case self::STAGE_CALLBACK_NEEDED:
                // Schedule callback reminder for 2 hours
                $this->scheduleTrigger($leadId, $phone, 'callback_reminder', '+2 hours', $metadata);
                break;
        }
    }

    /**
     * Schedule a future trigger
     */
    public function scheduleTrigger(?int $leadId, ?string $phone, string $triggerType, string $delay, array $metadata = []): void {
        $triggerAt = date('c', strtotime($delay));

        $stmt = $this->db->prepare("
            INSERT INTO pending_triggers (lead_id, phone, trigger_type, trigger_at, metadata, created_at)
            VALUES (:lead_id, :phone, :trigger_type, :trigger_at, :metadata, :created_at)
        ");

        $stmt->bindValue(':lead_id', $leadId, SQLITE3_INTEGER);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':trigger_type', $triggerType, SQLITE3_TEXT);
        $stmt->bindValue(':trigger_at', $triggerAt, SQLITE3_TEXT);
        $stmt->bindValue(':metadata', json_encode($metadata), SQLITE3_TEXT);
        $stmt->bindValue(':created_at', date('c'), SQLITE3_TEXT);
        $stmt->execute();
    }

    /**
     * Execute immediate actions based on stage transition
     */
    private function executeStageActions(?int $leadId, ?string $phone, string $stage, array $metadata): array {
        $actions = [];

        switch ($stage) {
            case self::STAGE_CALLBACK_NEEDED:
                // Send "we missed you" SMS
                if ($phone) {
                    $smsResult = $this->sendSMS($phone,
                        "We missed your call! EZ Mobile Mechanic here. " .
                        "Call us back at (904) 217-5152 or reply with what you need help with."
                    );
                    $actions['sms_sent'] = $smsResult;
                }
                break;

            case self::STAGE_QUOTE_APPROVED:
                // Send scheduling confirmation
                if ($phone) {
                    $smsResult = $this->sendSMS($phone,
                        "Quote approved! We'll call you within 2 hours to schedule your service. " .
                        "EZ Mobile Mechanic (904) 217-5152"
                    );
                    $actions['sms_sent'] = $smsResult;
                }
                break;
        }

        return $actions;
    }

    /**
     * Process all pending triggers (called by cron)
     */
    public function processPendingTriggers(): array {
        $now = date('c');
        $results = ['processed' => 0, 'actions' => []];

        $stmt = $this->db->prepare("
            SELECT * FROM pending_triggers
            WHERE trigger_at <= :now AND executed_at IS NULL
            ORDER BY trigger_at ASC
        ");
        $stmt->bindValue(':now', $now, SQLITE3_TEXT);
        $query = $stmt->execute();

        while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
            $result = $this->executePendingTrigger($row);
            $results['actions'][] = $result;
            $results['processed']++;

            // Mark as executed
            $update = $this->db->prepare("UPDATE pending_triggers SET executed_at = :now WHERE id = :id");
            $update->bindValue(':now', $now, SQLITE3_TEXT);
            $update->bindValue(':id', $row['id'], SQLITE3_INTEGER);
            $update->execute();
        }

        return $results;
    }

    /**
     * Execute a single pending trigger
     */
    private function executePendingTrigger(array $trigger): array {
        $metadata = json_decode($trigger['metadata'] ?? '{}', true);
        $result = ['trigger_type' => $trigger['trigger_type'], 'lead_id' => $trigger['lead_id']];

        switch ($trigger['trigger_type']) {
            case 'quote_reminder':
                // Check if quote was approved - if not, send reminder
                $currentStage = $trigger['lead_id'] ? $this->getCurrentStage($trigger['lead_id']) : null;
                if ($currentStage === self::STAGE_QUOTE_SENT || $currentStage === self::STAGE_QUOTE_VIEWED) {
                    if ($trigger['phone']) {
                        $this->sendSMS($trigger['phone'],
                            "Still thinking about your quote? We're here to help! " .
                            "Reply YES to approve or call (904) 217-5152 with questions. - EZ Mobile Mechanic"
                        );
                        $result['action'] = 'reminder_sent';
                    }
                } else {
                    $result['action'] = 'skipped_already_progressed';
                }
                break;

            case 'callback_reminder':
                // Send another callback reminder
                $currentStage = $trigger['lead_id'] ? $this->getCurrentStage($trigger['lead_id']) : null;
                if ($currentStage === self::STAGE_CALLBACK_NEEDED) {
                    if ($trigger['phone']) {
                        $this->sendSMS($trigger['phone'],
                            "Following up - we'd love to help with your vehicle! " .
                            "Call (904) 217-5152 or reply with what you need. - EZ Mobile Mechanic"
                        );
                        $result['action'] = 'callback_reminder_sent';
                    }
                } else {
                    $result['action'] = 'skipped_already_progressed';
                }
                break;

            case 'followup_review':
                // This is handled by the existing CustomerFlow system
                $result['action'] = 'delegated_to_customerflow';
                break;
        }

        return $result;
    }

    /**
     * Send SMS via SignalWire
     */
    private function sendSMS(string $to, string $message): array {
        if (!defined('SIGNALWIRE_PROJECT_ID') || !defined('SIGNALWIRE_API_TOKEN')) {
            return ['success' => false, 'error' => 'SignalWire not configured'];
        }

        $phone = $this->normalizePhone($to);
        $url = "https://" . SIGNALWIRE_SPACE . "/api/laml/2010-04-01/Accounts/" . SIGNALWIRE_PROJECT_ID . "/Messages.json";

        $data = [
            'To' => $phone,
            'From' => SIGNALWIRE_PHONE_NUMBER,
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD => SIGNALWIRE_PROJECT_ID . ':' . SIGNALWIRE_API_TOKEN,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => $httpCode === 201,
            'http_code' => $httpCode,
            'to' => $phone
        ];
    }

    /**
     * Normalize phone number to E.164
     */
    private function normalizePhone(string $phone): string {
        $cleaned = preg_replace('/[^\d\+]/', '', $phone);
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+1' . preg_replace('/^1/', '', $cleaned);
        }
        return $cleaned;
    }

    /**
     * Get transition history for a lead
     */
    public function getTransitionHistory(int $leadId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM transitions WHERE lead_id = :lead_id ORDER BY created_at DESC
        ");
        $stmt->bindValue(':lead_id', $leadId, SQLITE3_INTEGER);
        $query = $stmt->execute();

        $history = [];
        while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
            $row['metadata'] = json_decode($row['metadata'], true);
            $history[] = $row;
        }

        return $history;
    }

    /**
     * Cancel pending triggers for a lead (e.g., when quote is approved, cancel reminder)
     */
    public function cancelPendingTriggers(int $leadId, ?string $triggerType = null): int {
        $sql = "UPDATE pending_triggers SET executed_at = :now WHERE lead_id = :lead_id AND executed_at IS NULL";
        if ($triggerType) {
            $sql .= " AND trigger_type = :type";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':lead_id', $leadId, SQLITE3_INTEGER);
        if ($triggerType) {
            $stmt->bindValue(':type', $triggerType, SQLITE3_TEXT);
        }
        $stmt->execute();

        return $this->db->changes();
    }
}
