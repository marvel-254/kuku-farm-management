<?php
require_once __DIR__ . '/Model.php';

class Debt extends Model {
    protected $table = 'debts';

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
                COUNT(*) as total_debts,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(amount - paid_amount), 0) as total_outstanding,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_debts,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_debts,
                COUNT(CASE WHEN debt_type = 'owed_to_us' THEN 1 END) as debts_owed_to_us,
                COUNT(CASE WHEN debt_type = 'we_owe' THEN 1 END) as debts_we_owe,
                COUNT(CASE WHEN due_date < date('now') AND amount > paid_amount AND status = 'active' THEN 1 END) as overdue_debts
            FROM {$this->table}
            $dateCondition
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getWithDetails($limit = null) {
        $sql = "
            SELECT d.*,
                   u.username as created_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.created_by = u.id
            ORDER BY d.due_date ASC, d.created_at DESC
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

    public function getOutstandingDebts() {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE amount > paid_amount AND status = 'active'
            ORDER BY due_date ASC, created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOverdueDebts() {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE due_date < date('now') AND amount > paid_amount AND status = 'active'
            ORDER BY due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDebtsByType($type) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE debt_type = ? AND status = 'active'
            ORDER BY due_date ASC, created_at DESC
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public function updatePayment($debtId, $paymentAmount) {
        // Get current debt
        $debt = $this->findById($debtId);
        if (!$debt) return false;

        $newPaidAmount = $debt['paid_amount'] + $paymentAmount;
        $status = 'active';

        if ($newPaidAmount >= $debt['amount']) {
            $status = 'paid';
            $newPaidAmount = $debt['amount']; // Don't exceed total
        }

        return $this->update($debtId, [
            'paid_amount' => $newPaidAmount,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getTotalOutstandingByType($type = null) {
        $whereClause = "WHERE amount > paid_amount AND status = 'active'";
        if ($type) {
            $whereClause .= " AND debt_type = '$type'";
        }

        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(amount - paid_amount), 0) as total
            FROM {$this->table}
            $whereClause
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function getDebtPayments($debtId) {
        $stmt = $this->conn->prepare("
            SELECT dp.*, u.username as created_by_name
            FROM debt_payments dp
            LEFT JOIN users u ON dp.created_by = u.id
            WHERE dp.debt_id = ?
            ORDER BY dp.payment_date DESC
        ");
        $stmt->execute([$debtId]);
        return $stmt->fetchAll();
    }

    public function addDebtPayment($debtId, $paymentData) {
        $stmt = $this->conn->prepare("
            INSERT INTO debt_payments (debt_id, payment_amount, payment_date, payment_method, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $debtId,
            $paymentData['payment_amount'],
            $paymentData['payment_date'],
            $paymentData['payment_method'],
            $paymentData['notes'] ?? null,
            $paymentData['created_by']
        ]);
    }

    public function getTotalDebtPayments($debtId) {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(payment_amount), 0) as total
            FROM debt_payments
            WHERE debt_id = ?
        ");
        $stmt->execute([$debtId]);
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function getByDateRange($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE created_at >= ? AND created_at <= ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetchAll();
    }
}
?>