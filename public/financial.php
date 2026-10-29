<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/auth/Auth.php';

$auth = new Auth();
$auth->requireLogin();

require_once __DIR__ . '/../app/models/Sale.php';
require_once __DIR__ . '/../app/models/Expense.php';
require_once __DIR__ . '/../app/models/Invoice.php';
require_once __DIR__ . '/../app/models/Debt.php';

$saleModel = new Sale();
$expenseModel = new Expense();
$invoiceModel = new Invoice();
$debtModel = new Debt();

// Handle date filtering
$period = $_GET['period'] ?? 'month';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Set default date ranges based on period
if (empty($startDate) || empty($endDate)) {
    switch ($period) {
        case 'day':
            $startDate = $endDate = date('Y-m-d');
            break;
        case 'week':
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
            break;
        case 'year':
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
            break;
        default:
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
    }
}

// Get comprehensive financial data for the selected period
$saleStats = $saleModel->getStats($period);
$expenseStats = $expenseModel->getStats($period);
$invoiceStats = $invoiceModel->getStats($period);
$debtStats = $debtModel->getStats($period);

// Get detailed transactions for the date range
$sales = $saleModel->getByDateRange($startDate, $endDate);
$expenses = $expenseModel->getByDateRange($startDate, $endDate);
$invoices = $invoiceModel->getByDateRange($startDate, $endDate);
$debts = $debtModel->getByDateRange($startDate, $endDate);

// Outstanding amounts (all time, not filtered by period)
$outstandingReceivables = $invoiceModel->getTotalOutstanding();
$outstandingPayables = $debtModel->getTotalOutstandingByType('we_owe');
$receivablesOwedToUs = $debtModel->getTotalOutstandingByType('owed_to_us');

// Detailed lists (all time)
$outstandingInvoices = $invoiceModel->getOutstandingInvoices();
$overdueInvoices = $invoiceModel->getOverdueInvoices();
$outstandingDebts = $debtModel->getOutstandingDebts();
$overdueDebts = $debtModel->getOverdueDebts();

// Financial calculations for selected period
$totalRevenue = $saleStats['total_revenue'] ?? 0;
$totalExpenses = $expenseStats['total_amount'] ?? 0;
$cashFlow = $totalRevenue - $totalExpenses;
$netFinancialPosition = $cashFlow - $outstandingPayables + $outstandingReceivables + $receivablesOwedToUs;

// Calculate gains/losses for the period
$periodRevenue = 0;
$periodExpenses = 0;
$periodInvoices = 0;
$periodDebts = 0;

foreach ($sales as $sale) {
    $periodRevenue += $sale['total_amount'];
}
foreach ($expenses as $expense) {
    $periodExpenses += $expense['amount'];
}
foreach ($invoices as $invoice) {
    $periodInvoices += $invoice['total_amount'];
}
foreach ($debts as $debt) {
    $periodDebts += $debt['amount'];
}

