<?php
/**
 * Post-Service SMS Flow
 *
 * Step 4: Service completion notification
 * Step 5: Follow-up + Google review request
 */

class PostServiceFlow {
    private $db;
    private $signalwireProjectId;
    private $signalwireToken;
    private $signalwireSpace;
    private $businessNumber;
    private $reviewLink = 'https://g.page/r/CQepHCWnvxq4EBM/review';

    public function __construct() {
        $this->db = new SQLite3(__DIR__ . '/../data/service_flow.db');
        $this->initDB();

        if (defined('SIGNALWIRE_PROJECT_ID')) {
            $this->signalwireProjectId = SIGNALWIRE_PROJECT_ID;
            $this->signalwireToken = SIGNALWIRE_API_TOKEN;
            $this->signalwireSpace = SIGNALWIRE_SPACE;
            $this->businessNumber = SIGNALWIRE_PHONE_NUMBER ?? '+19047066669';
        }
    }

    private function initDB() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS service_completions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lead_id INTEGER,
                customer_phone TEXT NOT NULL,
                customer_name TEXT,
                vehicle TEXT,
                services_completed TEXT,
                completion_sms_sent_at TEXT,
                followup_sms_sent_at TEXT,
                review_requested_at TEXT,
                review_submitted BOOLEAN DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Step 4: Send service completion SMS
     *
     * @param array $data - ['customer_phone', 'customer_name', 'vehicle', 'services', 'lead_id', 'payment_link']
     * @return array
     */
    public function sendCompletionSMS(array $data): array {
        $phone = $data['customer_phone'];
        $name = $data['customer_name'] ?? 'Customer';
        $vehicle = $data['vehicle'] ?? 'your vehicle';

        $message = "Hi " . $name . "! 👋\n\n";
        $message .= "Great news - " . $vehicle . " is ready!\n\n";

        if (!empty($data['payment_link'])) {
            $message .= "💳 Tap to pay: " . $data['payment_link'] . "\n\n";
        }

        $message .= "📞 Questions? Call (904) 217-5152\n\n";
        $message .= "Thanks for choosing EZ Mobile Mechanic!";

        $smsResult = $this->sendSMS($phone, $message);

        // Store in database
        $stmt = $this->db->prepare("
            INSERT INTO service_completions
            (lead_id, customer_phone, customer_name, vehicle, services_completed, completion_sms_sent_at)
            VALUES (:lead_id, :phone, :name, :vehicle, :services, :sent_at)
        ");
        $stmt->bindValue(':lead_id', $data['lead_id'] ?? null, SQLITE3_INTEGER);
        $stmt->bindValue(':phone', $this->normalizePhone($phone), SQLITE3_TEXT);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':vehicle', $vehicle, SQLITE3_TEXT);
        $stmt->bindValue(':services', json_encode($data['services'] ?? []), SQLITE3_TEXT);
        $stmt->bindValue(':sent_at', date('c'), SQLITE3_TEXT);
        $stmt->execute();

        $serviceId = $this->db->lastInsertRowID();

        // Schedule follow-up SMS for 24 hours later
        // (In production, use cron job or scheduled task)

        return [
            'success' => $smsResult['success'],
            'service_id' => $serviceId,
            'message' => 'Completion SMS sent'
        ];
    }

    /**
     * Step 5: Send follow-up + review request
     * Call this 24 hours after service completion
     *
     * @param int $serviceId - Service completion ID
     * @return array
     */
    public function sendFollowUpWithReview(int $serviceId): array {
        $stmt = $this->db->prepare("SELECT * FROM service_completions WHERE id = :id");
        $stmt->bindValue(':id', $serviceId, SQLITE3_INTEGER);
        $service = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$service) {
            return ['success' => false, 'error' => 'Service not found'];
        }

        if ($service['followup_sms_sent_at']) {
            return ['success' => false, 'error' => 'Follow-up already sent'];
        }

        $name = $service['customer_name'] ?? 'there';
        $vehicle = $service['vehicle'] ?? 'your vehicle';

        $message = "Hey " . $name . "! It's Kyle from EZ Mobile Mechanic.\n\n";
        $message .= "Just checking in - how's " . $vehicle . " running after the service?\n\n";
        $message .= "Everything good? 👍\n\n";
        $message .= "If you're happy with the work, I'd really appreciate a quick Google review:\n";
        $message .= $this->reviewLink . "\n\n";
        $message .= "Takes 30 seconds and helps other folks find honest mechanical work.\n\n";
        $message .= "Thanks!\n- Kyle";

        $smsResult = $this->sendSMS($service['customer_phone'], $message);

        // Update database
        $stmt = $this->db->prepare("
            UPDATE service_completions
            SET followup_sms_sent_at = :sent_at, review_requested_at = :review_at
            WHERE id = :id
        ");
        $stmt->bindValue(':sent_at', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':review_at', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $serviceId, SQLITE3_INTEGER);
        $stmt->execute();

        return [
            'success' => $smsResult['success'],
            'message' => 'Follow-up and review request sent'
        ];
    }

    /**
     * Send follow-up for all services completed 24 hours ago
     * Run this via cron: 0 10 * * * php /path/to/send_scheduled_followups.php
     */
    public function sendScheduledFollowUps(): array {
        $twentyFourHoursAgo = date('c', strtotime('-24 hours'));

        $stmt = $this->db->prepare("
            SELECT id FROM service_completions
            WHERE completion_sms_sent_at <= :cutoff
            AND followup_sms_sent_at IS NULL
        ");
        $stmt->bindValue(':cutoff', $twentyFourHoursAgo, SQLITE3_TEXT);
        $result = $stmt->execute();

        $sent = 0;
        $failed = 0;

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $followupResult = $this->sendFollowUpWithReview($row['id']);
            if ($followupResult['success']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed
        ];
    }

    /**
     * Mark review as submitted (webhook from Google or manual)
     */
    public function markReviewSubmitted(int $serviceId): bool {
        $stmt = $this->db->prepare("UPDATE service_completions SET review_submitted = 1 WHERE id = :id");
        $stmt->bindValue(':id', $serviceId, SQLITE3_INTEGER);
        return $stmt->execute() !== false;
    }

    /**
     * Send SMS via SignalWire
     */
    private function sendSMS(string $to, string $message): array {
        if (!$this->signalwireProjectId) {
            return ['success' => false, 'error' => 'SignalWire not configured'];
        }

        $url = "https://{$this->signalwireSpace}/api/laml/2010-04-01/Accounts/{$this->signalwireProjectId}/Messages.json";

        $data = [
            'To' => $this->normalizePhone($to),
            'From' => $this->businessNumber,
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD => $this->signalwireProjectId . ':' . $this->signalwireToken,
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
            'http_code' => $httpCode
        ];
    }

    private function normalizePhone(string $phone): string {
        $cleaned = preg_replace('/[^\d\+]/', '', $phone);
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+1' . preg_replace('/^1/', '', $cleaned);
        }
        return $cleaned;
    }
}
