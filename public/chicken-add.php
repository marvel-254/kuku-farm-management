<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/ChickenBatch.php';
require_once __DIR__ . '/../app/models/Chicken.php';

$batchModel = new ChickenBatch();
$chickenModel = new Chicken();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batchData = [
        'group_id' => 1, // Default group
        'batch_number' => sanitize($_POST['batch_number'] ?? ''),
        'quantity' => intval($_POST['quantity'] ?? 0),
        'purchase_date' => sanitize($_POST['purchase_date'] ?? date('Y-m-d')),
        'purchase_cost' => floatval($_POST['purchase_cost'] ?? 0),
        'supplier' => sanitize($_POST['supplier'] ?? ''),
        'notes' => sanitize($_POST['notes'] ?? '')
    ];
    
    if (empty($batchData['batch_number']) || $batchData['quantity'] <= 0) {
        $error = 'Batch number and quantity are required';
    } else {
        if ($batchModel->create($batchData)) {
            $success = 'Chicken batch added successfully!';
            // Clear form data
            $batchData = [];
        } else {
            $error = 'Failed to add chicken batch. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Chicken Batch - <?php echo APP_NAME; ?></title>
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
                <a href="chickens.php" class="btn btn-outline-secondary">
                    ← Back to Chickens
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Add Chicken Batch</h1>
                <p>Create a new chicken batch</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Batch Information</h3>
                    <a href="chickens.php" class="btn btn-secondary">Back to Chickens</a>
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
                                <label for="batch_number" class="form-label">Batch Number *</label>
                                <input type="text" id="batch_number" name="batch_number" class="form-control" required 
                                       value="<?php echo htmlspecialchars($batchData['batch_number'] ?? ''); ?>"
                                       placeholder="e.g., BATCH-001">
                            </div>

                            <div class="form-group">
                                <label for="quantity" class="form-label">Number of Chickens *</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" required 
                                       value="<?php echo htmlspecialchars($batchData['quantity'] ?? ''); ?>"
                                       min="1">
                            </div>

                            <div class="form-group">
                                <label for="purchase_date" class="form-label">Purchase Date *</label>
                                <input type="date" id="purchase_date" name="purchase_date" class="form-control" required
                                       value="<?php echo htmlspecialchars($batchData['purchase_date'] ?? date('Y-m-d')); ?>">
                            </div>

                            <div class="form-group">
                                <label for="purchase_cost" class="form-label">Purchase Cost ($)</label>
                                <input type="number" id="purchase_cost" name="purchase_cost" class="form-control" 
                                       value="<?php echo htmlspecialchars($batchData['purchase_cost'] ?? ''); ?>"
                                       step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" id="supplier" name="supplier" class="form-control"
                                       value="<?php echo htmlspecialchars($batchData['supplier'] ?? ''); ?>"
                                       placeholder="Supplier name">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($batchData['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Add Chicken Batch</button>
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