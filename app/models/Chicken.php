<?php
require_once __DIR__ . '/Model.php';

class Chicken extends Model {
    protected $table = 'chickens';
    
    public function getStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_chickens,
                COUNT(CASE WHEN status = 'healthy' THEN 1 END) as healthy_chickens,
                COUNT(CASE WHEN status = 'sick' THEN 1 END) as sick_chickens,
                COUNT(CASE WHEN status = 'sold' THEN 1 END) as sold_chickens,
                COUNT(CASE WHEN status = 'deceased' THEN 1 END) as deceased_chickens,
                COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_chickens,
                COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_chickens,
                AVG(weight) as average_weight
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getByBatch($batchId) {
        $stmt = $this->conn->prepare("
            SELECT c.*, cb.batch_number, cg.name as group_name
            FROM {$this->table} c
            JOIN chicken_batches cb ON c.batch_id = cb.id
            JOIN chicken_groups cg ON cb.group_id = cg.id
            WHERE c.batch_id = ?
            ORDER BY c.tag_number
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }
    
    public function getWithBatchInfo() {
        $stmt = $this->conn->prepare("
            SELECT c.*, cb.batch_number, cb.purchase_date, cg.name as group_name
            FROM {$this->table} c
            JOIN chicken_batches cb ON c.batch_id = cb.id
            JOIN chicken_groups cg ON cb.group_id = cg.id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $id]);
    }
    
    public function getHealthyCount() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE status = 'healthy'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
}
?>