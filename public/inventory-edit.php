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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name'] ?? $item['name']),
        'category' => sanitize($_POST['category'] ?? $item['category']),
        'description' => sanitize($_POST['description'] ?? $item['description']),
        'unit' => sanitize($_POST['unit'] ?? $item['unit']),
        'unit_cost' => floatval($_POST['unit_cost'] ?? $item['unit_cost']),
        'supplier' => sanitize($_POST['supplier'] ?? $item['supplier'])
    ];

    if (empty($data['name']) || $data['unit_cost'] <= 0) {
        $error = 'Item name and unit cost are required';
    } else {
        if ($itemModel->update($id, $data)) {
            $success = 'Item updated successfully!';
            $item = $itemModel->findById($id); // Refresh data
        } else {
            $error = 'Failed to update item. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory Item - <?php echo APP_NAME; ?></title>
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
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Edit Inventory Item</h1>
                <p>Update item details: <?php echo htmlspecialchars($item['name']); ?></p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Item Information</h3>
                    <a href="inventory.php" class="btn btn-secondary">Back to Inventory</a>
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
                                    <option value="feed" <?php echo $item['category'] === 'feed' ? 'selected' : ''; ?>>Feed</option>
                                    <option value="medicine" <?php echo $item['category'] === 'medicine' ? 'selected' : ''; ?>>Medicine</option>
                                    <option value="equipment" <?php echo $item['category'] === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                                    <option value="supplies" <?php echo $item['category'] === 'supplies' ? 'selected' : ''; ?>>Supplies</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="name" class="form-label">Item Name *</label>
                                <input type="text" id="name" name="name" class="form-control" required
                                       value="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="unit" class="form-label">Unit *</label>
                                <select id="unit" name="unit" class="form-control" required>
                                    <option value="kg" <?php echo $item['unit'] === 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                                    <option value="g" <?php echo $item['unit'] === 'g' ? 'selected' : ''; ?>>Gram (g)</option>
                                    <option value="liter" <?php echo $item['unit'] === 'liter' ? 'selected' : ''; ?>>Liter</option>
                                    <option value="ml" <?php echo $item['unit'] === 'ml' ? 'selected' : ''; ?>>Milliliter (ml)</option>
                                    <option value="bottle" <?php echo $item['unit'] === 'bottle' ? 'selected' : ''; ?>>Bottle</option>
                                    <option value="box" <?php echo $item['unit'] === 'box' ? 'selected' : ''; ?>>Box</option>
                                    <option value="bag" <?php echo $item['unit'] === 'bag' ? 'selected' : ''; ?>>Bag</option>
                                    <option value="unit" <?php echo $item['unit'] === 'unit' ? 'selected' : ''; ?>>Unit</option>
                                    <option value="pack" <?php echo $item['unit'] === 'pack' ? 'selected' : ''; ?>>Pack</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="unit_cost" class="form-label">Unit Cost ($) *</label>
                                <input type="number" id="unit_cost" name="unit_cost" class="form-control" required
                                       value="<?php echo htmlspecialchars($item['unit_cost']); ?>" step="0.01" min="0">
                            </div>

                            <div class="form-group">
                                <label for="supplier" class="form-label">Supplier</label>
                                <input type="text" id="supplier" name="supplier" class="form-control"
                                       value="<?php echo htmlspecialchars($item['supplier'] ?? ''); ?>">
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">Update Item</button>
                            <a href="inventory-stock.php?id=<?php echo $item['id']; ?>" class="btn btn-info">Update Stock</a>
                            <a href="inventory.php" class="btn btn-secondary">Cancel</a>
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