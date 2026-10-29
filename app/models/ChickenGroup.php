<?php
require_once __DIR__ . '/Model.php';

class ChickenGroup extends Model {
    protected $table = 'chicken_groups';

    public function getWithStats() {
        $stmt = $this->conn->prepare("
            SELECT cg.*, COUNT(cb.id) as batch_count, COUNT(c.id) as chicken_count
            FROM {$this->table} cg
            LEFT JOIN chicken_batches cb ON cg.id = cb.group_id
            LEFT JOIN chickens c ON cb.id = c.batch_id
            GROUP BY cg.id
            ORDER BY cg.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>