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

$message = '';

if (isset($_POST['cancel_attempt'])) {
    $attemptId = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;
    if ($attemptId > 0) {
        $adminName = (string)($_SESSION['AdminLoginId'] ?? 'admin');
        $cancelReason = 'Cancelled by admin';
        $cancelAttemptStmt = mysqli_prepare($conn, "UPDATE quiz_attempts SET status = 'cancelled', cancelled_by = ?, cancel_reason = ?, completed_at = NOW() WHERE id = ? AND status = 'active'");
        mysqli_stmt_bind_param($cancelAttemptStmt, 'ssi', $adminName, $cancelReason, $attemptId);
        mysqli_stmt_execute($cancelAttemptStmt);
        $affected = mysqli_stmt_affected_rows($cancelAttemptStmt);
        mysqli_stmt_close($cancelAttemptStmt);
        $message = $affected > 0 ? ('Attempt #' . $attemptId . ' cancelled successfully.') : 'Attempt not found or already closed.';
    } else {
        $message = 'Invalid attempt selected.';
    }
}

if (isset($_POST['cancel_all_active_attempts'])) {
    $adminName = (string)($_SESSION['AdminLoginId'] ?? 'admin');
    $cancelReason = 'Cancelled by admin (bulk action)';
    $cancelAllStmt = mysqli_prepare($conn, "UPDATE quiz_attempts SET status = 'cancelled', cancelled_by = ?, cancel_reason = ?, completed_at = NOW() WHERE status = 'active'");
    mysqli_stmt_bind_param($cancelAllStmt, 'ss', $adminName, $cancelReason);
    mysqli_stmt_execute($cancelAllStmt);
    $cancelledCount = mysqli_stmt_affected_rows($cancelAllStmt);
    mysqli_stmt_close($cancelAllStmt);
    $message = $cancelledCount > 0 ? ($cancelledCount . ' active attempts cancelled.') : 'No active attempts to cancel.';
}

$activeAttempts = array();
$activeAttemptsResult = mysqli_query($conn, "
    SELECT id, username, subject, status, started_at
    FROM quiz_attempts
    WHERE status = 'active'
    ORDER BY started_at DESC
    LIMIT 300
");

if ($activeAttemptsResult) {
    while ($attemptRow = mysqli_fetch_assoc($activeAttemptsResult)) {
        $activeAttempts[] = $attemptRow;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Active Attempts | Admin | Quiz Competitors</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="page.css">
</head>

<body class="admin-theme">
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="adminpannel.php">ADMIN PANEL</a>
            <a href="admin_questions.php">QUESTIONS</a>
            <a href="admin_contacts.php">CONTACTS</a>
            <a href="admin_active_attempts.php" class="nav-active">ACTIVE ATTEMPTS</a>
            <a href="admin_payments.php">PAYMENTS</a>
            <a href="index2.html">HOME</a>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="nav-inline-form">
                <button type="submit" name="Logout" class="btnlogin-popup">LOG OUT</button>
            </form>
        </nav>
    </header>

    <main class="admin-panel-wrap">
        <div class="inner-card">
            <h2>Active Test Attempts</h2>
            <p class="muted-line">Signed in as <strong><?php echo htmlspecialchars($_SESSION['AdminLoginId']); ?></strong></p>

            <?php if ($message !== ''): ?>
                <div class="admin-msg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="admin-summary-grid">
                <div class="admin-summary-card">
                    <div class="admin-summary-label">Currently Active</div>
                    <div class="admin-summary-value"><?php echo count($activeAttempts); ?></div>
                </div>
                <div class="admin-summary-card">
                    <div class="admin-summary-label">Action</div>
                    <form method="POST" action="admin_active_attempts.php" onsubmit="return confirm('Cancel all currently active test attempts?');" class="form-reset-margin">
                        <button type="submit" name="cancel_all_active_attempts" class="admin-mini-btn admin-mini-btn-danger">Cancel All Active</button>
                    </form>
                </div>
            </div>

            <div class="admin-table-shell">
                <table class="admin-table-wide">
                    <thead>
                        <tr class="admin-table-head-row">
                            <th class="admin-cell-h">Attempt #</th>
                            <th class="admin-cell-h">User</th>
                            <th class="admin-cell-h">Subject</th>
                            <th class="admin-cell-h">Status</th>
                            <th class="admin-cell-h">Started At</th>
                            <th class="admin-cell-h">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($activeAttempts) === 0): ?>
                            <tr>
                                <td class="admin-cell" colspan="6">No active attempts right now.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeAttempts as $attempt): ?>
                                <tr>
                                    <td class="admin-cell"><?php echo (int)$attempt['id']; ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($attempt['username']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($attempt['subject']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($attempt['status']); ?></td>
                                    <td class="admin-cell"><?php echo htmlspecialchars($attempt['started_at']); ?></td>
                                    <td class="admin-cell">
                                        <form method="POST" action="admin_active_attempts.php" onsubmit="return confirm('Cancel this attempt?');" class="form-reset-margin">
                                            <input type="hidden" name="attempt_id" value="<?php echo (int)$attempt['id']; ?>">
                                            <button type="submit" name="cancel_attempt" class="admin-mini-btn admin-mini-btn-danger">Cancel Test</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="india-time.js"></script>
</body>

</html>
