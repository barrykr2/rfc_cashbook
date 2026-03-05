<?php
require_once __DIR__ . '/../../includes/auth.php';

// Sorting logic
$sort = $_GET['sort'] ?? 'last_project_date';
$dir = $_GET['dir'] ?? 'DESC';

// Allowed sort columns to prevent SQL injection
$allowed_sorts = ['client_name', 'contact_name', 'email', 'phone', 'last_project_date', 'addr_suburb'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'last_project_date';
}
if ($dir !== 'ASC' && $dir !== 'DESC') {
    $dir = 'DESC';
}

// Toggle direction for links
function sort_link($col, $current_sort, $current_dir, $label) {
    $new_dir = ($col === $current_sort && $current_dir === 'DESC') ? 'ASC' : 'DESC';
    $arrow = ($col === $current_sort) ? ($current_dir === 'ASC' ? ' ↑' : ' ↓') : '';
    return '<a href="?sort=' . $col . '&dir=' . $new_dir . '">' . $label . $arrow . '</a>';
}

$stmt = $pdo->prepare("SELECT * FROM customers ORDER BY $sort $dir");
$stmt->execute();
$clients = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/grid.css">
    <link rel="stylesheet" href="/css/navbar.css">
    <style>
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #eee; }
        th a { text-decoration: none; color: #333; display: block; }
        tr:hover { background-color: #f9f9f9; }
        .btn-add { display: inline-block; background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body class="container">
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Clients</h1>
        <a href="/create_customer.php?redirect_url=/admin/clients.php" class="btn-add">+ New Client</a>
    </div>

    <table>
        <thead>
            <tr>
                <th><?= sort_link('client_name', $sort, $dir, 'Client Name') ?></th>
                <th><?= sort_link('contact_name', $sort, $dir, 'Contact') ?></th>
                <th><?= sort_link('phone', $sort, $dir, 'Phone') ?></th>
                <th><?= sort_link('email', $sort, $dir, 'Email') ?></th>
                <th><?= sort_link('addr_suburb', $sort, $dir, 'Suburb') ?></th>
                <th><?= sort_link('last_project_date', $sort, $dir, 'Last Project') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $c): ?>
            <tr>
                <td><a href="/create_customer.php?id=<?= $c['id'] ?>&redirect_url=/admin/clients.php"><?= htmlspecialchars($c['client_name']) ?></a></td>
                <td><?= htmlspecialchars($c['contact_name']) ?></td>
                <td><?= htmlspecialchars($c['phone'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['addr_suburb']) ?></td>
                <td><?= $c['last_project_date'] ? date('d/m/Y', strtotime($c['last_project_date'])) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>