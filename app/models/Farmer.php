<?php
require_once __DIR__ . '/Model.php';

class Farmer extends Model {
    protected $table = 'farmers';
    
    public function getStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_farmers,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_farmers,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_farmers,
                COUNT(CASE WHEN join_date >= date('now', '-1 month') THEN 1 END) as new_farmers_month
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    public function getActiveFarmers() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function search($term) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? 
            ORDER BY name
        ");
        $searchTerm = "%$term%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    public function getWithUsers() {
        $stmt = $this->conn->prepare("
            SELECT f.*, u.username, u.email as user_email, u.role
            FROM {$this->table} f
            LEFT JOIN users u ON f.id = u.farmer_id
            ORDER BY f.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>