const { app, BrowserWindow, ipcMain, dialog, shell } = require('electron');
const path = require('path');
const fs = require('fs');
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcrypt');
const { v4: uuidv4 } = require('uuid');

let mainWindow;
let db;
let isLoggedIn = false;
let currentUser = null;

// Enhanced database setup with comprehensive schema
function setupDatabase() {
    const userDataPath = app.getPath('userData');
    const dbPath = path.join(userDataPath, 'kuku_farm_pro.db');
    
    // Ensure directory exists
    if (!fs.existsSync(userDataPath)) {
        fs.mkdirSync(userDataPath, { recursive: true });
    }
    
    db = new sqlite3.Database(dbPath, (err) => {
        if (err) {
            console.error('Database connection error:', err);
            showErrorDialog('Database Error', 'Failed to connect to database: ' + err.message);
        } else {
            console.log('Connected to SQLite database at:', dbPath);
            initializeTables();
            setupTriggers();
            createIndexes();
        }
    });
    
    return dbPath;
}

function createIndexes() {
    db.serialize(() => {
        // Performance indexes
        db.run('CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)');
        db.run('CREATE INDEX IF NOT EXISTS idx_farmers_status ON farmers(status)');
        db.run('CREATE INDEX IF NOT EXISTS idx_chickens_status ON chickens(status)');
        db.run('CREATE INDEX IF NOT EXISTS idx_chickens_batch ON chickens(batch_id)');
        db.run('CREATE INDEX IF NOT EXISTS idx_batches_status ON chicken_batches(status)');
        db.run('CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(sale_date)');
        db.run('CREATE INDEX IF NOT EXISTS idx_expenses_date ON expenses(expense_date)');
        db.run('CREATE INDEX IF NOT EXISTS idx_expenses_category ON expenses(category)');
        db.run('CREATE INDEX IF NOT EXISTS idx_inventory_category ON inventory(category)');
        db.run('CREATE INDEX IF NOT EXISTS idx_health_chicken ON health_records(chicken_id)');
        db.run('CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_logs(user_id)');
        db.run('CREATE INDEX IF NOT EXISTS idx_audit_date ON audit_logs(created_at)');
        
        console.log('Database indexes created successfully');
    });
}

