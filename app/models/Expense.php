<?php
require_once __DIR__ . '/Model.php';

class Expense extends Model {
    protected $table = 'expenses';
    
    public function getStats($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE expense_date >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE expense_date >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE expense_date >= date('now', '-1 year')";
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_expenses,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(AVG(amount), 0) as average_expense,
                COUNT(CASE WHEN category = 'feed' THEN 1 END) as feed_expenses,
                COUNT(CASE WHEN category = 'medicine' THEN 1 END) as medicine_expenses,
                COUNT(CASE WHEN category = 'labor' THEN 1 END) as labor_expenses,
                COUNT(CASE WHEN category = 'equipment' THEN 1 END) as equipment_expenses,
                COALESCE(SUM(CASE WHEN category = 'feed' THEN amount END), 0) as feed_total,
                COALESCE(SUM(CASE WHEN category = 'medicine' THEN amount END), 0) as medicine_total,
                COALESCE(SUM(CASE WHEN category = 'labor' THEN amount END), 0) as labor_total,
                COALESCE(SUM(CASE WHEN category = 'equipment' THEN amount END), 0) as equipment_total
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
            ORDER BY e.expense_date DESC, e.created_at DESC
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
            WHERE expense_date BETWEEN ? AND ?
            ORDER BY expense_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    public function getByCategory($category) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE category = ?
            ORDER BY expense_date DESC
        ");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    
    public function getTotalByCategory($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE expense_date >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE expense_date >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE expense_date >= date('now', '-1 year')";
        }
        
        $stmt = $this->conn->prepare("
            SELECT 
                category,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total
            FROM {$this->table}
            $dateCondition
            GROUP BY category
            ORDER BY total DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>