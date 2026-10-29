<?php
require_once __DIR__ . '/Model.php';

class Earning extends Model {
    protected $table = 'earnings';
    
    public function getStats($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE earning_date >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE earning_date >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE earning_date >= date('now', '-1 year')";
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_earnings,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(AVG(amount), 0) as average_amount,
                COUNT(CASE WHEN status = 'received' THEN 1 END) as received_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
            FROM {$this->table}
            $dateCondition
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getWithDetails($limit = null) {
        $sql = "
            SELECT e.*, u.username as created_by_name
            FROM {$this->table} e
            LEFT JOIN users u ON e.created_by = u.id
            ORDER BY e.earning_date DESC, e.created_at DESC
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
            WHERE earning_date BETWEEN ? AND ?
            ORDER BY earning_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    public function getBySource($source) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE source = ?
            ORDER BY earning_date DESC
        ");
        $stmt->execute([$source]);
        return $stmt->fetchAll();
    }
    
    public function getTotalBySource($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE earning_date >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE earning_date >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE earning_date >= date('now', '-1 year')";
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                source,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total
            FROM {$this->table}
            $dateCondition
            GROUP BY source
            ORDER BY total DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>