<?php
/**
 * Mobile Quote Viewer
 * Displays post-it sized quote optimized for phone screens
 */

require_once __DIR__ . '/../lib/QuoteSMS.php';

$quoteSMS = new QuoteSMS();

// Get quote ID from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$quoteId = $parts[1] ?? '';
$action = $parts[2] ?? '';

if (!$quoteId) {
    http_response_code(404);
    exit('Quote not found');
}

// Handle AI explanation request
if ($action === 'explain') {
    $quote = $quoteSMS->getQuote($quoteId);
    if (!$quote) {
        http_response_code(404);
        exit('Quote not found');
    }

    $result = $quoteSMS->requestAIExplanation($quoteId, $quote['customer_phone']);

    if ($result['success']) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>AI Calling You...</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                }
                .container {
                    background: white;
                    border-radius: 20px;
                    padding: 2rem;
                    text-align: center;
                    max-width: 400px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    animation: fadeIn 0.5s;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.9); }
                    to { opacity: 1; transform: scale(1); }
                }
                .phone-icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                    animation: ring 1s infinite;
                }
                @keyframes ring {
                    0%, 100% { transform: rotate(-15deg); }
                    50% { transform: rotate(15deg); }
                }
                h1 {
                    font-size: 1.5rem;
                    margin-bottom: 0.5rem;
                    color: #1a202c;
                }
                p {
                    color: #4a5568;
                    line-height: 1.6;
                    margin-bottom: 1.5rem;
                }
                .back-btn {
                    display: inline-block;
                    background: #667eea;
                    color: white;
                    padding: 0.75rem 1.5rem;
                    border-radius: 10px;
                    text-decoration: none;
                    margin-top: 1rem;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="phone-icon">📞</div>
                <h1>Calling You Now!</h1>
                <p>Our AI assistant is calling to explain your quote in detail. Answer your phone in a moment.</p>
                <p style="font-size: 0.9rem; color: #718096;">The call will come from<br><strong>(904) 706-6669</strong></p>
                <a href="/quote/<?php echo htmlspecialchars($quoteId); ?>" class="back-btn">Back to Quote</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        echo "Error: " . htmlspecialchars($result['error'] ?? 'Unknown error');
        exit;
    }
}

// Get quote data
$quote = $quoteSMS->getQuote($quoteId);

if (!$quote) {
    http_response_code(404);
    exit('Quote not found');
}

// Mark as viewed
$quoteSMS->markViewed($quoteId);

// Parse services
$services = $quote['services'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2563eb">
    <title>Quote <?php echo htmlspecialchars($quoteId); ?> - EZ Mobile Mechanic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            padding: 1rem;
            line-height: 1.5;
        }

        /* Post-it note style */
        .quote-card {
            background: linear-gradient(135deg, #fef08a 0%, #fde047 100%);
            border-radius: 8px;
            padding: 1.5rem;
            max-width: 400px;
            margin: 0 auto;
            box-shadow:
                0 1px 3px rgba(0,0,0,0.1),
                0 10px 20px rgba(0,0,0,0.15);
            transform: rotate(-1deg);
            border: 1px solid #fbbf24;
        }

        .header {
            text-align: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px dashed #ca8a04;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #78350f;
        }

        .quote-id {
            font-size: 0.75rem;
            color: #92400e;
            font-family: 'Courier New', monospace;
        }

        .vehicle {
            font-size: 1.1rem;
            font-weight: 600;
            color: #78350f;
            margin-bottom: 0.75rem;
        }

        .services {
            margin-bottom: 1rem;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(202, 138, 4, 0.2);
            font-size: 0.9rem;
        }

        .service-name {
            color: #78350f;
            flex: 1;
        }

        .service-price {
            font-weight: 600;
            color: #78350f;
            margin-left: 0.5rem;
        }

        .total-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #ca8a04;
            text-align: right;
        }

        .total-label {
            font-size: 1rem;
            color: #92400e;
            font-weight: 500;
        }

        .total-price {
            font-size: 2rem;
            font-weight: 700;
            color: #78350f;
        }

        .buttons {
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-ai {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        }

        .btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-call {
            background: white;
            color: #78350f;
            border: 2px solid #ca8a04;
        }

        .footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .status-approved {
            background: #10b981;
            color: white;
        }

        .status-sent {
            background: #f59e0b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="quote-card">
        <div class="header">
            <div class="logo">EZ Mobile Mechanic</div>
            <div class="quote-id">Quote #<?php echo htmlspecialchars($quoteId); ?></div>
            <?php if ($quote['status'] === 'approved'): ?>
            <span class="status-badge status-approved">✓ Approved</span>
            <?php else: ?>
            <span class="status-badge status-sent">Pending</span>
            <?php endif; ?>
        </div>

        <div class="vehicle">
            <?php echo htmlspecialchars($quote['vehicle']); ?>
        </div>

        <div class="services">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                <div class="service-item">
                    <div class="service-name"><?php echo htmlspecialchars($service['name'] ?? $service); ?></div>
                    <?php if (isset($service['price'])): ?>
                    <div class="service-price">$<?php echo number_format($service['price'], 2); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="service-item">
                    <div class="service-name"><?php echo htmlspecialchars($quote['breakdown'] ?: 'Service quote'); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="total-section">
            <div class="total-label">Total</div>
            <div class="total-price">$<?php echo number_format($quote['total_price'], 2); ?></div>
        </div>

        <div class="buttons">
            <a href="/quote/<?php echo htmlspecialchars($quoteId); ?>/explain" class="btn btn-ai">
                🔊 Hear AI Explain This Quote
            </a>

            <?php if ($quote['status'] !== 'approved'): ?>
            <button class="btn btn-approve" onclick="approveQuote()">
                ✓ Approve & Book Service
            </button>
            <?php else: ?>
            <div class="btn btn-approve" style="opacity: 0.7; cursor: default;">
                ✓ Quote Approved - We'll Contact You
            </div>
            <?php endif; ?>

            <a href="tel:+19042175152" class="btn btn-call">
                📞 Call (904) 217-5152
            </a>
        </div>

        <div class="footer">
            EZ Mobile Mechanic<br>
            "Proving that mechanics can be morally correct!"
        </div>
    </div>

    <script>
        function approveQuote() {
            if (confirm('Approve this quote and schedule service?')) {
                fetch('/api/quote-approve.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        quote_id: '<?php echo htmlspecialchars($quoteId); ?>'
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✓ Quote approved! We\'ll contact you shortly to schedule.');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => alert('Error approving quote'));
            }
        }
    </script>
</body>
</html>
