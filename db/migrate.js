const { Pool } = require('pg');
const fs = require('fs');
const path = require('path');

// Read database URL from environment
const databaseUrl = process.env.DATABASE_URL;

if (!databaseUrl) {
  console.error('ERROR: DATABASE_URL environment variable is not set.');
  console.error('Please copy .env.example to .env and configure your database connection.');
  process.exit(1);
}

// Create a pool instance
const pool = new Pool({ connectionString: databaseUrl });

async function runMigrations() {
  const client = await pool.connect();

  try {
    console.log('Connected to PostgreSQL database.');

    // Enable UUID extension (if needed in future)
    await client.query('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
    console.log('UUID extension ready.');

    // ============================================
    // FARMERS TABLE
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS farmers (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        join_date DATE DEFAULT CURRENT_DATE,
        status TEXT DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' farmers table ready.');

    // ============================================
    // USERS TABLE (Authentication)
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        role TEXT DEFAULT 'user' CHECK (role IN ('admin', 'manager', 'user')),
        farmer_id INTEGER REFERENCES farmers(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' users table ready.');

    // ============================================
    // CHICKEN GROUPS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS chicken_groups (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        breed VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' chicken_groups table ready.');

    // ============================================
    // CHICKEN BATCHES
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS chicken_batches (
        id SERIAL PRIMARY KEY,
        group_id INTEGER NOT NULL REFERENCES chicken_groups(id),
        batch_number VARCHAR(50) NOT NULL,
        quantity INTEGER NOT NULL,
        purchase_date DATE NOT NULL,
        purchase_cost DECIMAL(10,2),
        supplier VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' chicken_batches table ready.');

    // ============================================
    // CHICKENS (Individual)
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS chickens (
        id SERIAL PRIMARY KEY,
        batch_id INTEGER NOT NULL REFERENCES chicken_batches(id),
        tag_number VARCHAR(50) UNIQUE,
        gender TEXT CHECK (gender IN ('male', 'female')),
        birth_date DATE,
        weight DECIMAL(5,2),
        status TEXT DEFAULT 'healthy' CHECK (status IN ('healthy', 'sick', 'sold', 'deceased')),
        location VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' chickens table ready.');

    // ============================================
    // HEALTH RECORDS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS health_records (
        id SERIAL PRIMARY KEY,
        chicken_id INTEGER NOT NULL REFERENCES chickens(id),
        check_date DATE NOT NULL,
        weight DECIMAL(5,2),
        temperature DECIMAL(4,1),
        symptoms TEXT,
        diagnosis TEXT,
        treatment TEXT,
        veterinarian VARCHAR(100),
        cost DECIMAL(8,2),
        next_check_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' health_records table ready.');

    // ============================================
    // ITEMS (Inventory catalog)
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS items (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category TEXT CHECK (category IN ('feed', 'medicine', 'equipment', 'supplies')) NOT NULL,
        description TEXT,
        unit VARCHAR(20) NOT NULL,
        unit_cost DECIMAL(8,2),
        supplier VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' items table ready.');

    // ============================================
    // INVENTORY (Stock transactions)
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS inventory (
        id SERIAL PRIMARY KEY,
        item_id INTEGER NOT NULL REFERENCES items(id),
        transaction_type TEXT CHECK (transaction_type IN ('in', 'out')) NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        unit_cost DECIMAL(8,2),
        total_cost DECIMAL(10,2),
        transaction_date DATE NOT NULL,
        reference VARCHAR(100),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' inventory table ready.');

    // ============================================
    // SALES
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS sales (
        id SERIAL PRIMARY KEY,
        chicken_id INTEGER REFERENCES chickens(id),
        batch_id INTEGER REFERENCES chicken_batches(id),
        sale_date DATE NOT NULL,
        customer_name VARCHAR(100),
        customer_phone VARCHAR(20),
        quantity INTEGER DEFAULT 1,
        unit_price DECIMAL(8,2),
        total_amount DECIMAL(10,2),
        payment_method TEXT DEFAULT 'cash' CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer', 'credit')),
        payment_status TEXT DEFAULT 'paid' CHECK (payment_status IN ('paid', 'partial', 'pending')),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' sales table ready.');

    // ============================================
    // EXPENSES
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS expenses (
        id SERIAL PRIMARY KEY,
        category TEXT CHECK (category IN ('feed', 'medicine', 'labor', 'equipment', 'utilities', 'other')) NOT NULL,
        description TEXT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        expense_date DATE NOT NULL,
        reference VARCHAR(100),
        supplier VARCHAR(100),
        payment_method TEXT DEFAULT 'cash' CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer')),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' expenses table ready.');

    // ============================================
    // EARNINGS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS earnings (
        id SERIAL PRIMARY KEY,
        source VARCHAR(100) NOT NULL,
        description TEXT,
        amount DECIMAL(10,2) NOT NULL,
        earning_date DATE NOT NULL,
        reference VARCHAR(100),
        payment_method TEXT DEFAULT 'cash' CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer')),
        status TEXT DEFAULT 'received' CHECK (status IN ('received', 'pending')),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' earnings table ready.');

    // ============================================
    // MILK ENTRIES (optional diversification)
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS milk_entries (
        id SERIAL PRIMARY KEY,
        entry_date DATE NOT NULL,
        quantity_liters DECIMAL(6,2) NOT NULL,
        quality_grade TEXT DEFAULT 'A' CHECK (quality_grade IN ('A', 'B', 'C')),
        price_per_liter DECIMAL(6,2),
        total_amount DECIMAL(8,2),
        supplier VARCHAR(100),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' milk_entries table ready.');

    // ============================================
    // AUDIT LOGS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS audit_logs (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        action VARCHAR(50) NOT NULL,
        table_name VARCHAR(50),
        record_id INTEGER,
        old_values TEXT,
        new_values TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' audit_logs table ready.');

    // ============================================
    // FINANCE LOGS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS finance_logs (
        id SERIAL PRIMARY KEY,
        transaction_type TEXT CHECK (transaction_type IN ('income', 'expense')) NOT NULL,
        category VARCHAR(50),
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        reference_table VARCHAR(50),
        reference_id INTEGER,
        transaction_date DATE NOT NULL,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' finance_logs table ready.');

    // ============================================
    // INVOICES
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS invoices (
        id SERIAL PRIMARY KEY,
        invoice_number VARCHAR(50) UNIQUE NOT NULL,
        type TEXT CHECK (type IN ('sale', 'purchase')) NOT NULL,
        customer_supplier_name VARCHAR(100) NOT NULL,
        customer_supplier_phone VARCHAR(20),
        customer_supplier_email VARCHAR(100),
        invoice_date DATE NOT NULL,
        due_date DATE,
        total_amount DECIMAL(10,2) NOT NULL,
        paid_amount DECIMAL(10,2) DEFAULT 0,
        tax_amount DECIMAL(10,2) DEFAULT 0,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'partial', 'overdue', 'cancelled')),
        payment_terms TEXT,
        notes TEXT,
        reference_sale_id INTEGER REFERENCES sales(id) ON DELETE SET NULL,
        reference_expense_id INTEGER REFERENCES expenses(id) ON DELETE SET NULL,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' invoices table ready.');

    // ============================================
    // INVOICE ITEMS (line items)
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS invoice_items (
        id SERIAL PRIMARY KEY,
        invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        description TEXT NOT NULL,
        quantity DECIMAL(10,2) DEFAULT 1,
        unit_price DECIMAL(8,2) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        category VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' invoice_items table ready.');

    // ============================================
    // DEBTS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS debts (
        id SERIAL PRIMARY KEY,
        debtor_name VARCHAR(100) NOT NULL,
        debtor_phone VARCHAR(20),
        debtor_email VARCHAR(100),
        debt_type TEXT CHECK (debt_type IN ('owed_to_us', 'we_owe')) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        paid_amount DECIMAL(10,2) DEFAULT 0,
        due_date DATE,
        status TEXT DEFAULT 'active' CHECK (status IN ('active', 'paid', 'cancelled', 'disputed')),
        description TEXT,
        reference_id INTEGER,
        reference_type TEXT CHECK (reference_type IN ('sale', 'expense', 'invoice')),
        payment_schedule TEXT,
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' debts table ready.');

    // ============================================
    // DEBT PAYMENTS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS debt_payments (
        id SERIAL PRIMARY KEY,
        debt_id INTEGER NOT NULL REFERENCES debts(id) ON DELETE CASCADE,
        payment_amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method TEXT DEFAULT 'cash' CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer', 'credit')),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' debt_payments table ready.');

    // ============================================
    // INVOICE PAYMENTS
    // ============================================
    await client.query(`
      CREATE TABLE IF NOT EXISTS invoice_payments (
        id SERIAL PRIMARY KEY,
        invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        payment_amount DECIMAL(10,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method TEXT DEFAULT 'cash' CHECK (payment_method IN ('cash', 'mobile_money', 'bank_transfer', 'credit')),
        notes TEXT,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);
    console.log(' invoice_payments table ready.');

    // ============================================
    // SEED DEFAULT DATA
    // ============================================

    // Default admin user (password: admin123)
    const bcrypt = require('bcryptjs');
    const hashedPassword = await bcrypt.hash('admin123', 12);

    await client.query(`
      INSERT INTO users (username, password, email, role)
      VALUES ('admin', $1, 'admin@kuku.com', 'admin')
      ON CONFLICT (username) DO NOTHING
    `, [hashedPassword]);
    console.log(' default admin user ready (password: admin123).');

    // Default chicken group
    await client.query(`
      INSERT INTO chicken_groups (name, description, breed)
      VALUES ('Default Group', 'Default chicken group', 'Mixed Breed')
      ON CONFLICT DO NOTHING
    `);
    console.log(' default chicken group ready.');

    // Default inventory items
    await client.query(`
      INSERT INTO items (name, category, description, unit, unit_cost, supplier)
      VALUES
        ('Chicken Feed', 'feed', 'Standard chicken feed', 'kg', 2.50, 'Local Supplier'),
        ('Vitamins', 'medicine', 'Chicken vitamin supplements', 'bottle', 15.00, 'AgriStore'),
        ('Antibiotics', 'medicine', 'Broad spectrum antibiotics', 'box', 25.00, 'AgriStore'),
        ('Water Dispenser', 'equipment', 'Automatic water dispenser', 'unit', 45.00, 'Farm Equipment Co'),
        ('Feed Trough', 'equipment', 'Metal feeding trough', 'unit', 30.00, 'Farm Equipment Co')
      ON CONFLICT DO NOTHING
    `);
    console.log(' default inventory items ready.');

    console.log('\n✅ All migrations completed successfully!');
    console.log('   Database: ' + databaseUrl.split('@')[1]?.split('/')[0] || 'connected');

  } catch (err) {
    console.error('❌ Migration failed:', err.message);
    process.exit(1);
  } finally {
    client.release();
    await pool.end();
  }
}

// Run migrations
runMigrations();
