<?php
/**
 * A/B Testing Library for EZ Mobile Mechanic
 *
 * Features:
 * - Cookie-based variant assignment (persistent user experience)
 * - SQLite database for tracking (zero configuration)
 * - Automatic statistical significance calculation
 * - Winner detection with configurable confidence levels
 * - API endpoints for dynamic content updates
 *
 * @author Kyle - EZ Mobile Mechanic
 * @version 1.0.0
 */

class ABTesting {
    private $db;
    private $cookiePrefix = 'ez_ab_';
    private $cookieExpiry = 2592000; // 30 days
    private $confidenceThreshold = 0.95; // 95% confidence for winner
    private $minSampleSize = 100; // Minimum samples before declaring winner

    /**
     * Initialize A/B Testing system
     */
    public function __construct() {
        $dbPath = __DIR__ . '/../data/ab_testing.db';
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->db = new SQLite3($dbPath);
        $this->db->busyTimeout(5000);
        $this->initDatabase();
    }

    /**
     * Initialize database schema
     */
    private function initDatabase() {
        // Experiments table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS experiments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'active',
                winner_variant TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Variants table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS variants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                experiment_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                weight REAL DEFAULT 0.5,
                is_control BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (experiment_id) REFERENCES experiments(id),
                UNIQUE(experiment_id, name)
            )
        ");

