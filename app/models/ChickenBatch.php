<?php
require_once __DIR__ . '/Model.php';

class ChickenBatch extends Model {
    protected $table = 'chicken_batches';
    
    public function getWithDetails() {
        $stmt = $this->conn->prepare("
            SELECT cb.*, cg.name as group_name, cg.breed,
                   COUNT(c.id) as chicken_count,
                   COUNT(CASE WHEN c.status = 'healthy' THEN 1 END) as healthy_count,
                   COUNT(CASE WHEN c.status = 'sick' THEN 1 END) as sick_count,
                   COUNT(CASE WHEN c.status = 'sold' THEN 1 END) as sold_count,
                   COUNT(CASE WHEN c.status = 'deceased' THEN 1 END) as deceased_count
            FROM {$this->table} cb
            LEFT JOIN chicken_groups cg ON cb.group_id = cg.id
            LEFT JOIN chickens c ON cb.id = c.batch_id
            GROUP BY cb.id
            ORDER BY cb.purchase_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getActiveBatches() {
        $stmt = $this->conn->prepare("
            SELECT cb.*, cg.name as group_name, cg.breed,
                   COUNT(c.id) as chicken_count
            FROM {$this->table} cb
            JOIN chicken_groups cg ON cb.group_id = cg.id
            LEFT JOIN chickens c ON cb.id = c.batch_id AND c.status != 'sold'
            GROUP BY cb.id
            HAVING chicken_count > 0
            ORDER BY cb.purchase_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByGroup($groupId) {
        $stmt = $this->conn->prepare("
            SELECT cb.*, cg.name as group_name, cg.breed
            FROM {$this->table} cb
            JOIN chicken_groups cg ON cb.group_id = cg.id
            WHERE cb.group_id = ?
            ORDER BY cb.purchase_date DESC
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }
    
    public function getBatchStats($batchId) {
        $stmt = $this->conn->prepare("
            SELECT 
                cb.*,
                cg.name as group_name,
                cg.breed,
                COUNT(c.id) as total_chickens,
                COUNT(CASE WHEN c.status = 'healthy' THEN 1 END) as healthy_chickens,
                COUNT(CASE WHEN c.status = 'sick' THEN 1 END) as sick_chickens,
                COUNT(CASE WHEN c.status = 'sold' THEN 1 END) as sold_chickens,
                COUNT(CASE WHEN c.status = 'deceased' THEN 1 END) as deceased_chickens,
                AVG(c.weight) as average_weight,
                MIN(c.birth_date) as earliest_birth_date
            FROM {$this->table} cb
            JOIN chicken_groups cg ON cb.group_id = cg.id
            LEFT JOIN chickens c ON cb.id = c.batch_id
            WHERE cb.id = ?
            GROUP BY cb.id
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetch();
    }
}
?>