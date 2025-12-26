<?php
/**
 * CustomerFlow - Complete 1-2-3-4-5 Customer Journey
 *
 * 1. Call comes in → auto-estimate generated
 * 2. Quote SMS sent → customer views mobile quote → AI explains via callback
 * 3. Customer approves → booking confirmed
 * 4. Service complete → completion SMS sent
 * 5. 24hr follow-up → Google review request
 */

namespace CustomerFlow;

class Flow {
    private \SQLite3 $db;
    private array $config;

    public function __construct() {
        $this->loadConfig();
        $this->initDB();
    }

    private function loadConfig(): void {
        // Load from env file once
        $envPath = dirname(__DIR__, 2) . '/api/.env.local.php';
        if (file_exists($envPath)) {
            require_once $envPath;
        }

        $this->config = [
            'httpsms' => [
                'api_key' => 'uk_8AWve9dXJyI3ClEWIa2aQRBIDUAtBID79dKGwN4t30U6Fs-v9gLs9kJlA7hJu3De',
                'from' => '+19046634789',
            ],
            'signalwire' => [
                'project_id' => defined('SIGNALWIRE_PROJECT_ID') ? SIGNALWIRE_PROJECT_ID : '',
                'token' => defined('SIGNALWIRE_API_TOKEN') ? SIGNALWIRE_API_TOKEN : '',
                'space' => defined('SIGNALWIRE_SPACE') ? SIGNALWIRE_SPACE : '',
                'number' => defined('SIGNALWIRE_PHONE_NUMBER') ? SIGNALWIRE_PHONE_NUMBER : '+19047066669',
            ],
            'business' => [
                'name' => 'EZ Mobile Mechanic',
                'phone' => '(904) 217-5152',
                'phone_raw' => '+19042175152',
                'review_url' => 'https://g.page/r/CQepHCWnvxq4EBM/review',
                'base_url' => 'https://mechanicstaugustine.com',
            ],
        ];
    }

    private function initDB(): void {
        $dbPath = dirname(__DIR__, 2) . '/data/customer_flow.db';
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->db = new \SQLite3($dbPath);
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_id TEXT UNIQUE NOT NULL,
                phone TEXT NOT NULL,
                name TEXT,
                vehicle TEXT,
                services TEXT,
                total REAL,
                lead_id INTEGER,

                -- Step tracking
                step INTEGER DEFAULT 1,
                quote_sent_at TEXT,
                quote_viewed_at TEXT,
                ai_explained_at TEXT,
                approved_at TEXT,
                completed_at TEXT,
                followup_sent_at TEXT,
                review_requested_at TEXT,

                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX IF NOT EXISTS idx_phone ON jobs(phone);
            CREATE INDEX IF NOT EXISTS idx_step ON jobs(step);
        ");
    }

    // ========================================
    // STEP 2: Send Quote
    // ========================================

