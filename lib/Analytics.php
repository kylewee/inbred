<?php
/**
 * Analytics & Call Tracking Library
 *
 * Features:
 * - Track page views, events, and conversions
 * - Phone call tracking with caller ID matching
 * - Integration with A/B testing
 * - Google Analytics 4 event pushing
 * - Real-time conversion attribution
 *
 * @author Kyle - EZ Mobile Mechanic
 * @version 1.0.0
 */

class Analytics {
    private $db;
    private $visitorId;
    private $sessionId;
    private $ga4MeasurementId;
    private $ga4ApiSecret;

    public function __construct() {
        $dbPath = __DIR__ . '/../data/analytics.db';
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->db = new SQLite3($dbPath);
        $this->db->busyTimeout(5000);
        $this->initDatabase();
        $this->initSession();

        // GA4 config (set these in your .env.local.php)
        $this->ga4MeasurementId = defined('GA4_MEASUREMENT_ID') ? GA4_MEASUREMENT_ID : null;
        $this->ga4ApiSecret = defined('GA4_API_SECRET') ? GA4_API_SECRET : null;
    }

    private function initDatabase() {
        // Visitors table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS visitors (
                id TEXT PRIMARY KEY,
                first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                total_visits INTEGER DEFAULT 1,
                total_pageviews INTEGER DEFAULT 0,
                source TEXT,
                medium TEXT,
                campaign TEXT,
                landing_page TEXT,
                device_type TEXT,
                browser TEXT,
                city TEXT,
                converted BOOLEAN DEFAULT 0,
                phone_number TEXT
            )
        ");

