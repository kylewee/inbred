<?php
/**
 * Buyer Portal - Dashboard
 * Main page showing leads, balance, and stats
 */

require_once __DIR__ . '/BuyerAuth.php';
$auth = new BuyerAuth();
$buyer = $auth->requireAuth();
$db = $auth->getDb();

// Get stats
$stats = [
    'balance' => $buyer['balance'] / 100, // Convert cents to dollars
    'leads_total' => 0,
    'leads_this_week' => 0,
    'leads_today' => 0,
];

// Total leads
$stmt = $db->prepare("SELECT COUNT(*) as count FROM buyer_leads WHERE buyer_id = :id");
$stmt->bindValue(':id', $buyer['id']);
$stats['leads_total'] = $stmt->execute()->fetchArray()['count'] ?? 0;

// Leads this week
$stmt = $db->prepare("SELECT COUNT(*) as count FROM buyer_leads WHERE buyer_id = :id AND created_at >= date('now', '-7 days')");
$stmt->bindValue(':id', $buyer['id']);
$stats['leads_this_week'] = $stmt->execute()->fetchArray()['count'] ?? 0;

// Leads today
$stmt = $db->prepare("SELECT COUNT(*) as count FROM buyer_leads WHERE buyer_id = :id AND created_at >= date('now')");
$stmt->bindValue(':id', $buyer['id']);
$stats['leads_today'] = $stmt->execute()->fetchArray()['count'] ?? 0;

// Get recent leads
$stmt = $db->prepare("
    SELECT * FROM buyer_leads
    WHERE buyer_id = :id
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->bindValue(':id', $buyer['id']);
$result = $stmt->execute();
$leads = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $row['lead_data'] = json_decode($row['lead_data'], true);
    $leads[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Portal - Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2a4a75 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 20px; }
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .balance {
            background: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
        }
        .balance strong { font-size: 18px; }
        .header a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 14px;
        }
        .header a:hover { color: white; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-card h3 {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #1e3a5f;
        }
        .stat-card.balance-card .value { color: #27ae60; }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-header h2 {
            font-size: 20px;
            color: #333;
        }
        .btn {
            padding: 10px 20px;
            background: #1e3a5f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #2a4a75; }
        .btn-secondary {
            background: white;
            color: #1e3a5f;
            border: 2px solid #1e3a5f;
        }
        .btn-secondary:hover { background: #f0f4f8; }

        .leads-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td { font-size: 14px; }
        tr:hover { background: #fafbfc; }
        tr:last-child td { border-bottom: none; }

        .lead-name { font-weight: 600; color: #333; }
        .lead-contact { color: #666; font-size: 13px; }
        .lead-source {
            display: inline-block;
            padding: 4px 10px;
            background: #e8f4fd;
            color: #1e3a5f;
            border-radius: 4px;
            font-size: 12px;
        }
        .lead-price { font-weight: 600; color: #27ae60; }
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status.delivered { background: #e8f8ef; color: #27ae60; }
        .status.pending { background: #fff8e6; color: #f5a623; }
        .status.returned { background: #fee; color: #e74c3c; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state h3 { font-size: 18px; margin-bottom: 10px; color: #333; }

        .nav-links {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .nav-links a {
            color: #666;
            text-decoration: none;
            padding-bottom: 10px;
            border-bottom: 2px solid transparent;
        }
        .nav-links a:hover, .nav-links a.active {
            color: #1e3a5f;
            border-bottom-color: #1e3a5f;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Buyer Portal</h1>
        <div class="header-right">
            <div class="balance">
                Balance: <strong>$<?= number_format($stats['balance'], 2) ?></strong>
            </div>
            <a href="/buyer/add-funds.php">Add Funds</a>
            <a href="/buyer/logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="nav-links">
            <a href="/buyer/" class="active">Dashboard</a>
            <a href="/buyer/leads.php">All Leads</a>
            <a href="/buyer/transactions.php">Transactions</a>
            <a href="/buyer/settings.php">Settings</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card balance-card">
                <h3>Balance</h3>
                <div class="value">$<?= number_format($stats['balance'], 2) ?></div>
            </div>
            <div class="stat-card">
                <h3>Leads Today</h3>
                <div class="value"><?= $stats['leads_today'] ?></div>
            </div>
            <div class="stat-card">
                <h3>This Week</h3>
                <div class="value"><?= $stats['leads_this_week'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Leads</h3>
                <div class="value"><?= $stats['leads_total'] ?></div>
            </div>
        </div>

        <div class="section-header">
            <h2>Recent Leads</h2>
            <div>
                <a href="/buyer/export.php" class="btn btn-secondary">Export CSV</a>
            </div>
        </div>

        <div class="leads-table">
            <?php if (empty($leads)): ?>
                <div class="empty-state">
                    <h3>No leads yet</h3>
                    <p>Leads will appear here as they're assigned to you.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Source</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <?php $data = $lead['lead_data'] ?? []; ?>
                            <tr>
                                <td>
                                    <div class="lead-name"><?= htmlspecialchars($data['first_name'] ?? '') ?> <?= htmlspecialchars($data['last_name'] ?? '') ?></div>
                                    <?php if (!empty($data['company'])): ?>
                                        <div class="lead-contact"><?= htmlspecialchars($data['company']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($data['phone'] ?? '') ?></div>
                                    <div class="lead-contact"><?= htmlspecialchars($data['email'] ?? '') ?></div>
                                </td>
                                <td>
                                    <span class="lead-source"><?= htmlspecialchars($lead['site_domain'] ?? 'Direct') ?></span>
                                </td>
                                <td class="lead-price">$<?= number_format($lead['price'] / 100, 2) ?></td>
                                <td>
                                    <span class="status <?= $lead['status'] ?>"><?= ucfirst($lead['status']) ?></span>
                                </td>
                                <td><?= date('M j, g:i a', strtotime($lead['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
