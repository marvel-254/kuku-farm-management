// KUKU Farm Manager Pro - Main Application JavaScript

class KUKUFarmManager {
    constructor() {
        this.currentUser = null;
        this.currentPage = 'dashboard';
        this.init();
    }

    async init() {
        // Wait for app initialization
        window.electronAPI.onAppInitialized((event, data) => {
            console.log('KUKU Farm Manager Pro v' + data.version);
            console.log('Platform:', data.platform);
            console.log('User data path:', data.userDataPath);
        });

        // Setup event listeners
        this.setupEventListeners();
        
        // Check if user is logged in
        await this.checkAuthStatus();
    }

    setupEventListeners() {
        // Navigation
        window.electronAPI.onNavigateTo((event, page) => {
            this.navigateTo(page);
        });

        // Window controls
        document.getElementById('minimize-btn')?.addEventListener('click', () => {
            window.electronAPI.minimizeWindow();
        });

        document.getElementById('maximize-btn')?.addEventListener('click', () => {
            window.electronAPI.maximizeWindow();
        });

        document.getElementById('close-btn')?.addEventListener('click', () => {
            window.electronAPI.closeWindow();
        });

        // Menu toggle
        document.getElementById('menu-toggle')?.addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Logout
        document.getElementById('logout-btn')?.addEventListener('click', () => {
            this.logout();
        });

        // Login form
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        // Sidebar navigation
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', () => {
                const page = item.dataset.page;
                this.navigateTo(page);
            });
        });
    }

    async checkAuthStatus() {
        // For demo purposes, we'll show the main app
        // In a real app, you'd check stored credentials
        this.showMainApp();
    }

    async handleLogin() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        if (!username || !password) {
            this.showNotification('Error', 'Please enter both username and password');
            return;
        }

        this.showLoading(true);

        try {
            const result = await window.electronAPI.login({ username, password });
            
            if (result.success) {
                this.currentUser = result.user;
                this.showNotification('Success', 'Login successful! Welcome back, ' + result.user.username);
                this.showMainApp();
                await this.loadDashboardData();
            } else {
                this.showNotification('Error', result.message || 'Login failed');
            }
        } catch (error) {
            this.showNotification('Error', 'Login error: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    logout() {
        this.currentUser = null;
        this.showLoginScreen();
        window.electronAPI.logout();
        this.showNotification('Info', 'You have been logged out');
    }

    navigateTo(page) {
        this.currentPage = page;
        
        // Update active menu item
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-page="${page}"]`)?.classList.add('active');
        
        // Update page title
        const titles = {
            dashboard: 'Dashboard',
            farmers: 'Farmers',
            chickens: 'Chickens',
            batches: 'Batches',
            sales: 'Sales',
            expenses: 'Expenses',
            inventory: 'Inventory',
            health: 'Health',
            reports: 'Reports',
            settings: 'Settings'
        };
        
        document.getElementById('page-title').textContent = titles[page] || 'Dashboard';
        
        // Load page content
        this.loadPageContent(page);
    }

    loadPageContent(page) {
        const content = document.getElementById('page-content');
        if (!content) return;

        // Clear current content
        content.innerHTML = '';

        switch (page) {
            case 'dashboard':
                this.loadDashboardContent(content);
                break;
            case 'farmers':
                this.loadFarmersContent(content);
                break;
            case 'chickens':
                this.loadChickensContent(content);
                break;
            case 'batches':
                this.loadBatchesContent(content);
                break;
            case 'sales':
                this.loadSalesContent(content);
                break;
            case 'expenses':
                this.loadExpensesContent(content);
                break;
            case 'inventory':
                this.loadInventoryContent(content);
                break;
            case 'health':
                this.loadHealthContent(content);
                break;
            case 'reports':
                this.loadReportsContent(content);
                break;
            case 'settings':
                this.loadSettingsContent(content);
                break;
            default:
                this.loadDashboardContent(content);
        }
    }

    async loadDashboardData() {
        try {
            const stats = await window.electronAPI.getDashboardStats();
            this.updateDashboardStats(stats);
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
        }
    }

    updateDashboardStats(stats) {
        // Update stat cards
        document.getElementById('total-farmers').textContent = stats.farmers?.count || 0;
        document.getElementById('total-chickens').textContent = stats.chickens?.count || 0;
        document.getElementById('monthly-revenue').textContent = '$' + (stats.revenue?.total || 0).toLocaleString();
        document.getElementById('monthly-expenses').textContent = '$' + (stats.expenses?.total || 0).toLocaleString();

        // Update recent activity
        this.updateRecentActivity(stats);
        
        // Update charts
        this.updateCharts(stats);
    }

    updateRecentActivity(stats) {
        const salesContainer = document.getElementById('recent-sales');
        const expensesContainer = document.getElementById('recent-expenses');

        if (salesContainer && stats.recent_sales) {
            salesContainer.innerHTML = stats.recent_sales.map(sale => `
                <div class="activity-item">
                    <div class="activity-icon success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${sale.customer_name || 'Unknown Customer'}</div>
                        <div class="activity-details">
                            <span class="activity-amount">$${sale.total_amount || 0}</span>
                            <span class="activity-date">${this.formatDate(sale.sale_date)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        if (expensesContainer && stats.recent_expenses) {
            expensesContainer.innerHTML = stats.recent_expenses.map(expense => `
                <div class="activity-item">
                    <div class="activity-icon danger">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${expense.description}</div>
                        <div class="activity-details">
                            <span class="activity-amount">$${expense.amount || 0}</span>
                            <span class="activity-date">${this.formatDate(expense.expense_date)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    }

    updateCharts(stats) {
        // Revenue chart
        const revenueCtx = document.getElementById('revenue-chart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue',
                        data: [12000, 19000, 15000, 25000, 22000, 30000],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Expense chart
        const expenseCtx = document.getElementById('expense-chart');
        if (expenseCtx) {
            new Chart(expenseCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Feed', 'Medicine', 'Labor', 'Equipment', 'Other'],
                    datasets: [{
                        data: [35, 25, 20, 15, 5],
                        backgroundColor: [
                            '#3498db',
                            '#e74c3c',
                            '#f39c12',
                            '#2ecc71',
                            '#95a5a6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    loadDashboardContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-farmers">0</div>
                            <div class="stat-label">Total Farmers</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+12%</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-egg"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-chickens">0</div>
                            <div class="stat-label">Total Chickens</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+8%</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="monthly-revenue">$0</div>
                            <div class="stat-label">Monthly Revenue</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>+15%</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="monthly-expenses">$0</div>
                            <div class="stat-label">Monthly Expenses</div>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i>
                                <span>-5%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Revenue Trend</h3>
                            <div class="chart-controls">
                                <select class="form-control">
                                    <option>Last 7 days</option>
                                    <option>Last 30 days</option>
                                    <option>Last 3 months</option>
                                    <option>Last year</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenue-chart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Expense Breakdown</h3>
                            <div class="chart-controls">
                                <button class="btn btn-sm">Export</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="expense-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-grid">
                    <div class="activity-card">
                        <div class="activity-header">
                            <h3>Recent Sales</h3>
                            <button class="btn btn-sm btn-primary">View All</button>
                        </div>
                        <div class="activity-list" id="recent-sales">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>

                    <div class="activity-card">
                        <div class="activity-header">
                            <h3>Recent Expenses</h3>
                            <button class="btn btn-sm btn-warning">View All</button>
                        </div>
                        <div class="activity-list" id="recent-expenses">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    loadFarmersContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Farmers Management</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Farmer
                    </button>
                </div>
                <div class="content-card">
                    <p>Farmer management functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadChickensContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Chicken Management</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Batch
                    </button>
                </div>
                <div class="content-card">
                    <p>Chicken management functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadBatchesContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Batch Management</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Batch
                    </button>
                </div>
                <div class="content-card">
                    <p>Batch management functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadSalesContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Sales Management</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Sale
                    </button>
                </div>
                <div class="content-card">
                    <p>Sales management functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadExpensesContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Expense Management</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Expense
                    </button>
                </div>
                <div class="content-card">
                    <p>Expense management functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadInventoryContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Inventory Management</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Item
                    </button>
                </div>
                <div class="content-card">
                    <p>Inventory management functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadHealthContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Health Records</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Health Record
                    </button>
                </div>
                <div class="content-card">
                    <p>Health records functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadReportsContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Reports & Analytics</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                </div>
                <div class="content-card">
                    <p>Reports and analytics functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    loadSettingsContent(container) {
        container.innerHTML = `
            <div class="page-content-inner">
                <div class="page-header">
                    <h2>Settings</h2>
                </div>
                <div class="content-card">
                    <p>Settings functionality will be implemented here.</p>
                </div>
            </div>
        `;
    }

    showLoginScreen() {
        document.getElementById('login-screen').style.display = 'flex';
        document.getElementById('main-app').style.display = 'none';
    }

    showMainApp() {
        document.getElementById('login-screen').style.display = 'none';
        document.getElementById('main-app').style.display = 'flex';
        
        // Update user info
        if (this.currentUser) {
            document.getElementById('current-user-name').textContent = this.currentUser.username;
        }
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar?.classList.toggle('active');
    }

    showLoading(show) {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }

    showNotification(title, message, type = 'success') {
        const notification = document.getElementById('notification');
        if (!notification) return;

        const iconClass = type === 'success' ? 'fa-check-circle' : 
                          type === 'error' ? 'fa-exclamation-circle' : 
                          'fa-info-circle';

        notification.querySelector('.notification-icon i').className = `fas ${iconClass}`;
        notification.querySelector('.notification-title').textContent = title;
        notification.querySelector('.notification-message').textContent = message;

        notification.style.display = 'flex';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            this.hideNotification();
        }, 5000);
    }

    hideNotification() {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.style.display = 'none';
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) {
            return 'Today';
        } else if (diffDays === 1) {
            return 'Yesterday';
        } else if (diffDays < 7) {
            return diffDays + ' days ago';
        } else {
            return date.toLocaleDateString();
        }
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    new KUKUFarmManager();
});