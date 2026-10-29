<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Invoice.php';

$invoiceModel = new Invoice();
$invoices = $invoiceModel->getWithDetails();
$stats = $invoiceModel->getStats('month');
$outstandingInvoices = $invoiceModel->getOutstandingInvoices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - <?php echo APP_NAME; ?></title>
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
                <h1>Invoices</h1>
                <p>Manage sales and purchase invoices</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">I</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_invoices']; ?></h3>
                        <p>Total Invoices</p>
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
                    <div class="stat-icon">O</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($stats['total_outstanding'], 2); ?></h3>
                        <p>Outstanding</p>
                        <small>All time</small>
                    </div>
                </div>
            </div>

            <!-- Outstanding Invoices Alert -->
            <?php if (!empty($outstandingInvoices)): ?>
            <div class="alert alert-warning">
                <h4>Outstanding Invoices</h4>
                <p>You have <?php echo count($outstandingInvoices); ?> outstanding invoice(s) requiring attention.</p>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Invoices</h3>
                    <a href="invoice-add.php" class="btn btn-primary">Add Invoice</a>
                </div>
                <div class="card-body">
                    <?php if (empty($invoices)): ?>
                        <p class="text-muted">No invoices found. <a href="invoice-add.php">Create your first invoice</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Type</th>
                                        <th>Customer/Supplier</th>
                                        <th>Date</th>
                                        <th>Due Date</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $invoice['type'] === 'sale' ? 'success' : 'info'; ?>">
                                                    <?php echo ucfirst($invoice['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($invoice['customer_supplier_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?></td>
                                            <td><?php echo $invoice['due_date'] ? date('M d, Y', strtotime($invoice['due_date'])) : 'N/A'; ?></td>
                                            <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                            <td>$<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                            <td>$<?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge badge-<?php
                                                    echo $invoice['status'] === 'paid' ? 'success' :
                                                         ($invoice['status'] === 'overdue' ? 'danger' :
                                                          ($invoice['status'] === 'partial' ? 'warning' : 'secondary'));
                                                ?>">
                                                    <?php echo ucfirst($invoice['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="invoice-view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-info">View</a>
                                                <a href="invoice-edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <?php if ($invoice['total_amount'] > $invoice['paid_amount']): ?>
                                                    <a href="invoice-payment.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-success">Add Payment</a>
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