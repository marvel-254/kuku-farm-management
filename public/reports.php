<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Sale.php';
require_once __DIR__ . '/../app/models/Expense.php';
require_once __DIR__ . '/../app/models/Earning.php';

$saleModel = new Sale();
$expenseModel = new Expense();
$earningModel = new Earning();

// Get statistics for different periods
$saleStats = $saleModel->getStats('month');
$expenseStats = $expenseModel->getStats('month');
$earningStats = $earningModel->getStats('month');

// Calculate total chickens sold
$totalChickens = $saleStats['total_chickens_sold'] ?? 0;

// Calculate financial summary
$totalRevenue = ($saleStats['total_revenue'] ?? 0) + ($earningStats['total_amount'] ?? 0);
$totalExpenses = $expenseStats['total_amount'] ?? 0;
$netProfit = $totalRevenue - $totalExpenses;

// Get recent transactions
$recentSales = $saleModel->getWithDetails(10);
$recentExpenses = $expenseModel->getWithDetails(10);
$recentEarnings = $earningModel->getWithDetails(10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
    <script src="assets/js/animations.js" defer></script>
    <style>
        .report-summary {
            background: linear-gradient(135deg, var(--primary-color), #2980b9);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .report-summary h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .report-summary p {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .chart-placeholder {
            height: 300px;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.125rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content no-nav">
                <a href="dashboard.php" class="logo">KUKU</a>
                <div class="user-menu">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-navigation">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Reports</h1>
                <p>Financial reports and analytics</p>
            </div>

            <!-- Financial Summary -->
            <div class="report-summary">
                <h2>$<?php echo number_format($netProfit, 2); ?></h2>
                <p>Net Profit (This Month)</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">$</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
                        <p>Total Revenue</p>
                        <small>All time</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">E</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($totalExpenses, 2); ?></h3>
                        <p>Total Expenses</p>
                        <small>All time</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">N</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($netProfit, 2); ?></h3>
                        <p>Net Profit</p>
                        <small class="<?php echo $netProfit >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $netProfit >= 0 ? 'Profit' : 'Loss'; ?>
                        </small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">C</div>
                    <div class="stat-content">
                        <h3><?php echo $totalChickens; ?></h3>
                        <p>Chickens Sold</p>
                        <small>All time</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">S</div>
                    <div class="stat-content">
                        <h3><?php echo $saleStats['total_sales']; ?></h3>
                        <p>Total Sales</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">%</div>
                    <div class="stat-content">
                        <h3><?php echo $totalRevenue > 0 ? ($netProfit >= 0 ? '+' : '') . round(($netProfit / $totalRevenue) * 100, 1) . '%' : 'N/A'; ?></h3>
                        <p>Profit Margin</p>
                        <small>This month</small>
                    </div>
                </div>
            </div>
            </div>

            <!-- Charts Section -->
            <div class="dashboard-grid">
                <div class="chart-container">
                    <h3>Revenue vs Expenses</h3>
                    <div class="chart-placeholder">
                        Chart visualization would go here
                    </div>
                </div>

                <div class="chart-container">
                    <h3>Expense Categories</h3>
                    <div class="chart-placeholder">
                        Category breakdown would go here
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Sales</h3>
                        <a href="sales.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentSales)): ?>
                            <p class="text-muted">No recent sales</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($recentSales, 0, 5) as $sale): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></td>
                                                <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Recent Expenses</h3>
                        <a href="expenses.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentExpenses)): ?>
                            <p class="text-muted">No recent expenses</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Category</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($recentExpenses, 0, 5) as $expense): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                                <td><?php echo ucfirst($expense['category']); ?></td>
                                                <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> Farm Management System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>