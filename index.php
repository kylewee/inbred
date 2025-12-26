<?php
/**
 * EZ Mobile Mechanic - SEO-Optimized Homepage
 * Advanced Diagnostics Mobile Service - St. Augustine, FL
 *
 * SEO Features:
 * - LocalBusiness Schema Markup
 * - Service Schema Markup
 * - Open Graph tags
 * - Mobile-first responsive design
 * - Core Web Vitals optimization
 * - Semantic HTML5
 */

// Get current domain for schema markup
$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'mechanicstaugustine.com';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $domain;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary Meta Tags -->
    <meta name="title" content="Advanced Mobile Auto Diagnostics | EZ Mobile Mechanic St. Augustine, FL">
    <meta name="description" content="EZ Mobile Mechanic offers advanced automotive diagnostics with Snap-on ZEUS in St. Augustine, FL. Expert mobile diagnostics for check engine lights, ABS, SRS, hybrid repairs, and electronic control module reprogramming. Serving St. Johns County.">
    <meta name="keywords" content="mobile mechanic St. Augustine FL, advanced auto diagnostics, Snap-on ZEUS diagnostics, check engine light diagnosis, ABS repair St. Augustine, SRS airbag repair, hybrid vehicle diagnostics, ECM reprogramming, late model vehicle repair, mobile diagnostic service">
    <meta name="author" content="Kyle - EZ Mobile Mechanic">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="theme-color" content="#2563eb">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="<?php echo $baseUrl; ?>">
    <meta property="og:title" content="Advanced Mobile Auto Diagnostics | EZ Mobile Mechanic">
    <meta property="og:description" content="Professional mobile mechanical diagnostics with Snap-on ZEUS technology in St. Augustine, FL. Check engine lights, ABS, SRS, hybrid vehicles, and more.">
    <meta property="og:image" content="<?php echo $baseUrl; ?>/og-image.jpg">
    <meta property="og:site_name" content="EZ Mobile Mechanic">
    <meta property="og:locale" content="en_US">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $baseUrl; ?>">
    <meta name="twitter:title" content="Advanced Mobile Auto Diagnostics | EZ Mobile Mechanic">
    <meta name="twitter:description" content="Professional mobile mechanical diagnostics with Snap-on ZEUS technology in St. Augustine, FL.">
    <meta name="twitter:image" content="<?php echo $baseUrl; ?>/og-image.jpg">

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo $baseUrl; ?>">

    <!-- Preconnect to improve performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="//ajax.googleapis.com">

    <title>Advanced Mobile Auto Diagnostics | EZ Mobile Mechanic St. Augustine, FL</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --text: #1e293b;
            --border: #e2e8f0;
            --accent: #0ea5e9;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: white;
            overflow-x: hidden;
        }

        /* Header & Navigation */
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

        .logo-icon {
            font-size: 1.8rem;
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
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary);
        }

        .cta-btn {
            background: var(--primary);
            color: white !important;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .cta-btn:hover {
            background: var(--primary-dark);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2563eb 100%);
            color: white;
            padding: 6rem 1.5rem;
            text-align: center;
        }

        .hero-content {
            max-width: 1000px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            margin-bottom: 1rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            opacity: 0.95;
            color: #e0f2fe;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
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

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Section Header */
        .section-header {
            max-width: 1200px;
            margin: 0 auto 3rem;
            text-align: center;
        }

        .section-header h2 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            margin-bottom: 1rem;
            color: var(--dark);
            font-weight: 800;
        }

        .section-header p {
            font-size: 1.1rem;
            color: var(--secondary);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Diagnostics Focus Section */
        .diagnostics-intro {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            padding: 4rem 1.5rem;
        }

        .diagnostics-intro-content {
            max-width: 1000px;
            margin: 0 auto;
        }

        .diagnostics-intro h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .diagnostics-intro p {
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 1rem;
            line-height: 1.8;
        }

        .diagnostic-highlight {
            background: white;
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 0.5rem;
        }

        .diagnostic-highlight strong {
            color: var(--primary);
        }

        /* Services Grid */
        .services-section {
            padding: 4rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .service-card {
            background: white;
            border: 2px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
            transition: all 0.3s;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .service-card:hover {
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.15);
            transform: translateY(-5px);
        }

        .service-card:hover::before {
            opacity: 1;
        }

        .service-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .service-card h3 {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .service-card p {
            color: var(--secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .service-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .service-link:hover {
            text-decoration: underline;
        }

        /* Why Choose Section */
        .why-us {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 4rem 1.5rem;
        }

        .why-us-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .why-us h2 {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            font-weight: 800;
        }

        .why-us-list {
            list-style: none;
        }

        .why-us-list li {
            margin-bottom: 1.5rem;
            padding-left: 2.5rem;
            position: relative;
            font-size: 1rem;
            line-height: 1.6;
        }

        .why-us-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--success);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .why-us-list h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        /* Service Areas */
        .service-areas {
            background: var(--light);
            padding: 4rem 1.5rem;
        }

        .areas-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
        }

        .area-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .area-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .area-card h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .area-card a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .area-card a:hover {
            text-decoration: underline;
        }

        /* Contact Section */
        .contact-section {
            background: var(--light);
            padding: 4rem 1.5rem;
        }

        .contact-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .contact-item {
            display: flex;
            gap: 1rem;
        }

        .contact-icon {
            font-size: 1.5rem;
            color: var(--primary);
            flex-shrink: 0;
        }

        .contact-item h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .contact-item p {
            color: var(--secondary);
        }

        .contact-item a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .contact-item a:hover {
            text-decoration: underline;
        }

        /* Quote Form */
        .quote-form {
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 2rem;
        }

        .quote-form h3 {
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group button {
            width: 100%;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 3rem 1.5rem 1rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        footer h3 {
            margin-bottom: 1rem;
            color: var(--primary);
            font-weight: 700;
        }

        footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.95rem;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }

        footer a:hover {
            color: white;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav ul {
                gap: 0.5rem;
                font-size: 0.9rem;
            }

            .cta-btn {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }

            .hero {
                padding: 3rem 1.5rem;
            }

            .hero h1 {
                font-size: 1.8rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-buttons .btn {
                width: 100%;
            }

            .why-us-content,
            .contact-content {
                grid-template-columns: 1fr;
            }

            .service-card {
                padding: 1.5rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .logo {
                font-size: 1rem;
            }

            nav {
                padding: 0.75rem 1rem;
            }

            nav ul {
                gap: 0.25rem;
            }

            .section-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header role="banner">
        <nav role="navigation" aria-label="Main navigation">
            <a href="/" class="logo">
                <span class="logo-icon">🔧</span>
                <span>EZ Mobile Mechanic</span>
            </a>
            <ul>
                <li><a href="#diagnostics">Diagnostics</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#areas">Service Areas</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="/quote/" class="cta-btn">Get Quote</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" role="region" aria-label="Hero section">
        <div class="hero-content">
            <h1>Advanced Mobile Auto Diagnostics in St. Augustine</h1>
            <p class="hero-subtitle">Professional Snap-on ZEUS Diagnostics. We Come to You.</p>
            <p>Expert automotive diagnostics for check engine lights, ABS systems, airbag (SRS) repairs, hybrid vehicles, and complex electronic issues. Mobile service across St. Augustine and St. Johns County, FL.</p>
            <div class="hero-buttons">
                <a href="#contact" class="btn btn-primary">Get Diagnostics Today</a>
                <a href="tel:+19042175152" class="btn btn-secondary">Call (904) 217-5152</a>
            </div>
        </div>
    </section>

    <!-- Diagnostics Focus Section -->
    <section class="diagnostics-intro" id="diagnostics" role="region" aria-label="Advanced diagnostics information">
        <div class="diagnostics-intro-content">
            <h2>Why Choose Advanced Diagnostics?</h2>
            <p>Tired of guesswork and expensive tow bills? Our advanced diagnostic scanning eliminates uncertainty and saves you money.</p>

            <div class="diagnostic-highlight">
                <strong>🎯 Snap-on ZEUS Technology</strong>
                <p>Dealership-level diagnostics directly to your vehicle. We identify problems that other shops miss, from simple check engine lights to complex module issues.</p>
            </div>

            <div class="diagnostic-highlight">
                <strong>⚡ Electronic Control Module (ECM) Diagnostics & Reprogramming</strong>
                <p>Advanced module troubleshooting and recalibration for late-model vehicles. We handle complex electronic issues with precision.</p>
            </div>

            <div class="diagnostic-highlight">
                <strong>🚗 Hybrid & Electric Vehicle Diagnostics</strong>
                <p>Specialized diagnostics for hybrid systems, battery management, and electric drivetrain issues. Expertise in modern vehicle technology.</p>
            </div>

            <div class="diagnostic-highlight">
                <strong>🛡️ ABS & Airbag (SRS) System Diagnostics</strong>
                <p>Critical safety system troubleshooting. We diagnose and repair anti-lock braking systems and airbag control modules with precision.</p>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services-section" id="services" role="region" aria-label="Our services">
        <div class="section-header">
            <h2>Our Specialized Services</h2>
            <p>Comprehensive automotive diagnostic and repair services for all vehicle types</p>
        </div>
        <div class="services-grid">
            <article class="service-card">
                <div class="service-icon">🔍</div>
                <h3>Check Engine Light Diagnosis</h3>
                <p>Advanced fault code diagnosis and repair. We identify the exact problem and provide transparent solutions.</p>
                <a href="/diagnostics/" class="service-link">Learn More →</a>
            </article>
            <article class="service-card">
                <div class="service-icon">⚙️</div>
                <h3>Engine & Transmission Diagnostics</h3>
                <p>Complete engine control system diagnostics, fuel injection analysis, and transmission troubleshooting.</p>
                <a href="/engine-diagnostics/" class="service-link">Learn More →</a>
            </article>
            <article class="service-card">
                <div class="service-icon">🛡️</div>
                <h3>ABS & SRS System Repair</h3>
                <p>Anti-lock braking and airbag system diagnostics and repair. Critical safety systems handled with expertise.</p>
                <a href="/abs-srs-repair/" class="service-link">Learn More →</a>
            </article>
            <article class="service-card">
                <div class="service-icon">⚡</div>
                <h3>Hybrid & Electric Vehicles</h3>
                <p>Specialized diagnostics for hybrid battery systems, regenerative braking, and electric motor control.</p>
                <a href="/hybrid-diagnostics/" class="service-link">Learn More →</a>
            </article>
            <article class="service-card">
                <div class="service-icon">💾</div>
                <h3>ECM Reprogramming</h3>
                <p>Electronic control module diagnostics, reprogramming, and recalibration for late-model vehicles.</p>
                <a href="/ecm-reprogramming/" class="service-link">Learn More →</a>
            </article>
            <article class="service-card">
                <div class="service-icon">🚚</div>
                <h3>Diesel & Heavy Equipment</h3>
                <p>Advanced diagnostics for diesel engines, tractors, trailers, and commercial vehicles.</p>
                <a href="/diesel-diagnostics/" class="service-link">Learn More →</a>
            </article>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-us" id="why-us" role="region" aria-label="Why choose us">
        <div class="why-us-content">
            <div>
                <h2>Why Choose EZ Mobile Mechanic?</h2>
                <p style="margin-bottom: 2rem; opacity: 0.9; line-height: 1.8;">We bring dealership-quality diagnostics to your location, with transparent pricing and honest advice. No guesswork. Just results.</p>
            </div>
            <ul class="why-us-list">
                <li>
                    <h3>Advanced Diagnostics</h3>
                    <p>Snap-on ZEUS technology for accurate problem identification in any vehicle.</p>
                </li>
                <li>
                    <h3>Mobile Service</h3>
                    <p>We come to you. Save time, eliminate towing costs, get service at home or work.</p>
                </li>
                <li>
                    <h3>Expert Technician</h3>
                    <p>Years of experience with complex diagnostics, late-model vehicles, and electronic systems.</p>
                </li>
                <li>
                    <h3>Transparent Pricing</h3>
                    <p>Honest estimates with no hidden fees. You know exactly what you're paying for.</p>
                </li>
                <li>
                    <h3>Integrity First</h3>
                    <p>We recommend only necessary repairs. Your trust is our most valuable asset.</p>
                </li>
            </ul>
        </div>
    </section>

    <!-- Service Areas Section -->
    <section class="service-areas" id="areas" role="region" aria-label="Service areas">
        <div class="section-header">
            <h2>Serving St. Johns County, Florida</h2>
            <p>Mobile diagnostic and repair services throughout the greater St. Augustine area</p>
        </div>
        <div class="areas-grid">
            <div class="area-card">
                <h3>📍 St. Augustine</h3>
                <a href="/st-augustine-mobile-mechanic/">View Service Info</a>
            </div>
            <div class="area-card">
                <h3>📍 Picolata</h3>
                <a href="/picolata-auto-repair/">View Service Info</a>
            </div>
            <div class="area-card">
                <h3>📍 Riverdale</h3>
                <a href="/riverdale-mechanic/">View Service Info</a>
            </div>
            <div class="area-card">
                <h3>📍 Elkton</h3>
                <a href="/elkton-auto-repair/">View Service Info</a>
            </div>
            <div class="area-card">
                <h3>📍 Green Cove Springs</h3>
                <a href="/green-cove-springs-mechanic/">View Service Info</a>
            </div>
            <div class="area-card">
                <h3>📍 St. Johns County</h3>
                <a href="/st-johns-county-mechanic/">View Service Info</a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact" role="region" aria-label="Contact information and quote form">
        <div class="section-header">
            <h2>Get Expert Diagnostics Today</h2>
            <p>Fast, accurate diagnosis. Mobile service. Transparent pricing.</p>
        </div>
        <div class="contact-content">
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">📞</div>
                    <div>
                        <h3>Call Us Now</h3>
                        <p><a href="tel:+19042175152">(904) 217-5152</a></p>
                        <p style="font-size: 0.9rem; margin-top: 0.5rem; color: var(--secondary);">Monday - Sunday: Available for calls</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div>
                        <h3>Email</h3>
                        <p><a href="mailto:kyle@ezmobilemechanic.com">kyle@ezmobilemechanic.com</a></p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">📍</div>
                    <div>
                        <h3>Service Area</h3>
                        <p>St. Augustine, FL 32092 and surrounding St. Johns County areas</p>
                    </div>
                </div>
            </div>
            <form class="quote-form" method="POST" action="/api/quote_intake.php">
                <h3>Quick Diagnostic Request</h3>
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="vehicle">Vehicle (Year/Make/Model)</label>
                    <input type="text" id="vehicle" name="vehicle" placeholder="2020 Honda Civic">
                </div>
                <div class="form-group">
                    <label for="issue">Describe Your Issue *</label>
                    <textarea id="issue" name="issue" required placeholder="Check engine light? Strange sounds? Electronics issues?"></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Request Diagnostic Service</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer role="contentinfo">
        <div class="footer-content">
            <div>
                <h3>EZ Mobile Mechanic</h3>
                <p style="margin-bottom: 1rem;">Advanced automotive diagnostics and repairs. Serving St. Johns County, FL since 2025.</p>
                <p style="font-size: 0.9rem; opacity: 0.8;">"Proving that mechanics can be morally correct!"</p>
            </div>
            <div>
                <h3>Services</h3>
                <a href="#diagnostics">Advanced Diagnostics</a>
                <a href="/check-engine-light/">Check Engine Light</a>
                <a href="/abs-srs-repair/">ABS & SRS Repair</a>
                <a href="/hybrid-diagnostics/">Hybrid Diagnostics</a>
                <a href="/ecm-reprogramming/">ECM Reprogramming</a>
            </div>
            <div>
                <h3>Contact & Hours</h3>
                <a href="tel:+19042175152">(904) 217-5152</a>
                <a href="mailto:kyle@ezmobilemechanic.com">kyle@ezmobilemechanic.com</a>
                <p style="margin-top: 1rem; font-size: 0.9rem;">Mobile service - Available 7 days a week</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 EZ Mobile Mechanic. All rights reserved. | St. Augustine, FL 32092 | Licensed & Insured</p>
        </div>
    </footer>

    <!-- JSON-LD Schema Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "@id": "<?php echo $baseUrl; ?>",
        "name": "EZ Mobile Mechanic",
        "description": "Advanced mobile automotive diagnostics and repair service in St. Augustine, FL. Snap-on ZEUS diagnostics for check engine lights, ABS, SRS, hybrid vehicles, and electronic control modules.",
        "url": "<?php echo $baseUrl; ?>",
        "telephone": "+19042175152",
        "email": "kyle@ezmobilemechanic.com",
        "image": "<?php echo $baseUrl; ?>/logo.png",
        "priceRange": "$$",
        "areaServed": [
            {
                "@type": "City",
                "name": "St. Augustine",
                "addressCountry": "US",
                "addressRegion": "FL",
                "postalCode": "32092"
            },
            {
                "@type": "City",
                "name": "Picolata",
                "addressCountry": "US",
                "addressRegion": "FL"
            },
            {
                "@type": "City",
                "name": "Elkton",
                "addressCountry": "US",
                "addressRegion": "FL"
            },
            {
                "@type": "City",
                "name": "Green Cove Springs",
                "addressCountry": "US",
                "addressRegion": "FL"
            },
            {
                "@type": "AdministrativeArea",
                "name": "St. Johns County",
                "addressCountry": "US",
                "addressRegion": "FL"
            }
        ],
        "serviceArea": {
            "@type": "AdministrativeArea",
            "name": "St. Johns County, Florida",
            "addressCountry": "US",
            "addressRegion": "FL"
        },
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "US",
            "addressRegion": "FL",
            "addressLocality": "St. Augustine",
            "postalCode": "32092"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Service",
            "telephone": "+19042175152",
            "email": "kyle@ezmobilemechanic.com"
        },
        "sameAs": [
            "https://www.facebook.com/ezmobilemechanic",
            "https://www.google.com/maps/place/EZ+Mobile+Mechanic"
        ],
        "hasOfferCatalog": {
            "@type": "OfferCatalog",
            "name": "Automotive Diagnostic Services",
            "itemListElement": [
                {
                    "@type": "OfferCatalog",
                    "name": "Advanced Diagnostics",
                    "description": "Snap-on ZEUS diagnostic scanning for all vehicle systems"
                },
                {
                    "@type": "OfferCatalog",
                    "name": "Check Engine Light Diagnosis",
                    "description": "Professional check engine light diagnosis and repair"
                },
                {
                    "@type": "OfferCatalog",
                    "name": "ABS & SRS Repair",
                    "description": "Anti-lock braking and airbag system diagnostics and repair"
                },
                {
                    "@type": "OfferCatalog",
                    "name": "Hybrid Vehicle Diagnostics",
                    "description": "Specialized diagnostics for hybrid and electric vehicles"
                }
            ]
        }
    }
    </script>

    <!-- Service Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ProfessionalService",
        "name": "EZ Mobile Mechanic - Advanced Automotive Diagnostics",
        "description": "Mobile automotive diagnostic and repair service specializing in advanced check engine light diagnosis, ABS/SRS repair, hybrid vehicles, and ECM reprogramming",
        "url": "<?php echo $baseUrl; ?>",
        "telephone": "+19042175152",
        "email": "kyle@ezmobilemechanic.com",
        "knowsAbout": [
            "Automotive Diagnostics",
            "Check Engine Light Diagnosis",
            "ABS System Repair",
            "Airbag (SRS) System Repair",
            "Hybrid Vehicle Diagnostics",
            "ECM Reprogramming",
            "Diesel Engine Service",
            "Mobile Mechanic Service"
        ]
    }
    </script>

    <!-- Call Tracking for Analytics -->
    <script>
        // Get or create visitor ID
        function getVisitorId() {
            let vid = document.cookie.match(/ez_ab_visitor=([^;]+)/);
            if (vid) return vid[1];
            vid = 'v_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            document.cookie = 'ez_ab_visitor=' + vid + ';path=/;max-age=' + (365*24*60*60);
            return vid;
        }

        // Track call intent when phone links are clicked
        function trackCallClick(phoneNumber, source) {
            fetch('/api/call-track.php?action=intent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    visitor_id: getVisitorId(),
                    phone: phoneNumber,
                    page: window.location.pathname,
                    experiment: '',
                    variant: '',
                    utm_source: new URLSearchParams(window.location.search).get('utm_source') || source || '',
                    utm_medium: new URLSearchParams(window.location.search).get('utm_medium') || '',
                    utm_campaign: new URLSearchParams(window.location.search).get('utm_campaign') || ''
                })
            }).catch(err => console.error('Call tracking error:', err));
        }

        // Auto-attach tracking to all phone links
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[href^="tel:"]').forEach(function(link) {
                link.addEventListener('click', function() {
                    const phone = this.href.replace('tel:', '');
                    const source = this.closest('section')?.id || 'homepage';
                    trackCallClick(phone, source);
                });
            });
        });
    </script>
</body>
</html>