    public function sendQuote(array $data): array {
        $jobId = $this->generateJobId();
        $phone = $this->normalizePhone($data['phone']);

        // Store job
        $stmt = $this->db->prepare("
            INSERT INTO jobs (job_id, phone, name, vehicle, services, total, lead_id, quote_sent_at)
            VALUES (:job_id, :phone, :name, :vehicle, :services, :total, :lead_id, :now)
        ");
        $stmt->bindValue(':job_id', $jobId, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':name', $data['name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':vehicle', $data['vehicle'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':services', json_encode($data['services'] ?? []), SQLITE3_TEXT);
        $stmt->bindValue(':total', (float)($data['total'] ?? 0), SQLITE3_FLOAT);
        $stmt->bindValue(':lead_id', $data['lead_id'] ?? null, SQLITE3_INTEGER);
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->execute();

        // Send SMS
        $url = $this->config['business']['base_url'] . '/q/' . $jobId;
        $sms = $this->sms($phone, $this->quoteMessage($data, $url));

        return [
            'success' => true,
            'job_id' => $jobId,
            'url' => $url,
            'sms_sent' => $sms['success'],
        ];
    }

    private function quoteMessage(array $data, string $url): string {
        $vehicle = $data['vehicle'] ?? 'your vehicle';
        $total = number_format((float)($data['total'] ?? 0), 2);

        return "EZ Mobile Mechanic\n\n"
             . "{$vehicle}\n"
             . "Quote: \${$total}\n\n"
             . "View & Approve:\n{$url}\n\n"
             . "Reply YES to book";
    }

    // ========================================
    // STEP 2b: Quote Viewed / AI Explain
    // ========================================

    public function getJob(string $jobId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM jobs WHERE job_id = :id");
        $stmt->bindValue(':id', $jobId, SQLITE3_TEXT);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($row) {
            $row['services'] = json_decode($row['services'], true) ?: [];
        }
        return $row ?: null;
    }

    public function markViewed(string $jobId): void {
        $stmt = $this->db->prepare("UPDATE jobs SET quote_viewed_at = :now, updated_at = :now WHERE job_id = :id AND quote_viewed_at IS NULL");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $jobId, SQLITE3_TEXT);
        $stmt->execute();
    }

    public function requestAICall(string $jobId): array {
        $job = $this->getJob($jobId);
        if (!$job) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        // Mark AI explained
        $stmt = $this->db->prepare("UPDATE jobs SET ai_explained_at = :now, updated_at = :now WHERE job_id = :id");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $jobId, SQLITE3_TEXT);
        $stmt->execute();

        // Initiate outbound call
        return $this->call($job['phone'], $this->config['business']['base_url'] . '/voice/explain.php?job=' . $jobId);
    }

    // ========================================
    // STEP 3: Approve
    // ========================================

    public function approve(string $jobId): array {
        $job = $this->getJob($jobId);
        if (!$job) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        if ($job['approved_at']) {
            return ['success' => true, 'already_approved' => true];
        }

        $stmt = $this->db->prepare("UPDATE jobs SET step = 3, approved_at = :now, updated_at = :now WHERE job_id = :id");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $jobId, SQLITE3_TEXT);
        $stmt->execute();

        // Send confirmation
        $this->sms($job['phone'], "Confirmed! We'll contact you shortly to schedule.\n\n- Kyle, EZ Mobile Mechanic\n{$this->config['business']['phone']}");

        return ['success' => true, 'job_id' => $jobId];
    }

    public function approveByPhone(string $phone): array {
        $phone = $this->normalizePhone($phone);
        $stmt = $this->db->prepare("SELECT job_id FROM jobs WHERE phone = :phone AND approved_at IS NULL ORDER BY created_at DESC LIMIT 1");
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$row) {
            return ['success' => false, 'error' => 'No pending quote found'];
        }

