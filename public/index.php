<?php
require_once __DIR__ . '/../includes/auth.php';

// Query to fetch projects with calculated Profit (Revenue - Cost)
$sql = "
    SELECT 
        p.id, 
        p.name as project_name, 
        c.client_name, 
        w.name as category, 
        w.color_hex,
        p.status,
        p.is_fast_invoice,
        -- Calculate Revenue (Invoices)
        COALESCE((SELECT SUM(amount) FROM line_items WHERE project_id = p.id AND item_type = 'INV'), 0) as revenue,
        -- Calculate Cost (Jobs)
        COALESCE((SELECT SUM(amount) FROM line_items WHERE project_id = p.id AND item_type = 'JOB'), 0) as cost
    FROM project_headers p
    JOIN customers c ON p.customer_id = c.id
    JOIN work_categories w ON p.work_category_id = w.id
    ORDER BY p.created_at DESC
";

$stmt = $pdo->query($sql);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Construction Lite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/grid.css">
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <h1>Project Dashboard</h1>

    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Project</th>
                <th>Category</th>
                <th>Status</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">Cost</th>
                <th class="text-right">Profit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $p): 
                $profit = $p['revenue'] - $p['cost'];
            ?>
            <tr>
                <td><?= htmlspecialchars($p['client_name']) ?></td>
                <td>
                    <a href="project_details.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></a>
                    <?php if($p['is_fast_invoice']): ?> 👻<?php endif; ?>
                </td>
                <td>
                    <span class="pill" style="background-color: <?= $p['color_hex'] ?>">
                        <?= htmlspecialchars($p['category']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td class="money">$<?= number_format($p['revenue'], 2) ?></td>
                <td class="money">$<?= number_format($p['cost'], 2) ?></td>
                <td class="money <?= $profit >= 0 ? 'profit' : 'loss' ?>">$<?= number_format($profit, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>