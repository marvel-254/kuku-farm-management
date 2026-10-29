<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Sale.php';

$saleModel = new Sale();
$sales = $saleModel->getWithDetails();
$stats = $saleModel->getStats('month');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - <?php echo APP_NAME; ?></title>
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
                <h1>Sales</h1>
                <p>Record chicken sales and track revenue</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">$</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p>Total Revenue</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">S</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_sales']; ?></h3>
                        <p>Total Sales</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">C</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_chickens_sold']; ?></h3>
                        <p>Chickens Sold</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">A</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['average_sale'], 2); ?></h3>
                        <p>Average Sale</p>
                        <small>This month</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>All Sales</h3>
                    <a href="sale-add.php" class="btn btn-primary">Add Sale</a>
                </div>
                <div class="card-body">
                    <?php if (empty($sales)): ?>
                        <p class="text-muted">No sales found. <a href="sale-add.php">Add your first sale</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Chicken/Batch</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total Amount</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $sale): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($sale['tag_number']): ?>
                                                    <?php echo htmlspecialchars($sale['tag_number']); ?>
                                                <?php elseif ($sale['batch_number']): ?>
                                                    <?php echo htmlspecialchars($sale['batch_number']); ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $sale['quantity']; ?></td>
                                            <td>$<?php echo number_format($sale['unit_price'], 2); ?></td>
                                            <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $sale['payment_status'] === 'paid' ? 'success' : ($sale['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($sale['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="sale-edit.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="sale-delete.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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