$periodGain = $periodRevenue - $periodExpenses;
$periodNetGain = $periodGain + $periodInvoices - $periodDebts;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Overview - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/professional.css">
    <script src="assets/js/animations.js" defer></script>
    <style>
        .table-spreadsheet {
            border-collapse: collapse;
            width: 100%;
            font-size: 0.85em;
        }
        .table-spreadsheet th,
        .table-spreadsheet td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
            vertical-align: middle;
        }
        .table-spreadsheet th {
            background-color: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table-spreadsheet tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .table-spreadsheet tbody tr:hover {
            background-color: #e9ecef;
        }
        .text-right {
            text-align: right !important;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .badge {
            font-size: 0.75em;
            padding: 4px 8px;
        }
        .btn-block {
            width: 100%;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.25em;
        }
        .stat-large {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content no-nav">
                <a href="dashboard.php" class="logo">KUKU</a>
                <div class="user-menu">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="page-navigation">
                <a href="dashboard.php" class="btn btn-outline-primary">
                    ← Back to Dashboard
                </a>
            </div>

            <div class="page-header">
                <h1>Financial Overview</h1>
                <p>Complete financial position including receivables, payables, and net worth</p>
            </div>

            <!-- Period Filter -->
            <div class="card">
                <div class="card-header">
                    <h3>Filter by Period</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="row">
                        <div class="col-md-2">
                            <label class="form-label">Quick Select</label>
                            <select name="period" class="form-control" onchange="updateDateRange(this.value)">
                                <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" id="start_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" id="end_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="financial.php" class="btn btn-secondary btn-block">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Financial Summary -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">$</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
                        <p>Total Revenue</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">E</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($totalExpenses, 2); ?></h3>
                        <p>Total Expenses</p>
                        <small>This month</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">C</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($cashFlow, 2); ?></h3>
                        <p>Cash Flow</p>
                        <small class="<?php echo $cashFlow >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $cashFlow >= 0 ? 'Positive' : 'Negative'; ?>
                        </small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">N</div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($netFinancialPosition, 2); ?></h3>
                        <p>Net Financial Position</p>
                        <small class="<?php echo $netFinancialPosition >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $netFinancialPosition >= 0 ? 'Positive' : 'Negative'; ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Receivables and Payables Summary -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Money Owed to You</h4>
                        </div>
                        <div class="card-body">
                            <div class="stat-large text-success">$<?php echo number_format($outstandingReceivables + $receivablesOwedToUs, 2); ?></div>
                            <div class="mb-2">
                                <small>Invoices: $<?php echo number_format($outstandingReceivables, 2); ?></small><br>
                                <small>Debts: $<?php echo number_format($receivablesOwedToUs, 2); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Money You Owe</h4>
                        </div>
                        <div class="card-body">
                            <div class="stat-large text-danger">$<?php echo number_format($outstandingPayables, 2); ?></div>
                            <div class="mb-2">
                                <small>Outstanding debts</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4>Net Position</h4>
                        </div>
                        <div class="card-body">
                            <div class="stat-large <?php echo ($outstandingReceivables + $receivablesOwedToUs - $outstandingPayables) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                $<?php echo number_format(($outstandingReceivables + $receivablesOwedToUs - $outstandingPayables), 2); ?>
                            </div>
                            <div class="mb-2">
                                <small>Receivables - Payables</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown -->
            <div class="dashboard-grid">
                <!-- Outstanding Receivables -->
                <div class="card">
                    <div class="card-header">
                        <h3>Outstanding Receivables</h3>
                        <p>Money owed to you</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($outstandingInvoices) && $receivablesOwedToUs == 0): ?>
                            <p class="text-muted">No outstanding receivables</p>
                        <?php else: ?>
                            <!-- Invoices Receivable -->
                            <?php if (!empty($outstandingInvoices)): ?>
                            <h5>Outstanding Invoices</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Outstanding</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($outstandingInvoices as $invoice): ?>
                                            <tr class="<?php echo strtotime($invoice['due_date']) < time() ? 'table-warning' : ''; ?>">
                                                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                                <td><?php echo htmlspecialchars($invoice['customer_supplier_name']); ?></td>
                                                <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?></td>
                                                <td><?php echo $invoice['due_date'] ? date('M d, Y', strtotime($invoice['due_date'])) : 'N/A'; ?></td>
                                                <td><span class="badge badge-warning"><?php echo ucfirst($invoice['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                            <!-- Debt Receivables -->
                            <?php if ($receivablesOwedToUs > 0): ?>
                            <h5>Money Lent Out</h5>
                            <div class="alert alert-info">
                                <strong>Total Amount Lent:</strong> $<?php echo number_format($receivablesOwedToUs, 2); ?>
                                <br><small>This includes money lent to others that hasn't been repaid yet.</small>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Outstanding Payables -->
                <div class="card">
                    <div class="card-header">
                        <h3>Outstanding Payables</h3>
                        <p>Money you owe</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($outstandingDebts)): ?>
                            <p class="text-muted">No outstanding payables</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Debtor</th>
                                            <th>Type</th>
                                            <th>Total Amount</th>
                                            <th>Outstanding</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($outstandingDebts as $debt): ?>
                                            <?php if ($debt['debt_type'] === 'we_owe'): ?>
                                            <tr class="<?php echo strtotime($debt['due_date']) < time() ? 'table-danger' : ''; ?>">
                                                <td><?php echo htmlspecialchars($debt['debtor_name']); ?></td>
                                                <td><?php echo $debt['debt_type'] === 'we_owe' ? 'We Owe' : 'Owed to Us'; ?></td>
                                                <td>$<?php echo number_format($debt['amount'], 2); ?></td>
                                                <td>$<?php echo number_format($debt['amount'] - $debt['paid_amount'], 2); ?></td>
                                                <td><?php echo $debt['due_date'] ? date('M d, Y', strtotime($debt['due_date'])) : 'N/A'; ?></td>
                                                <td><span class="badge badge-danger"><?php echo ucfirst($debt['status']); ?></span></td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Overdue Items Alert -->
            <?php if (!empty($overdueInvoices) || !empty($overdueDebts)): ?>
            <div class="alert alert-danger">
                <h4>Overdue Items</h4>
                <p>The following items are past their due dates and need immediate attention:</p>
                <ul>
                    <?php foreach ($overdueInvoices as $invoice): ?>
                        <li><strong>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?>:</strong> $<?php echo number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?> owed by <?php echo htmlspecialchars($invoice['customer_supplier_name']); ?> (Due: <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>)</li>
                    <?php endforeach; ?>
                    <?php foreach ($overdueDebts as $debt): ?>
                        <?php if ($debt['debt_type'] === 'we_owe'): ?>
                            <li><strong>Debt to <?php echo htmlspecialchars($debt['debtor_name']); ?>:</strong> $<?php echo number_format($debt['amount'] - $debt['paid_amount'], 2); ?> (Due: <?php echo date('M d, Y', strtotime($debt['due_date'])); ?>)</li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Detailed Transaction Spreadsheet -->
            <div class="card">
                <div class="card-header">
                    <h3>Transaction Spreadsheet</h3>
                    <p>Detailed breakdown for period: <?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?></p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table-spreadsheet">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Category/Item</th>
                                    <th>Commodity/Service</th>
                                    <th>Description</th>
                                    <th class="text-right">Income (+)</th>
                                    <th class="text-right">Expense (-)</th>
                                    <th class="text-right">Net</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $allTransactions = [];

                                // Add sales
                                foreach ($sales as $sale) {
                                    $allTransactions[] = [
                                        'date' => $sale['sale_date'],
                                        'type' => 'Sale',
                                        'category' => 'Revenue',
                                        'commodity' => 'Chicken Sale',
                                        'description' => 'Sale to ' . ($sale['customer_name'] ?? 'Customer'),
                                        'income' => $sale['total_amount'],
                                        'expense' => 0,
                                        'payment_method' => $sale['payment_method'],
                                        'status' => $sale['payment_status']
                                    ];
                                }

                                // Add expenses
                                foreach ($expenses as $expense) {
                                    $allTransactions[] = [
                                        'date' => $expense['expense_date'],
                                        'type' => 'Expense',
                                        'category' => ucfirst($expense['category']),
                                        'commodity' => $expense['supplier'] ?? 'Supplier',
                                        'description' => $expense['description'],
                                        'income' => 0,
                                        'expense' => $expense['amount'],
                                        'payment_method' => $expense['payment_method'],
                                        'status' => 'Paid'
                                    ];
                                }

                                // Add invoices
                                foreach ($invoices as $invoice) {
                                    $allTransactions[] = [
                                        'date' => $invoice['invoice_date'],
                                        'type' => 'Invoice',
                                        'category' => ucfirst($invoice['type']),
                                        'commodity' => $invoice['customer_supplier_name'],
                                        'description' => $invoice['notes'] ?? 'Invoice',
                                        'income' => $invoice['total_amount'],
                                        'expense' => 0,
                                        'payment_method' => 'Invoice',
                                        'status' => $invoice['status']
                                    ];
                                }

                                // Add debts
                                foreach ($debts as $debt) {
                                    $type = $debt['debt_type'] === 'owed_to_us' ? 'Loan Given' : 'Loan Taken';
                                    $amount = $debt['debt_type'] === 'owed_to_us' ? $debt['amount'] : -$debt['amount'];

                                    $allTransactions[] = [
                                        'date' => date('Y-m-d', strtotime($debt['created_at'])),
                                        'type' => 'Debt',
                                        'category' => $type,
                                        'commodity' => $debt['debtor_name'],
                                        'description' => $debt['description'] ?? 'Debt transaction',
                                        'income' => $amount > 0 ? $amount : 0,
                                        'expense' => $amount < 0 ? abs($amount) : 0,
                                        'payment_method' => 'Debt',
                                        'status' => $debt['status']
                                    ];
                                }

                                // Sort by date descending
                                usort($allTransactions, function($a, $b) {
                                    return strtotime($b['date']) - strtotime($a['date']);
                                });

                                $runningTotal = 0;
                                foreach ($allTransactions as $transaction):
                                    $net = $transaction['income'] - $transaction['expense'];
                                    $runningTotal += $net;
                                ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                        <td><span class="badge badge-<?php
                                            echo $transaction['type'] === 'Sale' ? 'success' :
                                                 ($transaction['type'] === 'Expense' ? 'danger' :
                                                  ($transaction['type'] === 'Invoice' ? 'info' : 'warning'));
                                        ?>"><?php echo $transaction['type']; ?></span></td>
                                        <td><?php echo htmlspecialchars($transaction['category']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['commodity']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                        <td class="text-right text-success"><?php echo $transaction['income'] > 0 ? '$' . number_format($transaction['income'], 2) : ''; ?></td>
                                        <td class="text-right text-danger"><?php echo $transaction['expense'] > 0 ? '$' . number_format($transaction['expense'], 2) : ''; ?></td>
                                        <td class="text-right <?php echo $net >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <strong><?php echo $net >= 0 ? '+' : ''; ?><?php echo '$' . number_format($net, 2); ?></strong>
                                        </td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?></td>
                                        <td><span class="badge badge-<?php
                                            echo $transaction['status'] === 'paid' ? 'success' :
                                                 ($transaction['status'] === 'pending' ? 'warning' :
                                                  ($transaction['status'] === 'active' ? 'primary' : 'secondary'));
                                        ?>"><?php echo ucfirst($transaction['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="5" class="text-right">Period Totals:</th>
                                    <th class="text-right text-success">$<?php echo number_format($periodRevenue, 2); ?></th>
                                    <th class="text-right text-danger">$<?php echo number_format($periodExpenses, 2); ?></th>
                                    <th class="text-right <?php echo $periodNetGain >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo $periodNetGain >= 0 ? '+' : ''; ?><?php echo '$' . number_format($periodNetGain, 2); ?></strong>
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Financial Health Summary -->
            <div class="card">
                <div class="card-header">
                    <h3>Financial Health Summary</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Period Performance (<?php echo ucfirst($period); ?>)</h5>
                            <ul class="list-unstyled">
                                <li><strong>Revenue:</strong> $<?php echo number_format($periodRevenue, 2); ?></li>
                                <li><strong>Expenses:</strong> $<?php echo number_format($periodExpenses, 2); ?></li>
                                <li><strong>Cash Flow:</strong>
                                    <span class="<?php echo $periodGain >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        $<?php echo number_format($periodGain, 2); ?> (<?php echo $periodGain >= 0 ? 'Surplus' : 'Deficit'; ?>)
                                    </span>
                                </li>
                                <li><strong>Net Gain:</strong>
                                    <span class="<?php echo $periodNetGain >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        $<?php echo number_format($periodNetGain, 2); ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Overall Position</h5>
                            <ul class="list-unstyled">
                                <li><strong>Assets (Receivables):</strong> $<?php echo number_format($outstandingReceivables + $receivablesOwedToUs, 2); ?></li>
                                <li><strong>Liabilities (Payables):</strong> $<?php echo number_format($outstandingPayables, 2); ?></li>
                                <li><strong>Net Worth:</strong>
                                    <span class="<?php echo $netFinancialPosition >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        $<?php echo number_format($netFinancialPosition, 2); ?> (<?php echo $netFinancialPosition >= 0 ? 'Positive' : 'Negative'; ?>)
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> Farm Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function updateDateRange(period) {
            const today = new Date();
            let startDate, endDate;

            switch(period) {
                case 'day':
                    startDate = endDate = today.toISOString().split('T')[0];
                    break;
                case 'week':
                    const monday = new Date(today);
                    monday.setDate(today.getDate() - today.getDay() + 1);
                    const sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);
                    startDate = monday.toISOString().split('T')[0];
                    endDate = sunday.toISOString().split('T')[0];
                    break;
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
                    break;
                case 'year':
                    startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                    endDate = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
                    break;
            }

            document.getElementById('start_date').value = startDate;
            document.getElementById('end_date').value = endDate;
        }

        // Initialize date range on page load
        document.addEventListener('DOMContentLoaded', function() {
            const periodSelect = document.querySelector('select[name="period"]');
            if (periodSelect) {
                updateDateRange(periodSelect.value);
            }
        });
    </script>
</body>
</html>