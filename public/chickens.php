<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Chicken.php';
require_once __DIR__ . '/../app/models/ChickenBatch.php';

$chickenModel = new Chicken();
$batchModel = new ChickenBatch();

$chickens = $chickenModel->getWithBatchInfo();
$batches = $batchModel->getWithDetails();
$chickenStats = $chickenModel->getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chickens - <?php echo APP_NAME; ?></title>
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
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Chickens</h1>
                <p>Manage your chicken inventory and batches</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">C</div>
                    <div class="stat-content">
                        <h3><?php echo $chickenStats['total_chickens']; ?></h3>
                        <p>Total Chickens</p>
                        <small><?php echo $chickenStats['healthy_chickens']; ?> healthy</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">H</div>
                    <div class="stat-content">
                        <h3><?php echo $chickenStats['healthy_chickens']; ?></h3>
                        <p>Healthy Chickens</p>
                        <small><?php echo round(($chickenStats['healthy_chickens'] / max($chickenStats['total_chickens'], 1)) * 100, 1); ?>% of total</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">B</div>
                    <div class="stat-content">
                        <h3><?php echo count($batches); ?></h3>
                        <p>Active Batches</p>
                        <small>Total chicken batches</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">S</div>
                    <div class="stat-content">
                        <h3><?php echo $chickenStats['total_chickens'] - $chickenStats['healthy_chickens']; ?></h3>
                        <p>Other Status</p>
                        <small>Sick, sold, or deceased</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Chicken Batches</h3>
                    <a href="chicken-add.php" class="btn btn-primary">Add Batch</a>
                </div>
                <div class="card-body">
                    <?php if (empty($batches)): ?>
                        <p class="text-muted">No chicken batches found. <a href="chicken-add.php">Add your first batch</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Batch Number</th>
                                        <th>Group/Breed</th>
                                        <th>Quantity</th>
                                        <th>Healthy</th>
                                        <th>Sick</th>
                                        <th>Sold</th>
                                        <th>Purchase Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($batches as $batch): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($batch['batch_number']); ?></td>
                                            <td><?php echo htmlspecialchars($batch['group_name']); ?></td>
                                            <td><?php echo $batch['chicken_count']; ?></td>
                                            <td><?php echo $batch['healthy_count']; ?></td>
                                            <td><?php echo $batch['sick_count']; ?></td>
                                            <td><?php echo $batch['sold_count']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($batch['purchase_date'])); ?></td>
                                            <td>
                                                <a href="batch-view.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                                <a href="batch-edit.php?id=<?php echo $batch['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
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