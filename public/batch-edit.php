<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/ChickenBatch.php';
require_once __DIR__ . '/../app/models/ChickenGroup.php';

$batchModel = new ChickenBatch();
$groupModel = new ChickenGroup();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: chickens.php');
    exit;
}

$batch = $batchModel->findById($id);
if (!$batch) {
    header('Location: chickens.php');
    exit;
}

$groups = $groupModel->findAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'group_id' => sanitize($_POST['group_id'] ?? $batch['group_id']),
        'batch_number' => sanitize($_POST['batch_number'] ?? $batch['batch_number']),
        'quantity' => intval($_POST['quantity'] ?? $batch['quantity']),
        'purchase_date' => sanitize($_POST['purchase_date'] ?? $batch['purchase_date']),
        'purchase_cost' => floatval($_POST['purchase_cost'] ?? $batch['purchase_cost']),
        'supplier' => sanitize($_POST['supplier'] ?? $batch['supplier']),
        'notes' => sanitize($_POST['notes'] ?? $batch['notes'])
    ];

    if (empty($data['batch_number']) || $data['quantity'] <= 0) {
        $error = 'Batch number and quantity are required';
    } else {
        if ($batchModel->update($id, $data)) {
            $success = 'Batch updated successfully!';
            $batch = $batchModel->findById($id); // Refresh data
        } else {
            $error = 'Failed to update batch. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Batch - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="dashboard.php" class="logo">KUKU</a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="chickens.php" class="nav-link active">Chickens</a></li>
                        <li><a href="inventory.php" class="nav-link">Inventory</a></li>
                        <li><a href="sales.php" class="nav-link">Sales</a></li>
                        <li><a href="expenses.php" class="nav-link">Expenses</a></li>
                        <li><a href="invoices.php" class="nav-link">Invoices</a></li>
                        <li><a href="debts.php" class="nav-link">Debts</a></li>
                        <li><a href="financial.php" class="nav-link">Financial</a></li>
                        <li><a href="reports.php" class="nav-link">Reports</a></li>
                    </ul>
                </nav>
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
                <a href="chickens.php" class="btn btn-outline-secondary">
                    ← Back to Chickens
                </a>
                <a href="batch-view.php?id=<?php echo $batch['id']; ?>" class="btn btn-outline-info">
                    ← Back to Batch View
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Edit Batch</h1>
                <p>Update batch information: <?php echo htmlspecialchars($batch['batch_number']); ?></p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Batch Information</h3>
                    <a href="chickens.php" class="btn btn-secondary">Back to Batches</a>
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
                                <label for="group_id" class="form-label">Chicken Group *</label>
                                <select id="group_id" name="group_id" class="form-control" required>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>" <?php echo $batch['group_id'] == $group['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($group['name']); ?> (<?php echo htmlspecialchars($group['breed']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="batch_number" class="form-label">Batch Number *</label>
                                <input type="text" id="batch_number" name="batch_number" class="form-control" required
                                       value="<?php echo htmlspecialchars($batch['batch_number']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" required min="1"
                                       value="<?php echo htmlspecialchars($batch['quantity']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="purchase_date" class="form-label">Purchase Date *</label>
                                <input type="date" id="purchase_date" name="purchase_date" class="form-control" required
                                       value="<?php echo htmlspecialchars($batch['purchase_date']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="purchase_cost" class="form-label">Purchase Cost</label>
                                <input type="number" id="purchase_cost" name="purchase_cost" class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($batch['purchase_cost'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" id="supplier" name="supplier" class="form-control"
                                       value="<?php echo htmlspecialchars($batch['supplier'] ?? ''); ?>">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($batch['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Update Batch</button>
                            <a href="batch-view.php?id=<?php echo $batch['id']; ?>" class="btn btn-info">View Batch</a>
                            <a href="chickens.php" class="btn btn-secondary">Cancel</a>
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