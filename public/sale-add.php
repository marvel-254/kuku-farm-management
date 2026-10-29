<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Sale.php';

$saleModel = new Sale();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'sale_date' => sanitize($_POST['sale_date'] ?? date('Y-m-d')),
        'customer_name' => sanitize($_POST['customer_name'] ?? ''),
        'customer_phone' => sanitize($_POST['customer_phone'] ?? ''),
        'quantity' => intval($_POST['quantity'] ?? 1),
        'unit_price' => floatval($_POST['unit_price'] ?? 0),
        'total_amount' => floatval($_POST['total_amount'] ?? 0),
        'payment_method' => sanitize($_POST['payment_method'] ?? 'cash'),
        'payment_status' => sanitize($_POST['payment_status'] ?? 'paid'),
        'notes' => sanitize($_POST['notes'] ?? ''),
        'created_by' => $_SESSION['user_id']
    ];
    
    if (empty($data['customer_name']) || $data['unit_price'] <= 0) {
        $error = 'Customer name and unit price are required';
    } else {
        if ($saleModel->create($data)) {
            $success = 'Sale added successfully!';
            // Clear form data
            $data = [];
        } else {
            $error = 'Failed to add sale. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sale - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
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
                <a href="sales.php" class="btn btn-outline-secondary">
                    ← Back to Sales
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Add Sale</h1>
                <p>Record a new chicken sale</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Sale Information</h3>
                    <a href="sales.php" class="btn btn-secondary">Back to Sales</a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>Error</strong>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <strong>Success</strong>
                            <p><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="saleForm">
                        <div class="dashboard-grid">
                            <div class="form-group">
                                <label for="sale_date" class="form-label">Sale Date *</label>
                                <input type="date" id="sale_date" name="sale_date" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['sale_date'] ?? date('Y-m-d')); ?>">
                            </div>

                            <div class="form-group">
                                <label for="customer_name" class="form-label">Customer Name *</label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['customer_name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="customer_phone" class="form-label">Customer Phone</label>
                                <input type="tel" id="customer_phone" name="customer_phone" class="form-control"
                                       value="<?php echo htmlspecialchars($data['customer_phone'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" id="quantity" name="quantity" class="form-control"
                                       value="<?php echo htmlspecialchars($data['quantity'] ?? 1); ?>"
                                       min="1" onchange="calculateTotal()">
                            </div>

                            <div class="form-group">
                                <label for="unit_price" class="form-label">Unit Price ($) *</label>
                                <input type="number" id="unit_price" name="unit_price" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['unit_price'] ?? ''); ?>"
                                       step="0.01" min="0" onchange="calculateTotal()">
                            </div>

                            <div class="form-group">
                                <label for="total_amount" class="form-label">Total Amount ($)</label>
                                <input type="number" id="total_amount" name="total_amount" class="form-control" readonly
                                       value="<?php echo htmlspecialchars($data['total_amount'] ?? ''); ?>"
                                       step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-control">
                                    <option value="cash" <?php echo ($data['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="mobile_money" <?php echo ($data['payment_method'] ?? '') === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="bank_transfer" <?php echo ($data['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="credit" <?php echo ($data['payment_method'] ?? '') === 'credit' ? 'selected' : ''; ?>>Credit</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="payment_status" class="form-label">Payment Status</label>
                                <select id="payment_status" name="payment_status" class="form-control">
                                    <option value="paid" <?php echo ($data['payment_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="partial" <?php echo ($data['payment_status'] ?? '') === 'partial' ? 'selected' : ''; ?>>Partial</option>
                                    <option value="pending" <?php echo ($data['payment_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Add Sale</button>
                            <a href="sales.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> Farm Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('quantity').value) || 0;
            const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
            const total = quantity * unitPrice;
            document.getElementById('total_amount').value = total.toFixed(2);
        }
        
        // Calculate total on page load
        document.addEventListener('DOMContentLoaded', calculateTotal);
    </script>
</body>
</html>