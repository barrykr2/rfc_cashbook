<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = $_POST['client_name'];
    $contactName = $_POST['contact_name'];
    $email = $_POST['email'];
    $unit = $_POST['addr_unit'];
    $number = $_POST['addr_number'];
    $street = $_POST['addr_street'];
    $suburb = $_POST['addr_suburb'];
    $state = $_POST['addr_state'];
    $postcode = $_POST['addr_postcode'];

    $stmt = $pdo->prepare("
        INSERT INTO customers (client_name, contact_name, email, addr_unit, addr_number, addr_street, addr_suburb, addr_state, addr_postcode)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute([$clientName, $contactName, $email, $unit, $number, $street, $suburb, $state, $postcode]);
        // Redirect back to Create Project so they can use the new customer immediately
        header("Location: create_project.php");
        exit;
    } catch (PDOException $e) {
        die("Error creating customer: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Customer</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/grid.css">
    <link rel="stylesheet" href="css/form.css">
    <link rel="stylesheet" href="css/address.css">
</head>
<body class="container-sm">

    <a href="create_project.php" class="back-link">← Back to Create Project</a>

    <div class="card">
        <h1>Add New Customer</h1>

        <form method="post">
            <label>Client / Business Name</label>
            <input type="text" name="client_name" placeholder="e.g. BuildCo Pty Ltd" required>

            <label>Contact Person</label>
            <input type="text" name="contact_name" placeholder="e.g. Dave">

            <label>Email</label>
            <input type="text" name="email" placeholder="dave@example.com">

            <?php 
            $prefix = 'addr';
            $data = []; // No existing data for new customer
            include __DIR__ . '/../includes/address_form.php'; 
            ?>

            <button type="submit">Save Customer</button>
        </form>
    </div>

</body>
</html>