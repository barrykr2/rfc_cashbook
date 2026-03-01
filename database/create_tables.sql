-- ==============================================================================
-- DATABASE: Construction Lite (Pro Edition)
-- PURPOSE: Unified Job, Quote, and Invoice Management
-- ARCHITECTURE: Generalized Line Items with Flat Address Schema
-- ==============================================================================

PRAGMA foreign_keys = OFF;

-- CLEANUP: Drop all existing structures for a clean slate
DROP VIEW IF EXISTS v_job_costs;
DROP VIEW IF EXISTS v_client_items;
DROP TRIGGER IF EXISTS trg_update_customer_date;
DROP TRIGGER IF EXISTS trg_audit_line_items_update;
DROP TRIGGER IF EXISTS trg_audit_line_items_delete;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS line_items;
DROP TABLE IF EXISTS project_headers;
DROP TABLE IF EXISTS work_categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS account_codes;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS company_profile;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS app_config;

PRAGMA foreign_keys = ON;

-- ==============================================================================
-- 1. ENTITY TABLES (Who is involved)
-- ==============================================================================

-- Your Son's Business Details (For Invoice Headers)
CREATE TABLE company_profile (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    business_name     TEXT NOT NULL,
    abn               TEXT,
    email             TEXT,
    phone             TEXT,
    addr_unit         TEXT,
    addr_number       TEXT,
    addr_street       TEXT,
    addr_suburb       TEXT,
    addr_state        TEXT,
    addr_postcode     TEXT
);

-- Client Database (Billing Entities)
CREATE TABLE customers (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name       TEXT NOT NULL COLLATE NOCASE, -- e.g. "BuildCo PTY LTD"
    contact_name      TEXT,                        -- e.g. "Dave"
    email             TEXT,
    last_project_date DATE,
    addr_unit         TEXT,
    addr_number       TEXT,
    addr_street       TEXT,
    addr_suburb       TEXT,
    addr_state        TEXT,
    addr_postcode     TEXT
);

-- ==============================================================================
-- 2. LOOKUP TABLES (The Dictionaries)
-- ==============================================================================

CREATE TABLE work_categories (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    name  TEXT NOT NULL UNIQUE, -- e.g. Fencing, Decking, Landscaping
    color_hex TEXT DEFAULT '#cccccc' -- For UI "Pill" buttons (Dyslexia support)
);

CREATE TABLE account_codes (
    id              INTEGER PRIMARY KEY, -- e.g. 500, 530
    name            TEXT NOT NULL,       -- e.g. Materials, Fuel
    is_gst_default  BOOLEAN DEFAULT 1
);

CREATE TABLE suppliers (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            TEXT NOT NULL COLLATE NOCASE,
    default_acc_id  INTEGER REFERENCES account_codes(id),
    is_active       BOOLEAN DEFAULT 1
);

-- ==============================================================================
-- 3. THE WORK ENGINE (Header & Details)
-- ==============================================================================

-- The Container: Links a Customer to a Site (Project)
CREATE TABLE project_headers (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id       INTEGER NOT NULL,
    work_category_id  INTEGER REFERENCES work_categories(id),
    name              TEXT NOT NULL,         -- Project Title
    status            TEXT DEFAULT 'ACTIVE', -- ACTIVE, COMPLETED, INVOICED
    is_fast_invoice   BOOLEAN DEFAULT 0,     -- Flag for "Ghost Jobs"
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Site Address (Where the tools are used)
    site_unit         TEXT,
    site_number       TEXT,
    site_street       TEXT,
    site_suburb       TEXT,
    site_state        TEXT,
    site_postcode     TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- The Generalized Detail Table: The source of truth for all line items
CREATE TABLE line_items (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id      INTEGER NOT NULL,
    item_type       TEXT NOT NULL, -- 'JOB' (Internal), 'QUOTE' (Proposal), 'INV' (Final)
    transaction_date DATE DEFAULT CURRENT_DATE, -- Crucial for BAS (Tax Point)
    amount          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    gst_amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category_id     INTEGER REFERENCES account_codes(id),
    supplier_id     INTEGER REFERENCES suppliers(id),
    description     TEXT,
    paid_date       DATE, -- For Cash Basis revenue recognition
    is_reconciled   BOOLEAN DEFAULT 0, -- Used for Bank Rec later
    parent_item_id  INTEGER REFERENCES line_items(id), -- Maps Quote -> Invoice
    FOREIGN KEY (project_id) REFERENCES project_headers(id)
);

-- ==============================================================================
-- 4. VIEWS (UI Segregation)
-- ==============================================================================

-- Pulls the "Messy Truth" for internal cost tracking
CREATE VIEW v_job_costs AS
SELECT li.*, s.name as supplier_name, ac.name as account_name
FROM line_items li
LEFT JOIN suppliers s ON li.supplier_id = s.id
LEFT JOIN account_codes ac ON li.category_id = ac.id
WHERE li.item_type = 'JOB';

-- Pulls the "Polished Proposal" for client viewing
CREATE VIEW v_client_items AS
SELECT * FROM line_items WHERE item_type IN ('QUOTE', 'INV');

-- ==============================================================================
-- 5. AUTOMATION & AUDIT LOGGING
-- ==============================================================================

CREATE TABLE audit_log (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    row_id      INTEGER,
    action      TEXT,
    old_data    TEXT, -- JSON snapshot of state
    changed_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Update Customer's last engagement date automatically
CREATE TRIGGER trg_update_customer_date
AFTER INSERT ON project_headers
BEGIN
    UPDATE customers 
    SET last_project_date = date('now')
    WHERE id = NEW.customer_id;
END;

-- Capture line item history before updates
CREATE TRIGGER trg_audit_line_items_update
AFTER UPDATE ON line_items
BEGIN
    INSERT INTO audit_log (row_id, action, old_data)
    VALUES (OLD.id, 'UPDATE', json_object(
        'project_id', OLD.project_id,
        'type', OLD.item_type,
        'date', OLD.transaction_date,
        'desc', OLD.description,
        'amt', OLD.amount,
        'gst', OLD.gst_amount
    ));
END;

-- Capture deletions for recovery
CREATE TRIGGER trg_audit_line_items_delete
AFTER DELETE ON line_items
BEGIN
    INSERT INTO audit_log (row_id, action, old_data)
    VALUES (OLD.id, 'DELETE', json_object(
        'project_id', OLD.project_id,
        'type', OLD.item_type,
        'date', OLD.transaction_date,
        'desc', OLD.description,
        'amt', OLD.amount
    ));
END;

-- ==============================================================================
-- 6. PERFORMANCE INDEXES
-- ==============================================================================
CREATE INDEX idx_line_proj ON line_items(project_id);
CREATE INDEX idx_line_type ON line_items(item_type);
CREATE INDEX idx_proj_status ON project_headers(status);
CREATE INDEX idx_cust_name ON customers(client_name);

-- ==============================================================================
-- 7. SECURITY & CONFIGURATION
-- ==============================================================================

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    username TEXT,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE app_config (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
);