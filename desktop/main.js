const { app, BrowserWindow, Menu, ipcMain, dialog, shell, globalShortcut, Tray, nativeImage } = require('electron');
const path = require('path');
const fs = require('fs');
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcrypt');
const { v4: uuidv4 } = require('uuid');

let mainWindow;
let db;
let isLoggedIn = false;
let currentUser = null;

// Enhanced database setup with better schema
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
        }
    });
    
    return dbPath;
}

function initializeTables() {
    db.serialize(() => {
        // Enhanced Users table
        db.run(`CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT DEFAULT 'user',
            avatar TEXT,
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )`);

        // Enhanced Farmers table
        db.run(`CREATE TABLE IF NOT EXISTS farmers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            address TEXT,
            join_date DATE DEFAULT CURRENT_DATE,
            status TEXT DEFAULT 'active',
            avatar TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )`);

        // Enhanced Chicken Groups table
        db.run(`CREATE TABLE IF NOT EXISTS chicken_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            breed TEXT,
            color TEXT DEFAULT '#3498db',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )`);

        // Enhanced Chicken Batches table
        db.run(`CREATE TABLE IF NOT EXISTS chicken_batches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            group_id INTEGER,
            batch_number TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            purchase_date DATE NOT NULL,
            purchase_cost REAL,
            supplier TEXT,
            notes TEXT,
            status TEXT DEFAULT 'active',
            mortality_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES chicken_groups (id)
        )`);

        // Enhanced Chickens table
        db.run(`CREATE TABLE IF NOT EXISTS chickens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            batch_id INTEGER,
            tag_number TEXT UNIQUE,
            gender TEXT CHECK(gender IN ('male', 'female')),
            birth_date DATE,
            weight REAL,
            status TEXT DEFAULT 'healthy' CHECK(status IN ('healthy', 'sick', 'sold', 'deceased')),
            location TEXT,
            notes TEXT,
            image_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (batch_id) REFERENCES chicken_batches (id)
        )`);

        // Enhanced Sales table
        db.run(`CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            chicken_id INTEGER,
            batch_id INTEGER,
            sale_date DATE NOT NULL,
            customer_name TEXT,
            customer_phone TEXT,
            customer_email TEXT,
            quantity INTEGER DEFAULT 1,
            unit_price REAL,
            total_amount REAL,
            payment_method TEXT DEFAULT 'cash',
            payment_status TEXT DEFAULT 'paid',
            invoice_number TEXT,
            notes TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (chicken_id) REFERENCES chickens (id),
            FOREIGN KEY (batch_id) REFERENCES chicken_batches (id),
            FOREIGN KEY (created_by) REFERENCES users (id)
        )`);

        // Enhanced Expenses table
        db.run(`CREATE TABLE IF NOT EXISTS expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            category TEXT NOT NULL,
            description TEXT NOT NULL,
            amount REAL NOT NULL,
            expense_date DATE NOT NULL,
            supplier TEXT,
            payment_method TEXT DEFAULT 'cash',
            receipt_url TEXT,
            notes TEXT,
            tags TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users (id)
        )`);

        // Health Records table
        db.run(`CREATE TABLE IF NOT EXISTS health_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            chicken_id INTEGER NOT NULL,
            check_date DATE NOT NULL,
            weight REAL,
            temperature REAL,
            symptoms TEXT,
            diagnosis TEXT,
            treatment TEXT,
            veterinarian TEXT,
            cost REAL,
            next_check_date DATE,
            notes TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (chicken_id) REFERENCES chickens (id),
            FOREIGN KEY (created_by) REFERENCES users (id)
        )`);

        // Inventory table
        db.run(`CREATE TABLE IF NOT EXISTS inventory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            description TEXT,
            unit TEXT NOT NULL,
            unit_cost REAL,
            quantity_on_hand REAL DEFAULT 0,
            min_stock_level REAL DEFAULT 0,
            supplier TEXT,
            last_restocked DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )`);

        // Audit Logs table
        db.run(`CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            table_name TEXT,
            record_id TEXT,
            old_values TEXT,
            new_values TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )`);

        // Settings table
        db.run(`CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            value TEXT,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )`);

        // Insert default data
        insertDefaultData();
    });
}

