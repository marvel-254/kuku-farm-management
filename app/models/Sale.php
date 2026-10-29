<?php
require_once __DIR__ . '/Model.php';

class Sale extends Model {
    protected $table = 'sales';
    
    public function getStats($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE sale_date >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE sale_date >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE sale_date >= date('now', '-1 year')";
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_sales,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(AVG(total_amount), 0) as average_sale,
                COALESCE(SUM(quantity), 0) as total_chickens_sold,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_sales,
                COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_sales,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_sales
            FROM {$this->table}
            $dateCondition
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getWithDetails($limit = null) {
        $sql = "
            SELECT s.*, 
                   c.tag_number, 
                   cb.batch_number,
                   u.username as created_by_name
            FROM {$this->table} s
            LEFT JOIN chickens c ON s.chicken_id = c.id
            LEFT JOIN chicken_batches cb ON s.batch_id = cb.id
            LEFT JOIN users u ON s.created_by = u.id
            ORDER BY s.sale_date DESC, s.created_at DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
    
    public function getByDateRange($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE sale_date BETWEEN ? AND ?
            ORDER BY sale_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    public function getTotalRevenue($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE sale_date >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE sale_date >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE sale_date >= date('now', '-1 year')";
        }
        
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM {$this->table}
            $dateCondition
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
}
?>