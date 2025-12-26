<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Quote - Mechanics Saint Augustine</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 0;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 32px;
            text-align: center;
        }
        .card-header h1 {
            margin: 0 0 8px;
            font-size: 32px;
            font-weight: 700;
        }
        .card-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .card-body {
            padding: 32px;
        }
        .section {
            margin-bottom: 32px;
        }
        .section:last-child {
            margin-bottom: 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            margin: 0 0 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f7fafc;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #718096;
            font-weight: 600;
            font-size: 14px;
        }
        .info-value {
            color: #2d3748;
            font-weight: 500;
            text-align: right;
        }
        .estimate-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .estimate-table th {
            background: #f7fafc;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .estimate-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .estimate-table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background: #f7fafc;
            font-weight: 700;
            font-size: 18px;
        }
        .total-row td {
            padding: 16px 12px !important;
        }
        .price {
            color: #667eea;
            font-weight: 700;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-approved {
            background: #c6f6d5;
            color: #22543d;
        }
        .status-declined {
            background: #fed7d7;
            color: #742a2a;
        }
        .status-pending {
            background: #fef5e7;
            color: #975a16;
        }
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }
        .btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(72, 187, 120, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
            color: white;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(252, 129, 129, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .help-text {
            text-align: center;
            margin-top: 24px;
            color: white;
            font-size: 14px;
        }
        .help-text a {
            color: white;
            font-weight: 600;
            text-decoration: underline;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-info {
            background: #ebf8ff;
            border: 1px solid #90cdf4;
            color: #2c5282;
        }
        @media (max-width: 600px) {
            .action-buttons {
                flex-direction: column;
            }
            .card-body {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Your Service Quote</h1>
                <p>Review your estimate and approve to schedule service</p>
            </div>

            <div class="card-body">
                <!-- Customer Information -->
                <div class="section">
                    <h2 class="section-title">Customer Information</h2>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= htmlspecialchars($customer_name) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= htmlspecialchars($phone) ?></span>
                    </div>
                    <?php if (!empty($vehicle)): ?>
                    <div class="info-row">
                        <span class="info-label">Vehicle</span>
                        <span class="info-value"><?= htmlspecialchars($vehicle) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($lead['address'])): ?>
                    <div class="info-row">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?= htmlspecialchars($lead['address']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Service Description -->
                <?php if (!empty($service_description)): ?>
                <div class="section">
                    <h2 class="section-title">Service Needed</h2>
                    <p style="color: #4a5568; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($service_description)) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Estimate -->
                <?php if ($estimate): ?>
                <div class="section">
                    <h2 class="section-title">Estimate Breakdown</h2>

                    <?php if (isset($estimate['estimates']) && is_array($estimate['estimates'])): ?>
                        <!-- Detailed estimate with line items -->
                        <table class="estimate-table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Labor</th>
                                    <th>Parts</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grand_total = 0;
                                foreach ($estimate['estimates'] as $item):
                                    $item_total = ($item['labor_cost'] ?? 0) + ($item['parts_cost'] ?? 0);
                                    $grand_total += $item_total;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['repair'] ?? 'Service') ?></td>
                                    <td class="price">$<?= number_format($item['labor_cost'] ?? 0, 2) ?></td>
                                    <td class="price">$<?= number_format($item['parts_cost'] ?? 0, 2) ?></td>
                                    <td class="price">$<?= number_format($item_total, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="3">Total Estimate</td>
                                    <td class="price">$<?= number_format($estimate['grand_total'] ?? $grand_total, 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Simple estimate -->
                        <table class="estimate-table">
                            <tbody>
                                <?php if (isset($estimate['labor_cost']) && $estimate['labor_cost'] > 0): ?>
                                <tr>
                                    <td>Labor</td>
                                    <td class="price" style="text-align: right;">$<?= number_format($estimate['labor_cost'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($estimate['parts_cost']) && $estimate['parts_cost'] > 0): ?>
                                <tr>
                                    <td>Parts</td>
                                    <td class="price" style="text-align: right;">$<?= number_format($estimate['parts_cost'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="total-row">
                                    <td>Total Estimate</td>
                                    <td class="price" style="text-align: right;">$<?= number_format($estimate['total'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <i class="fa fa-info-circle" style="font-size: 20px;"></i>
                        <div>
                            <strong>Please Note:</strong> This is an estimate. Final price may vary based on inspection and additional repairs needed.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Appointment (if scheduled) -->
                <?php if ($dispatch_job): ?>
                <div class="section">
                    <h2 class="section-title">Scheduled Appointment</h2>
                    <div class="info-row">
                        <span class="info-label">Date & Time</span>
                        <span class="info-value">
                            <?= date('l, F j, Y', strtotime($dispatch_job['job_date'])) ?>
                            <?= !empty($dispatch_job['arrival_window']) ? ' - ' . htmlspecialchars($dispatch_job['arrival_window']) : '' ?>
                        </span>
                    </div>
                    <?php if (!empty($dispatch_job['technician'])): ?>
                    <div class="info-row">
                        <span class="info-label">Technician</span>
                        <span class="info-value"><?= htmlspecialchars($dispatch_job['technician']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-badge status-pending">
                                <?= htmlspecialchars(ucfirst($dispatch_job['status'])) ?>
                            </span>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status & Actions -->
                <div class="section">
                    <?php if ($is_approved): ?>
                        <div class="alert" style="background: #c6f6d5; border-color: #68d391; color: #22543d;">
                            <i class="fa fa-check-circle" style="font-size: 20px;"></i>
                            <div>
                                <strong>Quote Approved!</strong><br>
                                We'll contact you shortly to schedule your service.
                            </div>
                        </div>
                    <?php elseif ($is_declined): ?>
                        <div class="alert" style="background: #fed7d7; border-color: #fc8181; color: #742a2a;">
                            <i class="fa fa-times-circle" style="font-size: 20px;"></i>
                            <div>
                                <strong>Quote Declined</strong><br>
                                If you change your mind or have questions, please call us.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="action-buttons">
                            <a href="<?= url_for('customer_portal/quote/approve', 'id=' . $lead_id . '&action=approve') ?>"
                               class="btn btn-success"
                               onclick="return confirm('Approve this quote? We will contact you to schedule service.')">
                                <i class="fa fa-check"></i>
                                Approve Quote
                            </a>
                            <a href="<?= url_for('customer_portal/quote/approve', 'id=' . $lead_id . '&action=decline') ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Decline this quote? You can always call us to discuss.')">
                                <i class="fa fa-times"></i>
                                Decline
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="help-text">
            <p>
                Questions about your quote?<br>
                Call us at <a href="tel:+19047066669">(904) 706-6669</a>
            </p>
        </div>
    </div>
</body>
</html>
