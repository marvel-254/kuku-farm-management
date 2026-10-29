-- KUKU Farm Management System Database Schema
-- SQLite Database Schema (ENUM types converted to TEXT with CHECK constraints)

-- Farmers table
CREATE TABLE IF NOT EXISTS farmers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    join_date DATE DEFAULT CURRENT_DATE,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role TEXT DEFAULT 'user' CHECK(role IN ('admin', 'manager', 'user')),
    farmer_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE SET NULL
);

-- Chicken groups
CREATE TABLE IF NOT EXISTS chicken_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    breed VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Chicken batches
CREATE TABLE IF NOT EXISTS chicken_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id INTEGER NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    quantity INTEGER NOT NULL,
    purchase_date DATE NOT NULL,
    purchase_cost DECIMAL(10,2),
    supplier VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chicken_groups(id)
);

-- Individual chickens
CREATE TABLE IF NOT EXISTS chickens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL,
    tag_number VARCHAR(50) UNIQUE,
    gender TEXT CHECK(gender IN ('male', 'female')),
    birth_date DATE,
    weight DECIMAL(5,2),
    status TEXT DEFAULT 'healthy' CHECK(status IN ('healthy', 'sick', 'sold', 'deceased')),
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES chicken_batches(id)
);

-- Health records
CREATE TABLE IF NOT EXISTS health_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chicken_id INTEGER NOT NULL,
    check_date DATE NOT NULL,
    weight DECIMAL(5,2),
    temperature DECIMAL(4,1),
    symptoms TEXT,
    diagnosis TEXT,
    treatment TEXT,
    veterinarian VARCHAR(100),
    cost DECIMAL(8,2),
    next_check_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chicken_id) REFERENCES chickens(id)
);

-- Feed items/inventory
CREATE TABLE IF NOT EXISTS items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    category TEXT CHECK(category IN ('feed', 'medicine', 'equipment', 'supplies')) NOT NULL,
    description TEXT,
    unit VARCHAR(20) NOT NULL,
    unit_cost DECIMAL(8,2),
    supplier VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory transactions
CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    transaction_type TEXT CHECK(transaction_type IN ('in', 'out')) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(8,2),
    total_cost DECIMAL(10,2),
    transaction_date DATE NOT NULL,
    reference VARCHAR(100),
    notes TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Sales
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chicken_id INTEGER,
    batch_id INTEGER,
    sale_date DATE NOT NULL,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    quantity INTEGER DEFAULT 1,
    unit_price DECIMAL(8,2),
    total_amount DECIMAL(10,2),
    payment_method TEXT DEFAULT 'cash' CHECK(payment_method IN ('cash', 'mobile_money', 'bank_transfer', 'credit')),
    payment_status TEXT DEFAULT 'paid' CHECK(payment_status IN ('paid', 'partial', 'pending')),
    notes TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chicken_id) REFERENCES chickens(id),
    FOREIGN KEY (batch_id) REFERENCES chicken_batches(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Expenses
CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT CHECK(category IN ('feed', 'medicine', 'labor', 'equipment', 'utilities', 'other')) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    reference VARCHAR(100),
    supplier VARCHAR(100),
    payment_method TEXT DEFAULT 'cash' CHECK(payment_method IN ('cash', 'mobile_money', 'bank_transfer')),
    notes TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Earnings (general income not from sales)
CREATE TABLE IF NOT EXISTS earnings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    earning_date DATE NOT NULL,
    reference VARCHAR(100),
    payment_method TEXT DEFAULT 'cash' CHECK(payment_method IN ('cash', 'mobile_money', 'bank_transfer')),
    status TEXT DEFAULT 'received' CHECK(status IN ('received', 'pending')),
    notes TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Milk entries (if diversified to dairy)
CREATE TABLE IF NOT EXISTS milk_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_date DATE NOT NULL,
    quantity_liters DECIMAL(6,2) NOT NULL,
    quality_grade TEXT DEFAULT 'A' CHECK(quality_grade IN ('A', 'B', 'C')),
    price_per_liter DECIMAL(6,2),
    total_amount DECIMAL(8,2),
    supplier VARCHAR(100),
    notes TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Audit logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INTEGER,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Finance logs for tracking all financial transactions
CREATE TABLE IF NOT EXISTS finance_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_type TEXT CHECK(transaction_type IN ('income', 'expense')) NOT NULL,
    category VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    reference_table VARCHAR(50),
    reference_id INTEGER,
    transaction_date DATE NOT NULL,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT OR IGNORE INTO users (username, password, email, role) 
VALUES ('admin', '$2y$12$G0/ExN9La73ibrQM3mtTa.a04IPbiN01DuMz.DwvtEC0Oy9VQG44.', 'admin@kuku.com', 'admin');

-- Insert default chicken group
INSERT OR IGNORE INTO chicken_groups (name, description, breed) 
VALUES ('Default Group', 'Default chicken group', 'Mixed Breed');

-- Insert some default items
INSERT OR IGNORE INTO items (name, category, description, unit, unit_cost) VALUES
('Chicken Feed', 'feed', 'Standard chicken feed', 'kg', 2.50),
('Vitamins', 'medicine', 'Chicken vitamin supplements', 'bottle', 15.00),
('Antibiotics', 'medicine', 'Broad spectrum antibiotics', 'box', 25.00),
('Water Dispenser', 'equipment', 'Automatic water dispenser', 'unit', 45.00),
('Feed Trough', 'equipment', 'Metal feeding trough', 'unit', 30.00);