<?php
/**
 * Advanced Diagnostics Service Page - A/B Testing Enabled
 *
 * Experiment: diagnostics_page_v1
 * Variants:
 *   A (Control): Professional, information-focused
 *   B (Challenger): Urgency/emotion-focused, stronger CTAs
 *
 * This page automatically:
 * - Assigns visitors to a variant
 * - Tracks page views
 * - Tracks conversions (form submissions, call clicks)
 * - Collects data for statistical analysis
 */

require_once __DIR__ . '/../../lib/ABTesting.php';

// Initialize A/B testing
$ab = new ABTesting();

// Get variant for this visitor (experiment auto-creates if doesn't exist)
$experimentName = 'diagnostics_page_v1';
$variant = $ab->getVariant($experimentName);

// Track page view
$ab->trackEvent($experimentName, 'view');

// Get dynamic domain for schema
$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'mechanicstaugustine.com';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $domain;

// Variant-specific content
$variants = [
    'A' => [
        'headline' => 'Professional Check Engine Light Diagnosis',
        'subheadline' => 'Advanced Snap-on ZEUS diagnostics for accurate problem identification',
        'cta_primary' => 'Schedule Diagnostic Service',
        'cta_secondary' => 'Call (904) 706-6669',
        'hero_class' => 'hero-professional',
        'urgency_banner' => false,
        'benefits_style' => 'grid',
        'testimonial' => false
    ],
    'B' => [
        'headline' => 'Stop Guessing. Get Answers Today.',
        'subheadline' => 'Tired of the check engine light? We diagnose it RIGHT - the first time.',
        'cta_primary' => 'Fix My Check Engine Light NOW',
        'cta_secondary' => 'Call NOW: (904) 706-6669',
        'hero_class' => 'hero-urgent',
        'urgency_banner' => true,
        'benefits_style' => 'list',
        'testimonial' => true
    ]
];