function insertDefaultData() {
    // Insert default admin user
    const adminUuid = uuidv4();
    const hashedPassword = bcrypt.hashSync('admin123', 12);
    
    db.run(`INSERT OR IGNORE INTO users (uuid, username, password, email, role) 
             VALUES (?, ?, ?, ?, ?)`, 
             [adminUuid, 'admin', hashedPassword, 'admin@kuku.com', 'admin']);

    // Insert default chicken group
    const defaultGroupUuid = uuidv4();
    db.run(`INSERT OR IGNORE INTO chicken_groups (uuid, name, description, breed) 
             VALUES (?, ?, ?, ?)`, 
             [defaultGroupUuid, 'Default Group', 'Default chicken group for general farming', 'Mixed Breed']);

    // Insert default inventory items
    const defaultItems = [
        ['Chicken Feed', 'feed', 'Standard chicken feed for layers and broilers', 'kg', 2.50, 100],
        ['Layer Feed', 'feed', 'Specialized feed for laying hens', 'kg', 3.20, 50],
        ['Broiler Feed', 'feed', 'High-protein feed for meat chickens', 'kg', 2.80, 50],
        ['Vitamins', 'medicine', 'Chicken vitamin supplements', 'bottle', 15.00, 20],
        ['Antibiotics', 'medicine', 'Broad spectrum antibiotics', 'box', 25.00, 10],
        ['Vaccines', 'medicine', 'Routine chicken vaccines', 'dose', 8.50, 30],
        ['Water Dispenser', 'equipment', 'Automatic water dispenser', 'unit', 45.00, 5],
        ['Feed Trough', 'equipment', 'Metal feeding trough', 'unit', 30.00, 10],
        ['Heat Lamp', 'equipment', 'Brooder heat lamp for chicks', 'unit', 25.00, 15]
    ];

    defaultItems.forEach(item => {
        const itemUuid = uuidv4();
        db.run(`INSERT OR IGNORE INTO inventory (uuid, name, category, description, unit, unit_cost, quantity_on_hand) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)`, 
                 [itemUuid, ...item]);
    });

    // Insert default settings
    const defaultSettings = [
        ['farm_name', 'KUKU Farm', 'Name of your farm'],
        ['currency', 'USD', 'Default currency for financial calculations'],
        ['date_format', 'YYYY-MM-DD', 'Date format for display'],
        ['low_stock_alert', 'true', 'Enable low stock alerts'],
        ['backup_reminder', 'weekly', 'Backup reminder frequency']
    ];

    defaultSettings.forEach(setting => {
        db.run(`INSERT OR IGNORE INTO settings (key, value, description) 
                 VALUES (?, ?, ?)`, setting);
    });
}

function setupTriggers() {
    // Audit trigger for users table
    db.run(`CREATE TRIGGER IF NOT EXISTS audit_users_update
        AFTER UPDATE ON users
        BEGIN
            INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values)
            VALUES (NEW.id, 'UPDATE', 'users', NEW.uuid, OLD.username || ' -> ' || NEW.username, json_object('updated', json(NEW)));
        END`);

    // Update timestamp trigger
    db.run(`CREATE TRIGGER IF NOT EXISTS update_users_timestamp
        BEFORE UPDATE ON users
        BEGIN
            UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
        END`);
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1000,
        minHeight: 700,
        show: false,
        frame: false,
        titleBarStyle: 'hiddenInset',
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false,
            enableRemoteModule: false,
            webSecurity: false,
            sandbox: false
        },
        icon: path.join(__dirname, 'assets', 'icon.png')
    });

    // Create system tray
    createTray();

    // Set up global shortcuts
    setupGlobalShortcuts();

    // Load the app
    mainWindow.loadFile(path.join(__dirname, 'renderer', 'index.html'));

    // Handle window state
    setupWindowControls();

    // Show window when ready
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
        mainWindow.focus();
        mainWindow.center();
        
        // Send initial data
        mainWindow.webContents.send('app-initialized', {
            version: app.getVersion(),
            platform: process.platform,
            userDataPath: app.getPath('userData')
        });
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
        if (db) {
            db.close();
        }
    });

    // Handle external links
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });
}

