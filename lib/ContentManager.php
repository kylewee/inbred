<?php
/**
 * Content Manager - Blog & Dynamic Content System
 *
 * Features:
 * - Blog article management (create, update, publish)
 * - Dynamic content snippets for freshness signals
 * - Auto-generated SEO metadata
 * - Content versioning for A/B testing
 * - API endpoints for headless CMS functionality
 *
 * @author Kyle - EZ Mobile Mechanic
 * @version 1.0.0
 */

class ContentManager {
    private $db;

    public function __construct() {
        $dbPath = __DIR__ . '/../data/content.db';
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->db = new SQLite3($dbPath);
        $this->db->busyTimeout(5000);
        $this->initDatabase();
    }

    private function initDatabase() {
        // Articles table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                excerpt TEXT,
                content TEXT NOT NULL,
                category TEXT,
                tags TEXT,
                meta_title TEXT,
                meta_description TEXT,
                featured_image TEXT,
                author TEXT DEFAULT 'Kyle',
                status TEXT DEFAULT 'draft',
                views INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                published_at DATETIME
            )
        ");

        // Dynamic content snippets (for freshness)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS snippets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT UNIQUE NOT NULL,
                content TEXT NOT NULL,
                type TEXT DEFAULT 'text',
                auto_update BOOLEAN DEFAULT 0,
                update_frequency TEXT DEFAULT 'daily',
                last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Content stats for tracking
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS content_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                article_id INTEGER,
                event_type TEXT NOT NULL,
                visitor_id TEXT,
                referrer TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (article_id) REFERENCES articles(id)
            )
        ");

        // Indexes
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_articles_slug ON articles(slug)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_articles_status ON articles(status)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_articles_category ON articles(category)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_snippets_key ON snippets(key)");

        // Seed initial content if empty
        $this->seedInitialContent();
    }

    private function seedInitialContent() {
        // Check if we have articles
        $count = $this->db->querySingle("SELECT COUNT(*) FROM articles");
        if ($count > 0) return;

        // Seed blog articles optimized for mobile mechanic SEO
        $articles = [
            [
                'slug' => 'check-engine-light-meaning',
                'title' => 'What Does Your Check Engine Light Really Mean?',
                'excerpt' => 'That dreaded check engine light just came on. Before you panic, here\'s what it could mean and what to do next.',
                'content' => $this->getCheckEngineLightContent(),
                'category' => 'diagnostics',
                'tags' => 'check engine light,diagnostics,OBD2,trouble codes',
                'meta_title' => 'Check Engine Light Meaning | What To Do | St. Augustine Mobile Mechanic',
                'meta_description' => 'Check engine light on? Learn what it means, common causes, and when to get professional diagnostics. EZ Mobile Mechanic serves St. Augustine FL.',
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'slug' => 'mobile-mechanic-vs-shop',
                'title' => 'Mobile Mechanic vs Auto Shop: Which Is Right for You?',
                'excerpt' => 'Comparing the convenience of mobile mechanic services vs traditional auto repair shops. See which option saves you time and money.',
                'content' => $this->getMobileMechanicVsShopContent(),
                'category' => 'tips',
                'tags' => 'mobile mechanic,auto shop,convenience,cost comparison',
                'meta_title' => 'Mobile Mechanic vs Auto Shop | Pros & Cons | St. Augustine FL',
                'meta_description' => 'Should you use a mobile mechanic or go to a shop? Compare convenience, cost, and quality. EZ Mobile Mechanic St. Augustine.',
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'slug' => 'hybrid-car-maintenance-guide',
                'title' => 'Hybrid Car Maintenance: What Every Owner Should Know',
                'excerpt' => 'Hybrid vehicles have unique maintenance needs. Learn what\'s different and how to keep your hybrid running efficiently.',
                'content' => $this->getHybridMaintenanceContent(),
                'category' => 'maintenance',
                'tags' => 'hybrid,maintenance,electric vehicle,battery',
                'meta_title' => 'Hybrid Car Maintenance Guide | St. Augustine Hybrid Mechanic',
                'meta_description' => 'Hybrid vehicle maintenance tips from a certified technician. Battery care, brake systems, and more. Mobile hybrid service in St. Augustine.',
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'slug' => 'abs-warning-light-causes',
                'title' => 'ABS Warning Light: Common Causes and What to Do',
                'excerpt' => 'Your ABS light is on but your brakes seem fine. Is it safe to drive? Here\'s what you need to know.',
                'content' => $this->getABSWarningContent(),
                'category' => 'diagnostics',
                'tags' => 'ABS,brakes,warning light,safety',
                'meta_title' => 'ABS Warning Light Causes | Is It Safe to Drive? | St. Augustine',
                'meta_description' => 'ABS warning light on? Learn common causes, whether it\'s safe to drive, and when to get professional diagnosis. Mobile ABS repair in St. Augustine FL.',
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($articles as $article) {
            $stmt = $this->db->prepare("
                INSERT INTO articles (slug, title, excerpt, content, category, tags, meta_title, meta_description, status, published_at)
                VALUES (:slug, :title, :excerpt, :content, :category, :tags, :meta_title, :meta_description, :status, :published_at)
            ");
            foreach ($article as $key => $value) {
                $stmt->bindValue(':' . $key, $value, SQLITE3_TEXT);
            }
            $stmt->execute();
        }

        // Seed dynamic snippets
        $snippets = [
            ['key' => 'jobs_completed_this_month', 'content' => '47', 'type' => 'number', 'auto_update' => 1],
            ['key' => 'satisfied_customers', 'content' => '500+', 'type' => 'text', 'auto_update' => 0],
            ['key' => 'years_experience', 'content' => '15+', 'type' => 'text', 'auto_update' => 0],
            ['key' => 'current_special', 'content' => 'FREE diagnostic scan with any repair over $200', 'type' => 'text', 'auto_update' => 1],
            ['key' => 'last_updated_date', 'content' => date('F j, Y'), 'type' => 'date', 'auto_update' => 1],
            ['key' => 'service_area_count', 'content' => '6', 'type' => 'number', 'auto_update' => 0],
        ];

        foreach ($snippets as $snippet) {
            $stmt = $this->db->prepare("INSERT INTO snippets (key, content, type, auto_update) VALUES (:key, :content, :type, :auto_update)");
            $stmt->bindValue(':key', $snippet['key'], SQLITE3_TEXT);
            $stmt->bindValue(':content', $snippet['content'], SQLITE3_TEXT);
            $stmt->bindValue(':type', $snippet['type'], SQLITE3_TEXT);
            $stmt->bindValue(':auto_update', $snippet['auto_update'], SQLITE3_INTEGER);
            $stmt->execute();
        }
    }

    // Article content generators (SEO-optimized)
    private function getCheckEngineLightContent(): string {
        return <<<'HTML'
<p>That little orange light on your dashboard just illuminated, and your heart sank. The <strong>check engine light</strong> (also called the malfunction indicator lamp or MIL) is one of the most misunderstood warning lights in your vehicle.</p>

<h2>What Triggers the Check Engine Light?</h2>
<p>Your vehicle's onboard computer (ECM) constantly monitors dozens of sensors throughout the engine and emissions systems. When something falls outside normal parameters, it stores a <strong>diagnostic trouble code (DTC)</strong> and illuminates the check engine light.</p>

<h3>Common Causes Include:</h3>
<ul>
<li><strong>Loose gas cap</strong> - The most common and easiest fix</li>
<li><strong>Oxygen sensor failure</strong> - Affects fuel economy and emissions</li>
<li><strong>Catalytic converter issues</strong> - Can be expensive if ignored</li>
<li><strong>Mass airflow sensor</strong> - Causes rough running and poor acceleration</li>
<li><strong>Spark plugs or ignition coils</strong> - Leads to misfires and poor performance</li>
</ul>

<h2>Is It Safe to Drive?</h2>
<p>A <strong>steady check engine light</strong> usually indicates a less urgent issue. You can typically continue driving but should get it diagnosed soon.</p>
<p>A <strong>flashing check engine light</strong> is serious. This indicates a misfire that could damage your catalytic converter. Pull over safely and call for help.</p>

<h2>Why Professional Diagnostics Matter</h2>
<p>Auto parts stores offer free code reading, but that only tells part of the story. A code like P0420 (catalytic converter efficiency) could be caused by:</p>
<ul>
<li>Actual catalytic converter failure ($1,000+)</li>
<li>Oxygen sensor failure ($150-300)</li>
<li>Exhaust leak ($100-200)</li>
<li>Engine misfire causing false readings</li>
</ul>

<p>Professional diagnostics with equipment like the <strong>Snap-on ZEUS</strong> provides live data, freeze frame information, and system-wide analysis to pinpoint the actual cause—not just the symptom.</p>

<h2>Get Expert Diagnosis in St. Augustine</h2>
<p>EZ Mobile Mechanic brings dealership-level diagnostics directly to your location. We'll identify the real problem and give you honest options—not just replace parts until something works.</p>
HTML;
    }

    private function getMobileMechanicVsShopContent(): string {
        return <<<'HTML'
<p>When your car needs repair, you have options. Traditional auto shops have been around forever, but <strong>mobile mechanics</strong> are changing the game. Let's compare the two so you can make the best choice for your situation.</p>

<h2>Convenience: Mobile Mechanic Wins</h2>
<table>
<tr><th>Factor</th><th>Mobile Mechanic</th><th>Auto Shop</th></tr>
<tr><td>Location</td><td>Your home, work, or roadside</td><td>You drive to them</td></tr>
<tr><td>Wait time</td><td>Watch or do other things</td><td>Sit in waiting room</td></tr>
<tr><td>Transportation</td><td>Not needed</td><td>Need a ride or rental</td></tr>
<tr><td>Scheduling</td><td>Often same-day, flexible hours</td><td>Appointments during business hours</td></tr>
</table>

<h2>Cost Comparison</h2>
<p>Mobile mechanics often have <strong>lower overhead</strong>—no building rent, fewer employees, less equipment costs. This can translate to savings for you:</p>
<ul>
<li>No towing fees (saves $75-150)</li>
<li>No diagnostic fee markups</li>
<li>Competitive labor rates</li>
<li>No upselling pressure from service writers</li>
</ul>

<h2>When to Choose a Shop</h2>
<p>Some repairs genuinely need a shop environment:</p>
<ul>
<li>Major engine or transmission rebuilds</li>
<li>Alignments (requires alignment rack)</li>
<li>Tire mounting and balancing</li>
<li>Body work and painting</li>
</ul>

<h2>When Mobile Mechanic Makes Sense</h2>
<ul>
<li>Diagnostics and check engine lights</li>
<li>Brake repairs</li>
<li>Starter and alternator replacement</li>
<li>Battery replacement</li>
<li>Tune-ups and maintenance</li>
<li>Most electrical repairs</li>
<li>Cooling system work</li>
</ul>

<h2>The Verdict</h2>
<p>For most common repairs and all diagnostics, a mobile mechanic offers better value and convenience. Save the shop visits for specialized equipment-dependent work.</p>
HTML;
    }

    private function getHybridMaintenanceContent(): string {
        return <<<'HTML'
<p>Hybrid vehicles combine gasoline engines with electric motors, creating unique maintenance requirements. Here's what every hybrid owner should know.</p>

<h2>What's Different About Hybrid Maintenance?</h2>

<h3>Regenerative Braking = Longer Brake Life</h3>
<p>Hybrids use the electric motor to slow down, recapturing energy. This means your <strong>brake pads can last 2-3 times longer</strong> than conventional vehicles. However, this can cause issues:</p>
<ul>
<li>Rotors may rust from less use</li>
<li>Brake fluid still needs regular changes</li>
<li>Calipers can seize from inactivity</li>
</ul>

<h3>High-Voltage Battery System</h3>
<p>The hybrid battery pack is designed to last 150,000+ miles, but requires:</p>
<ul>
<li>Proper cooling system maintenance</li>
<li>Regular driving (sitting hurts batteries)</li>
<li>Specialized diagnostics when issues arise</li>
</ul>

<h3>Transmission Differences</h3>
<p>Most hybrids use CVT or eCVT transmissions. These require:</p>
<ul>
<li>Specific hybrid transmission fluid</li>
<li>Different service intervals than conventional automatics</li>
</ul>

<h2>Maintenance Schedule Adjustments</h2>
<table>
<tr><th>Service</th><th>Conventional</th><th>Hybrid</th></tr>
<tr><td>Oil change</td><td>5,000-7,500 mi</td><td>5,000-10,000 mi (less engine use)</td></tr>
<tr><td>Brake pads</td><td>30,000-50,000 mi</td><td>80,000-100,000 mi</td></tr>
<tr><td>Spark plugs</td><td>30,000-100,000 mi</td><td>Same</td></tr>
<tr><td>Coolant</td><td>30,000-50,000 mi</td><td>Same (plus inverter coolant)</td></tr>
</table>

<h2>Finding a Qualified Hybrid Technician</h2>
<p>Not every mechanic is trained for hybrid systems. Look for:</p>
<ul>
<li>High-voltage safety certification</li>
<li>Hybrid-specific diagnostic equipment</li>
<li>Experience with your specific make/model</li>
</ul>

<p>EZ Mobile Mechanic provides specialized hybrid diagnostics throughout St. Augustine. We have the training and equipment to properly diagnose and service your hybrid vehicle.</p>
HTML;
    }

    private function getABSWarningContent(): string {
        return <<<'HTML'
<p>Your <strong>ABS warning light</strong> just came on, but your brakes still seem to work fine. What's going on, and is it safe to drive?</p>

<h2>Understanding Your ABS System</h2>
<p>ABS (Anti-lock Braking System) prevents wheel lockup during hard braking. It uses sensors at each wheel to detect rotation speed and modulates brake pressure to maintain control.</p>

<h2>Common ABS Warning Light Causes</h2>

<h3>1. Wheel Speed Sensor Issues</h3>
<p>The most common cause. Sensors can fail due to:</p>
<ul>
<li>Dirt or debris buildup</li>
<li>Damaged wiring from road debris</li>
<li>Corroded connections</li>
<li>Worn sensor from bearing play</li>
</ul>

<h3>2. Low Brake Fluid</h3>
<p>Some vehicles illuminate the ABS light when brake fluid is low. This could indicate:</p>
<ul>
<li>Normal pad wear (fluid fills the caliper space)</li>
<li>Brake fluid leak (more serious)</li>
</ul>

<h3>3. ABS Module Problems</h3>
<p>The computer that controls ABS can fail due to:</p>
<ul>
<li>Internal electrical failure</li>
<li>Corrosion from water intrusion</li>
<li>Pump motor failure</li>
</ul>

<h3>4. Faulty ABS Pump</h3>
<p>The hydraulic pump that modulates pressure can wear out or lose prime.</p>

<h2>Is It Safe to Drive?</h2>
<p><strong>Yes, with caution.</strong> When the ABS light is on:</p>
<ul>
<li>Your regular brakes still work normally</li>
<li>ABS assistance is disabled</li>
<li>Wheels may lock during hard braking</li>
<li>Allow extra stopping distance</li>
</ul>

<h2>Why Professional Diagnosis Matters</h2>
<p>ABS trouble codes require specialized scan tools to read. Basic OBD2 readers won't access ABS modules. Professional diagnostics can:</p>
<ul>
<li>Read ABS-specific codes</li>
<li>View live wheel speed data</li>
<li>Test ABS pump function</li>
<li>Check wiring and sensor signals</li>
</ul>

<h2>Get Your ABS Diagnosed Today</h2>
<p>EZ Mobile Mechanic has professional ABS diagnostic capabilities. We'll pinpoint the exact cause and provide honest repair options—no parts-cannon guessing.</p>
HTML;
    }

    // Public methods
    public function getArticle(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE slug = :slug AND status = 'published'");
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result) {
            // Increment views
            $this->db->exec("UPDATE articles SET views = views + 1 WHERE slug = '" . SQLite3::escapeString($slug) . "'");
        }

        return $result ?: null;
    }

    public function getArticles(string $category = null, int $limit = 10, int $offset = 0): array {
        $sql = "SELECT id, slug, title, excerpt, category, tags, featured_image, author, views, published_at
                FROM articles WHERE status = 'published'";

        if ($category) {
            $sql .= " AND category = :category";
        }

        $sql .= " ORDER BY published_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        if ($category) {
            $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        }
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $articles = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $articles[] = $row;
        }

        return $articles;
    }

    public function getCategories(): array {
        $result = $this->db->query("SELECT DISTINCT category, COUNT(*) as count FROM articles WHERE status = 'published' GROUP BY category");
        $categories = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $categories[] = $row;
        }
        return $categories;
    }

    public function getSnippet(string $key): ?string {
        $stmt = $this->db->prepare("SELECT content FROM snippets WHERE key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        return $result ? $result['content'] : null;
    }

    public function updateSnippet(string $key, string $content): bool {
        $stmt = $this->db->prepare("UPDATE snippets SET content = :content, last_updated = CURRENT_TIMESTAMP WHERE key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        return $stmt->execute() !== false;
    }

    public function getAllSnippets(): array {
        $result = $this->db->query("SELECT * FROM snippets ORDER BY key");
        $snippets = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $snippets[] = $row;
        }
        return $snippets;
    }

    public function createArticle(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO articles (slug, title, excerpt, content, category, tags, meta_title, meta_description, status)
            VALUES (:slug, :title, :excerpt, :content, :category, :tags, :meta_title, :meta_description, :status)
        ");

        $stmt->bindValue(':slug', $data['slug'], SQLITE3_TEXT);
        $stmt->bindValue(':title', $data['title'], SQLITE3_TEXT);
        $stmt->bindValue(':excerpt', $data['excerpt'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':content', $data['content'], SQLITE3_TEXT);
        $stmt->bindValue(':category', $data['category'] ?? 'general', SQLITE3_TEXT);
        $stmt->bindValue(':tags', $data['tags'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':meta_title', $data['meta_title'] ?? $data['title'], SQLITE3_TEXT);
        $stmt->bindValue(':meta_description', $data['meta_description'] ?? $data['excerpt'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':status', $data['status'] ?? 'draft', SQLITE3_TEXT);

        $stmt->execute();
        return $this->db->lastInsertRowID();
    }

    public function trackContentView(int $articleId, string $visitorId, string $referrer = ''): void {
        $stmt = $this->db->prepare("INSERT INTO content_stats (article_id, event_type, visitor_id, referrer) VALUES (:article_id, 'view', :visitor_id, :referrer)");
        $stmt->bindValue(':article_id', $articleId, SQLITE3_INTEGER);
        $stmt->bindValue(':visitor_id', $visitorId, SQLITE3_TEXT);
        $stmt->bindValue(':referrer', $referrer, SQLITE3_TEXT);
        $stmt->execute();
    }

    public function getPopularArticles(int $limit = 5): array {
        $stmt = $this->db->prepare("SELECT slug, title, views FROM articles WHERE status = 'published' ORDER BY views DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $articles = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $articles[] = $row;
        }
        return $articles;
    }

    public function getRelatedArticles(string $category, string $excludeSlug, int $limit = 3): array {
        $stmt = $this->db->prepare("
            SELECT slug, title, excerpt FROM articles
            WHERE status = 'published' AND category = :category AND slug != :exclude
            ORDER BY published_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $stmt->bindValue(':exclude', $excludeSlug, SQLITE3_TEXT);
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $articles = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $articles[] = $row;
        }
        return $articles;
    }
}
