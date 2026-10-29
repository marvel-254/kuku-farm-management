<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Expense.php';

$expenseModel = new Expense();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'category' => sanitize($_POST['category'] ?? 'other'),
        'description' => sanitize($_POST['description'] ?? ''),
        'amount' => floatval($_POST['amount'] ?? 0),
        'expense_date' => sanitize($_POST['expense_date'] ?? date('Y-m-d')),
        'supplier' => sanitize($_POST['supplier'] ?? ''),
        'payment_method' => sanitize($_POST['payment_method'] ?? 'cash'),
        'notes' => sanitize($_POST['notes'] ?? ''),
        'created_by' => $_SESSION['user_id']
    ];
    
    if (empty($data['description']) || $data['amount'] <= 0) {
        $error = 'Description and amount are required';
    } else {
        if ($expenseModel->create($data)) {
            $success = 'Expense added successfully!';
            // Clear form data
            $data = [];
        } else {
            $error = 'Failed to add expense. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - <?php echo APP_NAME; ?></title>
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
                <a href="expenses.php" class="btn btn-outline-secondary">
                    ← Back to Expenses
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Add Expense</h1>
                <p>Record a new farm expense</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Expense Information</h3>
                    <a href="expenses.php" class="btn btn-secondary">Back to Expenses</a>
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
                                <label for="category" class="form-label">Category *</label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="feed" <?php echo ($data['category'] ?? '') === 'feed' ? 'selected' : ''; ?>>Feed</option>
                                    <option value="medicine" <?php echo ($data['category'] ?? '') === 'medicine' ? 'selected' : ''; ?>>Medicine</option>
                                    <option value="labor" <?php echo ($data['category'] ?? '') === 'labor' ? 'selected' : ''; ?>>Labor</option>
                                    <option value="equipment" <?php echo ($data['category'] ?? '') === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                                    <option value="utilities" <?php echo ($data['category'] ?? '') === 'utilities' ? 'selected' : ''; ?>>Utilities</option>
                                    <option value="other" <?php echo ($data['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label">Description *</label>
                                <input type="text" id="description" name="description" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['description'] ?? ''); ?>"
                                       placeholder="e.g., Chicken feed purchase">
                            </div>

                            <div class="form-group">
                                <label for="amount" class="form-label">Amount ($) *</label>
                                <input type="number" id="amount" name="amount" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['amount'] ?? ''); ?>"
                                       step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="expense_date" class="form-label">Expense Date *</label>
                                <input type="date" id="expense_date" name="expense_date" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['expense_date'] ?? date('Y-m-d')); ?>">
                            </div>

                            <div class="form-group">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" id="supplier" name="supplier" class="form-control"
                                       value="<?php echo htmlspecialchars($data['supplier'] ?? ''); ?>"
                                       placeholder="Supplier name">
                            </div>

                            <div class="form-group">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-control">
                                    <option value="cash" <?php echo ($data['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="mobile_money" <?php echo ($data['payment_method'] ?? '') === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="bank_transfer" <?php echo ($data['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                </select>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Add Expense</button>
                            <a href="expenses.php" class="btn btn-secondary">Cancel</a>
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