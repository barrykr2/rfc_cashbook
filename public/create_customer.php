<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = $_POST['client_name'];
    $contactName = $_POST['contact_name'];
    $email = $_POST['email'];
    $street = $_POST['addr_street'];
    $suburb = $_POST['addr_suburb'];

    $stmt = $pdo->prepare("
        INSERT INTO customers (client_name, contact_name, email, addr_street, addr_suburb)
        VALUES (?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute([$clientName, $contactName, $email, $street, $suburb]);
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

            <label>Address</label>
            <div class="flex gap-10">
                <div class="flex-1">
                    <input type="text" name="addr_street" placeholder="Street Address">
                </div>
                <div class="flex-1">
                    <input type="text" name="addr_suburb" placeholder="Suburb">
                </div>
            </div>

            <button type="submit">Save Customer</button>
        </form>
    </div>

</body>
</html>