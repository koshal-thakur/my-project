<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();
session_regenerate_id(true);
require_admin_login('adminlogin.php');

if (isset($_POST['Logout'])) {
    session_destroy();
    redirect_to('index2.html');
}

$adminMessage = '';

if (isset($_POST['approve_payment']) || isset($_POST['reject_payment'])) {
    $paymentId = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
    $approvalAction = isset($_POST['approve_payment']) ? 'approved' : 'rejected';
    $adminName = (string)($_SESSION['AdminLoginId'] ?? 'admin');

    if ($paymentId > 0) {
        $approvalStmt = mysqli_prepare(
            $conn,
            'UPDATE quiz_payments SET admin_approval_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = ?'
        );
        $paidStatus = 'paid';
        mysqli_stmt_bind_param($approvalStmt, 'ssis', $approvalAction, $adminName, $paymentId, $paidStatus);
        mysqli_stmt_execute($approvalStmt);
        $affectedRows = mysqli_stmt_affected_rows($approvalStmt);
        mysqli_stmt_close($approvalStmt);

        $adminMessage = $affectedRows > 0
            ? ('Payment #' . $paymentId . ' marked as ' . $approvalAction . '.')
            : 'Unable to update payment approval status.';
    } else {
        $adminMessage = 'Invalid payment record selected.';
    }
}

$filterType = strtolower(trim($_GET['filter'] ?? 'all'));
$allowedFilterTypes = array('all', 'today', 'week', 'custom');
if (!in_array($filterType, $allowedFilterTypes, true)) {
    $filterType = 'all';
}

$startDateInput = trim($_GET['start_date'] ?? '');
$endDateInput = trim($_GET['end_date'] ?? '');
$filterError = '';

$whereParts = array("status = 'paid'");

if ($filterType === 'today') {
    $whereParts[] = 'DATE(created_at) = CURDATE()';
} elseif ($filterType === 'week') {
    $whereParts[] = 'YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)';
} elseif ($filterType === 'custom') {
    $validDatePattern = '/^\d{4}-\d{2}-\d{2}$/';
    $isValidStart = preg_match($validDatePattern, $startDateInput) === 1;
    $isValidEnd = preg_match($validDatePattern, $endDateInput) === 1;

    if (!$isValidStart || !$isValidEnd) {
        $filterError = 'Please select both valid start and end dates.';
    } else {
        $startParts = explode('-', $startDateInput);
        $endParts = explode('-', $endDateInput);
        $startValid = checkdate((int)$startParts[1], (int)$startParts[2], (int)$startParts[0]);
        $endValid = checkdate((int)$endParts[1], (int)$endParts[2], (int)$endParts[0]);

        if (!$startValid || !$endValid) {
            $filterError = 'Invalid custom date values selected.';
        } elseif ($startDateInput > $endDateInput) {
            $filterError = 'Start date cannot be after end date.';
        } else {
            $startDateEscaped = mysqli_real_escape_string($conn, $startDateInput . ' 00:00:00');
            $endDateEscaped = mysqli_real_escape_string($conn, $endDateInput . ' 23:59:59');
            $whereParts[] = "created_at BETWEEN '$startDateEscaped' AND '$endDateEscaped'";
        }
    }
}

$whereClause = implode(' AND ', $whereParts);

$summary = array(
    'total_payments' => 0,
    'total_amount' => 0,
    'latest_payment_at' => ''
);

