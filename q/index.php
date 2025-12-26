<?php
/**
 * Mobile Quote Page
 * /q/{JOB_ID}
 * /q/{JOB_ID}/call - triggers AI callback
 */

require_once __DIR__ . '/../lib/CustomerFlow/Flow.php';

$flow = new CustomerFlow\Flow();

// Parse URL
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $path);
$jobId = $parts[1] ?? '';
$action = $parts[2] ?? '';

if (!$jobId) {
    http_response_code(404);
    exit('Not found');
}

// Handle AI call request
if ($action === 'call') {
    $result = $flow->requestAICall($jobId);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Handle approve action
if ($action === 'approve' || isset($_POST['approve'])) {
    $result = $flow->approve($jobId);
    if ($result['success']) {
        header('Location: /q/' . $jobId . '?approved=1');
        exit;
    }
}

// Get job data
$job = $flow->getJob($jobId);
if (!$job) {
    http_response_code(404);
    exit('Quote not found');
}

// Mark as viewed
$flow->markViewed($jobId);

$approved = $job['approved_at'] || isset($_GET['approved']);
$total = number_format($job['total'], 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#fbbf24">
    <title>Quote - EZ Mobile Mechanic</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,system-ui,sans-serif;background:#f5f5f4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
        .card{background:#fef08a;border-radius:4px;padding:1.5rem;max-width:320px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.15),0 0 0 1px rgba(0,0,0,.05);transform:rotate(-1deg)}
        .logo{font-weight:700;font-size:1.1rem;color:#78350f;margin-bottom:.25rem}
        .id{font-size:.7rem;color:#92400e;font-family:monospace;margin-bottom:1rem}
        .vehicle{font-size:1rem;font-weight:600;color:#78350f;margin-bottom:.75rem;padding-bottom:.75rem;border-bottom:2px dashed #d97706}
        .services{margin-bottom:1rem}
        .service{display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#78350f}
        .total{display:flex;justify-content:space-between;padding-top:.75rem;border-top:2px solid #d97706;font-weight:700;font-size:1.25rem;color:#78350f}
        .actions{margin-top:1.25rem;display:flex;flex-direction:column;gap:.5rem}
        .btn{display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.875rem;border-radius:6px;font-weight:600;font-size:.95rem;text-decoration:none;border:none;cursor:pointer;transition:transform .1s}
        .btn:active{transform:scale(.98)}
        .btn-call{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff}
        .btn-approve{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff}
        .btn-approved{background:#86efac;color:#166534;cursor:default}
        .btn-phone{background:#fff;color:#78350f;border:2px solid #d97706}
        .footer{text-align:center;margin-top:1rem;font-size:.7rem;color:#92400e}
        .approved-badge{background:#22c55e;color:#fff;padding:.25rem .75rem;border-radius:20px;font-size:.75rem;font-weight:600;display:inline-block;margin-top:.5rem}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">EZ Mobile Mechanic</div>
        <div class="id">#<?= htmlspecialchars($jobId) ?></div>

        <div class="vehicle"><?= htmlspecialchars($job['vehicle'] ?: 'Vehicle Service') ?></div>

        <div class="services">
            <?php foreach ($job['services'] as $svc): ?>
            <div class="service">
                <span><?= htmlspecialchars($svc['name'] ?? $svc) ?></span>
                <?php if (isset($svc['price'])): ?>
                <span>$<?= number_format($svc['price'], 2) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="total">
            <span>Total</span>
            <span>$<?= $total ?></span>
        </div>

        <?php if ($approved): ?>
        <div style="text-align:center">
            <span class="approved-badge">Approved</span>
            <p style="margin-top:.75rem;font-size:.85rem;color:#78350f">We'll contact you to schedule.</p>
        </div>
        <?php else: ?>
        <div class="actions">
            <button class="btn btn-call" onclick="callMe()">
                <span>🔊</span> Hear AI Explain
            </button>
            <form method="post" style="margin:0">
                <button type="submit" name="approve" value="1" class="btn btn-approve" style="width:100%">
                    ✓ Approve & Book
                </button>
            </form>
            <a href="tel:+19042175152" class="btn btn-phone">
                📞 Call (904) 217-5152
            </a>
        </div>
        <?php endif; ?>

        <div class="footer">
            "Proving that mechanics can be morally correct!"
        </div>
    </div>

    <script>
    function callMe() {
        fetch('/q/<?= $jobId ?>/call', {method:'POST'})
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    alert('Calling you now! Answer from (904) 706-6669');
                } else {
                    alert('Error: ' + (d.error || 'Unknown'));
                }
            });
    }
    </script>
</body>
</html>
