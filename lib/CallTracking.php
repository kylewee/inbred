<?php
/**
 * Call Tracking Library - A/B Testing Integration
 *
 * Connects phone calls to website sessions and A/B tests.
 * Tracks call conversions and attributes them to the correct variant.
 *
 * How it works:
 * 1. When user visits a page with A/B test, their phone clicks are tracked
 * 2. If they click "Call Now", we store their visitor ID with the phone number they'll call from
 * 3. When call comes in, we match caller ID to stored visitor sessions
 * 4. Call is attributed to the correct A/B variant
 *
 * @author Kyle - EZ Mobile Mechanic
 * @version 1.0.0
 */

class CallTracking {
    private $db;

    public function __construct() {
        $dbPath = __DIR__ . '/../data/call_tracking.db';
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->db = new SQLite3($dbPath);
        $this->db->busyTimeout(5000);
        $this->initDatabase();
    }

    private function initDatabase() {
        // Click-to-call tracking (before call is made)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS call_intents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                visitor_id TEXT NOT NULL,
                session_id TEXT,
                phone_clicked TEXT,
                source_page TEXT,
                ab_experiment TEXT,
                ab_variant TEXT,
                utm_source TEXT,
                utm_medium TEXT,
                utm_campaign TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Actual phone calls (from voice system)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS phone_calls (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                call_sid TEXT UNIQUE,
                caller_phone TEXT NOT NULL,
                called_number TEXT,
                call_status TEXT DEFAULT 'initiated',
                call_duration INTEGER DEFAULT 0,
                recording_url TEXT,
                transcription TEXT,
                visitor_id TEXT,
                session_id TEXT,
                ab_experiment TEXT,
                ab_variant TEXT,
                source_page TEXT,
                utm_source TEXT,
                was_answered BOOLEAN DEFAULT 0,
                lead_created BOOLEAN DEFAULT 0,
                lead_id INTEGER,
                attribution_method TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // A/B call conversions summary
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS ab_call_conversions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                experiment_name TEXT NOT NULL,
                variant_name TEXT NOT NULL,
                call_id INTEGER,
                conversion_type TEXT DEFAULT 'call',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (call_id) REFERENCES phone_calls(id)
            )
        ");

        // Indexes
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_intents_visitor ON call_intents(visitor_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_intents_created ON call_intents(created_at)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_calls_phone ON phone_calls(caller_phone)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_calls_experiment ON phone_calls(ab_experiment)");
    }

    /**
     * Track a click-to-call intent (user clicked phone link)
     * Called from JavaScript when user clicks phone number
     */
    public function trackCallIntent(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO call_intents (visitor_id, session_id, phone_clicked, source_page, ab_experiment, ab_variant, utm_source, utm_medium, utm_campaign)
            VALUES (:visitor_id, :session_id, :phone_clicked, :source_page, :ab_experiment, :ab_variant, :utm_source, :utm_medium, :utm_campaign)
        ");

        $stmt->bindValue(':visitor_id', $data['visitor_id'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':session_id', $data['session_id'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':phone_clicked', $data['phone_clicked'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':source_page', $data['source_page'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':ab_experiment', $data['ab_experiment'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':ab_variant', $data['ab_variant'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':utm_source', $data['utm_source'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':utm_medium', $data['utm_medium'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':utm_campaign', $data['utm_campaign'] ?? '', SQLITE3_TEXT);

        $stmt->execute();
        return $this->db->lastInsertRowID();
    }

    /**
     * Track an incoming phone call (called from voice webhook)
     * Attempts to match caller to a recent website visitor
     */
    public function trackIncomingCall(array $callData): array {
        $callerPhone = $this->normalizePhone($callData['From'] ?? $callData['caller_phone'] ?? '');
        $callSid = $callData['CallSid'] ?? $callData['call_sid'] ?? bin2hex(random_bytes(16));

        // Try to find attribution from recent call intents (last 24 hours)
        $attribution = $this->findCallAttribution($callerPhone);

        // Insert call record
        $stmt = $this->db->prepare("
            INSERT INTO phone_calls (call_sid, caller_phone, called_number, call_status, visitor_id, session_id, ab_experiment, ab_variant, source_page, utm_source, attribution_method)
            VALUES (:call_sid, :caller_phone, :called_number, :call_status, :visitor_id, :session_id, :ab_experiment, :ab_variant, :source_page, :utm_source, :attribution_method)
        ");

        $stmt->bindValue(':call_sid', $callSid, SQLITE3_TEXT);
        $stmt->bindValue(':caller_phone', $callerPhone, SQLITE3_TEXT);
        $stmt->bindValue(':called_number', $callData['To'] ?? $callData['called_number'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':call_status', $callData['CallStatus'] ?? 'initiated', SQLITE3_TEXT);
        $stmt->bindValue(':visitor_id', $attribution['visitor_id'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':session_id', $attribution['session_id'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':ab_experiment', $attribution['ab_experiment'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':ab_variant', $attribution['ab_variant'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':source_page', $attribution['source_page'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':utm_source', $attribution['utm_source'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':attribution_method', $attribution['method'] ?? 'none', SQLITE3_TEXT);

        $stmt->execute();
        $callId = $this->db->lastInsertRowID();

        // If we have A/B attribution, record the conversion
        if (!empty($attribution['ab_experiment']) && !empty($attribution['ab_variant'])) {
            $this->recordABConversion($attribution['ab_experiment'], $attribution['ab_variant'], $callId, 'call_initiated');

            // Also update the main A/B testing database
            $this->updateABTestingDB($attribution['ab_experiment'], 'conversion', [
                'action' => 'phone_call',
                'call_id' => $callId
            ]);
        }

        return [
            'call_id' => $callId,
            'call_sid' => $callSid,
            'attributed' => !empty($attribution['visitor_id']),
            'ab_experiment' => $attribution['ab_experiment'] ?? null,
            'ab_variant' => $attribution['ab_variant'] ?? null,
            'attribution_method' => $attribution['method'] ?? 'none'
        ];
    }

    /**
     * Update call with completion data
     */
    public function updateCall(string $callSid, array $data): bool {
        $updates = [];
        $params = [];

        $fields = ['call_status', 'call_duration', 'recording_url', 'transcription', 'was_answered', 'lead_created', 'lead_id'];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE phone_calls SET " . implode(', ', $updates) . " WHERE call_sid = :call_sid";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':call_sid', $callSid, SQLITE3_TEXT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $result = $stmt->execute();

        // If call was answered and has A/B attribution, record answered conversion
        if ($result && isset($data['was_answered']) && $data['was_answered']) {
            $callData = $this->getCallByCallSid($callSid);
            if ($callData && $callData['ab_experiment'] && $callData['ab_variant']) {
                $this->recordABConversion($callData['ab_experiment'], $callData['ab_variant'], $callData['id'], 'call_answered');

                $this->updateABTestingDB($callData['ab_experiment'], 'conversion', [
                    'action' => 'phone_call_answered',
                    'call_id' => $callData['id'],
                    'duration' => $data['call_duration'] ?? 0
                ]);
            }
        }

        return $result !== false;
    }

    /**
     * Find attribution for a phone call based on recent activity
     */
    private function findCallAttribution(string $callerPhone): array {
        // Method 1: Check if we tracked a click-to-call from this user recently
        // We match based on time proximity (call came within 5 minutes of click)
        $stmt = $this->db->prepare("
            SELECT visitor_id, session_id, ab_experiment, ab_variant, source_page, utm_source
            FROM call_intents
            WHERE created_at > datetime('now', '-5 minutes')
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result && $result['visitor_id']) {
            $result['method'] = 'click_tracking';
            return $result;
        }

        // Method 2: Check A/B testing cookies from recent sessions
        // This requires the user to have called from the same device
        // We track this via the ab-track API

        // Method 3: If no direct match, attribute to most recent active experiment
        $stmt = $this->db->prepare("
            SELECT DISTINCT ab_experiment, ab_variant
            FROM call_intents
            WHERE ab_experiment IS NOT NULL AND ab_experiment != ''
            AND created_at > datetime('now', '-24 hours')
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result && $result['ab_experiment']) {
            $result['method'] = 'recent_experiment';
            return $result;
        }

        return ['method' => 'none'];
    }

    /**
     * Record A/B conversion for call tracking
     */
    private function recordABConversion(string $experiment, string $variant, int $callId, string $type): void {
        $stmt = $this->db->prepare("
            INSERT INTO ab_call_conversions (experiment_name, variant_name, call_id, conversion_type)
            VALUES (:experiment, :variant, :call_id, :type)
        ");
        $stmt->bindValue(':experiment', $experiment, SQLITE3_TEXT);
        $stmt->bindValue(':variant', $variant, SQLITE3_TEXT);
        $stmt->bindValue(':call_id', $callId, SQLITE3_INTEGER);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->execute();
    }

    /**
     * Update the main A/B testing database with conversion
     */
    private function updateABTestingDB(string $experimentName, string $eventType, array $metadata = []): void {
        $abDbPath = __DIR__ . '/../data/ab_testing.db';
        if (!file_exists($abDbPath)) {
            return;
        }

        try {
            $abDb = new SQLite3($abDbPath);

            // Get experiment and variant IDs
            $stmt = $abDb->prepare("
                SELECT e.id as experiment_id, v.id as variant_id
                FROM experiments e
                JOIN variants v ON v.experiment_id = e.id
                WHERE e.name = :exp_name
                LIMIT 1
            ");
            $stmt->bindValue(':exp_name', $experimentName, SQLITE3_TEXT);
            $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

            if (!$result) {
                return;
            }

            // Find visitor ID from cookies if possible
            $visitorId = $_COOKIE['ez_ab_visitor'] ?? ('call_' . time());

            // Insert event
            $stmt = $abDb->prepare("
                INSERT INTO events (experiment_id, variant_id, event_type, visitor_id, metadata)
                VALUES (:exp_id, :var_id, :event_type, :visitor_id, :metadata)
            ");
            $stmt->bindValue(':exp_id', $result['experiment_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':var_id', $result['variant_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':event_type', $eventType, SQLITE3_TEXT);
            $stmt->bindValue(':visitor_id', $visitorId, SQLITE3_TEXT);
            $stmt->bindValue(':metadata', json_encode($metadata), SQLITE3_TEXT);
            $stmt->execute();

            $abDb->close();
        } catch (Exception $e) {
            // Log error but don't break the call flow
            error_log("CallTracking A/B update error: " . $e->getMessage());
        }
    }

    private function getCallByCallSid(string $callSid): ?array {
        $stmt = $this->db->prepare("SELECT * FROM phone_calls WHERE call_sid = :call_sid");
        $stmt->bindValue(':call_sid', $callSid, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return $result ?: null;
    }

    private function normalizePhone(string $phone): string {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Get call statistics for A/B experiments
     */
    public function getABCallStats(string $experimentName = null): array {
        $where = $experimentName ? "WHERE ab_experiment = :exp" : "WHERE ab_experiment IS NOT NULL";

        $sql = "
            SELECT
                ab_experiment,
                ab_variant,
                COUNT(*) as total_calls,
                SUM(CASE WHEN was_answered = 1 THEN 1 ELSE 0 END) as answered_calls,
                SUM(CASE WHEN lead_created = 1 THEN 1 ELSE 0 END) as leads_created,
                AVG(CASE WHEN call_duration > 0 THEN call_duration ELSE NULL END) as avg_duration,
                attribution_method
            FROM phone_calls
            $where
            GROUP BY ab_experiment, ab_variant, attribution_method
            ORDER BY ab_experiment, ab_variant
        ";

        $stmt = $this->db->prepare($sql);
        if ($experimentName) {
            $stmt->bindValue(':exp', $experimentName, SQLITE3_TEXT);
        }

        $result = $stmt->execute();
        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[] = $row;
        }
        return $stats;
    }

    /**
     * Get recent calls with attribution info
     */
    public function getRecentCalls(int $limit = 20): array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM phone_calls
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $calls = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $calls[] = $row;
        }
        return $calls;
    }
}
