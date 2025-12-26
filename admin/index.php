<?php
/**
 * Admin Dashboard - Password Protected
 * Username: kylewee
 * Password: rainonin
 */

session_start();

// Authentication check
$validUsername = 'kylewee';
$validPassword = 'rainonin';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin/');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    if ($_POST['username'] === $validUsername && $_POST['password'] === $validPassword) {
        $_SESSION['admin_authenticated'] = true;
        header('Location: /admin/');
        exit;
    } else {
        $loginError = 'Invalid credentials';
    }
}

// Check if authenticated
$isAuthenticated = !empty($_SESSION['admin_authenticated']);

// If not authenticated, show login form
if (!$isAuthenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Mechanics Saint Augustine</title>
        <style>
            body {
                font-family: system-ui, -apple-system, sans-serif;
                margin: 0;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #e2e8f0;
            }
            .login-box {
                background: rgba(15, 23, 42, 0.9);
                border: 1px solid rgba(148, 163, 184, 0.25);
                border-radius: 16px;
                padding: 48px;
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5);
                max-width: 400px;
                width: 100%;
            }
            h1 { margin: 0 0 8px; font-size: 1.8rem; }
            p { color: #94a3b8; margin: 0 0 32px; }
            label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: #cbd5e1;
            }
            input {
                width: 100%;
                padding: 12px;
                border: 1px solid rgba(148, 163, 184, 0.25);
                border-radius: 8px;
                background: rgba(15, 23, 42, 0.6);
                color: #e2e8f0;
                font-size: 1rem;
                margin-bottom: 20px;
                box-sizing: border-box;
            }
            input:focus {
                outline: none;
                border-color: #2563eb;
            }
            button {
                width: 100%;
                padding: 14px;
                background: #2563eb;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }
            button:hover {
                background: #1d4ed8;
            }
            .error {
                background: rgba(239, 68, 68, 0.15);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: #fca5a5;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>Admin Login</h1>
            <p>Mechanics Saint Augustine</p>
            <?php if (isset($loginError)): ?>
                <div class="error"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            <form method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Sign In</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// User is authenticated - show admin dashboard
$dbHost = 'localhost';
$dbUser = 'kylewee';
$dbPass = 'rainonin';
$dbName = 'rukovoditel';

// Helper function to execute shell commands safely
function execCommand($cmd) {
    $output = [];
    $returnVar = 0;
    exec($cmd . ' 2>&1', $output, $returnVar);
    return [
        'output' => implode("\n", $output),
        'exitCode' => $returnVar
    ];
}

// Get system stats
$voiceLogSize = file_exists(__DIR__ . '/../voice/voice.log') ? filesize(__DIR__ . '/../voice/voice.log') : 0;
$recordingsCount = count(glob(__DIR__ . '/../voice/recordings/*.mp3'));
$recordingsSize = 0;
foreach (glob(__DIR__ . '/../voice/recordings/*.mp3') as $file) {
    $recordingsSize += filesize($file);
}

// Get latest voice log entry
$latestCallLog = null;
if (file_exists(__DIR__ . '/../voice/voice.log')) {
    $logLines = file(__DIR__ . '/../voice/voice.log', FILE_IGNORE_NEW_LINES);
    if (!empty($logLines)) {
        $latestCallLog = json_decode(end($logLines), true);
    }
}

// Get recent CRM leads
$recentLeads = [];
try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if (!$mysqli->connect_error) {
        $result = $mysqli->query("
            SELECT id, field_219 as first_name, field_220 as last_name,
                   field_227 as phone, field_231 as year, field_232 as make, field_233 as model,
                   LEFT(field_230, 100) as notes_preview,
                   FROM_UNIXTIME(date_added) as created
            FROM app_entity_26
            ORDER BY id DESC
            LIMIT 10
        ");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recentLeads[] = $row;
            }
        }
        $mysqli->close();
    }
} catch (Exception $e) {
    // Silently fail
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mechanics Saint Augustine</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #0f172a;
            --panel: rgba(15, 23, 42, 0.75);
            --accent: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: rgba(148, 163, 184, 0.25);
        }
        * { box-sizing: border-box; }
        body {
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            margin: 0;
            background: radial-gradient(circle at top, rgba(37, 99, 235, 0.25), transparent 55%),
                        radial-gradient(circle at bottom, rgba(14, 116, 144, 0.2), transparent 60%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px 16px 80px;
        }
        header {
            max-width: 1400px;
            margin: 0 auto 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.4rem);
            letter-spacing: 0.02em;
        }
        .logout-btn {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.25);
        }
        main {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
        section {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(8px);
        }
        section.wide {
            grid-column: 1 / -1;
        }
        section h2 {
            margin: 0 0 16px;
            font-size: 1.25rem;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge.success { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        .badge.warning { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .badge.danger { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        pre {
            margin: 0;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(37, 99, 235, 0.25);
            padding: 12px;
            color: #cbd5f5;
            overflow-x: auto;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        code {
            font-family: "JetBrains Mono", "Courier New", monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        th {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 2px solid var(--border);
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        td {
            padding: 12px 8px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        tr:hover {
            background: rgba(37, 99, 235, 0.05);
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .stat {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .command-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            margin-top: 12px;
        }
        .command-box h3 {
            margin: 0 0 8px;
            font-size: 0.95rem;
            color: var(--text);
        }
        .notes {
            color: var(--muted);
            font-size: 0.85rem;
            margin-top: 8px;
            line-height: 1.5;
        }
        .refresh-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .refresh-btn:hover {
            background: #1d4ed8;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header>
        <h1>🔧 Admin Dashboard</h1>
        <a href="?logout" class="logout-btn">Logout</a>
    </header>

    <main>
        <!-- System Health -->
        <section>
            <h2>System Health</h2>
            <div class="stat-grid">
                <div class="stat">
                    <div class="stat-value"><?= number_format($voiceLogSize / 1024, 1) ?>KB</div>
                    <div class="stat-label">Voice Log Size</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?= $recordingsCount ?></div>
                    <div class="stat-label">Recordings</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?= number_format($recordingsSize / 1024 / 1024, 1) ?>MB</div>
                    <div class="stat-label">Storage Used</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?= count($recentLeads) ?></div>
                    <div class="stat-label">Recent Leads</div>
                </div>
            </div>
        </section>

        <!-- Latest Call -->
        <section>
            <h2>Latest Call <span class="badge <?= $latestCallLog ? 'success' : 'warning' ?>"><?= $latestCallLog ? 'Active' : 'No Data' ?></span></h2>
            <?php if ($latestCallLog): ?>
                <div class="command-box">
                    <h3>Call Details</h3>
                    <table>
                        <tr>
                            <td><strong>Time:</strong></td>
                            <td><?= htmlspecialchars($latestCallLog['ts'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>From:</strong></td>
                            <td><?= htmlspecialchars($latestCallLog['summary']['from'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Duration:</strong></td>
                            <td><?= htmlspecialchars($latestCallLog['summary']['duration'] ?? '0') ?>s</td>
                        </tr>
                        <tr>
                            <td><strong>Recording:</strong></td>
                            <td><?= !empty($latestCallLog['recording_saved']) ? '<span class="badge success">Saved</span>' : '<span class="badge warning">Not Saved</span>' ?></td>
                        </tr>
                        <tr>
                            <td><strong>CRM:</strong></td>
                            <td><?= !empty($latestCallLog['crm_result']['ok']) || !empty($latestCallLog['crm_result']['fallback']['ok']) ? '<span class="badge success">Created</span>' : '<span class="badge danger">Failed</span>' ?></td>
                        </tr>
                    </table>
                </div>
            <?php else: ?>
                <p class="notes">No calls logged yet. Make a test call to see data here.</p>
            <?php endif; ?>
        </section>

        <!-- Recent CRM Leads -->
        <section class="wide">
            <h2>Recent CRM Leads (Last 10)</h2>
            <?php if (!empty($recentLeads)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Vehicle</th>
                            <th>Notes Preview</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLeads as $lead): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($lead['id']) ?></td>
                                <td><?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) ?></td>
                                <td><?= htmlspecialchars($lead['phone']) ?></td>
                                <td><?= htmlspecialchars(($lead['year'] ?: '') . ' ' . ($lead['make'] ?: '') . ' ' . ($lead['model'] ?: '')) ?></td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($lead['notes_preview']) ?></td>
                                <td><?= htmlspecialchars($lead['created']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="notes">No CRM leads found. Database connection may be down.</p>
            <?php endif; ?>
        </section>

        <!-- Useful Commands -->
        <section class="wide">
            <h2>📝 Monitoring Commands</h2>

            <div class="command-box">
                <h3>View Latest Log Entry</h3>
                <pre><code>tail -1 voice/voice.log | jq '.recording_saved, .crm_result, .auto_estimate'</code></pre>
                <p class="notes">Shows if recording was saved, CRM lead created, and auto-estimate generated.</p>
            </div>

            <div class="command-box">
                <h3>Check Saved Recordings</h3>
                <pre><code>ls -lah voice/recordings/</code></pre>
                <p class="notes">Lists all saved MP3 recordings with file sizes.</p>
            </div>

            <div class="command-box">
                <h3>View Latest CRM Leads (SQL)</h3>
                <pre><code>mysql -u kylewee -p'rainonin' rukovoditel -e "SELECT id, field_219, field_227, field_230 FROM app_entity_26 ORDER BY id DESC LIMIT 5;"</code></pre>
                <p class="notes">Direct database query for latest 5 leads with notes.</p>
            </div>

            <div class="command-box">
                <h3>Monitor Voice Log (Live)</h3>
                <pre><code>tail -f voice/voice.log | jq '.'</code></pre>
                <p class="notes">Watch incoming calls in real-time with formatted JSON output.</p>
            </div>

            <div class="command-box">
                <h3>Check System Services</h3>
                <pre><code>sudo systemctl status php8.3-fpm caddy mysql</code></pre>
                <p class="notes">Verify all critical services are running.</p>
            </div>

            <div class="command-box">
                <h3>Test CRM API Connection</h3>
                <pre><code>php -r "require 'api/.env.local.php'; \$ch = curl_init(CRM_API_URL); curl_setopt_array(\$ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['key' => CRM_API_KEY, 'username' => CRM_USERNAME, 'password' => CRM_PASSWORD, 'action' => 'select', 'entity_id' => CRM_LEADS_ENTITY_ID])]); \$result = curl_exec(\$ch); echo \$result;"</code></pre>
                <p class="notes">Quick test to verify CRM API credentials are working.</p>
            </div>
        </section>

        <!-- Quick Links -->
        <section class="wide">
            <h2>🔗 Quick Links</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
                <a href="/estimate.php" target="_blank" style="display: block; padding: 12px; background: linear-gradient(135deg, rgba(37, 99, 235, 0.3), rgba(29, 78, 216, 0.3)); border: 2px solid var(--accent); border-radius: 8px; font-weight: 600;">💰 Quick Estimate (Admin)</a>
                <a href="/get-estimate.php" target="_blank" style="display: block; padding: 12px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(5, 150, 105, 0.3)); border: 2px solid var(--success); border-radius: 8px; font-weight: 600;">🌐 Customer Estimate Page</a>
                <a href="/admin/flow/" style="display: block; padding: 12px; background: linear-gradient(135deg, rgba(251, 191, 36, 0.3), rgba(245, 158, 11, 0.3)); border: 2px solid #fbbf24; border-radius: 8px; font-weight: 600;">📱 1-2-3-4-5 Customer Flow <span style="font-size: 0.75rem; background: #fbbf24; color: #78350f; padding: 0.25rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">NEW</span></a>
                <a href="https://mechanicstaugustine.com/crm/" target="_blank" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">📊 Rukovoditel CRM</a>
                <a href="/admin/leads_approval.php" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">✅ Lead Approvals</a>
                <a href="/admin/dispatch.php" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">🚗 Dispatch Board</a>
                <a href="/admin/parts_orders.php" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">🔧 Parts Orders</a>
                <a href="https://mechanicstaugustine.com/voice/recording_callback.php?action=recordings&token=msarec-2b7c9f1a5d4e" target="_blank" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">🎙️ Call Recordings</a>
                <a href="/" target="_blank" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">🌐 Public Website</a>
                <a href="/admin/ab-testing/" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">📊 A/B Testing Dashboard</a>
                <a href="/services/diagnostics/" target="_blank" style="display: block; padding: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px;">🔍 Diagnostics Page (A/B)</a>
            </div>
        </section>
    </main>

    <footer style="max-width: 1400px; margin: 48px auto 0; text-align: center; color: var(--muted); font-size: 0.85rem;">
        <p>Logged in as <strong>kylewee</strong> · <a href="?logout">Logout</a></p>
        <p style="margin-top: 8px;">Need to add features? Edit <code>/admin/index.php</code> and reload this page.</p>
    </footer>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
