const { app, BrowserWindow, ipcMain, dialog, Menu } = require('electron');
const path = require('path');
const fs = require('fs');
const sqlite3 = require('sqlite3').verbose();

// Keep a global reference of the window object
let mainWindow;

// Database setup
let db;
const dbPath = path.join(app.getPath('userData'), 'kuku_farm.db');

// Initialize database
function initDatabase() {
    db = new sqlite3.Database(dbPath, (err) => {
        if (err) {
            console.error('Database connection error:', err);
            return;
        }
        console.log('Connected to SQLite database');

        // Create tables if they don't exist
        createTables();
    });
}

function createTables() {
    const schemaPath = path.join(__dirname, 'database', 'migrations', '001_create_tables.sql');

    if (fs.existsSync(schemaPath)) {
        const schema = fs.readFileSync(schemaPath, 'utf8');
        const statements = schema.split(';').filter(stmt => stmt.trim().length > 0);

        statements.forEach((statement, index) => {
            db.run(statement.trim(), (err) => {
                if (err && !err.message.includes('already exists')) {
                    console.error(`Error executing statement ${index}:`, err);
                }
            });
        });

        // Also run the invoices and debts migration if it exists
        const invoicesDebtsPath = path.join(__dirname, 'database', 'migrations', '002_invoices_debts.sql');
        if (fs.existsSync(invoicesDebtsPath)) {
            const invoicesDebtsSchema = fs.readFileSync(invoicesDebtsPath, 'utf8');
            db.run(invoicesDebtsSchema, (err) => {
                if (err && !err.message.includes('already exists')) {
                    console.error('Error applying invoices/debts migration:', err);
                }
            });
        }

        // Also run the enhancement migration if it exists
        const enhancementPath = path.join(__dirname, 'database', 'migrations', '003_chicken_enhancement.sql');
        if (fs.existsSync(enhancementPath)) {
            const enhancementSchema = fs.readFileSync(enhancementPath, 'utf8');
            db.run(enhancementSchema, (err) => {
                if (err && !err.message.includes('already exists')) {
                    console.error('Error applying enhancement:', err);
                }
            });
        }
    }
}

function createWindow() {
    // Create the browser window
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1000,
        minHeight: 700,
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false,
            enableRemoteModule: true
        },
        icon: path.join(__dirname, 'assets', 'icons', 'icon.png'),
        title: 'KUKU Farm Management System',
        show: false
    });

    // Load the app
    mainWindow.loadFile('public/index.html');

    // Show window when ready to prevent visual flash
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
    });

    // Emitted when the window is closed
    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    // Create application menu
    createMenu();
}