$summaryQuery = mysqli_query($conn, "
    SELECT
        COUNT(*) AS total_payments,
        IFNULL(SUM(amount), 0) AS total_amount,
        MAX(created_at) AS latest_payment_at
    FROM quiz_payments
    WHERE $whereClause
");

if ($summaryQuery) {
    $summaryRow = mysqli_fetch_assoc($summaryQuery);
    $summary['total_payments'] = (int)($summaryRow['total_payments'] ?? 0);
    $summary['total_amount'] = (int)($summaryRow['total_amount'] ?? 0);
    $summary['latest_payment_at'] = (string)($summaryRow['latest_payment_at'] ?? '');
}

$paymentRows = array();
$paymentsQuery = mysqli_query($conn, "
    SELECT id, username, subject, amount, payment_method, gateway, transaction_ref, status, admin_approval_status, approved_by, approved_at, created_at
    FROM quiz_payments
    WHERE $whereClause
    ORDER BY id DESC
    LIMIT 500
");

if ($paymentsQuery) {
    while ($row = mysqli_fetch_assoc($paymentsQuery)) {
        $paymentRows[] = $row;
    }
}

$callbackRows = array();
$callbackStatusFilter = strtolower(trim($_GET['cb_status'] ?? 'all'));
$allowedCallbackStatuses = array('all', 'success', 'duplicate');
if (!in_array($callbackStatusFilter, $allowedCallbackStatuses, true)) {
    $callbackStatusFilter = 'all';
}
$callbackWhereClause = $callbackStatusFilter !== 'all'
    ? "WHERE callback_status = '" . mysqli_real_escape_string($conn, $callbackStatusFilter) . "'"
    : '';
$callbacksQuery = mysqli_query($conn, "
    SELECT id, username, order_id, transaction_ref, payment_record_id, callback_status, callback_message, created_at
    FROM quiz_payment_callbacks
    $callbackWhereClause
    ORDER BY id DESC
    LIMIT 300
");

if ($callbacksQuery) {
    while ($callbackRow = mysqli_fetch_assoc($callbacksQuery)) {
        $callbackRows[] = $callbackRow;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payments | Quiz Competitors</title>
    <link rel="stylesheet" href="page.css">
</head>
<body class="admin-theme">
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="adminpannel.php">ADMIN PANEL</a>
            <a href="admin_questions.php">QUESTIONS</a>
            <a href="admin_contacts.php">CONTACTS</a>
            <a href="admin_active_attempts.php">ACTIVE ATTEMPTS</a>
            <a href="admin_payments.php" class="nav-active">PAYMENTS</a>
            <a href="index2.html">HOME</a>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="nav-inline-form">
                <button type="submit" name="Logout" class="btnlogin-popup">LOG OUT</button>
            </form>
        </nav>
    </header>

    <main class="admin-panel-wrap">
        <div class="inner-card">
            <h2>Payment History</h2>
            <p class="muted-line">Signed in as <strong><?php echo htmlspecialchars($_SESSION['AdminLoginId']); ?></strong></p>

            <?php if ($adminMessage !== ''): ?>
                <div class="admin-msg admin-msg-tight"><?php echo htmlspecialchars($adminMessage); ?></div>
            <?php endif; ?>

            <form method="GET" action="admin_payments.php" class="admin-filter-grid">
                <div class="admin-field admin-field-reset">
                    <label for="filter">Filter</label>
                    <select id="filter" name="filter">
                        <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Paid</option>
                        <option value="today" <?php echo $filterType === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $filterType === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="custom" <?php echo $filterType === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                <div class="admin-field admin-field-reset">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDateInput); ?>">
                </div>
                <div class="admin-field admin-field-reset">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDateInput); ?>">
                </div>
                <div>
                    <button type="submit" class="btnlogin-popup btn-block">Apply Filter</button>
                </div>
            </form>

            <?php if ($filterError !== ''): ?>
                <div class="admin-msg admin-msg-tight"><?php echo htmlspecialchars($filterError); ?></div>
            <?php endif; ?>

            <div class="admin-summary-grid">
                <div class="admin-summary-card">
                    <div class="admin-summary-label">Total Payments</div>
                    <div class="admin-summary-value"><?php echo (int)$summary['total_payments']; ?></div>
                </div>
                <div class="admin-summary-card">
                    <div class="admin-summary-label">Total Collected</div>
                    <div class="admin-summary-value">₹<?php echo (int)$summary['total_amount']; ?></div>
                </div>
                <div class="admin-summary-card">
                    <div class="admin-summary-label">Latest Payment</div>
                    <div class="admin-summary-value-sm"><?php echo $summary['latest_payment_at'] !== '' ? htmlspecialchars($summary['latest_payment_at']) : 'N/A'; ?></div>
                </div>
            </div>

            <?php if (count($paymentRows) === 0): ?>
                <div class="admin-msg">No payment records found yet.</div>
            <?php else: ?>
                <div class="admin-table-shell">
                    <table class="admin-table-xwide">
                        <thead>
                            <tr class="admin-table-head-row">
                                <th class="admin-cell-h">#</th>
                                <th class="admin-cell-h">User</th>
                                <th class="admin-cell-h">Subject</th>
                                <th class="admin-cell-h">Amount</th>
                                <th class="admin-cell-h">Method</th>
                                <th class="admin-cell-h">Gateway</th>
                                <th class="admin-cell-h">Transaction Ref</th>
                                <th class="admin-cell-h">Status</th>
                                <th class="admin-cell-h">Admin Approval</th>
                                <th class="admin-cell-h">Approved By</th>
                                <th class="admin-cell-h">Created At</th>
                                <th class="admin-cell-h">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentRows as $payment): ?>
                                <tr>
                                    <td class="admin-cell"><?php echo (int)$payment['id']; ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['username']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['subject']); ?></td>
                                    <td class="admin-cell">₹<?php echo (int)$payment['amount']; ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['gateway']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['transaction_ref']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['status']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['admin_approval_status']); ?></td>
                                    <td class="admin-cell">
                                        <?php
                                            $approvedBy = trim((string)($payment['approved_by'] ?? ''));
                                            $approvedAt = trim((string)($payment['approved_at'] ?? ''));
                                            echo $approvedBy !== '' ? htmlspecialchars($approvedBy) : '—';
                                            if ($approvedAt !== '') {
                                                echo '<br><small>' . htmlspecialchars($approvedAt) . '</small>';
                                            }
                                        ?>
                                    </td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($payment['created_at']); ?></td>
                                    <td class="admin-cell">
                                        <?php if (($payment['admin_approval_status'] ?? '') !== 'approved'): ?>
                                            <form method="POST" action="admin_payments.php" class="admin-inline-form admin-inline-form-mr">
                                                <input type="hidden" name="payment_id" value="<?php echo (int)$payment['id']; ?>">
                                                <button type="submit" name="approve_payment" class="admin-mini-btn">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (($payment['admin_approval_status'] ?? '') !== 'rejected'): ?>
                                            <form method="POST" action="admin_payments.php" class="admin-inline-form">
                                                <input type="hidden" name="payment_id" value="<?php echo (int)$payment['id']; ?>">
                                                <button type="submit" name="reject_payment" class="admin-mini-btn admin-mini-btn-danger">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h3 class="admin-section-subtitle">Payment Callback Logs (Including Duplicates)</h3>
            <form method="GET" action="admin_payments.php" class="admin-callback-filter">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filterType); ?>">
                <?php if ($filterType === 'custom'): ?>
                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDateInput); ?>">
                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDateInput); ?>">
                <?php endif; ?>
                <label for="cb_status" class="admin-callback-label">Callback Status:</label>
                <select id="cb_status" name="cb_status" class="admin-callback-select">
                    <option value="all" <?php echo $callbackStatusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="success" <?php echo $callbackStatusFilter === 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="duplicate" <?php echo $callbackStatusFilter === 'duplicate' ? 'selected' : ''; ?>>Duplicate</option>
                </select>
                <button type="submit" class="btnlogin-popup">Filter</button>
            </form>
            <?php if (count($callbackRows) === 0): ?>
                <div class="admin-msg">No callback logs found yet.</div>
            <?php else: ?>
                <div class="admin-table-shell admin-callback-wrap">
                    <table class="admin-table-wide">
                        <thead>
                            <tr class="admin-table-head-row">
                                <th class="admin-cell-h">#</th>
                                <th class="admin-cell-h">User</th>
                                <th class="admin-cell-h">Order ID</th>
                                <th class="admin-cell-h">Transaction Ref</th>
                                <th class="admin-cell-h">Payment Row</th>
                                <th class="admin-cell-h">Callback Status</th>
                                <th class="admin-cell-h">Message</th>
                                <th class="admin-cell-h">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($callbackRows as $callback): ?>
                                <tr>
                                    <td class="admin-cell"><?php echo (int)$callback['id']; ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($callback['username']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($callback['order_id']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($callback['transaction_ref']); ?></td>
                                    <td class="admin-cell"><?php echo (int)($callback['payment_record_id'] ?? 0); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($callback['callback_status']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($callback['callback_message']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($callback['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="india-time.js"></script>
</body>
</html>
