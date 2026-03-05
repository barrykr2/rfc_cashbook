<?php
require_once __DIR__ . '/../includes/auth.php';

// Determine where to redirect back to. Default to create_project.php
$redirectUrl = $_GET['redirect_url'] ?? 'create_project.php';
$customerId = $_GET['id'] ?? null;

$customer = [];
if ($customerId) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    if (!$customer) die("Customer not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = $_POST['client_name'];
    $contactName = $_POST['contact_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $unit = $_POST['addr_unit'];
    $number = $_POST['addr_number'];
    $street = $_POST['addr_street'];
    $suburb = $_POST['addr_suburb'];
    $state = $_POST['addr_state'];
    $postcode = $_POST['addr_postcode'];

    if ($customerId) {
        $stmt = $pdo->prepare("
            UPDATE customers SET client_name=?, contact_name=?, email=?, phone=?, addr_unit=?, addr_number=?, addr_street=?, addr_suburb=?, addr_state=?, addr_postcode=?
            WHERE id=?
        ");
        $params = [$clientName, $contactName, $email, $phone, $unit, $number, $street, $suburb, $state, $postcode, $customerId];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO customers (client_name, contact_name, email, phone, addr_unit, addr_number, addr_street, addr_suburb, addr_state, addr_postcode)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $params = [$clientName, $contactName, $email, $phone, $unit, $number, $street, $suburb, $state, $postcode];
    }

    try {
        $stmt->execute($params);
        $newCustomerId = $customerId ?: $pdo->lastInsertId();

        // Append a query parameter for the new customer ID to the redirect URL
        $finalRedirectUrl = $redirectUrl . (strpos($redirectUrl, '?') === false ? '?' : '&') . 'new_customer_id=' . $newCustomerId;
        
        header("Location: " . $finalRedirectUrl);
        exit;
    } catch (PDOException $e) {
        die("Error creating customer: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $customerId ? 'Edit Customer' : 'New Customer' ?></title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/grid.css">
    <link rel="stylesheet" href="css/form.css">
    <link rel="stylesheet" href="css/address.css">
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
<body class="container-sm">

    <a href="<?= htmlspecialchars($redirectUrl) ?>" class="back-link">← Back</a>

    <div class="card">
        <h1><?= $customerId ? 'Edit Customer' : 'Add New Customer' ?></h1>

        <form method="post">
            <label>Client / Business Name</label>
            <input type="text" name="client_name" class="smart-title-case" value="<?= htmlspecialchars($customer['client_name'] ?? '') ?>" placeholder="e.g. BuildCo Pty Ltd" required spellcheck="true">

            <label>Contact Person</label>
            <input type="text" name="contact_name" class="smart-title-case" value="<?= htmlspecialchars($customer['contact_name'] ?? '') ?>" placeholder="e.g. Dave" spellcheck="true">

            <label>Email</label>
            <div class="input-with-action">
                <input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" placeholder="dave@example.com" spellcheck="false">
                <a href="#" class="btn-action mail-action" style="display:none;" title="Send Email" target="_blank">📧</a>
            </div>

            <label>Phone</label>
            <div class="input-with-action">
                <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="0400 000 000">
                <a href="#" class="btn-action tel-action" style="display:none;" title="Call" target="_blank">📞</a>
            </div>

            <?php 
            $prefix = 'addr';
            $data = $customer; 
            include __DIR__ . '/../includes/address_form.php'; 
            ?>

            <button type="submit">Save Customer</button>
        </form>
    </div>

    <script src="/js/form_helpers.js"></script>

</body>
</html>