        // Events table (views, conversions, etc.)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                experiment_id INTEGER NOT NULL,
                variant_id INTEGER NOT NULL,
                event_type TEXT NOT NULL,
                visitor_id TEXT NOT NULL,
                metadata TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (experiment_id) REFERENCES experiments(id),
                FOREIGN KEY (variant_id) REFERENCES variants(id)
            )
        ");

        // Indexes for performance
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_events_experiment ON events(experiment_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_events_variant ON events(variant_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_events_visitor ON events(visitor_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_events_type ON events(event_type)");
    }

    /**
     * Create or get an experiment
     */
    public function createExperiment(string $name, string $description = '', array $variants = ['A', 'B']): int {
        // Check if experiment exists
        $stmt = $this->db->prepare("SELECT id FROM experiments WHERE name = :name");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result) {
            return $result['id'];
        }

        // Create new experiment
        $stmt = $this->db->prepare("INSERT INTO experiments (name, description) VALUES (:name, :description)");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':description', $description, SQLITE3_TEXT);
        $stmt->execute();

        $experimentId = $this->db->lastInsertRowID();

        // Create variants
        $weight = 1.0 / count($variants);
        $isFirst = true;
        foreach ($variants as $variant) {
            $stmt = $this->db->prepare("INSERT INTO variants (experiment_id, name, weight, is_control) VALUES (:exp_id, :name, :weight, :is_control)");
            $stmt->bindValue(':exp_id', $experimentId, SQLITE3_INTEGER);
            $stmt->bindValue(':name', $variant, SQLITE3_TEXT);
            $stmt->bindValue(':weight', $weight, SQLITE3_FLOAT);
            $stmt->bindValue(':is_control', $isFirst ? 1 : 0, SQLITE3_INTEGER);
            $stmt->execute();
            $isFirst = false;
        }

        return $experimentId;
    }

    /**
     * Get the variant for a visitor (assigns if new)
     */
    public function getVariant(string $experimentName): array {
        $visitorId = $this->getVisitorId();
        $cookieName = $this->cookiePrefix . md5($experimentName);

        // Check if already assigned
        if (isset($_COOKIE[$cookieName])) {
            $variantName = $_COOKIE[$cookieName];
            return $this->getVariantDetails($experimentName, $variantName, $visitorId);
        }

        // Get experiment
        $stmt = $this->db->prepare("SELECT id, status, winner_variant FROM experiments WHERE name = :name");
        $stmt->bindValue(':name', $experimentName, SQLITE3_TEXT);
        $experiment = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$experiment) {
            // Experiment doesn't exist, create it
            $this->createExperiment($experimentName);
            return $this->getVariant($experimentName);
        }

        // If experiment has a winner, always serve winner
        if ($experiment['winner_variant']) {
            $variantName = $experiment['winner_variant'];
            $this->setCookie($cookieName, $variantName);
            return $this->getVariantDetails($experimentName, $variantName, $visitorId);
        }

        // Get variants and assign based on weight
        $stmt = $this->db->prepare("SELECT id, name, weight FROM variants WHERE experiment_id = :exp_id ORDER BY id");
        $stmt->bindValue(':exp_id', $experiment['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();

        $variants = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $variants[] = $row;
        }

        // Weighted random assignment
        $random = mt_rand() / mt_getrandmax();
        $cumulative = 0;
        $selectedVariant = $variants[0];

        foreach ($variants as $variant) {
            $cumulative += $variant['weight'];
            if ($random <= $cumulative) {
                $selectedVariant = $variant;
                break;
            }
        }

        // Set cookie and track assignment
        $this->setCookie($cookieName, $selectedVariant['name']);

        return [
            'experiment' => $experimentName,
            'variant' => $selectedVariant['name'],
            'variant_id' => $selectedVariant['id'],
            'visitor_id' => $visitorId,
            'is_new' => true
        ];
    }

    /**
     * Track an event (view, conversion, etc.)
     */
    public function trackEvent(string $experimentName, string $eventType, array $metadata = []): bool {
        $visitorId = $this->getVisitorId();
        $cookieName = $this->cookiePrefix . md5($experimentName);

        if (!isset($_COOKIE[$cookieName])) {
            return false; // Visitor not in experiment
        }

        $variantName = $_COOKIE[$cookieName];

        // Get experiment and variant IDs
        $stmt = $this->db->prepare("
            SELECT e.id as experiment_id, v.id as variant_id
            FROM experiments e
            JOIN variants v ON v.experiment_id = e.id
            WHERE e.name = :exp_name AND v.name = :var_name
        ");
        $stmt->bindValue(':exp_name', $experimentName, SQLITE3_TEXT);
        $stmt->bindValue(':var_name', $variantName, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$result) {
            return false;
        }

        // Insert event
        $stmt = $this->db->prepare("
            INSERT INTO events (experiment_id, variant_id, event_type, visitor_id, metadata)
            VALUES (:exp_id, :var_id, :event_type, :visitor_id, :metadata)
        ");
        $stmt->bindValue(':exp_id', $result['experiment_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':var_id', $result['variant_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':event_type', $eventType, SQLITE3_TEXT);
        $stmt->bindValue(':visitor_id', $visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':metadata', json_encode($metadata), SQLITE3_TEXT);
        $stmt->execute();

        // Check for winner after conversions
        if ($eventType === 'conversion') {
            $this->checkForWinner($experimentName);
        }

        return true;
    }

    /**
     * Get experiment statistics
     */
    public function getStats(string $experimentName): array {
        $stmt = $this->db->prepare("SELECT id FROM experiments WHERE name = :name");
        $stmt->bindValue(':name', $experimentName, SQLITE3_TEXT);
        $experiment = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$experiment) {
            return [];
        }

        // Get variants with stats
        $stmt = $this->db->prepare("
            SELECT
                v.id,
                v.name,
                v.is_control,
                COUNT(DISTINCT CASE WHEN e.event_type = 'view' THEN e.visitor_id END) as views,
                COUNT(DISTINCT CASE WHEN e.event_type = 'conversion' THEN e.visitor_id END) as conversions
            FROM variants v
            LEFT JOIN events e ON e.variant_id = v.id
            WHERE v.experiment_id = :exp_id
            GROUP BY v.id
            ORDER BY v.id
        ");
        $stmt->bindValue(':exp_id', $experiment['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();

        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $views = (int)$row['views'];
            $conversions = (int)$row['conversions'];
            $conversionRate = $views > 0 ? ($conversions / $views) * 100 : 0;

            $stats[] = [
                'variant' => $row['name'],
                'is_control' => (bool)$row['is_control'],
                'views' => $views,
                'conversions' => $conversions,
                'conversion_rate' => round($conversionRate, 2),
                'confidence' => 0 // Will be calculated
            ];
        }

        // Calculate statistical significance if we have enough data
        if (count($stats) >= 2) {
            $control = null;
            foreach ($stats as $stat) {
                if ($stat['is_control']) {
                    $control = $stat;
                    break;
                }
            }

            if ($control && $control['views'] >= $this->minSampleSize) {
                foreach ($stats as &$stat) {
                    if (!$stat['is_control'] && $stat['views'] >= $this->minSampleSize) {
                        $stat['confidence'] = $this->calculateConfidence(
                            $control['views'], $control['conversions'],
                            $stat['views'], $stat['conversions']
                        );
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Check if experiment has a statistically significant winner
     */
    public function checkForWinner(string $experimentName): ?string {
        $stats = $this->getStats($experimentName);

        if (count($stats) < 2) {
            return null;
        }

        $bestVariant = null;
        $bestRate = 0;
        $hasEnoughData = true;

        foreach ($stats as $stat) {
            if ($stat['views'] < $this->minSampleSize) {
                $hasEnoughData = false;
                break;
            }

            if ($stat['conversion_rate'] > $bestRate) {
                $bestRate = $stat['conversion_rate'];
                $bestVariant = $stat;
            }
        }

        if (!$hasEnoughData) {
            return null;
        }

        // Check if best variant has significant confidence over control
        $control = null;
        foreach ($stats as $stat) {
            if ($stat['is_control']) {
                $control = $stat;
                break;
            }
        }

        if ($bestVariant && $bestVariant['confidence'] >= $this->confidenceThreshold * 100) {
            // Declare winner
            $stmt = $this->db->prepare("UPDATE experiments SET winner_variant = :winner, status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE name = :name");
            $stmt->bindValue(':winner', $bestVariant['variant'], SQLITE3_TEXT);
            $stmt->bindValue(':name', $experimentName, SQLITE3_TEXT);
            $stmt->execute();

            return $bestVariant['variant'];
        }

        return null;
    }

    /**
     * Calculate statistical confidence using z-test
     */
    private function calculateConfidence(int $n1, int $c1, int $n2, int $c2): float {
        if ($n1 == 0 || $n2 == 0) {
            return 0;
        }

        $p1 = $c1 / $n1;
        $p2 = $c2 / $n2;
        $p = ($c1 + $c2) / ($n1 + $n2);

        if ($p == 0 || $p == 1) {
            return 0;
        }

        $se = sqrt($p * (1 - $p) * (1/$n1 + 1/$n2));

        if ($se == 0) {
            return 0;
        }

        $z = abs($p2 - $p1) / $se;

        // Convert z-score to confidence percentage (approximate)
        $confidence = (1 - 2 * (1 - $this->normalCDF($z))) * 100;

        return max(0, min(100, round($confidence, 1)));
    }

    /**
     * Approximate normal CDF
     */
    private function normalCDF(float $x): float {
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x) / sqrt(2);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return 0.5 * (1.0 + $sign * $y);
    }

    /**
     * Get or create visitor ID
     */
    private function getVisitorId(): string {
        $cookieName = $this->cookiePrefix . 'visitor';

        if (isset($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }

        $visitorId = bin2hex(random_bytes(16));
        $this->setCookie($cookieName, $visitorId);

        return $visitorId;
    }

    /**
     * Set a cookie
     */
    private function setCookie(string $name, string $value): void {
        if (!headers_sent()) {
            setcookie($name, $value, [
                'expires' => time() + $this->cookieExpiry,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        $_COOKIE[$name] = $value;
    }

    /**
     * Get variant details helper
     */
    private function getVariantDetails(string $experimentName, string $variantName, string $visitorId): array {
        $stmt = $this->db->prepare("
            SELECT v.id
            FROM variants v
            JOIN experiments e ON e.id = v.experiment_id
            WHERE e.name = :exp_name AND v.name = :var_name
        ");
        $stmt->bindValue(':exp_name', $experimentName, SQLITE3_TEXT);
        $stmt->bindValue(':var_name', $variantName, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        return [
            'experiment' => $experimentName,
            'variant' => $variantName,
            'variant_id' => $result ? $result['id'] : null,
            'visitor_id' => $visitorId,
            'is_new' => false
        ];
    }

    /**
     * Get all experiments
     */
    public function getAllExperiments(): array {
        $result = $this->db->query("
            SELECT e.*,
                   COUNT(DISTINCT ev.visitor_id) as total_visitors,
                   COUNT(DISTINCT CASE WHEN ev.event_type = 'conversion' THEN ev.visitor_id END) as total_conversions
            FROM experiments e
            LEFT JOIN events ev ON ev.experiment_id = e.id
            GROUP BY e.id
            ORDER BY e.created_at DESC
        ");

        $experiments = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $experiments[] = $row;
        }

        return $experiments;
    }

    /**
     * Apply winning variant changes automatically
     * This is called when a winner is declared to update the live pages
     */
    public function applyWinnerChanges(string $experimentName): bool {
        $stmt = $this->db->prepare("SELECT winner_variant FROM experiments WHERE name = :name");
        $stmt->bindValue(':name', $experimentName, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$result || !$result['winner_variant']) {
            return false;
        }

        // Log the winner application
        $logPath = __DIR__ . '/../data/ab_winners.log';
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'experiment' => $experimentName,
            'winner' => $result['winner_variant'],
            'action' => 'winner_applied'
        ];
        file_put_contents($logPath, json_encode($logEntry) . "\n", FILE_APPEND);

        return true;
    }

    /**
     * Reset an experiment (for new testing cycle)
     */
    public function resetExperiment(string $experimentName): bool {
        $stmt = $this->db->prepare("SELECT id FROM experiments WHERE name = :name");
        $stmt->bindValue(':name', $experimentName, SQLITE3_TEXT);
        $experiment = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$experiment) {
            return false;
        }

        // Clear events
        $stmt = $this->db->prepare("DELETE FROM events WHERE experiment_id = :exp_id");
        $stmt->bindValue(':exp_id', $experiment['id'], SQLITE3_INTEGER);
        $stmt->execute();

        // Reset experiment status
        $stmt = $this->db->prepare("UPDATE experiments SET status = 'active', winner_variant = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :exp_id");
        $stmt->bindValue(':exp_id', $experiment['id'], SQLITE3_INTEGER);
        $stmt->execute();

        return true;
    }
}
