<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/ChickenBatch.php';
require_once __DIR__ . '/../app/models/Chicken.php';

$batchModel = new ChickenBatch();
$chickenModel = new Chicken();

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

$chickens = $chickenModel->getByBatch($id);
$stats = $chickenModel->getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Batch - <?php echo APP_NAME; ?></title>
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
                <a href="batch-edit.php?id=<?php echo $batch['id']; ?>" class="btn btn-outline-info">
                    Edit This Batch
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Batch Details</h1>
                <p>Batch: <?php echo htmlspecialchars($batch['batch_number']); ?></p>
            </div>

            <!-- Batch Information -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Batch Information</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Batch Number:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($batch['batch_number']); ?></dd>

                                <dt class="col-sm-4">Group:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($batch['group_name'] ?? 'N/A'); ?></dd>

                                <dt class="col-sm-4">Purchase Date:</dt>
                                <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($batch['purchase_date'])); ?></dd>

                                <dt class="col-sm-4">Purchase Cost:</dt>
                                <dd class="col-sm-8">$<?php echo number_format($batch['purchase_cost'] ?? 0, 2); ?></dd>

                                <dt class="col-sm-4">Supplier:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($batch['supplier'] ?? 'N/A'); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Batch Statistics</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon">C</div>
                                    <div class="stat-content">
                                        <h3><?php echo count($chickens); ?></h3>
                                        <p>Total Chickens</p>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon">H</div>
                                    <div class="stat-content">
                                        <h3><?php echo count(array_filter($chickens, function($c) { return $c['status'] === 'healthy'; })); ?></h3>
                                        <p>Healthy</p>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-icon">S</div>
                                    <div class="stat-content">
                                        <h3><?php echo count(array_filter($chickens, function($c) { return $c['status'] === 'sold'; })); ?></h3>
                                        <p>Sold</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chickens in Batch -->
            <div class="card">
                <div class="card-header">
                    <h3>Chickens in This Batch</h3>
                    <a href="chickens.php" class="btn btn-secondary">Back to Batches</a>
                </div>
                <div class="card-body">
                    <?php if (empty($chickens)): ?>
                        <p class="text-muted">No chickens found in this batch.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tag Number</th>
                                        <th>Gender</th>
                                        <th>Status</th>
                                        <th>Birth Date</th>
                                        <th>Weight</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chickens as $chicken): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($chicken['tag_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($chicken['gender'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php
                                                    echo $chicken['status'] === 'healthy' ? 'success' :
                                                         ($chicken['status'] === 'sick' ? 'warning' :
                                                          ($chicken['status'] === 'sold' ? 'info' : 'secondary'));
                                                ?>">
                                                    <?php echo ucfirst($chicken['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $chicken['birth_date'] ? date('M d, Y', strtotime($chicken['birth_date'])) : 'N/A'; ?></td>
                                            <td><?php echo $chicken['weight'] ? $chicken['weight'] . ' kg' : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($chicken['location'] ?? 'N/A'); ?></td>
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