function createTray() {
    const trayIconPath = path.join(__dirname, 'assets', 'tray-icon.png');
    const tray = new Tray(trayIconPath);
    
    const contextMenu = Menu.buildFromTemplate([
        {
            label: 'Show KUKU Farm Manager',
            click: () => {
                mainWindow.show();
                mainWindow.focus();
            }
        },
        { type: 'separator' },
        {
            label: 'Dashboard',
            click: () => {
                mainWindow.webContents.send('navigate-to', 'dashboard');
                mainWindow.show();
            }
        },
        {
            label: 'Add Sale',
            accelerator: 'CmdOrCtrl+S',
            click: () => {
                mainWindow.webContents.send('open-new-sale');
                mainWindow.show();
            }
        },
        { type: 'separator' },
        {
            label: 'Quit',
            accelerator: process.platform === 'darwin' ? 'Cmd+Q' : 'Ctrl+Q',
            click: () => {
                app.quit();
            }
        }
    ]);
    
    tray.setToolTip('KUKU Farm Manager Pro');
    tray.setContextMenu(contextMenu);
    
    // Handle tray double-click
    tray.on('double-click', () => {
        mainWindow.show();
        mainWindow.focus();
    });
}

function setupGlobalShortcuts() {
    // Global shortcuts for quick access
    globalShortcut.register('CmdOrCtrl+Shift+K', () => {
        if (mainWindow) {
            mainWindow.show();
            mainWindow.focus();
        }
    });

    globalShortcut.register('CmdOrCtrl+Shift+S', () => {
        if (mainWindow && isLoggedIn) {
            mainWindow.webContents.send('quick-add-sale');
            mainWindow.show();
        }
    });

    globalShortcut.register('CmdOrCtrl+Shift+E', () => {
        if (mainWindow && isLoggedIn) {
            mainWindow.webContents.send('quick-add-expense');
            mainWindow.show();
        }
    });
}

function setupWindowControls() {
    // Custom window controls
    ipcMain.handle('window-minimize', () => {
        mainWindow.minimize();
    });

    ipcMain.handle('window-maximize', () => {
        if (mainWindow.isMaximized()) {
            mainWindow.unmaximize();
        } else {
            mainWindow.maximize();
        }
    });

    ipcMain.handle('window-close', () => {
        mainWindow.hide();
    });

    ipcMain.handle('window-quit', () => {
        app.quit();
    });
}

