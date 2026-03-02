<?php
require_once __DIR__ . '/../includes/auth.php';

// Fetch Customers
$customers = $pdo->query("SELECT id, client_name, addr_street, addr_suburb FROM customers ORDER BY client_name")->fetchAll();
// Fetch Categories
$categories = $pdo->query("SELECT * FROM work_categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'];
    $categoryId = $_POST['work_category_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $gst = $_POST['gst_amount'];

    // 1. Get Customer Address for Site Address default
    $stmt = $pdo->prepare("SELECT addr_unit, addr_number, addr_street, addr_suburb, addr_state, addr_postcode FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $cust = $stmt->fetch();
    
    $siteUnit = $cust['addr_unit'] ?? '';
    $siteNumber = $cust['addr_number'] ?? '';
    $siteStreet = $cust['addr_street'] ?? '';
    $siteSuburb = $cust['addr_suburb'] ?? '';
    $siteState = $cust['addr_state'] ?? '';
    $sitePostcode = $cust['addr_postcode'] ?? '';

    // 2. Create Project Header (Ghost Job)
    // Name is derived from description, Status=INVOICED, is_fast_invoice=1
    $stmt = $pdo->prepare("
        INSERT INTO project_headers (customer_id, work_category_id, name, site_unit, site_number, site_street, site_suburb, site_state, site_postcode, status, is_fast_invoice)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'INVOICED', 1)
    ");
    $stmt->execute([$customerId, $categoryId, $description, $siteUnit, $siteNumber, $siteStreet, $siteSuburb, $siteState, $sitePostcode]);
    $projectId = $pdo->lastInsertId();

    // 3. Create Revenue Line Item (INV)
    $stmt = $pdo->prepare("
        INSERT INTO line_items (project_id, item_type, transaction_date, description, amount, gst_amount)
        VALUES (?, 'INV', DATE('now'), ?, ?, ?)
    ");
    $stmt->execute([$projectId, $description, $amount, $gst]);

    // 4. Create Shadow Cost Item (JOB) - The "Secret Sauce"
    $stmt = $pdo->prepare("
        INSERT INTO line_items (project_id, item_type, transaction_date, description, amount, gst_amount)
        VALUES (?, 'JOB', DATE('now'), 'Cost Placeholder', 0.00, 0.00)
    ");
    $stmt->execute([$projectId]);

    // Redirect to the project details
    header("Location: project_details.php?id=$projectId");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Invoice</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/grid.css">
    <link rel="stylesheet" href="css/form.css">
    <style>
        /* Dynamic Color Overrides for Categories */
        <?php foreach ($categories as $cat): ?>
        input[value="<?= $cat['id'] ?>"]:checked + label {
            background-color: <?= $cat['color_hex'] ?>;
            color: white;
            border-color: <?= $cat['color_hex'] ?>;
        }
        input[value="<?= $cat['id'] ?>"] + label {
            border-left: 5px solid <?= $cat['color_hex'] ?>;
        }
        <?php endforeach; ?>
    </style>
    <script>
        function calculateGST() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            // GST is 1/11th of the total amount
            const gst = amount / 11;
            document.getElementById('gst_amount').value = gst.toFixed(2);
        }
    </script>
</head>
<body class="container-sm">

    <a href="index.php" class="back-link">← Back to Dashboard</a>

    <div class="card" style="border-top: 5px solid #2196F3;">
        <h1>⚡ Fast Invoice</h1>
        <p class="text-muted">Create a job and invoice in one step.</p>

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

            <label>Description (Job Name)</label>
            <input type="text" name="description" placeholder="e.g. Fix Leaky Tap" required>

            <div class="flex gap-10">
                <div class="flex-1">
                    <label>Total Amount (Inc GST)</label>
                    <input type="number" name="amount" id="amount" step="0.01" placeholder="0.00" required oninput="calculateGST()">
                </div>
                <div class="flex-1">
                    <label>GST Component</label>
                    <input type="number" name="gst_amount" id="gst_amount" step="0.01" placeholder="0.00">
                </div>
            </div>

            <button type="submit" style="background-color: #2196F3;">Create & Invoice</button>
        </form>
    </div>

</body>
</html>