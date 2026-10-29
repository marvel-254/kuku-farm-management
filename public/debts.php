<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Debt.php';

$debtModel = new Debt();
$debts = $debtModel->getWithDetails();
$stats = $debtModel->getStats('month');
$overdueDebts = $debtModel->getOverdueDebts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debts - <?php echo APP_NAME; ?></title>
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
                <h1>Debts</h1>
                <p>Manage debts owed and money you owe</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">D</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_debts']; ?></h3>
                        <p>Total Debts</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">$</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['total_amount'], 2); ?></h3>
                        <p>Total Value</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">P</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['total_paid'], 2); ?></h3>
                        <p>Total Paid</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">!</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['overdue_debts']; ?></h3>
                        <p>Overdue</p>
                        <small>All time</small>
                    </div>
                </div>
            </div>

            <!-- Overdue Debts Alert -->
            <?php if (!empty($overdueDebts)): ?>
            <div class="alert alert-danger">
                <h4>Overdue Debts</h4>
                <p>You have <?php echo count($overdueDebts); ?> overdue debt(s) that need immediate attention.</p>
            </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Debts Owed to You</h4>
                        </div>
                        <div class="card-body">
                            <p class="stat-large">$<?php echo number_format($debtModel->getTotalOutstandingByType('owed_to_us'), 2); ?></p>
                            <p class="text-muted">Outstanding amount from customers</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Debts You Owe</h4>
                        </div>
                        <div class="card-body">
                            <p class="stat-large">$<?php echo number_format($debtModel->getTotalOutstandingByType('we_owe'), 2); ?></p>
                            <p class="text-muted">Outstanding amount to suppliers</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>All Debts</h3>
                    <a href="debt-add.php" class="btn btn-primary">Add Debt</a>
                </div>
                <div class="card-body">
                    <?php if (empty($debts)): ?>
                        <p class="text-muted">No debts found. <a href="debt-add.php">Add your first debt record</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Debtor Name</th>
                                        <th>Type</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Outstanding</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($debts as $debt): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($debt['debtor_name']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $debt['debt_type'] === 'owed_to_us' ? 'success' : 'warning'; ?>">
                                                    <?php echo $debt['debt_type'] === 'owed_to_us' ? 'Owed to Us' : 'We Owe'; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($debt['amount'], 2); ?></td>
                                            <td>$<?php echo number_format($debt['paid_amount'], 2); ?></td>
                                            <td>$<?php echo number_format($debt['amount'] - $debt['paid_amount'], 2); ?></td>
                                            <td><?php echo $debt['due_date'] ? date('M d, Y', strtotime($debt['due_date'])) : 'N/A'; ?></td>
                                            <td>
                                                <span class="badge badge-<?php
                                                    echo $debt['status'] === 'paid' ? 'success' :
                                                         ($debt['status'] === 'active' && $debt['due_date'] && strtotime($debt['due_date']) < time() && $debt['amount'] > $debt['paid_amount'] ? 'danger' : 'secondary');
                                                ?>">
                                                    <?php echo ucfirst($debt['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="debt-view.php?id=<?php echo $debt['id']; ?>" class="btn btn-sm btn-info">View</a>
                                                <a href="debt-edit.php?id=<?php echo $debt['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($debt['amount'] > $debt['paid_amount'] && $debt['status'] === 'active'): ?>
                                                    <a href="debt-payment.php?id=<?php echo $debt['id']; ?>" class="btn btn-sm btn-success">Add Payment</a>
                                                <?php endif; ?>
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