// Create application menu
function createMenu() {
    const template = [
        {
            label: 'File',
            submenu: [
                {
                    label: 'New Record',
                    accelerator: 'CmdOrCtrl+N',
                    click: () => {
                        mainWindow.webContents.send('menu-action', 'new-record');
                    }
                },
                { type: 'separator' },
                {
                    label: 'Export Data',
                    click: () => {
                        mainWindow.webContents.send('menu-action', 'export');
                    }
                },
                {
                    label: 'Import Data',
                    click: () => {
                        mainWindow.webContents.send('menu-action', 'import');
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
            label: 'Edit',
            submenu: [
                { role: 'undo' },
                { role: 'redo' },
                { type: 'separator' },
                { role: 'cut' },
                { role: 'copy' },
                { role: 'paste' },
                { role: 'selectall' }
            ]
        },
        {
            label: 'View',
            submenu: [
                {
                    label: 'Dashboard',
                    accelerator: 'CmdOrCtrl+D',
                    click: () => {
                        mainWindow.webContents.send('navigate', 'dashboard');
                    }
                },
                {
                    label: 'Chickens',
                    accelerator: 'CmdOrCtrl+1',
                    click: () => {
                        mainWindow.webContents.send('navigate', 'chickens');
                    }
                },
                {
                    label: 'Inventory',
                    accelerator: 'CmdOrCtrl+2',
                    click: () => {
                        mainWindow.webContents.send('navigate', 'inventory');
                    }
                },
                {
                    label: 'Sales',
                    accelerator: 'CmdOrCtrl+3',
                    click: () => {
                        mainWindow.webContents.send('navigate', 'sales');
                    }
                },
                {
                    label: 'Financial',
                    accelerator: 'CmdOrCtrl+4',
                    click: () => {
                        mainWindow.webContents.send('navigate', 'financial');
                    }
                },
                { type: 'separator' },
                { role: 'reload' },
                { role: 'toggledevtools' },
                { role: 'resetzoom' },
                { role: 'zoomin' },
                { role: 'zoomout' },
                { type: 'separator' },
                { role: 'togglefullscreen' }
            ]
        },
        {
            label: 'Tools',
            submenu: [
                {
                    label: 'Backup Data',
                    click: () => {
                        mainWindow.webContents.send('menu-action', 'backup');
                    }
                },
                {
                    label: 'Restore Data',
                    click: () => {
                        mainWindow.webContents.send('menu-action', 'restore');
                    }
                },
                { type: 'separator' },
                {
                    label: 'Settings',
                    click: () => {
                        mainWindow.webContents.send('menu-action', 'settings');
                    }
                }
            ]
        },
        {
            label: 'Help',
            submenu: [
                {
                    label: 'Documentation',
                    click: () => {
                        mainWindow.webContents.send('menu-action', 'docs');
                    }
                },
                {
                    label: 'About',
                    click: () => {
                        dialog.showMessageBox(mainWindow, {
                            type: 'info',
                            title: 'About KUKU Farm Management',
                            message: 'KUKU Farm Management System v1.0.0',
                            detail: 'A comprehensive farm management solution for chicken farming operations.\n\nFeatures include inventory management, financial tracking, sales management, and detailed reporting.'
                        });
                    }
                }
            ]
        }
    ];

    // macOS specific menu adjustments
    if (process.platform === 'darwin') {
        template.unshift({
            label: app.getName(),
            submenu: [
                { role: 'about' },
                { type: 'separator' },
                { role: 'services', submenu: [] },
                { type: 'separator' },
                { role: 'hide' },
                { role: 'hideothers' },
                { role: 'unhide' },
                { type: 'separator' },
                { role: 'quit' }
            ]
        });
    }

    const menu = Menu.buildFromTemplate(template);
    Menu.setApplicationMenu(menu);
}

// IPC handlers for database operations
ipcMain.handle('db-query', async (event, { sql, params = [] }) => {
    return new Promise((resolve, reject) => {
        db.all(sql, params, (err, rows) => {
            if (err) {
                reject(err);
            } else {
                resolve(rows);
            }
        });
    });
});

ipcMain.handle('db-run', async (event, { sql, params = [] }) => {
    return new Promise((resolve, reject) => {
        db.run(sql, params, function(err) {
            if (err) {
                reject(err);
            } else {
                resolve({ lastID: this.lastID, changes: this.changes });
            }
        });
    });
});

ipcMain.handle('export-data', async () => {
    const result = await dialog.showSaveDialog(mainWindow, {
        title: 'Export Data',
        defaultPath: 'kuku_farm_backup.json',
        filters: [
            { name: 'JSON Files', extensions: ['json'] },
            { name: 'All Files', extensions: ['*'] }
        ]
    });

    if (!result.canceled) {
        // Export all data
        const tables = ['users', 'farmers', 'chicken_groups', 'chicken_batches', 'chickens',
                       'items', 'inventory', 'sales', 'expenses', 'invoices', 'debts'];

        const exportData = {};

        for (const table of tables) {
            try {
                const rows = await new Promise((resolve, reject) => {
                    db.all(`SELECT * FROM ${table}`, (err, rows) => {
                        if (err) reject(err);
                        else resolve(rows);
                    });
                });
                exportData[table] = rows;
            } catch (err) {
                console.error(`Error exporting table ${table}:`, err);
            }
        }

        fs.writeFileSync(result.filePath, JSON.stringify(exportData, null, 2));
        return { success: true, path: result.filePath };
    }

    return { success: false };
});

ipcMain.handle('import-data', async () => {
    const result = await dialog.showOpenDialog(mainWindow, {
        title: 'Import Data',
        filters: [
            { name: 'JSON Files', extensions: ['json'] },
            { name: 'All Files', extensions: ['*'] }
        ],
        properties: ['openFile']
    });

    if (!result.canceled && result.filePaths.length > 0) {
        try {
            const data = JSON.parse(fs.readFileSync(result.filePaths[0], 'utf8'));

            // Import data (simplified - in production, you'd want more robust import logic)
            for (const [table, rows] of Object.entries(data)) {
                if (Array.isArray(rows) && rows.length > 0) {
                    // Clear existing data and insert new data
                    await new Promise((resolve, reject) => {
                        db.run(`DELETE FROM ${table}`, (err) => {
                            if (err) reject(err);
                            else resolve();
                        });
                    });

                    for (const row of rows) {
                        const columns = Object.keys(row).join(', ');
                        const placeholders = Object.keys(row).map(() => '?').join(', ');
                        const values = Object.values(row);

                        await new Promise((resolve, reject) => {
                            db.run(`INSERT INTO ${table} (${columns}) VALUES (${placeholders})`, values, (err) => {
                                if (err) reject(err);
                                else resolve();
                            });
                        });
                    }
                }
            }

            return { success: true };
        } catch (err) {
            console.error('Import error:', err);
            return { success: false, error: err.message };
        }
    }

    return { success: false };
});

// App event listeners
app.on('ready', () => {
    initDatabase();
    createWindow();
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (mainWindow === null) {
        createWindow();
    }
});

// Handle app quit
app.on('before-quit', () => {
    if (db) {
        db.close((err) => {
            if (err) {
                console.error('Error closing database:', err);
            } else {
                console.log('Database connection closed');
            }
        });
    }
});