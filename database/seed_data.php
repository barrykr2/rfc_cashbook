<?php

$dbPath = __DIR__ . '/construction.db';

if (!file_exists($dbPath)) {
    die("Database not found. Run init_db.php first.\n");
}

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Seeding database with test data...\n";
    $pdo->beginTransaction();

    // ---------------------------------------------------------
    // 1. Reference Data (Categories, Accounts, Suppliers)
    // ---------------------------------------------------------

    // Work Categories (with Dyslexia-friendly colors)
    $categories = [
        ['Fencing', '#8B4513'],       // SaddleBrown
        ['Decking', '#DEB887'],       // Burlywood
        ['General Repairs', '#4682B4'], // SteelBlue
        ['Landscaping', '#228B22']    // ForestGreen
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO work_categories (name, color_hex) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo " - Categories inserted\n";

    // Account Codes (Simplified Chart of Accounts)
    $codes = [
        [100, 'Sales', 1],
        [300, 'Materials', 1],
        [310, 'Fuel/Travel', 1],
        [320, 'Tools', 1],
        [330, 'Subcontractors', 0],
        [400, 'Phone', 1],
        [500, 'Overheads', 0]
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO account_codes (id, name, is_gst_default) VALUES (?, ?, ?)");
    foreach ($codes as $code) {
        $stmt->execute($code);
    }
    echo " - Account Codes inserted\n";

    // Suppliers
    $suppliers = [
        ['Bunnings', 300],
        ['Mitre 10', 300],
        ['Ampol', 310],
        ['Total Tools', 320]
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO suppliers (name, default_acc_id) VALUES (?, ?)");
    foreach ($suppliers as $sup) {
        $stmt->execute($sup);
    }
    echo " - Suppliers inserted\n";

    // ---------------------------------------------------------
    // 2. Customers
    // ---------------------------------------------------------
    
    $stmt = $pdo->prepare("INSERT INTO customers (client_name, contact_name, email, phone, addr_street, addr_suburb) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Customer A: Corporate
    $stmt->execute(['BuildCo Pty Ltd', 'Dave', 'dave@buildco.com', '02 9999 8888', '123 Construction Rd', 'BuilderTown']);
    $buildCoId = $pdo->lastInsertId();

    // Customer B: Residential
    $stmt->execute(['Mrs. Smith', 'Mary', 'mary@gmail.com', '0411 222 333', '42 Wallaby Way', 'Sydney']);
    $mrsSmithId = $pdo->lastInsertId();
    
    echo " - Customers inserted\n";

    // ---------------------------------------------------------
    // 3. SCENARIO: The "Ghost Job" (Fast Invoice)
    // ---------------------------------------------------------
    // Context: Mrs. Smith needed a tap fixed immediately. No quote, just did it and invoiced.
    
    // A. Project Header
    $stmt = $pdo->prepare("INSERT INTO project_headers (customer_id, work_category_id, name, status, is_fast_invoice, site_street, site_suburb) VALUES (?, (SELECT id FROM work_categories WHERE name='General Repairs'), ?, 'INVOICED', 1, ?, ?)");
    $stmt->execute([$mrsSmithId, 'Fix Leaky Tap', '42 Wallaby Way', 'Sydney']);
    $ghostProjectId = $pdo->lastInsertId();

    // B. Line Item: Revenue (The Invoice)
    $stmt = $pdo->prepare("INSERT INTO line_items (project_id, item_type, transaction_date, amount, gst_amount, description, paid_date) VALUES (?, 'INV', DATE('now'), 165.00, 15.00, 'Tap Washer Replacement', DATE('now'))");
    $stmt->execute([$ghostProjectId]);

    // C. Line Item: Shadow Cost (The Placeholder)
    $stmt = $pdo->prepare("INSERT INTO line_items (project_id, item_type, transaction_date, amount, gst_amount, description) VALUES (?, 'JOB', DATE('now'), 0.00, 0.00, 'Cost Placeholder')");
    $stmt->execute([$ghostProjectId]);

    echo " - Ghost Job created (Project ID: $ghostProjectId)\n";

    // ---------------------------------------------------------
    // 4. SCENARIO: Standard Project (Decking)
    // ---------------------------------------------------------
    // Context: BuildCo wants a deck. Quote -> Materials -> Invoice.

    // A. Project Header
    $stmt = $pdo->prepare("INSERT INTO project_headers (customer_id, work_category_id, name, status, site_street, site_suburb) VALUES (?, (SELECT id FROM work_categories WHERE name='Decking'), ?, 'ACTIVE', ?, ?)");
    $stmt->execute([$buildCoId, 'Backyard Deck', '123 Construction Rd', 'BuilderTown']);
    $stdProjectId = $pdo->lastInsertId();

    // B. The Quote
    $stmt = $pdo->prepare("INSERT INTO line_items (project_id, item_type, transaction_date, amount, gst_amount, description) VALUES (?, 'QUOTE', DATE('now', '-7 days'), 5500.00, 500.00, 'Timber Decking 5x5m')");
    $stmt->execute([$stdProjectId]);
    $quoteId = $pdo->lastInsertId();

    // C. Job Cost: Materials (Bunnings)
    $stmt = $pdo->prepare("INSERT INTO line_items (project_id, item_type, transaction_date, amount, gst_amount, category_id, supplier_id, description, is_reconciled) VALUES (?, 'JOB', DATE('now', '-2 days'), 1320.00, 120.00, 300, (SELECT id FROM suppliers WHERE name='Bunnings'), 'Merbau Timber', 1)");
    $stmt->execute([$stdProjectId]);

    // D. Job Cost: Fuel (Ampol) - Unreconciled
    $stmt = $pdo->prepare("INSERT INTO line_items (project_id, item_type, transaction_date, amount, gst_amount, category_id, supplier_id, description, is_reconciled) VALUES (?, 'JOB', DATE('now', '-1 days'), 88.00, 8.00, 310, (SELECT id FROM suppliers WHERE name='Ampol'), 'Fuel for Ute', 0)");
    $stmt->execute([$stdProjectId]);

    // E. The Invoice (Linked to Quote)
    $stmt = $pdo->prepare("INSERT INTO line_items (project_id, item_type, transaction_date, amount, gst_amount, description, parent_item_id) VALUES (?, 'INV', DATE('now'), 5500.00, 500.00, 'Timber Decking 5x5m - Final', ?)");
    $stmt->execute([$stdProjectId, $quoteId]);

    echo " - Standard Project created (Project ID: $stdProjectId)\n";

    $pdo->commit();
    echo "Seed data complete.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}