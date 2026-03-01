<?php
require_once __DIR__ . '/../includes/auth.php';

$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    die("Project ID not specified.");
}

// 1. Fetch Project Header
$stmt = $pdo->prepare("
    SELECT p.*, c.client_name, c.contact_name, c.email, w.name as category_name, w.color_hex
    FROM project_headers p
    JOIN customers c ON p.customer_id = c.id
    JOIN work_categories w ON p.work_category_id = w.id
    WHERE p.id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    die("Project not found.");
}

// 2. Fetch Line Items
$stmt = $pdo->prepare("
    SELECT li.*, s.name as supplier_name, ac.name as account_name
    FROM line_items li
    LEFT JOIN suppliers s ON li.supplier_id = s.id
    LEFT JOIN account_codes ac ON li.category_id = ac.id
    WHERE li.project_id = ?
    ORDER BY li.transaction_date DESC, li.id DESC
");
$stmt->execute([$projectId]);
$lineItems = $stmt->fetchAll();

// Calculate Financials
$revenue = 0;
$cost = 0;
foreach ($lineItems as $item) {
    if ($item['item_type'] === 'INV') $revenue += $item['amount'];
    if ($item['item_type'] === 'JOB') $cost += $item['amount'];
}
$profit = $revenue - $cost;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project: <?= htmlspecialchars($project['name']) ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f9; max-width: 1000px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { margin: 0 0 10px 0; }
        .meta { color: #666; font-size: 0.9em; }
        
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-val { font-size: 1.5em; font-weight: bold; font-family: monospace; }
        .profit { color: green; }
        .loss { color: red; }

        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #eee; }
        .money { font-family: monospace; text-align: right; }
        
        /* Dyslexia-friendly Color Coding */
        .type-QUOTE { border-left: 6px solid #2196F3; background-color: #e3f2fd; } /* Blue */
        .type-JOB   { border-left: 6px solid #ff9800; background-color: #fff3e0; } /* Yellow */
        .type-INV   { border-left: 6px solid #4caf50; background-color: #e8f5e9; } /* Green */
        
        .pill { padding: 4px 8px; border-radius: 12px; color: white; font-size: 0.85em; font-weight: bold; display: inline-block; }
        .back-link { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #333; }
    </style>
</head>
<body>

    <a href="index.php" class="back-link">← Back to Dashboard</a>

    <div class="header">
        <div style="float:right">
            <span class="pill" style="background-color: <?= $project['color_hex'] ?>">
                <?= htmlspecialchars($project['category_name']) ?>
            </span>
        </div>
        <h1><?= htmlspecialchars($project['name']) ?></h1>
        <div class="meta">
            <strong>Client:</strong> <?= htmlspecialchars($project['client_name']) ?> (<?= htmlspecialchars($project['contact_name']) ?>)<br>
            <strong>Site:</strong> <?= htmlspecialchars(trim($project['site_number'] . ' ' . $project['site_street'] . ' ' . $project['site_suburb'])) ?><br>
            <strong>Status:</strong> <?= htmlspecialchars($project['status']) ?>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div>Revenue (Invoiced)</div>
            <div class="stat-val">$<?= number_format($revenue, 2) ?></div>
        </div>
        <div class="stat-box">
            <div>Costs (Expenses)</div>
            <div class="stat-val">$<?= number_format($cost, 2) ?></div>
        </div>
        <div class="stat-box">
            <div>Net Profit</div>
            <div class="stat-val <?= $profit >= 0 ? 'profit' : 'loss' ?>">$<?= number_format($profit, 2) ?></div>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <h2 style="margin:0;">Transaction History</h2>
        <a href="add_line_item.php?project_id=<?= $projectId ?>" style="background:#333; color:white; padding:8px 15px; text-decoration:none; border-radius:4px;">+ Add Item</a>
    </div>
    <table>
        <thead>
            <tr>
                <th width="5%">Type</th>
                <th width="15%">Date</th>
                <th>Description</th>
                <th>Supplier / Account</th>
                <th style="text-align:right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lineItems as $item): ?>
            <tr class="type-<?= $item['item_type'] ?>">
                <td><strong><?= $item['item_type'] ?></strong></td>
                <td><?= $item['transaction_date'] ?></td>
                <td>
                    <?= htmlspecialchars($item['description']) ?>
                    <?php if($item['is_reconciled']): ?>
                        <span title="Reconciled">✅</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                        if ($item['supplier_name']) echo htmlspecialchars($item['supplier_name']);
                        elseif ($item['account_name']) echo htmlspecialchars($item['account_name']);
                        else echo '-';
                    ?>
                </td>
                <td class="money">
                    $<?= number_format($item['amount'], 2) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($lineItems)): ?>
                <tr><td colspan="5" style="text-align:center; color:#888;">No transactions found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>