<?php
require_once __DIR__ . '/../includes/auth.php';

// Fetch Customers for dropdown (Later we can make this a search)
$customers = $pdo->query("SELECT id, client_name FROM customers ORDER BY client_name")->fetchAll();

// Fetch Categories for visual selection
$categories = $pdo->query("SELECT * FROM work_categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'];
    $categoryId = $_POST['work_category_id'];
    $name = $_POST['name'];
    
    // Address Fields
    $street = $_POST['site_street'];
    $suburb = $_POST['site_suburb'];

    // Insert Project Header
    $stmt = $pdo->prepare("
        INSERT INTO project_headers (customer_id, work_category_id, name, site_street, site_suburb, status)
        VALUES (?, ?, ?, ?, ?, 'ACTIVE')
    ");
    
    try {
        $stmt->execute([$customerId, $categoryId, $name, $street, $suburb]);
        $newId = $pdo->lastInsertId();
        header("Location: project_details.php?id=$newId");
        exit;
    } catch (PDOException $e) {
        die("Error creating project: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Project</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/grid.css">
    <link rel="stylesheet" href="css/form.css">
    <link rel="stylesheet" href="css/navbar.css">
    <style>
        /* Dynamic Color Overrides for Categories */
        <?php foreach ($categories as $cat): ?>
        input[value="<?= $cat['id'] ?>"]:checked + label {
            background-color: <?= $cat['color_hex'] ?>;
            color: white;
            border-color: <?= $cat['color_hex'] ?>;
        }
        /* Unselected state hint */
        input[value="<?= $cat['id'] ?>"] + label {
            border-left: 5px solid <?= $cat['color_hex'] ?>;
        }
        <?php endforeach; ?>
    </style>
</head>
<body class="container">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <a href="index.php" class="back-link">← Back to Dashboard</a>

    <div class="card container-sm" style="margin: 0 auto;">
        <h1>Start New Project</h1>

        <form method="post">
            
            <label>Select Client</label>
            <div class="flex gap-10">
                <select name="customer_id" required class="flex-1">
                    <option value="">-- Choose Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['client_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="create_customer.php" class="btn-add" style="margin-top: 5px;">+ New</a>
            </div>

            <label>Work Category</label>
            <div class="radio-group">
                <?php foreach ($categories as $cat): ?>
                <div class="radio-option">
                    <input type="radio" name="work_category_id" id="cat_<?= $cat['id'] ?>" value="<?= $cat['id'] ?>" required>
                    <label for="cat_<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <label>Project Name</label>
            <input type="text" name="name" placeholder="e.g. Backyard Deck, Kitchen Reno" required>

            <label>Site Address</label>
            <div class="flex gap-10">
                <div class="flex-1">
                    <input type="text" name="site_street" placeholder="Street Address" required>
                </div>
                <div class="flex-1">
                    <input type="text" name="site_suburb" placeholder="Suburb" required>
                </div>
            </div>
            <p class="text-muted" style="font-size:0.8em; margin-top:5px;">* Address auto-complete coming soon</p>

            <button type="submit">Create Project</button>
        </form>
    </div>

</body>
</html>