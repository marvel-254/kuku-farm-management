<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Item.php';

$itemModel = new Item();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: inventory.php');
    exit;
}

$item = $itemModel->findById($id);
if (!$item) {
    header('Location: inventory.php');
    exit;
}

$currentStock = $itemModel->getCurrentStock($id);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionType = sanitize($_POST['transaction_type'] ?? 'in');
    $quantity = floatval($_POST['quantity'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if ($quantity <= 0) {
        $error = 'Quantity must be greater than zero';
    } else {
        // Insert inventory transaction using model method
        if ($itemModel->addStockTransaction($id, $transactionType, $quantity, $notes)) {
            $successMessage = 'Stock updated successfully!';
            $currentStock = $itemModel->getCurrentStock($id); // Refresh stock
        } else {
            $error = 'Failed to update stock. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stock - <?php echo APP_NAME; ?></title>
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
                <a href="inventory.php" class="btn btn-outline-secondary">
                    ← Back to Inventory
                </a>
                <a href="inventory-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-info">
                    ← Back to Edit Item
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Update Stock</h1>
                <p>Manage stock levels for: <?php echo htmlspecialchars($item['name']); ?></p>
            </div>

            <!-- Current Stock Display -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="stat-large"><?php echo number_format($currentStock, 2); ?> <?php echo htmlspecialchars($item['unit']); ?></div>
                            <p class="text-muted">Current Stock</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="stat-large">$<?php echo number_format($currentStock * $item['unit_cost'], 2); ?></div>
                            <p class="text-muted">Current Value</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Item Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Name:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($item['name']); ?></dd>

                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8"><?php echo ucfirst(htmlspecialchars($item['category'])); ?></dd>

                                <dt class="col-sm-4">Unit:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($item['unit']); ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Unit Cost:</dt>
                                <dd class="col-sm-8">$<?php echo number_format($item['unit_cost'], 2); ?></dd>

                                <dt class="col-sm-4">Supplier:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></dd>

                                <dt class="col-sm-4">Description:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Update Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Update Stock Level</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>Error</strong>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success">
                            <strong>Success</strong>
                            <p><?php echo htmlspecialchars($successMessage); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="dashboard-grid">
                            <div class="form-group">
                                <label for="transaction_type" class="form-label">Transaction Type *</label>
                                <select id="transaction_type" name="transaction_type" class="form-control" required>
                                    <option value="in">Stock In (Add to Inventory)</option>
                                    <option value="out">Stock Out (Remove from Inventory)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" required
                                       min="0.01" step="0.01" placeholder="Enter quantity">
                                <small class="form-text text-muted">Enter quantity in <?php echo htmlspecialchars($item['unit']); ?> units</small>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3"
                                          placeholder="Optional notes about this stock update"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Update Stock</button>
                            <a href="inventory-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-info">Edit Item Details</a>
                            <a href="inventory.php" class="btn btn-secondary">Back to Inventory</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Stock Transactions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Recent Stock Transactions</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Get recent transactions for this item
                    $transactions = $itemModel->getRecentTransactions($id, 10);
                    ?>

                    <?php if (empty($transactions)): ?>
                        <p class="text-muted">No stock transactions recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total Value</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $transaction['transaction_type'] === 'in' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($transaction['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>$<?php echo number_format($transaction['unit_cost'], 2); ?></td>
                                            <td>$<?php echo number_format($transaction['total_cost'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['notes'] ?? 'N/A'); ?></td>
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