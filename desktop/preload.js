const { contextBridge, ipcRenderer } = require('electron');

// Expose protected methods to renderer
contextBridge.exposeInMainWorld('electronAPI', {
    // Navigation
    navigateTo: (page) => ipcRenderer.send('navigate-to', page),
    
    // Window controls
    minimizeWindow: () => ipcRenderer.send('window-minimize'),
    maximizeWindow: () => ipcRenderer.send('window-maximize'),
    closeWindow: () => ipcRenderer.send('window-close'),
    quitApp: () => ipcRenderer.send('window-quit'),
    
    // Database operations
    login: (credentials) => ipcRenderer.invoke('login', credentials),
    logout: () => ipcRenderer.send('logout'),
    
    getDashboardStats: () => ipcRenderer.invoke('get-dashboard-stats'),
    getFarmers: () => ipcRenderer.invoke('get-farmers'),
    addFarmer: (farmer) => ipcRenderer.invoke('add-farmer', farmer),
    
    getChickenBatches: () => ipcRenderer.invoke('get-chicken-batches'),
    addChickenBatch: (batch) => ipcRenderer.invoke('add-chicken-batch', batch),
    
    getSales: () => ipcRenderer.invoke('get-sales'),
    addSale: (sale) => ipcRenderer.invoke('add-sale', sale),
    
    getExpenses: () => ipcRenderer.invoke('get-expenses'),
    addExpense: (expense) => ipcRenderer.invoke('add-expense', expense),
    
    // File operations
    exportData: (filePath) => ipcRenderer.send('export-data', filePath),
    importData: (filePath) => ipcRenderer.send('import-data', filePath),
    backupDatabase: (filePath) => ipcRenderer.send('backup-database', filePath),
    restoreDatabase: (filePath) => ipcRenderer.send('restore-database', filePath),
    
    // App info
    getVersion: () => ipcRenderer.invoke('get-version'),
    getAppInfo: () => ipcRenderer.invoke('get-app-info'),
    
    // Events
    onNavigateTo: (callback) => ipcRenderer.on('navigate-to', callback),
    onOpenNewFarmer: (callback) => ipcRenderer.on('open-new-farmer', callback),
    onOpenNewSale: (callback) => ipcRenderer.on('open-new-sale', callback),
    onOpenNewExpense: (callback) => ipcRenderer.on('open-new-expense', callback),
    onAppInitialized: (callback) => ipcRenderer.on('app-initialized', callback),
    onLoginSuccess: (callback) => ipcRenderer.on('login-success', callback),
    onLogout: (callback) => ipcRenderer.on('logout', callback),
    
    // Remove listeners
    removeAllListeners: (channel) => ipcRenderer.removeAllListeners(channel)
});