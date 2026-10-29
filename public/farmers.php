<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Farmer.php';

$farmerModel = new Farmer();
$farmers = $farmerModel->findAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers - <?php echo APP_NAME; ?></title>
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
                        <li><a href="farmers.php" class="nav-link active">Farmers</a></li>
                        <li><a href="chickens.php" class="nav-link">Chickens</a></li>
                        <li><a href="sales.php" class="nav-link">Sales</a></li>
                        <li><a href="expenses.php" class="nav-link">Expenses</a></li>
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
            <div class="page-header">
                <h1>Farmers</h1>
                <p>Manage farm owners and workers</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>All Farmers</h3>
                    <a href="farmer-add.php" class="btn btn-primary">Add Farmer</a>
                </div>
                <div class="card-body">
                    <?php if (empty($farmers)): ?>
                        <p class="text-muted">No farmers found. <a href="farmer-add.php">Add your first farmer</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>User Account</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($farmers as $farmer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($farmer['name']); ?></td>
                                            <td><?php echo htmlspecialchars($farmer['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($farmer['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $farmer['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($farmer['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($farmer['username']): ?>
                                                    <span class="badge badge-success"><?php echo htmlspecialchars($farmer['username']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">No Account</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="farmer-edit.php?id=<?php echo $farmer['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="farmer-delete.php?id=<?php echo $farmer['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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