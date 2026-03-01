<?php
require_once __DIR__ . '/../includes/auth.php';

$project_id = $_GET['project_id'] ?? $_POST['project_id'] ?? null;

if (!$project_id) {
    die("Project ID missing.");
}

// Fetch Project Context
$stmt = $pdo->prepare("SELECT * FROM project_headers WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die("Project not found.");
}

// Fetch Dropdowns
$suppliers = $pdo->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name")->fetchAll();
$accounts = $pdo->query("SELECT * FROM account_codes ORDER BY id")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['item_type'];
    $date = $_POST['transaction_date'];
    $desc = $_POST['description'];
    $amount = $_POST['amount'];
    $gst = $_POST['gst_amount'];
    // Handle empty strings for optional foreign keys
    $supplier = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    $category = !empty($_POST['category_id']) ? $_POST['category_id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO line_items (project_id, item_type, transaction_date, description, amount, gst_amount, supplier_id, category_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$project_id, $type, $date, $desc, $amount, $gst, $supplier, $category]);
    
    header("Location: project_details.php?id=" . $project_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - <?= htmlspecialchars($project['name']) ?></title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f9; max-width: 600px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type="text"], input[type="date"], input[type="number"], select {
            width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px;
        }
        .radio-group { display: flex; gap: 10px; margin-top: 5px; }
        .radio-option { flex: 1; }
        .radio-option input { display: none; }
        .radio-option label { 
            display: block; padding: 10px; text-align: center; border: 2px solid #ddd; border-radius: 4px; cursor: pointer; 
        }
        
        /* Color Coding Selection */
        input[value="JOB"]:checked + label { background-color: #fff3e0; border-color: #ff9800; color: #e65100; }
        input[value="INV"]:checked + label { background-color: #e8f5e9; border-color: #4caf50; color: #1b5e20; }
        input[value="QUOTE"]:checked + label { background-color: #e3f2fd; border-color: #2196F3; color: #0d47a1; }

        button { margin-top: 20px; padding: 12px 20px; background: #333; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background: #555; }
        .back-link { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #333; }
    </style>
    <script>
        function toggleFields() {
            const type = document.querySelector('input[name="item_type"]:checked').value;
            const supplierDiv = document.getElementById('supplier-div');
            // Only show supplier for Expenses (JOB)
            if (type === 'JOB') {
                supplierDiv.style.display = 'block';
            } else {
                supplierDiv.style.display = 'none';
                document.getElementById('supplier_id').value = '';
            }
        }
    </script>
</head>
<body>
    <a href="project_details.php?id=<?= $project_id ?>" class="back-link">← Back to Project</a>
    
    <div class="card">
        <h1>Add Transaction</h1>
        <p>For: <strong><?= htmlspecialchars($project['name']) ?></strong></p>

        <form method="post">
            <input type="hidden" name="project_id" value="<?= $project_id ?>">

            <label>Type</label>
            <div class="radio-group">
                <div class="radio-option">
                    <input type="radio" name="item_type" id="type_job" value="JOB" checked onclick="toggleFields()">
                    <label for="type_job">Expense</label>
                </div>
                <div class="radio-option">
                    <input type="radio" name="item_type" id="type_inv" value="INV" onclick="toggleFields()">
                    <label for="type_inv">Invoice</label>
                </div>
                <div class="radio-option">
                    <input type="radio" name="item_type" id="type_quote" value="QUOTE" onclick="toggleFields()">
                    <label for="type_quote">Quote</label>
                </div>
            </div>

            <label>Date</label>
            <input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" required>

            <label>Description</label>
            <input type="text" name="description" placeholder="e.g. Timber, Labor, Deposit" required>

            <div id="supplier-div">
                <label>Supplier</label>
                <select name="supplier_id" id="supplier_id">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label>Category / Account</label>
            <select name="category_id">
                <option value="">-- Select Category --</option>
                <?php foreach ($accounts as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div style="display:flex; gap:10px;">
                <div style="flex:1">
                    <label>Amount (Inc GST)</label>
                    <input type="number" name="amount" step="0.01" placeholder="0.00" required>
                </div>
                <div style="flex:1">
                    <label>GST Amount</label>
                    <input type="number" name="gst_amount" step="0.01" placeholder="0.00">
                </div>
            </div>

            <button type="submit">Save Transaction</button>
        </form>
    </div>
</body>
</html>