function setupMenu() {
    const template = [
        {
            label: 'File',
            submenu: [
                {
                    label: 'New Farmer',
                    accelerator: 'CmdOrCtrl+N',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('open-new-farmer');
                        }
                    }
                },
                {
                    label: 'New Sale',
                    accelerator: 'CmdOrCtrl+S',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('open-new-sale');
                        }
                    }
                },
                {
                    label: 'New Expense',
                    accelerator: 'CmdOrCtrl+E',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('open-new-expense');
                        }
                    }
                },
                { type: 'separator' },
                {
                    label: 'Import Data',
                    click: async () => {
                        const result = await dialog.showOpenDialog(mainWindow, {
                            title: 'Import Data',
                            filters: [
                                { name: 'CSV Files', extensions: ['csv'] },
                                { name: 'JSON Files', extensions: ['json'] }
                            ],
                            properties: ['openFile']
                        });
                        
                        if (!result.canceled) {
                            mainWindow.webContents.send('import-data', result.filePaths[0]);
                        }
                    }
                },
                {
                    label: 'Export Data',
                    click: async () => {
                        const result = await dialog.showSaveDialog(mainWindow, {
                            title: 'Export Data',
                            defaultPath: 'kuku_farm_export.csv',
                            filters: [
                                { name: 'CSV Files', extensions: ['csv'] },
                                { name: 'JSON Files', extensions: ['json'] }
                            ]
                        });
                        
                        if (!result.canceled) {
                            mainWindow.webContents.send('export-data', result.filePath);
                        }
                    }
                },
                { type: 'separator' },
                {
                    label: 'Exit',
                    accelerator: process.platform === 'darwin' ? 'Cmd+Q' : 'Ctrl+Q',
                    click: () => {
                        app.quit();
                    }
                }
            ]
        },
        {
            label: 'View',
            submenu: [
                {
                    label: 'Dashboard',
                    accelerator: 'CmdOrCtrl+D',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('navigate-to', 'dashboard');
                        }
                    }
                },
                {
                    label: 'Farmers',
                    accelerator: 'CmdOrCtrl+F',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('navigate-to', 'farmers');
                        }
                    }
                },
                {
                    label: 'Chickens',
                    accelerator: 'CmdOrCtrl+H',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('navigate-to', 'chickens');
                        }
                    }
                },
                {
                    label: 'Sales',
                    accelerator: 'CmdOrCtrl+Shift+S',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('navigate-to', 'sales');
                        }
                    }
                },
                {
                    label: 'Expenses',
                    accelerator: 'CmdOrCtrl+Shift+E',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('navigate-to', 'expenses');
                        }
                    }
                },
                { type: 'separator' },
                {
                    label: 'Reload',
                    accelerator: 'F5',
                    click: () => {
                        mainWindow.webContents.reload();
                    }
                },
                {
                    label: 'Developer Tools',
                    accelerator: process.platform === 'darwin' ? 'Alt+Cmd+I' : 'Ctrl+Shift+I',
                    click: () => {
                        mainWindow.webContents.toggleDevTools();
                    }
                }
            ]
        },
        {
            label: 'Tools',
            submenu: [
                {
                    label: 'Backup Database',
                    accelerator: 'CmdOrCtrl+Shift+B',
                    click: async () => {
                        const result = await dialog.showSaveDialog(mainWindow, {
                            title: 'Backup Database',
                            defaultPath: `kuku_backup_${new Date().toISOString().split('T')[0]}.db`,
                            filters: [
                                { name: 'Database Files', extensions: ['db'] }
                            ]
                        });
                        
                        if (!result.canceled) {
                            mainWindow.webContents.send('backup-database', result.filePath);
                        }
                    }
                },
                {
                    label: 'Restore Database',
                    accelerator: 'CmdOrCtrl+Shift+R',
                    click: async () => {
                        const result = await dialog.showOpenDialog(mainWindow, {
                            title: 'Restore Database',
                            filters: [
                                { name: 'Database Files', extensions: ['db'] }
                            ],
                            properties: ['openFile']
                        });
                        
                        if (!result.canceled) {
                            const confirmed = await dialog.showMessageBox(mainWindow, {
                                type: 'warning',
                                buttons: ['Cancel', 'Restore'],
                                defaultId: 1,
                                title: 'Confirm Restore',
                                message: 'This will replace your current database. Are you sure?'
                            });
                            
                            if (confirmed.response === 1) {
                                mainWindow.webContents.send('restore-database', result.filePaths[0]);
                            }
                        }
                    }
                },
                { type: 'separator' },
                {
                    label: 'Settings',
                    accelerator: 'CmdOrCtrl+,',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('navigate-to', 'settings');
                        }
                    }
                },
                {
                    label: 'Clear Cache',
                    click: () => {
                        mainWindow.webContents.send('clear-cache');
                    }
                }
            ]
        },
        {
            label: 'Reports',
            submenu: [
                {
                    label: 'Financial Report',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('generate-report', 'financial');
                        }
                    }
                },
                {
                    label: 'Inventory Report',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('generate-report', 'inventory');
                        }
                    }
                },
                {
                    label: 'Health Report',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('generate-report', 'health');
                        }
                    }
                },
                { type: 'separator' },
                {
                    label: 'Custom Report',
                    click: () => {
                        if (isLoggedIn) {
                            mainWindow.webContents.send('navigate-to', 'reports');
                        }
                    }
                }
            ]
        },
        {
            label: 'Help',
            submenu: [
                {
                    label: 'User Guide',
                    click: () => {
                        shell.openExternal('https://kukufarm.com/guide');
                    }
                },
                {
                    label: 'Video Tutorials',
                    click: () => {
                        shell.openExternal('https://kukufarm.com/tutorials');
                    }
                },
                {
                    label: 'Check for Updates',
                    click: () => {
                        mainWindow.webContents.send('check-updates');
                    }
                },
                { type: 'separator' },
                {
                    label: 'About KUKU Farm Manager Pro',
                    click: () => {
                        dialog.showMessageBox(mainWindow, {
                            type: 'info',
                            title: 'About',
                            message: 'KUKU Farm Manager Pro v2.0.0',
                            detail: 'Professional farm management system with advanced analytics and IoT integration.\n\n© 2024 KUKU Systems. All rights reserved.'
                        });
                    }
                }
            ]
        }
    ];

    const menu = Menu.buildFromTemplate(template);
    Menu.setApplicationMenu(menu);
}

