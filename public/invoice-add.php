<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Invoice.php';

$invoiceModel = new Invoice();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'invoice_number' => $invoiceModel->generateInvoiceNumber(),
        'type' => sanitize($_POST['type'] ?? 'sale'),
        'customer_supplier_name' => sanitize($_POST['customer_supplier_name'] ?? ''),
        'customer_supplier_phone' => sanitize($_POST['customer_supplier_phone'] ?? ''),
        'customer_supplier_email' => sanitize($_POST['customer_supplier_email'] ?? ''),
        'invoice_date' => sanitize($_POST['invoice_date'] ?? date('Y-m-d')),
        'due_date' => sanitize($_POST['due_date'] ?? ''),
        'total_amount' => floatval($_POST['total_amount'] ?? 0),
        'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
        'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
        'payment_terms' => sanitize($_POST['payment_terms'] ?? ''),
        'notes' => sanitize($_POST['notes'] ?? ''),
        'created_by' => $_SESSION['user_id']
    ];

    if (empty($data['customer_supplier_name']) || $data['total_amount'] <= 0) {
        $error = 'Customer/Supplier name and total amount are required';
    } else {
        if ($invoiceModel->create($data)) {
            $success = 'Invoice created successfully!';
            // Clear form data
            $data = [];
        } else {
            $error = 'Failed to create invoice. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Invoice - <?php echo APP_NAME; ?></title>
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
                <a href="invoices.php" class="btn btn-outline-secondary">
                    ← Back to Invoices
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Add Invoice</h1>
                <p>Create a new sales or purchase invoice</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Invoice Information</h3>
                    <a href="invoices.php" class="btn btn-secondary">Back to Invoices</a>
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

                    <form method="POST">
                        <div class="dashboard-grid">
                            <div class="form-group">
                                <label for="type" class="form-label">Invoice Type *</label>
                                <select id="type" name="type" class="form-control" required>
                                    <option value="sale" <?php echo ($data['type'] ?? '') === 'sale' ? 'selected' : ''; ?>>Sales Invoice</option>
                                    <option value="purchase" <?php echo ($data['type'] ?? '') === 'purchase' ? 'selected' : ''; ?>>Purchase Invoice</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="customer_supplier_name" class="form-label">Customer/Supplier Name *</label>
                                <input type="text" id="customer_supplier_name" name="customer_supplier_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['customer_supplier_name'] ?? ''); ?>"
                                       placeholder="Enter customer or supplier name">
                            </div>

                            <div class="form-group">
                                <label for="customer_supplier_phone" class="form-label">Phone</label>
                                <input type="tel" id="customer_supplier_phone" name="customer_supplier_phone" class="form-control"
                                       value="<?php echo htmlspecialchars($data['customer_supplier_phone'] ?? ''); ?>"
                                       placeholder="Phone number">
                            </div>

                            <div class="form-group">
                                <label for="customer_supplier_email" class="form-label">Email</label>
                                <input type="email" id="customer_supplier_email" name="customer_supplier_email" class="form-control"
                                       value="<?php echo htmlspecialchars($data['customer_supplier_email'] ?? ''); ?>"
                                       placeholder="Email address">
                            </div>

                            <div class="form-group">
                                <label for="invoice_date" class="form-label">Invoice Date *</label>
                                <input type="date" id="invoice_date" name="invoice_date" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['invoice_date'] ?? date('Y-m-d')); ?>">
                            </div>

                            <div class="form-group">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" id="due_date" name="due_date" class="form-control"
                                       value="<?php echo htmlspecialchars($data['due_date'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="total_amount" class="form-label">Total Amount ($) *</label>
                                <input type="number" id="total_amount" name="total_amount" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['total_amount'] ?? ''); ?>"
                                       step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="tax_amount" class="form-label">Tax Amount ($)</label>
                                <input type="number" id="tax_amount" name="tax_amount" class="form-control"
                                       value="<?php echo htmlspecialchars($data['tax_amount'] ?? ''); ?>"
                                       step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="discount_amount" class="form-label">Discount Amount ($)</label>
                                <input type="number" id="discount_amount" name="discount_amount" class="form-control"
                                       value="<?php echo htmlspecialchars($data['discount_amount'] ?? ''); ?>"
                                       step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="payment_terms" class="form-label">Payment Terms</label>
                                <input type="text" id="payment_terms" name="payment_terms" class="form-control"
                                       value="<?php echo htmlspecialchars($data['payment_terms'] ?? ''); ?>"
                                       placeholder="e.g., Net 30 days">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Create Invoice</button>
                            <a href="invoices.php" class="btn btn-secondary">Cancel</a>
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
</body>
</html>