// Enhanced IPC handlers for all CRUD operations
ipcMain.handle('get-chickens', async () => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT c.*, cb.batch_number, cg.name as group_name, cg.breed
            FROM chickens c
            LEFT JOIN chicken_batches cb ON c.batch_id = cb.id
            LEFT JOIN chicken_groups cg ON cb.group_id = cg.id
            ORDER BY c.created_at DESC
        `;
        
        db.all(query, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

ipcMain.handle('add-chicken', async (event, chicken) => {
    return new Promise((resolve, reject) => {
        const chickenUuid = uuidv4();
        const stmt = db.prepare(`
            INSERT INTO chickens (uuid, batch_id, tag_number, gender, birth_date, weight, status, location, notes, image_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `);
        
        stmt.run([
            chickenUuid,
            chicken.batch_id,
            chicken.tag_number,
            chicken.gender,
            chicken.birth_date,
            chicken.weight,
            chicken.status || 'healthy',
            chicken.location,
            chicken.notes || '',
            chicken.image_url || ''
        ], function(err) {
            if (err) reject(err);
            else {
                logActivity(currentUser.id, 'CREATE', 'chickens', chickenUuid, null, JSON.stringify(chicken));
                resolve({ success: true, id: this.lastID, uuid: chickenUuid });
            }
        });
        stmt.finalize();
    });
});

ipcMain.handle('update-chicken', async (event, chicken) => {
    return new Promise((resolve, reject) => {
        const stmt = db.prepare(`
            UPDATE chickens 
            SET batch_id = ?, tag_number = ?, gender = ?, birth_date = ?, weight = ?, 
                status = ?, location = ?, notes = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP
            WHERE uuid = ?
        `);
        
        stmt.run([
            chicken.batch_id,
            chicken.tag_number,
            chicken.gender,
            chicken.birth_date,
            chicken.weight,
            chicken.status,
            chicken.location,
            chicken.notes,
            chicken.image_url,
            chicken.uuid
        ], function(err) {
            if (err) reject(err);
            else {
                logActivity(currentUser.id, 'UPDATE', 'chickens', chicken.uuid, null, JSON.stringify(chicken));
                resolve({ success: true });
            }
        });
        stmt.finalize();
    });
});

ipcMain.handle('delete-chicken', async (event, chickenUuid) => {
    return new Promise((resolve, reject) => {
        // Get chicken data for audit
        db.get('SELECT * FROM chickens WHERE uuid = ?', [chickenUuid], (err, chicken) => {
            if (err) reject(err);
            else {
                // Delete the chicken
                db.run('DELETE FROM chickens WHERE uuid = ?', [chickenUuid], (err) => {
                    if (err) reject(err);
                    else {
                        logActivity(currentUser.id, 'DELETE', 'chickens', chickenUuid, JSON.stringify(chicken), null);
                        resolve({ success: true });
                    }
                });
            }
        });
    });
});

// Enhanced batch management
ipcMain.handle('get-chicken-batches-with-stats', async () => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                cb.*,
                cg.name as group_name,
                cg.breed,
                COUNT(c.id) as total_chickens,
                COUNT(CASE WHEN c.status = 'healthy' THEN 1 END) as healthy_chickens,
                COUNT(CASE WHEN c.status = 'sick' THEN 1 END) as sick_chickens,
                COUNT(CASE WHEN c.status = 'sold' THEN 1 END) as sold_chickens,
                COUNT(CASE WHEN c.status = 'deceased' THEN 1 END) as deceased_chickens,
                AVG(c.weight) as average_weight,
                cb.purchase_cost,
                (cb.purchase_cost / NULLIF(COUNT(c.id), 0)) as cost_per_chicken
            FROM chicken_batches cb
            LEFT JOIN chicken_groups cg ON cb.group_id = cg.id
            LEFT JOIN chickens c ON cb.id = c.batch_id
            GROUP BY cb.id
            ORDER BY cb.purchase_date DESC
        `;
        
        db.all(query, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

// Enhanced sales management
ipcMain.handle('get-sales-with-details', async () => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                s.*,
                c.tag_number,
                cb.batch_number,
                cg.name as group_name,
                u.username as created_by_name
            FROM sales s
            LEFT JOIN chickens c ON s.chicken_id = c.id
            LEFT JOIN chicken_batches cb ON s.batch_id = cb.id
            LEFT JOIN chicken_groups cg ON cb.group_id = cg.id
            LEFT JOIN users u ON s.created_by = u.id
            ORDER BY s.sale_date DESC, s.created_at DESC
        `;
        
        db.all(query, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

// Enhanced expense management
ipcMain.handle('get-expenses-by-category', async () => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                category,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total,
                COALESCE(AVG(amount), 0) as average,
                COALESCE(MIN(amount), 0) as min_amount,
                COALESCE(MAX(amount), 0) as max_amount
            FROM expenses
            WHERE expense_date >= date('now', '-1 month')
            GROUP BY category
            ORDER BY total DESC
        `;
        
        db.all(query, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

// Inventory management
ipcMain.handle('get-inventory-with-alerts', async () => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                i.*,
                CASE 
                    WHEN i.quantity_on_hand <= i.min_stock_level THEN 1
                    ELSE 0
                END as low_stock_alert
            FROM inventory i
            ORDER BY 
                CASE 
                    WHEN i.quantity_on_hand <= i.min_stock_level THEN 0
                    ELSE 1
                END,
                i.name
        `;
        
        db.all(query, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

// Health records management
ipcMain.handle('get-health-records-by-chicken', async (event, chickenUuid) => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                hr.*,
                u.username as veterinarian_name
            FROM health_records hr
            LEFT JOIN users u ON hr.veterinarian = u.id
            WHERE hr.chicken_id = (SELECT id FROM chickens WHERE uuid = ?)
            ORDER BY hr.check_date DESC
        `;
        
        db.all(query, [chickenUuid], (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

// Advanced reporting
ipcMain.handle('get-financial-report', async (event, dateRange) => {
    return new Promise((resolve, reject) => {
        const { startDate, endDate } = dateRange;
        
        const query = `
            SELECT 
                'Revenue' as type,
                COALESCE(SUM(total_amount), 0) as amount,
                COUNT(*) as count,
                AVG(total_amount) as average,
                MIN(total_amount) as min_amount,
                MAX(total_amount) as max_amount
            FROM sales
            WHERE sale_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                'Expenses' as type,
                COALESCE(SUM(amount), 0) as amount,
                COUNT(*) as count,
                AVG(amount) as average,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount
            FROM expenses
            WHERE expense_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                'Net Profit' as type,
                (COALESCE((SELECT SUM(total_amount) FROM sales WHERE sale_date BETWEEN ? AND ?), 0) - 
                 COALESCE((SELECT SUM(amount) FROM expenses WHERE expense_date BETWEEN ? AND ?), 0)) as amount,
                1 as count,
                (COALESCE((SELECT SUM(total_amount) FROM sales WHERE sale_date BETWEEN ? AND ?), 0) - 
                 COALESCE((SELECT SUM(amount) FROM expenses WHERE expense_date BETWEEN ? AND ?), 0)) as average,
                (COALESCE((SELECT SUM(total_amount) FROM sales WHERE sale_date BETWEEN ? AND ?), 0) - 
                 COALESCE((SELECT SUM(amount) FROM expenses WHERE expense_date BETWEEN ? AND ?), 0)) as min_amount,
                (COALESCE((SELECT SUM(total_amount) FROM sales WHERE sale_date BETWEEN ? AND ?), 0) - 
                 COALESCE((SELECT SUM(amount) FROM expenses WHERE expense_date BETWEEN ? AND ?), 0)) as max_amount
        `;
        
        db.all(query, [startDate, endDate, startDate, endDate, startDate, endDate, startDate, endDate, startDate, endDate, startDate, endDate], (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

// Data export functionality
ipcMain.handle('export-data', async (event, exportOptions) => {
    return new Promise((resolve, reject) => {
        const { format, type, dateRange } = exportOptions;
        
        try {
            let data = [];
            let filename = '';
            
            switch (type) {
                case 'sales':
                    data = await getSalesData(dateRange);
                    filename = `sales_export_${new Date().toISOString().split('T')[0]}`;
                    break;
                case 'expenses':
                    data = await getExpensesData(dateRange);
                    filename = `expenses_export_${new Date().toISOString().split('T')[0]}`;
                    break;
                case 'chickens':
                    data = await getChickensData(dateRange);
                    filename = `chickens_export_${new Date().toISOString().split('T')[0]}`;
                    break;
                case 'farmers':
                    data = await getFarmersData();
                    filename = `farmers_export_${new Date().toISOString().split('T')[0]}`;
                    break;
            }
            
            if (format === 'csv') {
                const csv = convertToCSV(data);
                const filePath = path.join(app.getPath('downloads'), `${filename}.csv`);
                fs.writeFileSync(filePath, csv);
                resolve({ success: true, filePath });
            } else if (format === 'json') {
                const json = JSON.stringify(data, null, 2);
                const filePath = path.join(app.getPath('downloads'), `${filename}.json`);
                fs.writeFileSync(filePath, json);
                resolve({ success: true, filePath });
            } else {
                reject(new Error('Unsupported export format'));
            }
        } catch (error) {
            reject(error);
        }
    });
});

// Helper functions
async function getSalesData(dateRange) {
    return new Promise((resolve, reject) => {
        const { startDate, endDate } = dateRange;
        const query = `
            SELECT 
                s.*,
                c.tag_number,
                cb.batch_number,
                u.username as created_by_name
            FROM sales s
            LEFT JOIN chickens c ON s.chicken_id = c.id
            LEFT JOIN chicken_batches cb ON s.batch_id = cb.id
            LEFT JOIN users u ON s.created_by = u.id
            WHERE s.sale_date BETWEEN ? AND ?
            ORDER BY s.sale_date DESC
        `;
        
        db.all(query, [startDate, endDate], (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
}

async function getExpensesData(dateRange) {
    return new Promise((resolve, reject) => {
        const { startDate, endDate } = dateRange;
        const query = `
            SELECT 
                e.*,
                u.username as created_by_name
            FROM expenses e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.expense_date BETWEEN ? AND ?
            ORDER BY e.expense_date DESC
        `;
        
        db.all(query, [startDate, endDate], (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
}

function convertToCSV(data) {
    if (data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    const csvHeaders = headers.join(',');
    
    const csvRows = data.map(row => {
        return headers.map(header => {
            const value = row[header];
            return typeof value === 'string' && value.includes(',') 
                ? `"${value.replace(/"/g, '""')}"` 
                : value;
        }).join(',');
    });
    
    return [csvHeaders, ...csvRows].join('\n');
}

// Settings management
ipcMain.handle('get-settings', async () => {
    return new Promise((resolve, reject) => {
        db.all('SELECT * FROM settings', (err, rows) => {
            if (err) reject(err);
            else {
                const settings = {};
                rows.forEach(row => {
                    settings[row.key] = row.value;
                });
                resolve(settings);
            }
        });
    });
});

ipcMain.handle('update-setting', async (event, key, value) => {
    return new Promise((resolve, reject) => {
        db.run('INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)', 
               [key, value], function(err) {
            if (err) reject(err);
            else {
                logActivity(currentUser.id, 'UPDATE', 'settings', key, null, value);
                resolve({ success: true });
            }
        });
    });
});

// Search functionality
ipcMain.handle('search-global', async (event, searchTerm) => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 'chickens' as type, c.tag_number as identifier, c.name as title, 'Chicken' as category, c.status as status
            FROM chickens c
            WHERE c.tag_number LIKE ? OR c.notes LIKE ?
            
            UNION ALL
            
            SELECT 'sales' as type, s.invoice_number as identifier, s.customer_name as title, 'Sale' as category, s.total_amount as status
            FROM sales s
            WHERE s.customer_name LIKE ? OR s.notes LIKE ?
            
            UNION ALL
            
            SELECT 'expenses' as type, e.id as identifier, e.description as title, e.category as category, e.amount as status
            FROM expenses e
            WHERE e.description LIKE ? OR e.notes LIKE ?
            
            UNION ALL
            
            SELECT 'farmers' as type, f.email as identifier, f.name as title, 'Farmer' as category, f.status as status
            FROM farmers f
            WHERE f.name LIKE ? OR f.email LIKE ? OR f.phone LIKE ?
            
            LIMIT 50
        `;
        
        const searchPattern = `%${searchTerm}%`;
        db.all(query, [searchPattern, searchPattern, searchPattern, searchPattern, searchPattern, searchPattern, searchPattern, searchPattern], (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

// Backup and restore functionality
ipcMain.handle('backup-database', async (event, filePath) => {
    return new Promise((resolve, reject) => {
        try {
            const sourcePath = path.join(app.getPath('userData'), 'kuku_farm_pro.db');
            
            if (fs.existsSync(sourcePath)) {
                fs.copyFileSync(sourcePath, filePath);
                logActivity(currentUser.id, 'BACKUP', 'database', 'kuku_farm_pro.db', null, filePath);
                resolve({ success: true, message: 'Database backed up successfully' });
            } else {
                reject(new Error('Source database file not found'));
            }
        } catch (error) {
            reject(error);
        }
    });
});

ipcMain.handle('restore-database', async (event, filePath) => {
    return new Promise((resolve, reject) => {
        try {
            if (fs.existsSync(filePath)) {
                const targetPath = path.join(app.getPath('userData'), 'kuku_farm_pro.db');
                
                // Create backup of current database before restore
                const backupPath = path.join(app.getPath('userData'), `kuku_backup_${Date.now()}.db`);
                if (fs.existsSync(targetPath)) {
                    fs.copyFileSync(targetPath, backupPath);
                }
                
                // Copy new database
                fs.copyFileSync(filePath, targetPath);
                
                logActivity(currentUser.id, 'RESTORE', 'database', 'kuku_farm_pro.db', backupPath, filePath);
                resolve({ success: true, message: 'Database restored successfully' });
            } else {
                reject(new Error('Backup file not found'));
            }
        } catch (error) {
            reject(error);
        }
    });
});

// Enhanced statistics and analytics
ipcMain.handle('get-advanced-dashboard-stats', async () => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                -- Farmer stats
                (SELECT COUNT(*) FROM farmers WHERE status = 'active') as active_farmers,
                (SELECT COUNT(*) FROM farmers WHERE status = 'inactive') as inactive_farmers,
                
                -- Chicken stats
                (SELECT COUNT(*) FROM chickens WHERE status = 'healthy') as healthy_chickens,
                (SELECT COUNT(*) FROM chickens WHERE status = 'sick') as sick_chickens,
                (SELECT COUNT(*) FROM chickens WHERE status = 'sold') as sold_chickens,
                (SELECT COUNT(*) FROM chickens WHERE status = 'deceased') as deceased_chickens,
                (SELECT AVG(weight) FROM chickens WHERE status = 'healthy') as average_weight,
                
                -- Batch stats
                (SELECT COUNT(*) FROM chicken_batches WHERE status = 'active') as active_batches,
                
                -- Financial stats (current month)
                (SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sale_date >= date('now', '-1 month')) as monthly_revenue,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date >= date('now', '-1 month')) as monthly_expenses,
                
                -- Financial stats (current year)
                (SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sale_date >= date('now', '-1 year')) as yearly_revenue,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date >= date('now', '-1 year')) as yearly_expenses,
                
                -- Recent activity counts
                (SELECT COUNT(*) FROM sales WHERE sale_date >= date('now', '-7 days')) as recent_sales_count,
                (SELECT COUNT(*) FROM expenses WHERE expense_date >= date('now', '-7 days')) as recent_expenses_count,
                (SELECT COUNT(*) FROM health_records WHERE check_date >= date('now', '-7 days')) as recent_health_checks_count
        `;
        
        db.get(query, (err, row) => {
            if (err) reject(err);
            else resolve(row);
        });
    });
});

// Performance monitoring
ipcMain.handle('get-performance-metrics', async () => {
    return new Promise((resolve, reject) => {
        const query = `
            SELECT 
                (SELECT COUNT(*) FROM audit_logs WHERE created_at >= datetime('now', '-1 hour')) as hourly_actions,
                (SELECT COUNT(*) FROM audit_logs WHERE created_at >= datetime('now', '-24 hours')) as daily_actions,
                (SELECT COUNT(*) FROM audit_logs WHERE created_at >= datetime('now', '-7 days')) as weekly_actions,
                (SELECT COUNT(*) FROM audit_logs) as total_actions
        `;
        
        db.get(query, (err, row) => {
            if (err) reject(err);
            else resolve(row);
        });
    });
});

// Continue with the existing main.js code...
// (The rest of the main.js file would continue here)