function showErrorDialog(title, message) {
    dialog.showErrorBox(title, message);
}

// Enhanced IPC handlers
ipcMain.handle('login', async (event, credentials) => {
    return new Promise((resolve, reject) => {
        db.get('SELECT * FROM users WHERE username = ?', [credentials.username], (err, user) => {
            if (err) {
                reject(err);
            } else if (!user) {
                resolve({ success: false, message: 'User not found' });
            } else {
                bcrypt.compare(credentials.password, user.password, (err, result) => {
                    if (result) {
                        isLoggedIn = true;
                        currentUser = {
                            id: user.id,
                            uuid: user.uuid,
                            username: user.username,
                            email: user.email,
                            role: user.role
                        };
                        
                        // Update last login
                        db.run('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?', [user.id]);
                        
                        // Log the login
                        logActivity(user.id, 'LOGIN', 'users', user.uuid, null, JSON.stringify(currentUser));
                        
                        resolve({ success: true, user: currentUser });
                    } else {
                        resolve({ success: false, message: 'Invalid password' });
                    }
                });
            }
        });
    });
});

function logActivity(userId, action, table, recordId, oldValues, newValues) {
    db.run(`INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values) 
             VALUES (?, ?, ?, ?, ?, ?)`, 
             [userId, action, table, recordId, oldValues, newValues]);
}

// Database operation handlers
ipcMain.handle('get-dashboard-stats', async () => {
    return new Promise((resolve, reject) => {
        const queries = {
            farmers: "SELECT COUNT(*) as count FROM farmers WHERE status = 'active'",
            chickens: "SELECT COUNT(*) as count FROM chickens WHERE status = 'healthy'",
            batches: "SELECT COUNT(*) as count FROM chicken_batches WHERE status = 'active'",
            revenue: "SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE sale_date >= date('now', '-1 month')",
            expenses: "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_date >= date('now', '-1 month')",
            recent_sales: "SELECT * FROM sales ORDER BY sale_date DESC LIMIT 5",
            recent_expenses: "SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 5"
        };

        const results = {};
        let completed = 0;
        const total = Object.keys(queries).length;

        Object.entries(queries).forEach(([key, query]) => {
            db.all(query, (err, rows) => {
                if (!err) {
                    results[key] = key.includes('recent_') ? rows : (rows[0] || {});
                }
                
                if (++completed === total) {
                    resolve(results);
                }
            });
        });
    });
});

// More IPC handlers for all CRUD operations
ipcMain.handle('get-farmers', async () => {
    return new Promise((resolve, reject) => {
        db.all('SELECT * FROM farmers ORDER BY name', (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
});

ipcMain.handle('add-farmer', async (event, farmer) => {
    return new Promise((resolve, reject) => {
        const farmerUuid = uuidv4();
        const stmt = db.prepare(`INSERT INTO farmers (uuid, name, email, phone, address, status, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)`);
        stmt.run([
            farmerUuid,
            farmer.name,
            farmer.email,
            farmer.phone,
            farmer.address,
            farmer.status || 'active',
            farmer.notes || ''
        ], function(err) {
            if (err) reject(err);
            else {
                logActivity(currentUser.id, 'CREATE', 'farmers', farmerUuid, null, JSON.stringify(farmer));
                resolve({ success: true, id: this.lastID, uuid: farmerUuid });
            }
        });
        stmt.finalize();
    });
});

// Add more handlers for chickens, sales, expenses, etc.
// (Continuing in next response due to length limits...)

// App event handlers
app.whenReady().then(() => {
    const dbPath = setupDatabase();
    createWindow();
    setupMenu();
    
    console.log('KUKU Farm Manager Pro is ready!');
    console.log('Database path:', dbPath);
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
        createWindow();
    }
});

app.on('before-quit', () => {
    // Clean up global shortcuts
    globalShortcut.unregisterAll();
});

// Auto-updater setup (for future releases)
app.on('ready', () => {
    // Auto-updater logic can be added here
    console.log('Auto-updater ready');
});