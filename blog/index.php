<?php
/**
 * Blog Listing Page - SEO Optimized
 *
 * Features:
 * - Dynamic content from SQLite database
 * - Category filtering
 * - Schema markup for blog posts
 * - Analytics tracking
 * - Fresh content signals for search engines
 */

require_once __DIR__ . '/../lib/ContentManager.php';
require_once __DIR__ . '/../lib/Analytics.php';

$content = new ContentManager();
$analytics = new Analytics();

// Track page view
$analytics->trackPageview('/blog/', 'Blog - EZ Mobile Mechanic');

// Get category filter
$category = $_GET['category'] ?? null;

// Get articles
$articles = $content->getArticles($category, 10);
$categories = $content->getCategories();
$popularArticles = $content->getPopularArticles(5);

// Get dynamic snippets for freshness
$lastUpdated = $content->getSnippet('last_updated_date');
$jobsThisMonth = $content->getSnippet('jobs_completed_this_month');

$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'mechanicstaugustine.com';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $domain;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <meta name="title" content="Auto Repair Tips & Guides | EZ Mobile Mechanic Blog">
    <meta name="description" content="Expert automotive advice, diagnostic tips, and maintenance guides from EZ Mobile Mechanic. Learn about check engine lights, hybrid cars, ABS systems, and more.">
    <meta name="keywords" content="auto repair tips, car maintenance, check engine light, hybrid car maintenance, mobile mechanic blog, St. Augustine">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $baseUrl; ?>/blog/">
    <meta property="og:title" content="Auto Repair Tips & Guides | EZ Mobile Mechanic">
    <meta property="og:description" content="Expert automotive advice from your local mobile mechanic.">

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo $baseUrl; ?>/blog/<?php echo $category ? "?category=$category" : ''; ?>">

    <!-- Last Modified for freshness signal -->
    <meta http-equiv="last-modified" content="<?php echo date('D, d M Y H:i:s T'); ?>">

    <title>Auto Repair Tips & Guides | EZ Mobile Mechanic Blog</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --dark: #0f172a;
            --light: #f8fafc;
            --text: #1e293b;
            --border: #e2e8f0;
        }

        body {
            font-family: "Inter", system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--light);
        }

        header {
            background: white;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
            text-decoration: none;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
        }

        nav a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
        }

        nav a:hover { color: var(--primary); }

        .page-header {
            background: linear-gradient(135deg, var(--dark) 0%, #1e293b 100%);
            color: white;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }

        /* Articles Grid */
        .articles-grid {
            display: grid;
            gap: 1.5rem;
        }

        .article-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            overflow: hidden;
            display: grid;
            grid-template-columns: 200px 1fr;
            transition: all 0.3s;
        }

        .article-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .article-image {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }

        .article-content {
            padding: 1.5rem;
        }

        .article-category {
            display: inline-block;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .article-content h2 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .article-content h2 a {
            color: inherit;
            text-decoration: none;
        }

        .article-content h2 a:hover {
            color: var(--primary);
        }

        .article-content p {
            color: var(--secondary);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .article-meta {
            font-size: 0.85rem;
            color: var(--secondary);
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .sidebar-widget {
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .sidebar-widget h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
        }

        .category-list {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 0.5rem;
        }

        .category-list a {
            color: var(--text);
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .category-list a:hover {
            background: var(--light);
            color: var(--primary);
        }

        .category-list a.active {
            background: var(--primary);
            color: white;
        }

        .category-count {
            background: var(--light);
            padding: 0.1rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
        }

        .popular-list {
            list-style: none;
        }

        .popular-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .popular-list li:last-child {
            border-bottom: none;
        }

        .popular-list a {
            color: var(--text);
            text-decoration: none;
            font-size: 0.95rem;
        }

        .popular-list a:hover {
            color: var(--primary);
        }

        .popular-views {
            font-size: 0.8rem;
            color: var(--secondary);
        }

        /* CTA Box */
        .cta-box {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-align: center;
        }

        .cta-box h3 {
            border-bottom: none;
            color: white;
        }

        .cta-box p {
            font-size: 0.9rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .cta-box .btn {
            background: white;
            color: var(--primary);
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }

        .cta-box .btn:hover {
            background: var(--light);
        }

        /* Freshness Badge */
        .freshness-badge {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            text-align: center;
        }

        footer {
            background: var(--dark);
            color: white;
            padding: 2rem;
            text-align: center;
            margin-top: 3rem;
        }

        footer a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 900px) {
            main {
                grid-template-columns: 1fr;
            }

            .article-card {
                grid-template-columns: 1fr;
            }

            .article-image {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="/" class="logo">🔧 EZ Mobile Mechanic</a>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/#services">Services</a></li>
                <li><a href="/blog/" class="active">Blog</a></li>
                <li><a href="/#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-header">
        <h1>Auto Repair Tips & Guides</h1>
        <p>Expert advice from your local mobile mechanic. Learn about diagnostics, maintenance, and keeping your vehicle running right.</p>
    </div>

    <main>
        <div class="articles-grid">
            <?php if (empty($articles)): ?>
            <div class="article-card" style="grid-template-columns: 1fr; text-align: center; padding: 3rem;">
                <p>No articles found. Check back soon!</p>
            </div>
            <?php else: ?>
            <?php foreach ($articles as $article):
                $icons = [
                    'diagnostics' => '🔍',
                    'maintenance' => '🔧',
                    'tips' => '💡',
                    'general' => '📝'
                ];
                $icon = $icons[$article['category']] ?? '📝';
            ?>
            <article class="article-card">
                <div class="article-image"><?php echo $icon; ?></div>
                <div class="article-content">
                    <span class="article-category"><?php echo htmlspecialchars($article['category']); ?></span>
                    <h2><a href="/blog/<?php echo htmlspecialchars($article['slug']); ?>/"><?php echo htmlspecialchars($article['title']); ?></a></h2>
                    <p><?php echo htmlspecialchars($article['excerpt']); ?></p>
                    <div class="article-meta">
                        By <?php echo htmlspecialchars($article['author']); ?> • <?php echo date('M j, Y', strtotime($article['published_at'])); ?>
                        • <?php echo number_format($article['views']); ?> views
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <aside class="sidebar">
            <div class="freshness-badge">
                Updated: <?php echo $lastUpdated; ?><br>
                <strong><?php echo $jobsThisMonth; ?></strong> jobs completed this month
            </div>

            <div class="sidebar-widget">
                <h3>Categories</h3>
                <ul class="category-list">
                    <li><a href="/blog/" <?php echo !$category ? 'class="active"' : ''; ?>>All Articles</a></li>
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/blog/?category=<?php echo urlencode($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'class="active"' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($cat['category'])); ?>
                            <span class="category-count"><?php echo $cat['count']; ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-widget">
                <h3>Popular Articles</h3>
                <ul class="popular-list">
                    <?php foreach ($popularArticles as $popular): ?>
                    <li>
                        <a href="/blog/<?php echo htmlspecialchars($popular['slug']); ?>/"><?php echo htmlspecialchars($popular['title']); ?></a>
                        <div class="popular-views"><?php echo number_format($popular['views']); ?> views</div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-widget cta-box">
                <h3>Need Help?</h3>
                <p>Having car trouble? Get expert diagnostics right at your location.</p>
                <a href="tel:+19042175152" class="btn">Call (904) 217-5152</a>
            </div>
        </aside>
    </main>

    <footer>
        <p>&copy; 2025 EZ Mobile Mechanic | <a href="/"">Home</a> | <a href="/#contact">Contact</a></p>
    </footer>

    <!-- Blog Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Blog",
        "name": "EZ Mobile Mechanic Blog",
        "description": "Auto repair tips, diagnostic guides, and maintenance advice from a professional mobile mechanic in St. Augustine, FL.",
        "url": "<?php echo $baseUrl; ?>/blog/",
        "publisher": {
            "@type": "Organization",
            "name": "EZ Mobile Mechanic",
            "url": "<?php echo $baseUrl; ?>"
        },
        "blogPost": [
            <?php
            $blogPosts = [];
            foreach ($articles as $article) {
                $blogPosts[] = json_encode([
                    "@type" => "BlogPosting",
                    "headline" => $article['title'],
                    "description" => $article['excerpt'],
                    "url" => $baseUrl . '/blog/' . $article['slug'] . '/',
                    "datePublished" => date('c', strtotime($article['published_at'])),
                    "author" => ["@type" => "Person", "name" => $article['author']]
                ], JSON_UNESCAPED_SLASHES);
            }
            echo implode(",\n            ", $blogPosts);
            ?>
        ]
    }
    </script>
</body>
</html>
