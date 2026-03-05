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
<html lang="en-AU">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Settings</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/grid.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/address.css">
    <style>
        .address-wrapper { position: relative; }
        .address-map-link {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            text-decoration: none;
            font-size: 1.2em;
            z-index: 2; /* To appear above autocomplete elements */
        }
    </style>
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
            <input type="text" name="business_name" class="smart-title-case" value="<?= htmlspecialchars($company['business_name'] ?? '') ?>" required placeholder="e.g. Joe's Handyman Services" spellcheck="true">

            <div class="flex gap-10">
                <div class="flex-1">
                    <label>ABN</label>
                    <input type="text" name="abn" value="<?= htmlspecialchars($company['abn'] ?? '') ?>" placeholder="00 000 000 000">
                </div>
                <div class="flex-1">
                    <label>Phone</label>
                    <div class="input-with-action">
                        <input type="text" name="phone" value="<?= htmlspecialchars($company['phone'] ?? '') ?>" placeholder="0400 000 000">
                        <a href="#" class="btn-action tel-action" style="display:none;" title="Call" target="_blank">📞</a>
                    </div>
                </div>
            </div>

            <label>Email (for Invoicing)</label>
            <div class="input-with-action">
                <input type="email" name="email" value="<?= htmlspecialchars($company['email'] ?? '') ?>" placeholder="joe@example.com">
                <a href="#" class="btn-action mail-action" style="display:none;" title="Send Email" target="_blank">📧</a>
            </div>

            <h3 style="margin-bottom: 0; margin-top: 20px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Business Address</h3>
            <?php 
            $prefix = 'addr';
            $data = $company;
            include __DIR__ . '/../../includes/address_form.php'; 
            ?>

            <button type="submit">Save Details</button>
        </form>
    </div>

    <script src="/js/form_helpers.js"></script>
</body>
</html>