$v = $variants[$variant['variant']] ?? $variants['A'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <meta name="title" content="Check Engine Light Diagnosis St. Augustine | EZ Mobile Mechanic">
    <meta name="description" content="Expert check engine light diagnosis in St. Augustine, FL. Snap-on ZEUS advanced diagnostics identify the exact problem. Mobile service - we come to you. Call (904) 706-6669.">
    <meta name="keywords" content="check engine light diagnosis, St. Augustine auto repair, mobile mechanic diagnostics, Snap-on ZEUS, check engine light fix, OBD2 diagnosis">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $baseUrl; ?>/services/diagnostics/">
    <meta property="og:title" content="Check Engine Light Diagnosis | EZ Mobile Mechanic St. Augustine">
    <meta property="og:description" content="Expert check engine light diagnosis with Snap-on ZEUS technology. Mobile service in St. Augustine, FL.">

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo $baseUrl; ?>/services/diagnostics/">

    <title>Check Engine Light Diagnosis St. Augustine | EZ Mobile Mechanic</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0f172a;
            --light: #f8fafc;
            --text: #1e293b;
            --border: #e2e8f0;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: white;
        }

        /* Header */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: white;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        nav a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
        }

        nav a:hover { color: var(--primary); }

        /* Urgency Banner (Variant B) */
        .urgency-banner {
            background: linear-gradient(90deg, var(--danger), #dc2626);
            color: white;
            text-align: center;
            padding: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }

        /* Hero Sections */
        .hero-professional {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2563eb 100%);
            color: white;
            padding: 5rem 1.5rem;
            text-align: center;
        }

        .hero-urgent {
            background: linear-gradient(135deg, #0f172a 0%, #7f1d1d 50%, #dc2626 100%);
            color: white;
            padding: 5rem 1.5rem;
            text-align: center;
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.2rem);
            margin-bottom: 1rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-urgent {
            background: var(--warning);
            color: var(--dark);
            animation: shake 0.5s ease-in-out infinite;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        /* Content Sections */
        section {
            padding: 4rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
            text-align: center;
        }

        /* Benefits Grid (Variant A) */
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .benefit-card {
            background: var(--light);
            padding: 2rem;
            border-radius: 0.75rem;
            text-align: center;
            border: 1px solid var(--border);
        }

        .benefit-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .benefit-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .benefit-card p {
            color: var(--secondary);
        }

        /* Benefits List (Variant B) */
        .benefits-list {
            max-width: 700px;
            margin: 2rem auto;
        }

        .benefits-list li {
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            font-size: 1.1rem;
        }

        .benefits-list li::before {
            content: "✓";
            color: var(--success);
            font-size: 1.5rem;
            font-weight: bold;
        }

        /* Testimonial (Variant B) */
        .testimonial {
            background: linear-gradient(135deg, var(--light) 0%, #e0f2fe 100%);
            padding: 3rem;
            border-radius: 1rem;
            text-align: center;
            margin: 2rem 0;
        }

        .testimonial blockquote {
            font-size: 1.3rem;
            font-style: italic;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .testimonial cite {
            color: var(--secondary);
            font-style: normal;
        }

        /* Pricing */
        .pricing-box {
            background: var(--dark);
            color: white;
            padding: 3rem;
            border-radius: 1rem;
            text-align: center;
            max-width: 600px;
            margin: 2rem auto;
        }

        .pricing-box .price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--success);
        }

        .pricing-box p {
            opacity: 0.9;
            margin: 1rem 0;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            text-align: center;
            padding: 4rem 1.5rem;
        }

        .cta-section h2 {
            color: white;
        }

        /* Form */
        .quote-form {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            max-width: 500px;
            margin: 2rem auto 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }

        footer a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 1.8rem; }
            .hero-buttons { flex-direction: column; }
            .hero-buttons .btn { width: 100%; }
            nav ul { gap: 1rem; font-size: 0.9rem; }
        }

        /* A/B Variant Debug (remove in production) */
        .ab-debug {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: #0f0;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.8rem;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <!-- Urgency Banner (Variant B only) -->
    <?php if ($v['urgency_banner']): ?>
    <div class="urgency-banner">
        Limited Time: FREE Diagnostic with Any Repair! Call Now
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <header>
        <nav>
            <a href="/" class="logo">🔧 EZ Mobile Mechanic</a>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/#services">Services</a></li>
                <li><a href="/#contact">Contact</a></li>
                <li><a href="tel:+19047066669" class="btn btn-primary" onclick="trackConversion('call_click')">Call Now</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section (varies by variant) -->
    <section class="<?php echo $v['hero_class']; ?>">
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($v['headline']); ?></h1>
            <p><?php echo htmlspecialchars($v['subheadline']); ?></p>
            <div class="hero-buttons">
                <a href="#quote" class="btn <?php echo $v['urgency_banner'] ? 'btn-urgent' : 'btn-primary'; ?>" onclick="trackConversion('cta_click')">
                    <?php echo htmlspecialchars($v['cta_primary']); ?>
                </a>
                <a href="tel:+19047066669" class="btn btn-secondary" onclick="trackConversion('call_click')">
                    <?php echo htmlspecialchars($v['cta_secondary']); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section>
        <h2>Why Choose Professional Diagnostics?</h2>

        <?php if ($v['benefits_style'] === 'grid'): ?>
        <!-- Variant A: Grid Layout -->
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">🎯</div>
                <h3>Accurate Diagnosis</h3>
                <p>Snap-on ZEUS technology reads all systems, not just basic codes.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">💰</div>
                <h3>Save Money</h3>
                <p>No more replacing parts that aren't broken. Fix the actual problem.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🚗</div>
                <h3>Mobile Service</h3>
                <p>We come to you. No towing, no waiting rooms.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">⚡</div>
                <h3>Fast Results</h3>
                <p>Get answers in minutes, not days.</p>
            </div>
        </div>
        <?php else: ?>
        <!-- Variant B: List Layout -->
        <ul class="benefits-list">
            <li>Stop wasting money on parts you don't need</li>
            <li>Get the REAL answer - not guesswork</li>
            <li>We come to YOUR location (home, work, roadside)</li>
            <li>Same-day service available</li>
            <li>Transparent pricing - no surprises</li>
            <li>Fix it right the FIRST time</li>
        </ul>
        <?php endif; ?>
    </section>

    <?php if ($v['testimonial']): ?>
    <!-- Testimonial (Variant B only) -->
    <section>
        <div class="testimonial">
            <blockquote>
                "Other shops wanted to charge me $400 to 'figure out' my check engine light. Kyle diagnosed it in 15 minutes and saved me from replacing an expensive part I didn't need. Highly recommend!"
            </blockquote>
            <cite>- Mike T., St. Augustine</cite>
        </div>
    </section>
    <?php endif; ?>

    <!-- Pricing -->
    <section>
        <h2>Diagnostic Service Pricing</h2>
        <div class="pricing-box">
            <div class="price">$150</div>
            <p>Complete Diagnostic Scan</p>
            <p style="font-size: 0.9rem; opacity: 0.8;">Snap-on ZEUS advanced diagnostics - full system scan, code reading, live data analysis</p>
            <a href="#quote" class="btn btn-primary" style="margin-top: 1rem;" onclick="trackConversion('pricing_cta')">
                Schedule Now
            </a>
        </div>
    </section>

    <!-- CTA Section with Form -->
    <section class="cta-section" id="quote">
        <h2>Get Your Vehicle Diagnosed Today</h2>
        <p style="max-width: 600px; margin: 0 auto 2rem; opacity: 0.9;">
            Fill out the form below and we'll call you to schedule your diagnostic appointment.
        </p>

        <form class="quote-form" method="POST" action="/api/quote_intake.php" onsubmit="trackConversion('form_submit')">
            <input type="hidden" name="source" value="diagnostics_page">
            <input type="hidden" name="ab_variant" value="<?php echo htmlspecialchars($variant['variant']); ?>">

            <div class="form-group">
                <label for="name">Your Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="vehicle">Vehicle (Year/Make/Model) *</label>
                <input type="text" id="vehicle" name="vehicle" required placeholder="e.g., 2018 Honda Accord">
            </div>
            <div class="form-group">
                <label for="issue">What symptoms are you experiencing?</label>
                <textarea id="issue" name="issue" rows="3" placeholder="Check engine light on? Running rough? Other symptoms?"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Request Diagnostic Appointment
                </button>
            </div>
        </form>
    </section>

    <!-- Footer -->
    <footer>
        <p>EZ Mobile Mechanic | <a href="tel:+19047066669">(904) 706-6669</a> | St. Augustine, FL 32092</p>
        <p style="margin-top: 0.5rem; opacity: 0.7;">© 2025 EZ Mobile Mechanic. All rights reserved.</p>
    </footer>

    <!-- A/B Debug Badge (remove in production) -->
    <div class="ab-debug">
        Variant: <?php echo htmlspecialchars($variant['variant']); ?>
    </div>

    <!-- A/B Tracking Script -->
    <script>
        const experimentName = '<?php echo $experimentName; ?>';
        const currentVariant = '<?php echo $variant['variant']; ?>';

        // Get or create visitor ID for call attribution
        function getVisitorId() {
            let vid = document.cookie.match(/ez_ab_visitor=([^;]+)/);
            if (vid) return vid[1];
            vid = 'v_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            document.cookie = 'ez_ab_visitor=' + vid + ';path=/;max-age=' + (365*24*60*60);
            return vid;
        }

        // Store current experiment/variant in cookies for call attribution
        document.cookie = 'ez_ab_experiment=' + experimentName + ';path=/;max-age=' + (30*60);
        document.cookie = 'ez_ab_variant=' + currentVariant + ';path=/;max-age=' + (30*60);

        function trackConversion(action) {
            // Track in A/B testing system
            fetch('/api/ab-track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    experiment: experimentName,
                    event: 'conversion',
                    metadata: { action: action }
                })
            }).catch(err => console.error('Tracking error:', err));

            // If it's a call click, also track for phone call attribution
            if (action === 'call_click') {
                trackCallIntent('+19047066669');
            }
        }

        // Track call intent for phone attribution
        function trackCallIntent(phoneNumber) {
            fetch('/api/call-track.php?action=intent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    visitor_id: getVisitorId(),
                    phone: phoneNumber,
                    page: window.location.pathname,
                    experiment: experimentName,
                    variant: currentVariant,
                    utm_source: new URLSearchParams(window.location.search).get('utm_source') || '',
                    utm_medium: new URLSearchParams(window.location.search).get('utm_medium') || '',
                    utm_campaign: new URLSearchParams(window.location.search).get('utm_campaign') || ''
                })
            }).catch(err => console.error('Call tracking error:', err));
        }

        // Track scroll depth
        let maxScroll = 0;
        window.addEventListener('scroll', function() {
            const scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
                if (maxScroll === 25 || maxScroll === 50 || maxScroll === 75 || maxScroll === 100) {
                    fetch('/api/ab-track.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            experiment: experimentName,
                            event: 'scroll_' + maxScroll,
                            metadata: { depth: maxScroll }
                        })
                    });
                }
            }
        });

        // Track time on page
        setTimeout(function() {
            fetch('/api/ab-track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    experiment: experimentName,
                    event: 'engaged_30s',
                    metadata: {}
                })
            });
        }, 30000);
    </script>

    <!-- Schema Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Check Engine Light Diagnosis",
        "description": "Professional check engine light diagnosis using Snap-on ZEUS advanced diagnostic technology. Mobile service in St. Augustine, FL.",
        "provider": {
            "@type": "LocalBusiness",
            "name": "EZ Mobile Mechanic",
            "telephone": "+19047066669",
            "address": {
                "@type": "PostalAddress",
                "addressLocality": "St. Augustine",
                "addressRegion": "FL",
                "postalCode": "32092"
            }
        },
        "areaServed": {
            "@type": "City",
            "name": "St. Augustine",
            "addressRegion": "FL"
        },
        "offers": {
            "@type": "Offer",
            "price": "89",
            "priceCurrency": "USD",
            "description": "Complete diagnostic scan including full system scan, code reading, live data analysis"
        }
    }
    </script>
</body>
</html>
