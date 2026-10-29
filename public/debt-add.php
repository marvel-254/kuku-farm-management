<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Debt.php';

$debtModel = new Debt();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'debtor_name' => sanitize($_POST['debtor_name'] ?? ''),
        'debtor_phone' => sanitize($_POST['debtor_phone'] ?? ''),
        'debtor_email' => sanitize($_POST['debtor_email'] ?? ''),
        'debt_type' => sanitize($_POST['debt_type'] ?? 'owed_to_us'),
        'amount' => floatval($_POST['amount'] ?? 0),
        'due_date' => sanitize($_POST['due_date'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'payment_schedule' => sanitize($_POST['payment_schedule'] ?? ''),
        'notes' => sanitize($_POST['notes'] ?? ''),
        'created_by' => $_SESSION['user_id']
    ];

    if (empty($data['debtor_name']) || $data['amount'] <= 0) {
        $error = 'Debtor name and amount are required';
    } else {
        if ($debtModel->create($data)) {
            $success = 'Debt record created successfully!';
            // Clear form data
            $data = [];
        } else {
            $error = 'Failed to create debt record. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Debt - <?php echo APP_NAME; ?></title>
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
                <a href="debts.php" class="btn btn-outline-secondary">
                    ← Back to Debts
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Add Debt Record</h1>
                <p>Create a new debt record for money owed or money you owe</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Debt Information</h3>
                    <a href="debts.php" class="btn btn-secondary">Back to Debts</a>
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
                                <label for="debt_type" class="form-label">Debt Type *</label>
                                <select id="debt_type" name="debt_type" class="form-control" required>
                                    <option value="owed_to_us" <?php echo ($data['debt_type'] ?? '') === 'owed_to_us' ? 'selected' : ''; ?>>Money Owed to You</option>
                                    <option value="we_owe" <?php echo ($data['debt_type'] ?? '') === 'we_owe' ? 'selected' : ''; ?>>Money You Owe</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="debtor_name" class="form-label">Debtor Name *</label>
                                <input type="text" id="debtor_name" name="debtor_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['debtor_name'] ?? ''); ?>"
                                       placeholder="Enter debtor name">
                            </div>

                            <div class="form-group">
                                <label for="debtor_phone" class="form-label">Phone</label>
                                <input type="tel" id="debtor_phone" name="debtor_phone" class="form-control"
                                       value="<?php echo htmlspecialchars($data['debtor_phone'] ?? ''); ?>"
                                       placeholder="Phone number">
                            </div>

                            <div class="form-group">
                                <label for="debtor_email" class="form-label">Email</label>
                                <input type="email" id="debtor_email" name="debtor_email" class="form-control"
                                       value="<?php echo htmlspecialchars($data['debtor_email'] ?? ''); ?>"
                                       placeholder="Email address">
                            </div>

                            <div class="form-group">
                                <label for="amount" class="form-label">Amount ($) *</label>
                                <input type="number" id="amount" name="amount" class="form-control" required
                                       value="<?php echo htmlspecialchars($data['amount'] ?? ''); ?>"
                                       step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" id="due_date" name="due_date" class="form-control"
                                       value="<?php echo htmlspecialchars($data['due_date'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="payment_schedule" class="form-label">Payment Schedule</label>
                                <input type="text" id="payment_schedule" name="payment_schedule" class="form-control"
                                       value="<?php echo htmlspecialchars($data['payment_schedule'] ?? ''); ?>"
                                       placeholder="e.g., Monthly installments">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="2"><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Create Debt Record</button>
                            <a href="debts.php" class="btn btn-secondary">Cancel</a>
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