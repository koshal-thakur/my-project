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

if (isset($_POST['start_quiz_live'])) {
    mysqli_query($conn, 'UPDATE quiz_control SET is_quiz_live = 1 WHERE id = 1');
    $message = 'Quiz has been started. Users can enter only if their payment is approved.';
}

if (isset($_POST['stop_quiz_live'])) {
    mysqli_query($conn, 'UPDATE quiz_control SET is_quiz_live = 0 WHERE id = 1');
    $message = 'Quiz has been stopped.';
}

if (isset($_POST['save_timer'])) {
    $minutesInput = isset($_POST['quiz_total_minutes']) ? (int)$_POST['quiz_total_minutes'] : 0;

    if ($minutesInput < 1 || $minutesInput > 120) {
        $message = 'Please enter valid quiz timer minutes between 1 and 120.';
    } else {
        $totalTimeSeconds = $minutesInput * 60;
        $timerStmt = mysqli_prepare($conn, 'UPDATE quiz_settings SET total_time_seconds = ? WHERE id = 1');
        mysqli_stmt_bind_param($timerStmt, 'i', $totalTimeSeconds);
        mysqli_stmt_execute($timerStmt);
        mysqli_stmt_close($timerStmt);
        $message = 'Quiz timer updated successfully.';
    }
}

$quizTimerSeconds = get_quiz_total_time_seconds($conn);
$quizTimerMinutes = (int)max(1, floor($quizTimerSeconds / 60));

$quizControlRow = null;
$quizControlResult = mysqli_query($conn, 'SELECT is_quiz_live FROM quiz_control WHERE id = 1 LIMIT 1');
if ($quizControlResult) {
    $quizControlRow = mysqli_fetch_assoc($quizControlResult);
}
$isQuizLive = ((int)($quizControlRow['is_quiz_live'] ?? 0) === 1);

?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Panel | Quiz Competitors</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="page.css">

</head>

<body class="admin-theme">
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="adminpannel.php" class="nav-active">ADMIN PANEL</a>
            <a href="admin_questions.php">QUESTIONS</a>
            <a href="admin_contacts.php">CONTACTS</a>
            <a href="admin_active_attempts.php">ACTIVE ATTEMPTS</a>
            <a href="admin_payments.php">PAYMENTS</a>
            <a href="index2.html">HOME</a>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="nav-inline-form">
                <button type="submit" name="Logout" class="btnlogin-popup">LOG OUT</button>
            </form>
        </nav>
    </header>

    <main class="admin-panel-wrap">
        <div class="inner-card admin-feature-shell">
            <div class="admin-dashboard-head">
                <h2>Admin Dashboard
                </h2>
                <p>Signed in as <strong><?php echo htmlspecialchars($_SESSION['AdminLoginId']); ?></strong></p>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="admin-msg"><?php echo htmlspecialchars($message); ?></div>
            <?php } ?>

            <h3 class="admin-section-title">Admin Control Table</h3>
            <div class="sb-table-wrap admin-stack-gap">
                <table class="sb-table admin-feature-table">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Details</th>
                            <th>Current Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="admin-feature-name"><strong>🚦 Quiz Live Control</strong></td>
                            <td>Enable or disable quiz entry for participants.</td>
                            <td>
                                <span class="admin-feature-value <?php echo $isQuizLive ? 'admin-live-value-live' : 'admin-live-value-stopped'; ?>">
                                    <?php echo $isQuizLive ? 'LIVE' : 'STOPPED'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="admin-feature-actions">
                                    <form method="POST" action="adminpannel.php" class="form-reset-margin admin-inline-form">
                                        <button type="submit" name="start_quiz_live" class="admin-mini-btn">Start</button>
                                    </form>
                                    <form method="POST" action="adminpannel.php" class="form-reset-margin admin-inline-form">
                                        <button type="submit" name="stop_quiz_live" class="admin-mini-btn admin-mini-btn-danger">Stop</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="admin-feature-name"><strong>⏱ Quiz Timer</strong></td>
                            <td>Set total quiz duration in minutes.</td>
                            <td><span class="admin-feature-value"><?php echo $quizTimerMinutes; ?> min</span></td>
                            <td>
                                <div class="admin-feature-actions">
                                    <form method="POST" action="adminpannel.php" class="admin-timer-form">
                                        <input type="number" name="quiz_total_minutes" min="1" max="120" value="<?php echo $quizTimerMinutes; ?>" required>
                                        <button type="submit" name="save_timer" class="admin-mini-btn">Save</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="admin-feature-name"><strong>❓ Questions</strong></td>
                            <td>Manage quiz questions, subjects, and answers.</td>
                            <td><span class="admin-feature-muted">Question bank module</span></td>
                            <td>
                                <div class="admin-feature-actions">
                                    <a href="admin_questions.php" class="admin-mini-btn">Open</a>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="admin-feature-name"><strong>📬 Contacts</strong></td>
                            <td>Review contact form submissions from users.</td>
                            <td><span class="admin-feature-muted">Inbox module</span></td>
                            <td>
                                <div class="admin-feature-actions">
                                    <a href="admin_contacts.php" class="admin-mini-btn">Open</a>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="admin-feature-name"><strong>🧪 Active Attempts</strong></td>
                            <td>Monitor currently running quiz attempts.</td>
                            <td><span class="admin-feature-muted">Attempt tracking module</span></td>
                            <td>
                                <div class="admin-feature-actions">
                                    <a href="admin_active_attempts.php" class="admin-mini-btn">Open</a>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="admin-feature-name"><strong>💳 Payments</strong></td>
                            <td>Approve/reject payments and check logs.</td>
                            <td><span class="admin-feature-muted">Payment review module</span></td>
                                                        <td>
                                                                <div class="admin-feature-actions">
                                                                        <a href="admin_payments.php" class="admin-mini-btn">Open</a>
                                                                </div>
                                                        </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</body>

</html>