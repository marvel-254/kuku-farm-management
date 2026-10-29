<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Farmer.php';

$farmerModel = new Farmer();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'status' => sanitize($_POST['status'] ?? 'active'),
        'join_date' => sanitize($_POST['join_date'] ?? date('Y-m-d'))
    ];
    
    if (empty($data['name'])) {
        $error = 'Farmer name is required';
    } else {
        if ($farmerModel->create($data)) {
            $success = 'Farmer added successfully!';
            // Clear form data
            $data = [];
        } else {
            $error = 'Failed to add farmer. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Farmer - <?php echo APP_NAME; ?></title>
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
                <h1>Add Farmer</h1>
                <p>Add a new farmer to the system</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Farmer Information</h3>
                    <a href="farmers.php" class="btn btn-secondary">Back to Farmers</a>
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
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" required 
                                       value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="active" <?php echo ($data['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($data['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="join_date" class="form-label">Join Date</label>
                                <input type="date" id="join_date" name="join_date" class="form-control"
                                       value="<?php echo htmlspecialchars($data['join_date'] ?? date('Y-m-d')); ?>">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($data['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Add Farmer</button>
                            <a href="farmers.php" class="btn btn-secondary">Cancel</a>
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