        // Sessions table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sessions (
                id TEXT PRIMARY KEY,
                visitor_id TEXT NOT NULL,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
                pageviews INTEGER DEFAULT 0,
                source TEXT,
                medium TEXT,
                campaign TEXT,
                landing_page TEXT,
                device_type TEXT,
                browser TEXT,
                converted BOOLEAN DEFAULT 0,
                conversion_type TEXT,
                FOREIGN KEY (visitor_id) REFERENCES visitors(id)
            )
        ");

        // Page views table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pageviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                visitor_id TEXT NOT NULL,
                session_id TEXT NOT NULL,
                page_url TEXT NOT NULL,
                page_title TEXT,
                referrer TEXT,
                time_on_page INTEGER DEFAULT 0,
                scroll_depth INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (visitor_id) REFERENCES visitors(id),
                FOREIGN KEY (session_id) REFERENCES sessions(id)
            )
        ");

        // Events table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                visitor_id TEXT NOT NULL,
                session_id TEXT NOT NULL,
                event_name TEXT NOT NULL,
                event_category TEXT,
                event_action TEXT,
                event_label TEXT,
                event_value REAL,
                metadata TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (visitor_id) REFERENCES visitors(id),
                FOREIGN KEY (session_id) REFERENCES sessions(id)
            )
        ");

        // Phone calls table (linked to web sessions)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS phone_calls (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                call_sid TEXT UNIQUE,
                caller_phone TEXT NOT NULL,
                called_number TEXT,
                visitor_id TEXT,
                session_id TEXT,
                ab_experiment TEXT,
                ab_variant TEXT,
                source TEXT,
                landing_page TEXT,
                call_status TEXT,
                call_duration INTEGER DEFAULT 0,
                recording_url TEXT,
                transcription TEXT,
                lead_created BOOLEAN DEFAULT 0,
                lead_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (visitor_id) REFERENCES visitors(id),
                FOREIGN KEY (session_id) REFERENCES sessions(id)
            )
        ");

        // Conversions table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS conversions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                visitor_id TEXT NOT NULL,
                session_id TEXT,
                conversion_type TEXT NOT NULL,
                conversion_value REAL DEFAULT 0,
                source TEXT,
                medium TEXT,
                campaign TEXT,
                landing_page TEXT,
                ab_experiment TEXT,
                ab_variant TEXT,
                phone_call_id INTEGER,
                form_submission_id TEXT,
                metadata TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (visitor_id) REFERENCES visitors(id),
                FOREIGN KEY (phone_call_id) REFERENCES phone_calls(id)
            )
        ");

        // Indexes
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_visitors_phone ON visitors(phone_number)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_visitor ON sessions(visitor_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_pageviews_session ON pageviews(session_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_events_session ON events(session_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_calls_phone ON phone_calls(caller_phone)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_calls_visitor ON phone_calls(visitor_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_conversions_type ON conversions(conversion_type)");
    }

    private function initSession() {
        // Get or create visitor ID
        $this->visitorId = $this->getOrCreateVisitorId();

        // Get or create session ID
        $this->sessionId = $this->getOrCreateSessionId();
    }

    private function getOrCreateVisitorId(): string {
        $cookieName = 'ez_vid';

        if (isset($_COOKIE[$cookieName])) {
            $visitorId = $_COOKIE[$cookieName];

            // Update last seen
            $stmt = $this->db->prepare("UPDATE visitors SET last_seen = CURRENT_TIMESTAMP, total_visits = total_visits + 1 WHERE id = :id");
            $stmt->bindValue(':id', $visitorId, SQLITE3_TEXT);
            $stmt->execute();

            return $visitorId;
        }

        // Create new visitor
        $visitorId = bin2hex(random_bytes(16));

        // Parse UTM parameters
        $source = $_GET['utm_source'] ?? ($_SERVER['HTTP_REFERER'] ?? 'direct');
        $medium = $_GET['utm_medium'] ?? 'none';
        $campaign = $_GET['utm_campaign'] ?? '';

        // Detect device
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = $this->detectDeviceType($userAgent);
        $browser = $this->detectBrowser($userAgent);

        $stmt = $this->db->prepare("
            INSERT INTO visitors (id, source, medium, campaign, landing_page, device_type, browser)
            VALUES (:id, :source, :medium, :campaign, :landing_page, :device_type, :browser)
        ");
        $stmt->bindValue(':id', $visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':source', $source, SQLITE3_TEXT);
        $stmt->bindValue(':medium', $medium, SQLITE3_TEXT);
        $stmt->bindValue(':campaign', $campaign, SQLITE3_TEXT);
        $stmt->bindValue(':landing_page', $_SERVER['REQUEST_URI'] ?? '/', SQLITE3_TEXT);
        $stmt->bindValue(':device_type', $deviceType, SQLITE3_TEXT);
        $stmt->bindValue(':browser', $browser, SQLITE3_TEXT);
        $stmt->execute();

        // Set cookie (1 year)
        if (!headers_sent()) {
            setcookie($cookieName, $visitorId, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        $_COOKIE[$cookieName] = $visitorId;

        return $visitorId;
    }

    private function getOrCreateSessionId(): string {
        $cookieName = 'ez_sid';
        $sessionTimeout = 1800; // 30 minutes

        if (isset($_COOKIE[$cookieName])) {
            $sessionId = $_COOKIE[$cookieName];

            // Check if session is still valid
            $stmt = $this->db->prepare("SELECT last_activity FROM sessions WHERE id = :id");
            $stmt->bindValue(':id', $sessionId, SQLITE3_TEXT);
            $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

            if ($result) {
                $lastActivity = strtotime($result['last_activity']);
                if (time() - $lastActivity < $sessionTimeout) {
                    // Update session activity
                    $stmt = $this->db->prepare("UPDATE sessions SET last_activity = CURRENT_TIMESTAMP, pageviews = pageviews + 1 WHERE id = :id");
                    $stmt->bindValue(':id', $sessionId, SQLITE3_TEXT);
                    $stmt->execute();

                    return $sessionId;
                }
            }
        }

        // Create new session
        $sessionId = bin2hex(random_bytes(16));

        $source = $_GET['utm_source'] ?? ($_SERVER['HTTP_REFERER'] ?? 'direct');
        $medium = $_GET['utm_medium'] ?? 'none';
        $campaign = $_GET['utm_campaign'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $this->db->prepare("
            INSERT INTO sessions (id, visitor_id, source, medium, campaign, landing_page, device_type, browser)
            VALUES (:id, :visitor_id, :source, :medium, :campaign, :landing_page, :device_type, :browser)
        ");
        $stmt->bindValue(':id', $sessionId, SQLITE3_TEXT);
        $stmt->bindValue(':visitor_id', $this->visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':source', $source, SQLITE3_TEXT);
        $stmt->bindValue(':medium', $medium, SQLITE3_TEXT);
        $stmt->bindValue(':campaign', $campaign, SQLITE3_TEXT);
        $stmt->bindValue(':landing_page', $_SERVER['REQUEST_URI'] ?? '/', SQLITE3_TEXT);
        $stmt->bindValue(':device_type', $this->detectDeviceType($userAgent), SQLITE3_TEXT);
        $stmt->bindValue(':browser', $this->detectBrowser($userAgent), SQLITE3_TEXT);
        $stmt->execute();

        // Set session cookie
        if (!headers_sent()) {
            setcookie($cookieName, $sessionId, [
                'expires' => 0, // Session cookie
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        $_COOKIE[$cookieName] = $sessionId;

        return $sessionId;
    }

    public function trackPageview(string $pageUrl = null, string $pageTitle = null): void {
        $stmt = $this->db->prepare("
            INSERT INTO pageviews (visitor_id, session_id, page_url, page_title, referrer)
            VALUES (:visitor_id, :session_id, :page_url, :page_title, :referrer)
        ");
        $stmt->bindValue(':visitor_id', $this->visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':session_id', $this->sessionId, SQLITE3_TEXT);
        $stmt->bindValue(':page_url', $pageUrl ?? $_SERVER['REQUEST_URI'] ?? '/', SQLITE3_TEXT);
        $stmt->bindValue(':page_title', $pageTitle ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':referrer', $_SERVER['HTTP_REFERER'] ?? '', SQLITE3_TEXT);
        $stmt->execute();

        // Update visitor pageviews
        $this->db->exec("UPDATE visitors SET total_pageviews = total_pageviews + 1 WHERE id = '" . SQLite3::escapeString($this->visitorId) . "'");
    }

    public function trackEvent(string $eventName, string $category = null, string $action = null, string $label = null, float $value = null, array $metadata = []): void {
        $stmt = $this->db->prepare("
            INSERT INTO events (visitor_id, session_id, event_name, event_category, event_action, event_label, event_value, metadata)
            VALUES (:visitor_id, :session_id, :event_name, :category, :action, :label, :value, :metadata)
        ");
        $stmt->bindValue(':visitor_id', $this->visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':session_id', $this->sessionId, SQLITE3_TEXT);
        $stmt->bindValue(':event_name', $eventName, SQLITE3_TEXT);
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $stmt->bindValue(':action', $action, SQLITE3_TEXT);
        $stmt->bindValue(':label', $label, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_FLOAT);
        $stmt->bindValue(':metadata', json_encode($metadata), SQLITE3_TEXT);
        $stmt->execute();

        // Push to GA4 if configured
        $this->pushToGA4($eventName, [
            'event_category' => $category,
            'event_action' => $action,
            'event_label' => $label,
            'value' => $value
        ]);
    }

    /**
     * Track a phone call and attribute it to a web session
     */
    public function trackPhoneCall(array $callData): int {
        $callerPhone = $callData['caller_phone'] ?? $callData['From'] ?? '';
        $callSid = $callData['call_sid'] ?? $callData['CallSid'] ?? '';

        // Try to find visitor by phone number
        $visitorId = null;
        $sessionId = null;
        $abExperiment = null;
        $abVariant = null;
        $source = 'phone';
        $landingPage = null;

        // Check if we have this phone number associated with a visitor
        $stmt = $this->db->prepare("SELECT id FROM visitors WHERE phone_number = :phone ORDER BY last_seen DESC LIMIT 1");
        $stmt->bindValue(':phone', $this->normalizePhone($callerPhone), SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result) {
            $visitorId = $result['id'];

            // Get latest session for this visitor
            $stmt = $this->db->prepare("SELECT id, source, landing_page FROM sessions WHERE visitor_id = :visitor_id ORDER BY started_at DESC LIMIT 1");
            $stmt->bindValue(':visitor_id', $visitorId, SQLITE3_TEXT);
            $sessionResult = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

            if ($sessionResult) {
                $sessionId = $sessionResult['id'];
                $source = $sessionResult['source'];
                $landingPage = $sessionResult['landing_page'];
            }

            // Check for A/B test assignment
            $abCookiePrefix = 'ez_ab_';
            // We can't read cookies from a phone call, but we can check the events table
            $stmt = $this->db->prepare("
                SELECT metadata FROM events
                WHERE visitor_id = :visitor_id AND event_name LIKE 'ab_%'
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->bindValue(':visitor_id', $visitorId, SQLITE3_TEXT);
            $abResult = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if ($abResult && $abResult['metadata']) {
                $abData = json_decode($abResult['metadata'], true);
                $abExperiment = $abData['experiment'] ?? null;
                $abVariant = $abData['variant'] ?? null;
            }
        }

        // Insert phone call record
        $stmt = $this->db->prepare("
            INSERT INTO phone_calls (call_sid, caller_phone, called_number, visitor_id, session_id, ab_experiment, ab_variant, source, landing_page, call_status)
            VALUES (:call_sid, :caller_phone, :called_number, :visitor_id, :session_id, :ab_experiment, :ab_variant, :source, :landing_page, :call_status)
        ");
        $stmt->bindValue(':call_sid', $callSid, SQLITE3_TEXT);
        $stmt->bindValue(':caller_phone', $this->normalizePhone($callerPhone), SQLITE3_TEXT);
        $stmt->bindValue(':called_number', $callData['called_number'] ?? $callData['To'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':visitor_id', $visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':session_id', $sessionId, SQLITE3_TEXT);
        $stmt->bindValue(':ab_experiment', $abExperiment, SQLITE3_TEXT);
        $stmt->bindValue(':ab_variant', $abVariant, SQLITE3_TEXT);
        $stmt->bindValue(':source', $source, SQLITE3_TEXT);
        $stmt->bindValue(':landing_page', $landingPage, SQLITE3_TEXT);
        $stmt->bindValue(':call_status', $callData['call_status'] ?? 'initiated', SQLITE3_TEXT);
        $stmt->execute();

        $callId = $this->db->lastInsertRowID();

        // Record conversion
        $this->recordConversion('phone_call', 0, [
            'phone_call_id' => $callId,
            'caller_phone' => $callerPhone,
            'ab_experiment' => $abExperiment,
            'ab_variant' => $abVariant
        ], $visitorId, $sessionId);

        return $callId;
    }

    /**
     * Update phone call with completion data
     */
    public function updatePhoneCall(string $callSid, array $data): bool {
        $sets = [];
        $params = [];

        if (isset($data['call_status'])) {
            $sets[] = 'call_status = :call_status';
            $params[':call_status'] = $data['call_status'];
        }
        if (isset($data['call_duration'])) {
            $sets[] = 'call_duration = :call_duration';
            $params[':call_duration'] = (int)$data['call_duration'];
        }
        if (isset($data['recording_url'])) {
            $sets[] = 'recording_url = :recording_url';
            $params[':recording_url'] = $data['recording_url'];
        }
        if (isset($data['transcription'])) {
            $sets[] = 'transcription = :transcription';
            $params[':transcription'] = $data['transcription'];
        }
        if (isset($data['lead_created'])) {
            $sets[] = 'lead_created = :lead_created';
            $params[':lead_created'] = $data['lead_created'] ? 1 : 0;
        }
        if (isset($data['lead_id'])) {
            $sets[] = 'lead_id = :lead_id';
            $params[':lead_id'] = (int)$data['lead_id'];
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE phone_calls SET " . implode(', ', $sets) . " WHERE call_sid = :call_sid";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':call_sid', $callSid, SQLITE3_TEXT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute() !== false;
    }

    /**
     * Link a form submission to the current session
     */
    public function trackFormSubmission(string $formName, array $formData): void {
        // Store phone number for future call attribution
        if (!empty($formData['phone'])) {
            $phone = $this->normalizePhone($formData['phone']);
            $stmt = $this->db->prepare("UPDATE visitors SET phone_number = :phone WHERE id = :id");
            $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
            $stmt->bindValue(':id', $this->visitorId, SQLITE3_TEXT);
            $stmt->execute();
        }

        $this->trackEvent('form_submission', 'forms', 'submit', $formName, null, $formData);

        // Record conversion
        $this->recordConversion('form_submission', 0, [
            'form_name' => $formName,
            'form_data' => $formData
        ]);
    }

    public function recordConversion(string $type, float $value = 0, array $metadata = [], string $visitorId = null, string $sessionId = null): int {
        $visitorId = $visitorId ?? $this->visitorId;
        $sessionId = $sessionId ?? $this->sessionId;

        // Get session attribution data
        $source = null;
        $medium = null;
        $campaign = null;
        $landingPage = null;

        if ($sessionId) {
            $stmt = $this->db->prepare("SELECT source, medium, campaign, landing_page FROM sessions WHERE id = :id");
            $stmt->bindValue(':id', $sessionId, SQLITE3_TEXT);
            $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if ($result) {
                $source = $result['source'];
                $medium = $result['medium'];
                $campaign = $result['campaign'];
                $landingPage = $result['landing_page'];
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO conversions (visitor_id, session_id, conversion_type, conversion_value, source, medium, campaign, landing_page, ab_experiment, ab_variant, metadata)
            VALUES (:visitor_id, :session_id, :type, :value, :source, :medium, :campaign, :landing_page, :ab_experiment, :ab_variant, :metadata)
        ");
        $stmt->bindValue(':visitor_id', $visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':session_id', $sessionId, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':value', $value, SQLITE3_FLOAT);
        $stmt->bindValue(':source', $source, SQLITE3_TEXT);
        $stmt->bindValue(':medium', $medium, SQLITE3_TEXT);
        $stmt->bindValue(':campaign', $campaign, SQLITE3_TEXT);
        $stmt->bindValue(':landing_page', $landingPage, SQLITE3_TEXT);
        $stmt->bindValue(':ab_experiment', $metadata['ab_experiment'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':ab_variant', $metadata['ab_variant'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':metadata', json_encode($metadata), SQLITE3_TEXT);
        $stmt->execute();

        // Mark session as converted
        if ($sessionId) {
            $stmt = $this->db->prepare("UPDATE sessions SET converted = 1, conversion_type = :type WHERE id = :id");
            $stmt->bindValue(':type', $type, SQLITE3_TEXT);
            $stmt->bindValue(':id', $sessionId, SQLITE3_TEXT);
            $stmt->execute();
        }

        // Mark visitor as converted
        if ($visitorId) {
            $this->db->exec("UPDATE visitors SET converted = 1 WHERE id = '" . SQLite3::escapeString($visitorId) . "'");
        }

        // Push conversion to GA4
        $this->pushToGA4('conversion', [
            'conversion_type' => $type,
            'value' => $value
        ]);

        return $this->db->lastInsertRowID();
    }

    private function pushToGA4(string $eventName, array $params): void {
        if (!$this->ga4MeasurementId || !$this->ga4ApiSecret) {
            return;
        }

        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$this->ga4MeasurementId}&api_secret={$this->ga4ApiSecret}";

        $data = [
            'client_id' => $this->visitorId,
            'events' => [[
                'name' => $eventName,
                'params' => array_filter($params)
            ]]
        ];

        // Fire and forget (don't block page load)
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 1
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function normalizePhone(string $phone): string {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    private function detectDeviceType(string $userAgent): string {
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            if (preg_match('/ipad|tablet/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }
        return 'desktop';
    }

    private function detectBrowser(string $userAgent): string {
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/MSIE|Trident/i', $userAgent)) return 'IE';
        return 'Other';
    }

    public function getVisitorId(): string {
        return $this->visitorId;
    }

    public function getSessionId(): string {
        return $this->sessionId;
    }

    // Reporting methods
    public function getConversionStats(string $startDate = null, string $endDate = null): array {
        $where = "1=1";
        if ($startDate) $where .= " AND created_at >= '$startDate'";
        if ($endDate) $where .= " AND created_at <= '$endDate'";

        $result = $this->db->query("
            SELECT
                conversion_type,
                COUNT(*) as count,
                SUM(conversion_value) as total_value,
                source,
                ab_variant
            FROM conversions
            WHERE $where
            GROUP BY conversion_type, source, ab_variant
            ORDER BY count DESC
        ");

        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[] = $row;
        }
        return $stats;
    }

    public function getPhoneCallStats(string $startDate = null, string $endDate = null): array {
        $where = "1=1";
        if ($startDate) $where .= " AND created_at >= '$startDate'";
        if ($endDate) $where .= " AND created_at <= '$endDate'";

        $result = $this->db->query("
            SELECT
                COUNT(*) as total_calls,
                SUM(CASE WHEN call_duration > 0 THEN 1 ELSE 0 END) as connected_calls,
                SUM(CASE WHEN lead_created = 1 THEN 1 ELSE 0 END) as leads_created,
                AVG(call_duration) as avg_duration,
                source,
                ab_variant
            FROM phone_calls
            WHERE $where
            GROUP BY source, ab_variant
        ");

        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[] = $row;
        }
        return $stats;
    }

    public function getTrafficStats(string $startDate = null, string $endDate = null): array {
        $where = "1=1";
        if ($startDate) $where .= " AND started_at >= '$startDate'";
        if ($endDate) $where .= " AND started_at <= '$endDate'";

        $result = $this->db->query("
            SELECT
                source,
                medium,
                COUNT(*) as sessions,
                SUM(pageviews) as pageviews,
                SUM(CASE WHEN converted = 1 THEN 1 ELSE 0 END) as conversions,
                ROUND(SUM(CASE WHEN converted = 1 THEN 1.0 ELSE 0 END) / COUNT(*) * 100, 2) as conversion_rate
            FROM sessions
            WHERE $where
            GROUP BY source, medium
            ORDER BY sessions DESC
        ");

        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[] = $row;
        }
        return $stats;
    }
}
