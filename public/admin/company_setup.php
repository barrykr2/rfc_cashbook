<?php
require_once __DIR__ . '/../../includes/auth.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['business_name'];
    $abn = $_POST['abn'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $unit = $_POST['addr_unit'];
    $number = $_POST['addr_number'];
    $street = $_POST['addr_street'];
    $suburb = $_POST['addr_suburb'];
    $state = $_POST['addr_state'];
    $postcode = $_POST['addr_postcode'];

    // Upsert (Insert or Replace) ID 1 to ensure single record
    $stmt = $pdo->prepare("
        INSERT OR REPLACE INTO company_profile 
        (id, business_name, abn, email, phone, addr_unit, addr_number, addr_street, addr_suburb, addr_state, addr_postcode)
        VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute([$name, $abn, $email, $phone, $unit, $number, $street, $suburb, $state, $postcode]);
        $success = "Details saved successfully.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing details
$company = $pdo->query("SELECT * FROM company_profile WHERE id = 1")->fetch();
if (!$company) {
    $company = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Settings</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/grid.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/navbar.css">
</head>
<body class="container">
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    
    <div class="card container-sm" style="margin: 0 auto;">
        <h1>Company Details</h1>
        <p class="text-muted">These details will appear on your invoices.</p>

        <?php if ($success): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>Business Name</label>
            <input type="text" name="business_name" value="<?= htmlspecialchars($company['business_name'] ?? '') ?>" required placeholder="e.g. Joe's Handyman Services">

            <div class="flex gap-10">
                <div class="flex-1">
                    <label>ABN</label>
                    <input type="text" name="abn" value="<?= htmlspecialchars($company['abn'] ?? '') ?>" placeholder="00 000 000 000">
                </div>
                <div class="flex-1">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" placeholder="0400 000 000">
                </div>
            </div>

            <label>Email (for Invoicing)</label>
            <input type="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>" placeholder="joe@example.com">

            <h3 style="margin-bottom: 0; margin-top: 20px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Business Address</h3>
            
            <div class="flex gap-10">
                <div style="flex: 1;">
                    <label>Unit</label>
                    <input type="text" name="addr_unit" value="<?= htmlspecialchars($company['addr_unit'] ?? '') ?>">
                </div>
                <div style="flex: 1;">
                    <label>Number</label>
                    <input type="text" name="addr_number" value="<?= htmlspecialchars($company['addr_number'] ?? '') ?>">
                </div>
            </div>

            <label>Street</label>
            <input type="text" name="addr_street" value="<?= htmlspecialchars($company['addr_street'] ?? '') ?>">

            <div class="flex gap-10">
                <div class="flex-1">
                    <label>Suburb</label>
                    <input type="text" name="addr_suburb" value="<?= htmlspecialchars($company['addr_suburb'] ?? '') ?>">
                </div>
                <div style="width: 80px;">
                    <label>State</label>
                    <input type="text" name="addr_state" value="<?= htmlspecialchars($company['addr_state'] ?? '') ?>">
                </div>
                <div style="width: 100px;">
                    <label>Postcode</label>
                    <input type="text" name="addr_postcode" value="<?= htmlspecialchars($company['addr_postcode'] ?? '') ?>">
                </div>
            </div>

            <button type="submit">Save Details</button>
        </form>
    </div>
</body>
</html>