        return $this->approve($row['job_id']);
    }

    // ========================================
    // STEP 4: Service Complete
    // ========================================

    public function complete(string $jobId, ?string $paymentLink = null): array {
        $job = $this->getJob($jobId);
        if (!$job) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        $stmt = $this->db->prepare("UPDATE jobs SET step = 4, completed_at = :now, updated_at = :now WHERE job_id = :id");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $jobId, SQLITE3_TEXT);
        $stmt->execute();

        $name = $job['name'] ?: 'there';
        $vehicle = $job['vehicle'] ?: 'Your vehicle';

        $msg = "Hi {$name}!\n\n{$vehicle} is ready.";
        if ($paymentLink) {
            $msg .= "\n\nPay: {$paymentLink}";
        }
        $msg .= "\n\nQuestions? {$this->config['business']['phone']}\n\n- EZ Mobile Mechanic";

        $this->sms($job['phone'], $msg);

        return ['success' => true, 'job_id' => $jobId];
    }

    // ========================================
    // STEP 5: Follow-up + Review
    // ========================================

    public function sendFollowUp(string $jobId): array {
        $job = $this->getJob($jobId);
        if (!$job) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        if ($job['followup_sent_at']) {
            return ['success' => false, 'error' => 'Follow-up already sent'];
        }

        $stmt = $this->db->prepare("UPDATE jobs SET step = 5, followup_sent_at = :now, review_requested_at = :now, updated_at = :now WHERE job_id = :id");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $jobId, SQLITE3_TEXT);
        $stmt->execute();

        $name = $job['name'] ?: 'there';
        $vehicle = $job['vehicle'] ?: 'your vehicle';

        $msg = "Hey {$name}, it's Kyle.\n\n"
             . "How's {$vehicle} running?\n\n"
             . "If you're happy, a quick review helps a lot:\n"
             . $this->config['business']['review_url'] . "\n\n"
             . "Thanks!";

        $this->sms($job['phone'], $msg);

        return ['success' => true, 'job_id' => $jobId];
    }

    public function sendDueFollowUps(): array {
        // Jobs completed 24+ hours ago without follow-up
        $cutoff = date('c', strtotime('-24 hours'));

        $result = $this->db->query("
            SELECT job_id FROM jobs
            WHERE completed_at IS NOT NULL
            AND completed_at <= '{$cutoff}'
            AND followup_sent_at IS NULL
        ");

        $sent = 0;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $r = $this->sendFollowUp($row['job_id']);
            if ($r['success']) $sent++;
        }

        return ['sent' => $sent];
    }

    // ========================================
    // httpSMS API (via Android phone gateway)
    // ========================================

    private function sms(string $to, string $body): array {
        $cfg = $this->config['httpsms'];
        if (!$cfg['api_key']) {
            return ['success' => false, 'error' => 'httpSMS not configured'];
        }

        // Normalize phone number to E.164
        $to = preg_replace('/[^\d]/', '', $to);
        if (strlen($to) === 10) {
            $to = '+1' . $to;
        } elseif (strlen($to) === 11 && $to[0] === '1') {
            $to = '+' . $to;
        } elseif (strpos($to, '+') !== 0) {
            $to = '+' . $to;
        }

        $url = "https://api.httpsms.com/v1/messages/send";

        $payload = json_encode([
            'from' => $cfg['from'],
            'to' => $to,
            'content' => $body,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $cfg['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        return [
            'success' => $code >= 200 && $code < 300,
            'code' => $code,
            'error' => $error ?: ($result['message'] ?? null),
            'response' => $result,
        ];
    }

    private function call(string $to, string $webhookUrl): array {
        $cfg = $this->config['signalwire'];
        if (!$cfg['project_id']) {
            return ['success' => false, 'error' => 'SignalWire not configured'];
        }

        $url = "https://{$cfg['space']}/api/laml/2010-04-01/Accounts/{$cfg['project_id']}/Calls.json";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD => $cfg['project_id'] . ':' . $cfg['token'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'To' => $to,
                'From' => $cfg['number'],
                'Url' => $webhookUrl,
                'Method' => 'POST',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['success' => $code === 201, 'code' => $code];
    }

    // ========================================
    // Utilities
    // ========================================

    private function generateJobId(): string {
        return strtoupper(substr(base_convert(bin2hex(random_bytes(5)), 16, 36), 0, 8));
    }

    private function normalizePhone(string $phone): string {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }
        return '+' . $digits;
    }

    public function getStats(): array {
        $row = $this->db->querySingle("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN approved_at IS NOT NULL THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN followup_sent_at IS NOT NULL THEN 1 ELSE 0 END) as followed_up
            FROM jobs
        ", true);
        return $row ?: [];
    }

    public function getRecent(int $limit = 20): array {
        $result = $this->db->query("SELECT * FROM jobs ORDER BY created_at DESC LIMIT {$limit}");
        $jobs = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['services'] = json_decode($row['services'], true) ?: [];
            $jobs[] = $row;
        }
        return $jobs;
    }
}
