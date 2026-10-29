<?php
require_once __DIR__ . '/Model.php';

class Item extends Model {
    protected $table = 'items';

    public function getStats($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE created_at >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE created_at >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE created_at >= date('now', '-1 year')";
        }

        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) as total_items,
                COUNT(CASE WHEN category = 'feed' THEN 1 END) as feed_items,
                COUNT(CASE WHEN category = 'medicine' THEN 1 END) as medicine_items,
                COUNT(CASE WHEN category = 'equipment' THEN 1 END) as equipment_items,
                COUNT(CASE WHEN category = 'supplies' THEN 1 END) as supplies_items
            FROM {$this->table}
            $dateCondition
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getByCategory($category) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE category = ?
            ORDER BY name ASC
        ");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    public function getCurrentStock($itemId) {
        $stmt = $this->conn->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END), 0) as current_stock
            FROM inventory
            WHERE item_id = ?
        ");
        $stmt->execute([$itemId]);
        $result = $stmt->fetch();
        return $result['current_stock'];
    }

    public function getItemsWithStock() {
        $stmt = $this->conn->prepare("
            SELECT i.*,
                   COALESCE(stock.current_stock, 0) as current_stock
            FROM {$this->table} i
            LEFT JOIN (
                SELECT item_id,
                       SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END) as current_stock
                FROM inventory
                GROUP BY item_id
            ) stock ON i.id = stock.item_id
            ORDER BY i.category ASC, i.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLowStockItems($threshold = 10) {
        $stmt = $this->conn->prepare("
            SELECT i.*,
                   COALESCE(stock.current_stock, 0) as current_stock
            FROM {$this->table} i
            LEFT JOIN (
                SELECT item_id,
                       SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END) as current_stock
                FROM inventory
                GROUP BY item_id
            ) stock ON i.id = stock.item_id
            WHERE COALESCE(stock.current_stock, 0) <= ?
            ORDER BY COALESCE(stock.current_stock, 0) ASC
        ");
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }

    public function getInventoryValue() {
        $stmt = $this->conn->prepare("
            SELECT
                SUM(COALESCE(stock.current_stock, 0) * i.unit_cost) as total_value
            FROM {$this->table} i
            LEFT JOIN (
                SELECT item_id,
                       SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE -quantity END) as current_stock
                FROM inventory
                GROUP BY item_id
            ) stock ON i.id = stock.item_id
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total_value'];
    }

    public function getRecentTransactions($itemId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT i.*, u.username as created_by_name
            FROM inventory i
            LEFT JOIN users u ON i.created_by = u.id
            WHERE i.item_id = ?
            ORDER BY i.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$itemId, $limit]);
        return $stmt->fetchAll();
    }

    public function addStockTransaction($itemId, $transactionType, $quantity, $notes = '', $createdBy = null) {
        // Get item details for unit cost
        $item = $this->findById($itemId);
        if (!$item) return false;

        $unitCost = $item['unit_cost'];
        $totalCost = $quantity * $unitCost;

        $stmt = $this->conn->prepare("
            INSERT INTO inventory (item_id, transaction_type, quantity, unit_cost, total_cost, transaction_date, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $itemId,
            $transactionType,
            $quantity,
            $unitCost,
            $totalCost,
            date('Y-m-d'),
            $notes,
            $createdBy ?? $_SESSION['user_id'] ?? 1
        ]);
    }
}
?>