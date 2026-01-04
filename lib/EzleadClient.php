<?php
/**
 * EzLead Client - API client for ezlead4u.com lead distribution
 *
 * Sends leads from master-template to ezlead4u.com HQ for distribution
 * to contractor buyers via round robin, weighted, or highest bidder.
 *
 * Usage:
 *   $client = new EzleadClient();
 *   $result = $client->directPost('sod', 'FL', 'John', '555-1234', ...);
 */

class EzleadClient {
    private $baseUrl;
    private $apiKey;
    private $enabled;
    private $timeout;

    /**
     * Initialize client from config constants
     */
    public function __construct() {
        // Load config if not already loaded
        $bootstrapPath = __DIR__ . '/../config/bootstrap.php';
        if (file_exists($bootstrapPath) && !defined('EZLEAD_BASE_URL')) {
            require_once $bootstrapPath;
        }

        $this->baseUrl = defined('EZLEAD_BASE_URL') ? rtrim(EZLEAD_BASE_URL, '/') : null;
        $this->apiKey = defined('EZLEAD_API_KEY') ? EZLEAD_API_KEY : null;
        $this->enabled = defined('EZLEAD_ENABLED') ? EZLEAD_ENABLED : false;
        $this->timeout = defined('EZLEAD_TIMEOUT') ? EZLEAD_TIMEOUT : 10;
    }

    /**
     * Check if client is configured and enabled
     */
    public function isEnabled(): bool {
        return $this->enabled && $this->baseUrl && $this->apiKey;
    }

    /**
     * Ping - Check buyer availability before sending full lead
     *
     * @param string $vertical  Lead vertical (sod, roofing, hvac, mechanic, etc.)
     * @param string $state     2-letter state code (FL, CA, etc.)
     * @param string|null $zipCode  Optional zip code for better targeting
     * @param string|null $city     Optional city name
     * @return array {success, accepted, price, ping_id, buyers_available, error}
     */
    public function ping(string $vertical, string $state, ?string $zipCode = null, ?string $city = null): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'EzLead client not configured'];
        }

        $payload = [
            'vertical' => $vertical,
            'state' => strtoupper($state),
        ];

        if ($zipCode) $payload['zip_code'] = $zipCode;
        if ($city) $payload['city'] = $city;

        return $this->request('POST', '/api/v1/ping', $payload);
    }

    /**
     * Post - Submit full lead after successful ping
     *
     * @param string $pingId      Ping ID from ping() response
     * @param string $firstName   Customer first name (required)
     * @param string $phone       Customer phone (required)
     * @param string|null $lastName
     * @param string|null $email
     * @param string|null $address
     * @param string|null $description
     * @return array {success, lead_id, status, price, duplicate, error}
     */
    public function post(
        string $pingId,
        string $firstName,
        string $phone,
        ?string $lastName = null,
        ?string $email = null,
        ?string $address = null,
        ?string $description = null
    ): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'EzLead client not configured'];
        }

        $payload = [
            'ping_id' => $pingId,
            'first_name' => $firstName,
            'phone' => $this->normalizePhone($phone),
        ];

        if ($lastName) $payload['last_name'] = $lastName;
        if ($email) $payload['email'] = $email;
        if ($address) $payload['address'] = $address;
        if ($description) $payload['description'] = $description;

        return $this->request('POST', '/api/v1/ping/post', $payload);
    }

    /**
     * Direct Post - Combined ping + post in single request (simpler integration)
     *
     * @param string $vertical    Lead vertical (required)
     * @param string $state       2-letter state code (required)
     * @param string $firstName   Customer first name (required)
     * @param string $phone       Customer phone (required)
     * @param string|null $lastName
     * @param string|null $email
     * @param string|null $city
     * @param string|null $zipCode
     * @param string|null $address
     * @param string|null $description
     * @return array {success, lead_id, status, price, duplicate, error}
     */
    public function directPost(
        string $vertical,
        string $state,
        string $firstName,
        string $phone,
        ?string $lastName = null,
        ?string $email = null,
        ?string $city = null,
        ?string $zipCode = null,
        ?string $address = null,
        ?string $description = null
    ): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'EzLead client not configured'];
        }

        $payload = [
            'vertical' => $vertical,
            'state' => strtoupper($state),
            'first_name' => $firstName,
            'phone' => $this->normalizePhone($phone),
        ];

        if ($lastName) $payload['last_name'] = $lastName;
        if ($email) $payload['email'] = $email;
        if ($city) $payload['city'] = $city;
        if ($zipCode) $payload['zip_code'] = $zipCode;
        if ($address) $payload['address'] = $address;
        if ($description) $payload['description'] = $description;

        return $this->request('POST', '/api/v1/ping/direct', $payload);
    }

    /**
     * Route Call - Get contractor phone number for call forwarding
     *
     * @param string $callerId     Caller's phone number
     * @param string $calledNumber Number that was called (determines vertical)
     * @param string $vertical     Lead vertical
     * @param string $state        2-letter state code
     * @param string|null $zipCode
     * @return array {success, phone, buyer_id, buyer_name, error}
     */
    public function routeCall(
        string $callerId,
        string $calledNumber,
        string $vertical,
        string $state,
        ?string $zipCode = null
    ): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'EzLead client not configured'];
        }

        $payload = [
            'caller_id' => $this->normalizePhone($callerId),
            'called_number' => $this->normalizePhone($calledNumber),
            'vertical' => $vertical,
            'state' => strtoupper($state),
        ];

        if ($zipCode) $payload['zip_code'] = $zipCode;

        return $this->request('POST', '/api/v1/phone/route', $payload);
    }

    /**
     * Log Call - Record call details after completion
     *
     * @param string $callerId
     * @param int $buyerId
     * @param int|null $duration  Call duration in seconds
     * @param string|null $recordingUrl
     * @return array {success, call_id, error}
     */
    public function logCall(
        string $callerId,
        int $buyerId,
        ?int $duration = null,
        ?string $recordingUrl = null
    ): array {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'EzLead client not configured'];
        }

        $payload = [
            'caller_id' => $this->normalizePhone($callerId),
            'buyer_id' => $buyerId,
        ];

        if ($duration !== null) $payload['duration'] = $duration;
        if ($recordingUrl) $payload['recording_url'] = $recordingUrl;

        return $this->request('POST', '/api/v1/phone/log', $payload);
    }

    /**
     * Make HTTP request to ezlead4u.com API
     */
    private function request(string $method, string $endpoint, array $payload): array {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log('error', "Curl error: $error", ['url' => $url]);
            return ['success' => false, 'error' => "Connection failed: $error"];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $data['detail'] ?? $data['error'] ?? "HTTP $httpCode";
            $this->log('error', "API error: $errorMsg", ['url' => $url, 'code' => $httpCode]);
            return ['success' => false, 'error' => $errorMsg, 'http_code' => $httpCode];
        }

        if (!$data) {
            $this->log('error', "Invalid JSON response", ['url' => $url, 'response' => $response]);
            return ['success' => false, 'error' => 'Invalid response from server'];
        }

        $this->log('info', "API success", ['url' => $url, 'response' => $data]);
        return $data;
    }

    /**
     * Normalize phone to +1XXXXXXXXXX format
     */
    private function normalizePhone(string $phone): string {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        } elseif (strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }

        return $phone; // Return as-is if format unknown
    }

    /**
     * Log to file (if logging enabled)
     */
    private function log(string $level, string $message, array $context = []): void {
        $logFile = defined('EZLEAD_LOG_FILE') ? EZLEAD_LOG_FILE : null;
        if (!$logFile) return;

        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
