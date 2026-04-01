<?php
    require_once 'config.php';
    require_once 'redirect_helper.php';
    require_user_login('LOGINpage.php');
    session_regenerate_id(true);

    if (isset($_POST['Logout'])) {
        session_destroy();
        redirect_to('LOGINpage.php');
    }

    $accountUsername = $_SESSION['username'] ?? '';

    $profile = [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $accountUsername,
        'email' => $_SESSION['email'] ?? '',
        'created_at' => $_SESSION['created_at'] ?? null,
    ];

    $profileStmt = mysqli_prepare($conn, 'SELECT id, username, email, created_at FROM user WHERE username = ? LIMIT 1');
    if ($profileStmt) {
        mysqli_stmt_bind_param($profileStmt, 's', $accountUsername);
        mysqli_stmt_execute($profileStmt);
        $profileResult = mysqli_stmt_get_result($profileStmt);
        $profileRow = $profileResult ? mysqli_fetch_assoc($profileResult) : null;
        if ($profileRow) {
            $profile['id'] = $profileRow['id'] ?? $profile['id'];
            $profile['username'] = $profileRow['username'] ?? $profile['username'];
            $profile['email'] = $profileRow['email'] ?? $profile['email'];
            $profile['created_at'] = $profileRow['created_at'] ?? $profile['created_at'];
            $_SESSION['user_id'] = (int)$profile['id'];
            $_SESSION['email'] = (string)$profile['email'];
            $_SESSION['created_at'] = $profile['created_at'];
        }
        mysqli_stmt_close($profileStmt);
    }

    $profileUserId = isset($profile['id']) ? (int)$profile['id'] : 0;
    $profileEmail = trim((string)($profile['email'] ?? ''));
    $supportChatNotice = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_support_chat'])) {
        $chatMessage = trim((string)($_POST['support_chat_message'] ?? ''));
        if ($chatMessage === '') {
            $supportChatNotice = 'Please enter a message before sending.';
        } else {
            $chatMessage = mb_substr($chatMessage, 0, 2000);
            $senderName = $accountUsername !== '' ? $accountUsername : 'User';

            $insertChatStmt = mysqli_prepare($conn,
                'INSERT INTO support_chat_messages (user_id, username, email, sender_role, sender_name, message) VALUES (?, ?, ?, ?, ?, ?)'
            );
            if ($insertChatStmt) {
                $chatUserId = $profileUserId > 0 ? $profileUserId : 0;
                $chatRole = 'user';
                mysqli_stmt_bind_param($insertChatStmt, 'isssss', $chatUserId, $accountUsername, $profileEmail, $chatRole, $senderName, $chatMessage);
                if (mysqli_stmt_execute($insertChatStmt)) {
                    $supportChatNotice = 'Message sent to admin.';
                } else {
                    $supportChatNotice = 'Could not send message right now. Please try again.';
                }
                mysqli_stmt_close($insertChatStmt);
            } else {
                $supportChatNotice = 'Could not send message right now. Please try again.';
            }
        }
    }

    // Fetch user ranking from leaderboard
    $rankRow = null;
    $rankStmt = mysqli_prepare($conn, "
        SELECT score, time,
               (SELECT COUNT(*) + 1 FROM leaderboard l2
                WHERE l2.score > l1.score
                   OR (l2.score = l1.score AND l2.time < l1.time)) AS ranking
        FROM leaderboard l1
        WHERE l1.username = ?
        LIMIT 1
    ");
    if ($rankStmt) {
        mysqli_stmt_bind_param($rankStmt, 's', $accountUsername);
        mysqli_stmt_execute($rankStmt);
        $rankResult = mysqli_stmt_get_result($rankStmt);
        $rankRow = $rankResult ? mysqli_fetch_assoc($rankResult) : null;
        mysqli_stmt_close($rankStmt);
    }

    // Fetch payment history
    $paymentHistory = [];
    $payStmt = mysqli_prepare($conn,
        'SELECT subject, amount, payment_method, gateway, transaction_ref, status, admin_approval_status, created_at
         FROM quiz_payments WHERE username = ? ORDER BY id DESC LIMIT 20'
    );
    if ($payStmt) {
        mysqli_stmt_bind_param($payStmt, 's', $accountUsername);
        mysqli_stmt_execute($payStmt);
        $payResult = mysqli_stmt_get_result($payStmt);
        while ($row = mysqli_fetch_assoc($payResult)) {
            $paymentHistory[] = $row;
        }
        mysqli_stmt_close($payStmt);
    }

    // Fetch quiz attempt history
    $attemptHistory = [];
    $attStmt = mysqli_prepare($conn,
        "SELECT subject, status, started_at, completed_at
         FROM quiz_attempts WHERE username = ? ORDER BY id DESC LIMIT 20"
    );
    if ($attStmt) {
        mysqli_stmt_bind_param($attStmt, 's', $accountUsername);
        mysqli_stmt_execute($attStmt);
        $attResult = mysqli_stmt_get_result($attStmt);
        while ($row = mysqli_fetch_assoc($attResult)) {
            $attemptHistory[] = $row;
        }
        mysqli_stmt_close($attStmt);
    }

    $totalPayments = count($paymentHistory);
    $approvedPayments = 0;
    foreach ($paymentHistory as $paymentItem) {
        if (($paymentItem['admin_approval_status'] ?? '') === 'approved') {
            $approvedPayments++;
        }
    }

    $totalAttempts = count($attemptHistory);
    $completedAttempts = 0;
    foreach ($attemptHistory as $attemptItem) {
        if (($attemptItem['status'] ?? '') === 'completed') {
            $completedAttempts++;
        }
    }

    // Fetch support chat thread for this user
    $supportChatMessages = [];
    if ($profileUserId > 0 && $profileEmail !== '') {
        $supportStmt = mysqli_prepare($conn,
            'SELECT sender_role, sender_name, message, created_at
             FROM support_chat_messages
             WHERE user_id = ? OR email = ?
             ORDER BY id ASC LIMIT 300'
        );
        if ($supportStmt) {
            mysqli_stmt_bind_param($supportStmt, 'is', $profileUserId, $profileEmail);
            mysqli_stmt_execute($supportStmt);
            $supportResult = mysqli_stmt_get_result($supportStmt);
            while ($supportRow = mysqli_fetch_assoc($supportResult)) {
                $supportChatMessages[] = $supportRow;
            }
            mysqli_stmt_close($supportStmt);
        }
    } elseif ($profileUserId > 0) {
        $supportStmt = mysqli_prepare($conn,
            'SELECT sender_role, sender_name, message, created_at
             FROM support_chat_messages
             WHERE user_id = ?
             ORDER BY id ASC LIMIT 300'
        );
        if ($supportStmt) {
            mysqli_stmt_bind_param($supportStmt, 'i', $profileUserId);
            mysqli_stmt_execute($supportStmt);
            $supportResult = mysqli_stmt_get_result($supportStmt);
            while ($supportRow = mysqli_fetch_assoc($supportResult)) {
                $supportChatMessages[] = $supportRow;
            }
            mysqli_stmt_close($supportStmt);
        }
    } elseif ($profileEmail !== '') {
        $supportStmt = mysqli_prepare($conn,
            'SELECT sender_role, sender_name, message, created_at
             FROM support_chat_messages
             WHERE email = ?
             ORDER BY id ASC LIMIT 300'
        );
        if ($supportStmt) {
            mysqli_stmt_bind_param($supportStmt, 's', $profileEmail);
            mysqli_stmt_execute($supportStmt);
            $supportResult = mysqli_stmt_get_result($supportStmt);
            while ($supportRow = mysqli_fetch_assoc($supportResult)) {
                $supportChatMessages[] = $supportRow;
            }
            mysqli_stmt_close($supportStmt);
        }
    }

    $supportChatSignature = '0:0';
    if (!empty($supportChatMessages)) {
        $lastMessage = $supportChatMessages[count($supportChatMessages) - 1];
        $supportChatSignature = count($supportChatMessages) . ':' . (int)($lastMessage['id'] ?? 0);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — Quiz Competitors</title>
    <link rel="stylesheet" href="page.css">
</head>
<body class="account-page">
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="index.php">Home</a>
            <a href="welcomequiz.php">Start Quiz</a>
            <a href="Scoreboard.php">Rankings</a>
            <a href="account.php" class="nav-active">My Account</a>
            <a href="support_replies.php">Support Replies</a>
        </nav>
    </header>

    <div class="account-wrap">
        <section class="account-dashboard-hero" aria-label="Account dashboard overview">
            <div class="account-hero-graphic" aria-hidden="true">
                <span class="account-hero-orb account-hero-orb-1"></span>
                <span class="account-hero-orb account-hero-orb-2"></span>
                <span class="account-hero-grid"></span>
            </div>
            <div class="account-dashboard-content">
                <div>
                    <span class="account-dashboard-tag">My Dashboard</span>
                    <h1>Welcome, <?php echo htmlspecialchars((string)$profile['username']); ?> 👋</h1>
                    <p>Track quiz progress, payment approvals, and support updates from one place.</p>
                    <div class="account-dashboard-meta">
                        <span>Total Attempts: <?php echo (int)$totalAttempts; ?></span>
                        <span>Completed: <?php echo (int)$completedAttempts; ?></span>
                        <span>Payments: <?php echo (int)$totalPayments; ?></span>
                    </div>
                    <div class="account-dashboard-actions">
                        <a href="welcomequiz.php">Start Quiz</a>
                        <a href="Scoreboard.php">Open Rankings</a>
                        <a href="support_replies.php">Support Replies</a>
                    </div>
                </div>

                <div class="account-dashboard-highlights">
                    <div class="account-highlight-card">
                        <span class="account-highlight-icon">🏆</span>
                        <span>Best Rank</span>
                        <strong><?php echo $rankRow ? ('#' . (int)$rankRow['ranking']) : '—'; ?></strong>
                    </div>
                    <div class="account-highlight-card">
                        <span class="account-highlight-icon">🎯</span>
                        <span>Best Score</span>
                        <strong><?php echo $rankRow ? ((int)$rankRow['score'] . ' pts') : '—'; ?></strong>
                    </div>
                    <div class="account-highlight-card">
                        <span class="account-highlight-icon">✅</span>
                        <span>Approved Payments</span>
                        <strong><?php echo (int)$approvedPayments; ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <div class="account-top-grid status-card-gap">
            <div class="status-card">
                <div class="account-avatar">👤</div>
                <h1>Hello, <?php echo htmlspecialchars((string)$profile['username']); ?>!</h1>
                <p class="account-subtitle">Welcome to your dashboard.</p>
                <?php if ($rankRow): ?>
                    <p class="account-rank-line">
                        Best Score: <strong><?php echo (int)$rankRow['score']; ?></strong> &nbsp;|&nbsp;
                        Time: <strong><?php echo (int)$rankRow['time']; ?>s</strong> &nbsp;|&nbsp;
                        Rank: <strong>#<?php echo (int)$rankRow['ranking']; ?></strong>
                    </p>
                <?php else: ?>
                    <p class="account-rank-line">No quiz completed yet.</p>
                <?php endif; ?>
                <div class="account-metrics">
                    <div class="account-metric-card">
                        <span class="account-metric-icon">📝</span>
                        <span>Total Attempts</span>
                        <strong><?php echo (int)$totalAttempts; ?></strong>
                    </div>
                    <div class="account-metric-card">
                        <span class="account-metric-icon">🚀</span>
                        <span>Completed</span>
                        <strong><?php echo (int)$completedAttempts; ?></strong>
                    </div>
                    <div class="account-metric-card">
                        <span class="account-metric-icon">💳</span>
                        <span>Payments</span>
                        <strong><?php echo (int)$totalPayments; ?></strong>
                    </div>
                    <div class="account-metric-card">
                        <span class="account-metric-icon">🛡️</span>
                        <span>Approved</span>
                        <strong><?php echo (int)$approvedPayments; ?></strong>
                    </div>
                </div>
                <div class="status-actions status-actions-gap">
                    <a href="welcomequiz.php">🚀 Start Quiz</a>
                    <a href="Scoreboard.php">🏆 View Scoreboard</a>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="form-reset-margin">
                        <button type="submit" name="Logout">🚪 Log Out</button>
                    </form>
                </div>
            </div>

            <div class="account-panel account-panel-emphasis">
                <h2 class="account-panel-title">👤 Account Details</h2>
                <div class="account-profile-grid">
                    <div class="account-profile-item">
                        <span class="account-profile-icon">🆔</span>
                        <span class="account-profile-label">User ID</span>
                        <strong><?php echo htmlspecialchars((string)($profile['id'] ?? '—')); ?></strong>
                    </div>
                    <div class="account-profile-item">
                        <span class="account-profile-icon">👤</span>
                        <span class="account-profile-label">Username</span>
                        <strong><?php echo htmlspecialchars((string)$profile['username']); ?></strong>
                    </div>
                    <div class="account-profile-item">
                        <span class="account-profile-icon">✉️</span>
                        <span class="account-profile-label">Email</span>
                        <strong><?php echo htmlspecialchars((string)($profile['email'] ?: '—')); ?></strong>
                    </div>
                    <div class="account-profile-item">
                        <span class="account-profile-icon">📅</span>
                        <span class="account-profile-label">Member Since</span>
                        <strong>
                            <?php
                                echo !empty($profile['created_at'])
                                    ? htmlspecialchars(date('d M Y, H:i', strtotime((string)$profile['created_at'])))
                                    : '—';
                            ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Messages -->
        <div class="account-panel account-panel-gap" id="support-messages">
            <div class="account-mini-head">
                <h2 class="account-panel-title">💬 Support Replies</h2>
                <a class="account-mini-link" href="support_replies.php">Open Chat</a>
            </div>
            <p class="account-empty">Need help with payments, attempts, or results? Chat directly with admin.</p>
            <div class="status-actions status-actions-gap">
                <a href="support_replies.php">Open Support Replies Chat</a>
            </div>
        </div>

        <!-- Payment History -->
        <div class="account-panel account-panel-gap" id="payment-history">
            <div class="account-mini-head">
                <h2 class="account-panel-title">💳 Payment History</h2>
                <span class="account-mini-title"><?php echo count($paymentHistory); ?> records</span>
            </div>
            <?php if (empty($paymentHistory)): ?>
                <p class="account-empty">No payments recorded yet.</p>
            <?php else: ?>
                <div class="sb-table-wrap account-table-wrap">
                    <table class="sb-table account-table-compact">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Approval</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($paymentHistory as $pay): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pay['subject']); ?></td>
                                <td>₹<?php echo (int)$pay['amount']; ?></td>
                                <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                <td class="status-pill <?php echo $pay['status']==='paid' ? 'status-pill-success' : 'status-pill-danger'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($pay['status'])); ?>
                                </td>
                                <td class="status-pill <?php echo $pay['admin_approval_status']==='approved' ? 'status-pill-success' : ($pay['admin_approval_status']==='rejected' ? 'status-pill-danger' : 'status-pill-warn'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($pay['admin_approval_status'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($pay['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quiz Attempt History -->
        <div class="account-panel" id="attempt-history">
            <div class="account-mini-head">
                <h2 class="account-panel-title">📝 Quiz Attempt History</h2>
                <span class="account-mini-title"><?php echo count($attemptHistory); ?> records</span>
            </div>
            <?php if (empty($attemptHistory)): ?>
                <p class="account-empty">No quiz attempts recorded yet.</p>
            <?php else: ?>
                <div class="sb-table-wrap account-table-wrap">
                    <table class="sb-table account-table-compact">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($attemptHistory as $att): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($att['subject']); ?></td>
                                <?php
                                    $statusClass = 'status-pill-muted';
                                    if ($att['status'] === 'completed') $statusClass = 'status-pill-success';
                                    elseif ($att['status'] === 'cancelled') $statusClass = 'status-pill-danger';
                                    elseif ($att['status'] === 'active') $statusClass = 'status-pill-warn';
                                ?>
                                <td class="status-pill <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars(ucfirst($att['status'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($att['started_at']))); ?></td>
                                <td><?php echo $att['completed_at'] ? htmlspecialchars(date('d M Y, H:i', strtotime($att['completed_at']))) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    (function () {
        try {
            var savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
            }
        } catch (e) {}
    })();
    </script>
    <script src="india-time.js"></script>
</body>
</html>

