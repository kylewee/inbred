<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_approved ? 'Quote Approved' : 'Quote Declined' ?> - Mechanics Saint Augustine</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 20px;
        }
        .confirmation-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .icon-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 48px;
            color: white;
        }
        .icon-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }
        .icon-declined {
            background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
        }
        h1 {
            color: #2d3748;
            margin: 0 0 16px;
            font-size: 32px;
            font-weight: 700;
        }
        p {
            color: #718096;
            margin: 0 0 32px;
            font-size: 16px;
            line-height: 1.6;
        }
        .details-box {
            background: #f7fafc;
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #718096;
            font-weight: 600;
            font-size: 14px;
        }
        .detail-value {
            color: #2d3748;
            font-weight: 500;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .help-text {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
            font-size: 14px;
        }
        .help-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .help-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php if ($is_approved): ?>
            <div class="icon-circle icon-success">
                <i class="fa fa-check"></i>
            </div>
            <h1>Quote Approved!</h1>
            <p>
                Thank you for approving your quote. We'll contact you shortly to schedule your service.
            </p>
        <?php else: ?>
            <div class="icon-circle icon-declined">
                <i class="fa fa-times"></i>
            </div>
            <h1>Quote Declined</h1>
            <p>
                We understand. If you change your mind or have any questions, please don't hesitate to call us.
            </p>
        <?php endif; ?>

        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">Customer</span>
                <span class="detail-value"><?= htmlspecialchars($customer_name) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value"><?= htmlspecialchars($phone) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value" style="color: <?= $is_approved ? '#38a169' : '#e53e3e' ?>; font-weight: 700;">
                    <?= $is_approved ? 'Approved' : 'Declined' ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value"><?= date('F j, Y g:i A') ?></span>
            </div>
        </div>

        <?php if ($is_approved): ?>
            <p style="background: #ebf8ff; border: 1px solid #90cdf4; color: #2c5282; padding: 16px; border-radius: 8px; font-size: 14px; margin: 24px 0;">
                <i class="fa fa-info-circle"></i>
                <strong>Next Steps:</strong> We'll call you at <?= htmlspecialchars($phone) ?> within 24 hours to schedule your appointment.
            </p>
        <?php endif; ?>

        <a href="<?= url_for('customer_portal/quote/view', 'id=' . $lead_id) ?>" class="btn">
            <i class="fa fa-arrow-left"></i> Back to Quote
        </a>

        <div class="help-text">
            <p>
                Need immediate assistance?<br>
                Call us at <a href="tel:+19047066669">(904) 706-6669</a>
            </p>
        </div>
    </div>

    <?php if ($is_approved): ?>
    <script>
        // Optional: Auto-redirect after 10 seconds
        setTimeout(function() {
            // Uncomment to enable auto-redirect
            // window.location.href = '<?= url_for('customer_portal/quote/view', 'id=' . $lead_id) ?>';
        }, 10000);
    </script>
    <?php endif; ?>
</body>
</html>
