<?php
/**
 * Mobile Quote SMS System
 *
 * Sends mobile-optimized quotes with AI explainer callback
 */

class QuoteSMS {
    private $db;
    private $signalwireProjectId;
    private $signalwireToken;
    private $signalwireSpace;
    private $businessNumber;

    public function __construct() {
        $this->db = new SQLite3(__DIR__ . '/../data/quotes.db');
        $this->initDB();

        // Load SignalWire credentials
        if (defined('SIGNALWIRE_PROJECT_ID')) {
            $this->signalwireProjectId = SIGNALWIRE_PROJECT_ID;
            $this->signalwireToken = SIGNALWIRE_API_TOKEN;
            $this->signalwireSpace = SIGNALWIRE_SPACE;
            $this->businessNumber = SIGNALWIRE_PHONE_NUMBER ?? '+19047066669';
        }
    }

    private function initDB() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS quotes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                quote_id TEXT UNIQUE NOT NULL,
                customer_phone TEXT NOT NULL,
                customer_name TEXT,
                vehicle TEXT,
                services TEXT,
                total_price REAL,
                breakdown TEXT,
                lead_id INTEGER,
                status TEXT DEFAULT 'sent',
                sent_at TEXT,
                viewed_at TEXT,
                approved_at TEXT,
                ai_explained_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Create and send mobile quote via SMS
     *
     * @param array $quoteData - ['customer_phone', 'customer_name', 'vehicle', 'services' => [], 'total', 'lead_id']
     * @return array - ['success', 'quote_id', 'sms_sent', 'quote_url']
     */
    public function sendQuote(array $quoteData): array {
        // Generate unique quote ID
        $quoteId = 'Q' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Format services and calculate total
        $services = $quoteData['services'] ?? [];
        $totalPrice = $quoteData['total'] ?? 0;

        // Store quote in database
        $stmt = $this->db->prepare("
            INSERT INTO quotes (quote_id, customer_phone, customer_name, vehicle, services, total_price, breakdown, lead_id, sent_at)
            VALUES (:quote_id, :phone, :name, :vehicle, :services, :total, :breakdown, :lead_id, :sent_at)
        ");

        $stmt->bindValue(':quote_id', $quoteId, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $this->normalizePhone($quoteData['customer_phone']), SQLITE3_TEXT);
        $stmt->bindValue(':name', $quoteData['customer_name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':vehicle', $quoteData['vehicle'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':services', json_encode($services), SQLITE3_TEXT);
        $stmt->bindValue(':total', $totalPrice, SQLITE3_FLOAT);
        $stmt->bindValue(':breakdown', $quoteData['breakdown'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':lead_id', $quoteData['lead_id'] ?? null, SQLITE3_INTEGER);
        $stmt->bindValue(':sent_at', date('c'), SQLITE3_TEXT);
        $stmt->execute();

        // Generate mobile quote URL
        $quoteUrl = "https://mechanicstaugustine.com/quote/" . $quoteId;

        // Send SMS
        $smsResult = $this->sendQuoteSMS($quoteData['customer_phone'], $quoteId, $quoteUrl, $totalPrice, $quoteData['vehicle']);

        return [
            'success' => true,
            'quote_id' => $quoteId,
            'sms_sent' => $smsResult['success'] ?? false,
            'quote_url' => $quoteUrl,
            'sms_sid' => $smsResult['sid'] ?? null
        ];
    }

    /**
     * Send SMS with mobile quote link and AI explainer button
     */
    private function sendQuoteSMS(string $phone, string $quoteId, string $quoteUrl, float $total, string $vehicle): array {
        if (!$this->signalwireProjectId) {
            return ['success' => false, 'error' => 'SignalWire not configured'];
        }

        $message = "EZ Mobile Mechanic - Your Quote\n\n";
        $message .= "Vehicle: " . $vehicle . "\n";
        $message .= "Total: $" . number_format($total, 2) . "\n\n";
        $message .= "📱 View: " . $quoteUrl . "\n\n";
        $message .= "🔊 Tap to hear AI explain your quote:\n";
        $message .= "https://mechanicstaugustine.com/quote/" . $quoteId . "/explain\n\n";
        $message .= "Reply YES to approve & book";

        return $this->sendSMS($phone, $message);
    }

    /**
     * Request AI callback to explain quote
     */
    public function requestAIExplanation(string $quoteId, string $customerPhone): array {
        $quote = $this->getQuote($quoteId);
        if (!$quote) {
            return ['success' => false, 'error' => 'Quote not found'];
        }

        // Update AI explained timestamp
        $stmt = $this->db->prepare("UPDATE quotes SET ai_explained_at = :now WHERE quote_id = :id");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $quoteId, SQLITE3_TEXT);
        $stmt->execute();

        // Trigger SignalWire AI call
        return $this->initiateAICall($customerPhone, $quote);
    }

    /**
     * Initiate AI phone call to explain quote
     */
    private function initiateAICall(string $phone, array $quote): array {
        if (!$this->signalwireProjectId) {
            return ['success' => false, 'error' => 'SignalWire not configured'];
        }

        // Build TwiML for AI explanation
        $callbackUrl = "https://mechanicstaugustine.com/voice/quote_explainer.php?quote_id=" . $quote['quote_id'];

        $url = "https://{$this->signalwireSpace}/api/laml/2010-04-01/Accounts/{$this->signalwireProjectId}/Calls.json";

        $data = [
            'To' => $this->normalizePhone($phone),
            'From' => $this->businessNumber,
            'Url' => $callbackUrl,
            'Method' => 'POST'
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

        if ($httpCode === 201) {
            $result = json_decode($response, true);
            return [
                'success' => true,
                'call_sid' => $result['sid'] ?? null,
                'message' => 'AI will call you in a moment to explain your quote'
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to initiate call',
            'http_code' => $httpCode
        ];
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

        if ($httpCode === 201) {
            $result = json_decode($response, true);
            return [
                'success' => true,
                'sid' => $result['sid'] ?? null
            ];
        }

        return [
            'success' => false,
            'error' => 'SMS send failed',
            'http_code' => $httpCode
        ];
    }

    /**
     * Get quote by ID
     */
    public function getQuote(string $quoteId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM quotes WHERE quote_id = :id");
        $stmt->bindValue(':id', $quoteId, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result) {
            $result['services'] = json_decode($result['services'], true);
        }

        return $result ?: null;
    }

    /**
     * Mark quote as viewed
     */
    public function markViewed(string $quoteId): bool {
        $stmt = $this->db->prepare("UPDATE quotes SET viewed_at = :now WHERE quote_id = :id AND viewed_at IS NULL");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $quoteId, SQLITE3_TEXT);
        return $stmt->execute() !== false;
    }

    /**
     * Approve quote (customer says YES)
     */
    public function approveQuote(string $quoteId): bool {
        $stmt = $this->db->prepare("UPDATE quotes SET status = 'approved', approved_at = :now WHERE quote_id = :id");
        $stmt->bindValue(':now', date('c'), SQLITE3_TEXT);
        $stmt->bindValue(':id', $quoteId, SQLITE3_TEXT);
        return $stmt->execute() !== false;
    }

    /**
     * Normalize phone number
     */
    private function normalizePhone(string $phone): string {
        $cleaned = preg_replace('/[^\d\+]/', '', $phone);
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+1' . preg_replace('/^1/', '', $cleaned);
        }
        return $cleaned;
    }
}
