<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Expense.php';

$expenseModel = new Expense();
$expenses = $expenseModel->getWithDetails();
$stats = $expenseModel->getStats('month');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
    <script src="assets/js/animations.js" defer></script>
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
                <h1>Expenses</h1>
                <p>Track farm expenses and costs</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">$</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['total_amount'], 2); ?></h3>
                        <p>Total Expenses</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">T</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_expenses']; ?></h3>
                        <p>Total Transactions</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">A</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['average_expense'], 2); ?></h3>
                        <p>Average Expense</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">F</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['feed_total'], 2); ?></h3>
                        <p>Feed Costs</p>
                        <small>This month</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>All Expenses</h3>
                    <a href="expense-add.php" class="btn btn-primary">Add Expense</a>
                </div>
                <div class="card-body">
                    <?php if (empty($expenses)): ?>
                        <p class="text-muted">No expenses found. <a href="expense-add.php">Add your first expense</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Supplier</th>
                                        <th>Payment Method</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $expense): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $expense['category'] === 'feed' ? 'success' : ($expense['category'] === 'medicine' ? 'warning' : 'primary'); ?>">
                                                    <?php echo ucfirst($expense['category']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                            <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($expense['supplier'] ?? 'N/A'); ?></td>
                                            <td><?php echo ucfirst($expense['payment_method']); ?></td>
                                            <td>
                                                <a href="expense-edit.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="expense-delete.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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