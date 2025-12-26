<?php
/**
 * A/B Analytics Dashboard View for CRM
 */

// Load analytics libraries
require_once CFG_PATH_TO_PLUGINS . '/../../../lib/ABTesting.php';
require_once CFG_PATH_TO_PLUGINS . '/../../../lib/CallTracking.php';

$ab = new ABTesting();
$callTracker = new CallTracking();

$experiments = $ab->getAllExperiments();
$callStats = $callTracker->getABCallStats();
$recentCalls = $callTracker->getRecentCalls(10);

// Calculate totals
$totalViews = 0;
$totalConversions = 0;
$totalCalls = 0;
$attributedCalls = 0;

foreach ($experiments as $exp) {
    $stats = $ab->getStats($exp['name']);
    foreach ($stats['variants'] ?? [] as $v) {
        $totalViews += $v['views'];
        $totalConversions += $v['conversions'];
    }
}

foreach ($callStats as $cs) {
    $totalCalls += $cs['total_calls'];
    if ($cs['ab_experiment']) {
        $attributedCalls += $cs['total_calls'];
    }
}
?>

<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h3><?php echo number_format($totalViews); ?></h3>
                <p class="mb-0">Page Views</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h3><?php echo number_format($totalConversions); ?></h3>
                <p class="mb-0">Web Conversions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h3><?php echo number_format($totalCalls); ?></h3>
                <p class="mb-0">Tracked Calls</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h3><?php echo number_format($attributedCalls); ?></h3>
                <p class="mb-0">A/B Attributed Calls</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">A/B Test Results</h5>
            </div>
            <div class="card-body">
                <?php if (empty($experiments)): ?>
                <p class="text-muted">No A/B experiments found.</p>
                <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Experiment</th>
                            <th>Variant</th>
                            <th>Views</th>
                            <th>Conversions</th>
                            <th>Rate</th>
                            <th>Calls</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($experiments as $exp):
                            $stats = $ab->getStats($exp['name']);
                            $expCallStats = array_filter($callStats, fn($c) => $c['ab_experiment'] === $exp['name']);

                            foreach ($stats['variants'] ?? [] as $variant):
                                $variantCalls = 0;
                                foreach ($expCallStats as $cs) {
                                    if ($cs['ab_variant'] === $variant['name']) {
                                        $variantCalls += $cs['total_calls'];
                                    }
                                }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exp['name']); ?></td>
                            <td>
                                <strong>Variant <?php echo htmlspecialchars($variant['name']); ?></strong>
                                <?php if ($stats['winner'] === $variant['name']): ?>
                                <span class="badge badge-success">Winner</span>
                                <?php elseif ($variant['is_control']): ?>
                                <span class="badge badge-secondary">Control</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($variant['views']); ?></td>
                            <td><?php echo number_format($variant['conversions']); ?></td>
                            <td>
                                <span class="<?php echo $variant['conversion_rate'] > 0.05 ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo number_format($variant['conversion_rate'] * 100, 2); ?>%
                                </span>
                            </td>
                            <td><?php echo number_format($variantCalls); ?></td>
                            <td>
                                <?php if ($exp['status'] === 'active'): ?>
                                <span class="badge badge-primary">Active</span>
                                <?php else: ?>
                                <span class="badge badge-secondary"><?php echo ucfirst($exp['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Calls</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentCalls)): ?>
                <p class="text-muted p-3">No calls tracked yet.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentCalls as $call): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong><?php echo htmlspecialchars(substr($call['caller_phone'], 0, 3) . '***' . substr($call['caller_phone'], -4)); ?></strong>
                                <?php if ($call['was_answered']): ?>
                                <span class="badge badge-success badge-sm">Answered</span>
                                <?php else: ?>
                                <span class="badge badge-warning badge-sm">Missed</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo date('M j, g:i a', strtotime($call['created_at'])); ?></small>
                        </div>
                        <?php if ($call['ab_experiment']): ?>
                        <small class="text-muted">
                            A/B: <?php echo htmlspecialchars($call['ab_experiment']); ?> / <?php echo htmlspecialchars($call['ab_variant']); ?>
                        </small>
                        <?php endif; ?>
                        <?php if ($call['lead_id']): ?>
                        <br><a href="<?php echo url_for('items/items', 'path=26-' . $call['lead_id']); ?>" class="btn btn-xs btn-info">View Lead #<?php echo $call['lead_id']; ?></a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body">
                <a href="/admin/analytics/" target="_blank" class="btn btn-block btn-outline-primary mb-2">Full Analytics Dashboard</a>
                <a href="/admin/ab-testing/" target="_blank" class="btn btn-block btn-outline-secondary">A/B Testing Admin</a>
            </div>
        </div>
    </div>
</div>
