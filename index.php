<?php
/**
 * Landing Page Template (test edit)
 * Config-driven - works for any business type
 *
 * SEO Features:
 * - LocalBusiness Schema Markup
 * - Open Graph tags
 * - Mobile-first responsive design
 * - Semantic HTML5
 */

// Load config
require_once __DIR__ . '/config/bootstrap.php';

// Get site info from config
$siteName = config('site.name', 'Business Name');
$tagline = config('site.tagline', 'Professional Services');
$phone = config('site.phone', '');
$email = config('site.email', '');
$address = config('site.address', '');
$serviceArea = config('site.service_area', '');
$domain = config('site.domain', $_SERVER['HTTP_HOST'] ?? 'localhost');

// Business info
$businessType = config('business.type', 'contractor');
$category = config('business.category', 'Home Services');
$services = config('business.services', []);

// Branding
$primaryColor = config('branding.primary_color', '#2563eb');
$secondaryColor = config('branding.secondary_color', '#10b981');

// SEO
$metaDescription = config('seo.meta_description', "Professional {$category} services in {$serviceArea}.");
$titleTemplate = config('seo.title_template', '%page% | %site_name%');
$pageTitle = str_replace(['%page%', '%site_name%'], [$tagline, $siteName], $titleTemplate);

// URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $domain;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary Meta Tags -->
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="<?= htmlspecialchars($primaryColor) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="<?= $baseUrl ?>">
    <meta property="og:title" content="<?= htmlspecialchars($siteName) ?> | <?= htmlspecialchars($tagline) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">

    <!-- Canonical -->
    <link rel="canonical" href="<?= $baseUrl ?>">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: <?= $primaryColor ?>;
            --secondary: <?= $secondaryColor ?>;
            --dark: #1e293b;
            --light: #f8fafc;
            --text: #334155;
            --border: #e2e8f0;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: white;
        }

        /* Header */
        header {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .header-cta {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            filter: brightness(1.1);
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, color-mix(in srgb, var(--primary), #000 20%) 100%);
            color: white;
            padding: 4rem 1rem;
            text-align: center;
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero .btn-primary {
            background: white;
            color: var(--primary);
        }

        .hero .btn-outline {
            border-color: white;
            color: white;
        }

        /* Services */
        .services {
            padding: 4rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .service-card {
            padding: 2rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            text-align: center;
            transition: all 0.2s;
        }

        .service-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .service-icon {
            width: 60px;
            height: 60px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .service-card h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        /* Quote Form */
        .quote-section {
            background: var(--light);
            padding: 4rem 1rem;
        }

        .quote-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary), transparent 80%);
        }

        /* Contact */
        .contact {
            padding: 4rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .contact-item {
            text-align: center;
        }

        .contact-item strong {
            display: block;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .contact-item a {
            color: var(--primary);
            text-decoration: none;
            font-size: 1.25rem;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }

        footer p {
            opacity: 0.8;
        }

        /* Phone link styling */
        .phone-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .header-inner {
                justify-content: center;
                text-align: center;
            }

            .contact-info {
                flex-direction: column;
                gap: 1.5rem;
            }
        }
    </style>

    <!-- LocalBusiness Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "<?= htmlspecialchars($siteName) ?>",
        "description": "<?= htmlspecialchars($metaDescription) ?>",
        "url": "<?= $baseUrl ?>",
        "telephone": "<?= htmlspecialchars($phone) ?>",
        "email": "<?= htmlspecialchars($email) ?>",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "<?= htmlspecialchars($serviceArea) ?>"
        },
        "areaServed": "<?= htmlspecialchars($serviceArea) ?>"
    }
    </script>
</head>
<body>
    <header>
        <div class="header-inner">
            <a href="/" class="logo"><?= htmlspecialchars($siteName) ?></a>
            <div class="header-cta">
                <?php if ($phone): ?>
                <a href="tel:<?= htmlspecialchars($phone) ?>" class="btn btn-primary">
                    Call <?= htmlspecialchars($phone) ?>
                </a>
                <?php endif; ?>
                <a href="#quote" class="btn btn-outline">Get Quote</a>
            </div>
        </div>
    </header>

    <section class="hero">
        <h1><?= htmlspecialchars($siteName) ?></h1>
        <p><?= htmlspecialchars($tagline) ?>. Serving <?= htmlspecialchars($serviceArea) ?>.</p>
        <div class="hero-cta">
            <?php if ($phone): ?>
            <a href="tel:<?= htmlspecialchars($phone) ?>" class="btn btn-primary">Call Now</a>
            <?php endif; ?>
            <a href="#quote" class="btn btn-outline">Get Free Quote</a>
        </div>
    </section>

    <?php if (!empty($services)): ?>
    <section class="services">
        <div class="section-title">
            <h2>Our Services</h2>
            <p>Professional <?= strtolower($category) ?> services for your needs</p>
        </div>
        <div class="services-grid">
            <?php foreach ($services as $index => $service): ?>
            <div class="service-card">
                <div class="service-icon"><?= $index + 1 ?></div>
                <h3><?= htmlspecialchars($service) ?></h3>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="quote-section" id="quote">
        <div class="quote-form">
            <div class="section-title">
                <h2>Get a Free Quote</h2>
                <p>Fill out the form and we'll get back to you quickly</p>
            </div>
            <form action="/api/form-submit.php" method="POST">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email (optional)</label>
                    <input type="email" id="email" name="email">
                </div>
                <?php
                // Render dynamic fields based on business type
                $inputFields = config('estimates.input_fields', []);
                foreach ($inputFields as $field):
                    $fieldName = $field['name'];
                    $fieldLabel = $field['label'];
                    $fieldType = $field['type'];
                    $required = !empty($field['required']) ? 'required' : '';
                ?>
                <div class="form-group">
                    <label for="<?= $fieldName ?>"><?= htmlspecialchars($fieldLabel) ?></label>
                    <?php if ($fieldType === 'select' && !empty($field['options'])): ?>
                    <select id="<?= $fieldName ?>" name="<?= $fieldName ?>" <?= $required ?>>
                        <option value="">Select...</option>
                        <?php foreach ($field['options'] as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php elseif ($fieldType === 'textarea'): ?>
                    <textarea id="<?= $fieldName ?>" name="<?= $fieldName ?>" <?= $required ?>></textarea>
                    <?php elseif ($fieldType === 'checkbox'): ?>
                    <input type="checkbox" id="<?= $fieldName ?>" name="<?= $fieldName ?>" value="1">
                    <?php else: ?>
                    <input type="<?= $fieldType === 'number' ? 'number' : 'text' ?>" id="<?= $fieldName ?>" name="<?= $fieldName ?>" <?= $required ?>>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary" style="width: 100%; position: relative; z-index: 10;">Get Quote</button>
            </form>
        </div>
    </section>

    <section class="contact" id="contact">
        <div class="section-title">
            <h2>Contact Us</h2>
            <p>We're here to help</p>
        </div>
        <div class="contact-info">
            <?php if ($phone): ?>
            <div class="contact-item">
                <strong>Phone</strong>
                <a href="tel:<?= htmlspecialchars($phone) ?>"><?= htmlspecialchars($phone) ?></a>
            </div>
            <?php endif; ?>
            <?php if ($email): ?>
            <div class="contact-item">
                <strong>Email</strong>
                <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a>
            </div>
            <?php endif; ?>
            <?php if ($serviceArea): ?>
            <div class="contact-item">
                <strong>Service Area</strong>
                <span><?= htmlspecialchars($serviceArea) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</p>
    </footer>
</body>
</html>
