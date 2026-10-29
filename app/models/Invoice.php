<?php
require_once __DIR__ . '/Model.php';

class Invoice extends Model {
    protected $table = 'invoices';

    public function getStats($period = 'month') {
        $dateCondition = '';
        if ($period === 'month') {
            $dateCondition = "WHERE invoice_date >= date('now', '-1 month')";
        } elseif ($period === 'week') {
            $dateCondition = "WHERE invoice_date >= date('now', '-7 days')";
        } elseif ($period === 'year') {
            $dateCondition = "WHERE invoice_date >= date('now', '-1 year')";
        }

        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) as total_invoices,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(total_amount - paid_amount), 0) as total_outstanding,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_invoices,
                COUNT(CASE WHEN type = 'sale' THEN 1 END) as sales_invoices,
                COUNT(CASE WHEN type = 'purchase' THEN 1 END) as purchase_invoices
            FROM {$this->table}
            $dateCondition
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getWithDetails($limit = null) {
        $sql = "
            SELECT i.*,
                   u.username as created_by_name
            FROM {$this->table} i
            LEFT JOIN users u ON i.created_by = u.id
            ORDER BY i.invoice_date DESC, i.created_at DESC
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
            WHERE invoice_date BETWEEN ? AND ?
            ORDER BY invoice_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function getOutstandingInvoices() {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE total_amount > paid_amount AND status IN ('pending', 'partial')
            ORDER BY due_date ASC, invoice_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOverdueInvoices() {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE due_date < date('now') AND total_amount > paid_amount AND status IN ('pending', 'partial')
            ORDER BY due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updatePayment($invoiceId, $paymentAmount) {
        // Get current invoice
        $invoice = $this->findById($invoiceId);
        if (!$invoice) return false;

        $newPaidAmount = $invoice['paid_amount'] + $paymentAmount;
        $status = 'partial';

        if ($newPaidAmount >= $invoice['total_amount']) {
            $status = 'paid';
            $newPaidAmount = $invoice['total_amount']; // Don't exceed total
        }

        return $this->update($invoiceId, [
            'paid_amount' => $newPaidAmount,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getTotalOutstanding() {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(total_amount - paid_amount), 0) as total
            FROM {$this->table}
            WHERE total_amount > paid_amount AND status IN ('pending', 'partial')
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function getInvoiceItems($invoiceId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM invoice_items
            WHERE invoice_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    public function addInvoiceItem($invoiceId, $itemData) {
        $stmt = $this->conn->prepare("
            INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total_amount, category)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $invoiceId,
            $itemData['description'],
            $itemData['quantity'],
            $itemData['unit_price'],
            $itemData['total_amount'],
            $itemData['category'] ?? null
        ]);
    }

    public function generateInvoiceNumber() {
        // Generate invoice number: INV-YYYY-MM-XXXX
        $prefix = 'INV-' . date('Y-m');
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM {$this->table}
            WHERE invoice_number LIKE ?
        ");
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch();
        $sequence = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . '-' . $sequence;
    }
}
?>