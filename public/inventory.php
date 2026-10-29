<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Item.php';

$itemModel = new Item();
$items = $itemModel->getItemsWithStock();
$stats = $itemModel->getStats('month');
$lowStockItems = $itemModel->getLowStockItems(10);
$inventoryValue = $itemModel->getInventoryValue();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
    <script src="assets/js/animations.js" defer></script>
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
                <h1>Inventory Management</h1>
                <p>Track feeds, medications, equipment, and supplies</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">I</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_items']; ?></h3>
                        <p>Total Items</p>
                        <small>In inventory</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">F</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['feed_items']; ?></h3>
                        <p>Feed Items</p>
                        <small>Available</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">M</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['medicine_items']; ?></h3>
                        <p>Medications</p>
                        <small>Available</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">$</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($inventoryValue, 2); ?></h3>
                        <p>Inventory Value</p>
                        <small>Total cost</small>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <?php if (!empty($lowStockItems)): ?>
            <div class="alert alert-warning">
                <h4>Low Stock Alert</h4>
                <p>You have <?php echo count($lowStockItems); ?> items with low stock levels that need attention.</p>
            </div>
            <?php endif; ?>

            <!-- Quick Category Links -->
            <div class="row">
                <div class="col-md-3">
                    <a href="#feed" class="btn btn-success btn-block">Feed Items</a>
                </div>
                <div class="col-md-3">
                    <a href="#medicine" class="btn btn-info btn-block">Medications</a>
                </div>
                <div class="col-md-3">
                    <a href="#equipment" class="btn btn-warning btn-block">Equipment</a>
                </div>
                <div class="col-md-3">
                    <a href="#supplies" class="btn btn-secondary btn-block">Supplies</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>All Inventory Items</h3>
                    <a href="inventory-add.php" class="btn btn-primary">Add Item</a>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                        <p class="text-muted">No inventory items found. <a href="inventory-add.php">Add your first item</a>.</p>
                    <?php else: ?>
                        <!-- Feed Items -->
                        <?php
                        $feedItems = array_filter($items, function($item) {
                            return $item['category'] === 'feed';
                        });
                        if (!empty($feedItems)):
                        ?>
                        <h4 id="feed">Feed Items</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Unit</th>
                                        <th>Unit Cost</th>
                                        <th>Current Stock</th>
                                        <th>Total Value</th>
                                        <th>Supplier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($feedItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>$<?php echo number_format($item['unit_cost'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $item['current_stock'] <= 10 ? 'badge-danger' : 'badge-success'; ?>">
                                                    <?php echo $item['current_stock']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="inventory-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="inventory-stock.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">Update Stock</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- Medicine Items -->
                        <?php
                        $medicineItems = array_filter($items, function($item) {
                            return $item['category'] === 'medicine';
                        });
                        if (!empty($medicineItems)):
                        ?>
                        <h4 id="medicine">Medications</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Unit</th>
                                        <th>Unit Cost</th>
                                        <th>Current Stock</th>
                                        <th>Total Value</th>
                                        <th>Supplier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medicineItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>$<?php echo number_format($item['unit_cost'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $item['current_stock'] <= 10 ? 'badge-danger' : 'badge-success'; ?>">
                                                    <?php echo $item['current_stock']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="inventory-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="inventory-stock.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">Update Stock</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- Equipment Items -->
                        <?php
                        $equipmentItems = array_filter($items, function($item) {
                            return $item['category'] === 'equipment';
                        });
                        if (!empty($equipmentItems)):
                        ?>
                        <h4 id="equipment">Equipment</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Unit</th>
                                        <th>Unit Cost</th>
                                        <th>Current Stock</th>
                                        <th>Total Value</th>
                                        <th>Supplier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($equipmentItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td>$<?php echo number_format($item['unit_cost'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $item['current_stock'] <= 5 ? 'badge-danger' : 'badge-success'; ?>">
                                                    <?php echo $item['current_stock']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="inventory-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="inventory-stock.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">Update Stock</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- Supplies Items -->
                        <?php
                        $suppliesItems = array_filter($items, function($item) {
                            return $item['category'] === 'supplies';
                        });
                        if (!empty($suppliesItems)):
                        ?>
                        <h4 id="supplies">Supplies</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Unit</th>
                                        <th>Unit Cost</th>
                                        <th>Current Stock</th>
                                        <th>Total Value</th>
                                        <th>Supplier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliesItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>$<?php echo number_format($item['unit_cost'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $item['current_stock'] <= 10 ? 'badge-danger' : 'badge-success'; ?>">
                                                    <?php echo $item['current_stock']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="inventory-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="inventory-stock.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success">Update Stock</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
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