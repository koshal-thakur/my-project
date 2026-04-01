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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_contact'])) {
    $contactId = (int)($_POST['contact_id'] ?? 0);
    $adminReply = trim($_POST['admin_reply'] ?? '');

    if ($contactId <= 0 || $adminReply === '') {
        $adminMessage = 'Please select a valid message and enter a reply.';
    } else {
        $adminReply = mb_substr($adminReply, 0, 2000);
        $adminName = (string)($_SESSION['AdminLoginId'] ?? 'admin');

        $replyStmt = mysqli_prepare($conn, 'UPDATE contacts SET admin_reply = ?, replied_by = ?, replied_at = NOW() WHERE id = ? LIMIT 1');
        if ($replyStmt) {
            mysqli_stmt_bind_param($replyStmt, 'ssi', $adminReply, $adminName, $contactId);
            mysqli_stmt_execute($replyStmt);
            $affectedRows = mysqli_stmt_affected_rows($replyStmt);
            mysqli_stmt_close($replyStmt);

            $adminMessage = $affectedRows > 0
                ? 'Reply sent successfully.'
                : 'Message not found or reply unchanged.';
        } else {
            $adminMessage = 'Unable to save reply right now. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_support_chat_admin'])) {
    $threadUserId = (int)($_POST['thread_user_id'] ?? 0);
    $threadUsername = trim((string)($_POST['thread_username'] ?? ''));
    $threadEmail = trim((string)($_POST['thread_email'] ?? ''));
    $chatMessage = trim((string)($_POST['admin_chat_message'] ?? ''));

    if ($chatMessage === '' || ($threadUserId <= 0 && $threadEmail === '')) {
        $adminMessage = 'Please choose a valid user thread and enter a message.';
    } else {
        $chatMessage = mb_substr($chatMessage, 0, 2000);
        $adminName = (string)($_SESSION['AdminLoginId'] ?? 'admin');
        $role = 'admin';

        $chatInsertStmt = mysqli_prepare($conn,
            'INSERT INTO support_chat_messages (user_id, username, email, sender_role, sender_name, message) VALUES (?, ?, ?, ?, ?, ?)'
        );
        if ($chatInsertStmt) {
            $safeUserId = $threadUserId > 0 ? $threadUserId : 0;
            mysqli_stmt_bind_param($chatInsertStmt, 'isssss', $safeUserId, $threadUsername, $threadEmail, $role, $adminName, $chatMessage);
            if (mysqli_stmt_execute($chatInsertStmt)) {
                $adminMessage = 'Support reply sent successfully.';
            } else {
                $adminMessage = 'Unable to send support reply right now.';
            }
            mysqli_stmt_close($chatInsertStmt);
        } else {
            $adminMessage = 'Unable to send support reply right now.';
        }
    }
}

$contactMessages = array();
$contactsQuery = mysqli_query($conn, "SELECT id, user_id, first_name, last_name, email, phone, message, admin_reply, replied_by, replied_at, created_at FROM contacts ORDER BY id DESC LIMIT 300");
if ($contactsQuery) {
    while ($contactRow = mysqli_fetch_assoc($contactsQuery)) {
        $contactMessages[] = $contactRow;
    }
}

$chatThreads = array();
$threadsQuery = mysqli_query($conn,
    "SELECT user_id, username, email, MAX(created_at) AS latest_at, COUNT(*) AS total_messages
     FROM support_chat_messages
     GROUP BY user_id, username, email
     ORDER BY latest_at DESC
     LIMIT 200"
);
if ($threadsQuery) {
    while ($threadRow = mysqli_fetch_assoc($threadsQuery)) {
        $chatThreads[] = $threadRow;
    }
}

$selectedThreadUserId = isset($_GET['thread_user_id']) ? (int)$_GET['thread_user_id'] : 0;
$selectedThreadEmail = trim((string)($_GET['thread_email'] ?? ''));

if (($selectedThreadUserId <= 0 && $selectedThreadEmail === '') && count($chatThreads) > 0) {
    $selectedThreadUserId = (int)($chatThreads[0]['user_id'] ?? 0);
    $selectedThreadEmail = trim((string)($chatThreads[0]['email'] ?? ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_support_chat_admin'])) {
    $selectedThreadUserId = (int)($_POST['thread_user_id'] ?? 0);
    $selectedThreadEmail = trim((string)($_POST['thread_email'] ?? ''));
}

$selectedThreadUsername = '';
foreach ($chatThreads as $threadItem) {
    $threadUserId = (int)($threadItem['user_id'] ?? 0);
    $threadEmail = trim((string)($threadItem['email'] ?? ''));
    if ($threadUserId === $selectedThreadUserId && $threadEmail === $selectedThreadEmail) {
        $selectedThreadUsername = trim((string)($threadItem['username'] ?? ''));
        break;
    }
}

$selectedThreadMessages = array();
if ($selectedThreadUserId > 0 && $selectedThreadEmail !== '') {
    $selectedThreadStmt = mysqli_prepare($conn,
        'SELECT sender_role, sender_name, message, created_at
         FROM support_chat_messages
         WHERE user_id = ? OR email = ?
         ORDER BY id ASC LIMIT 400'
    );
    if ($selectedThreadStmt) {
        mysqli_stmt_bind_param($selectedThreadStmt, 'is', $selectedThreadUserId, $selectedThreadEmail);
        mysqli_stmt_execute($selectedThreadStmt);
        $selectedThreadResult = mysqli_stmt_get_result($selectedThreadStmt);
        while ($chatRow = mysqli_fetch_assoc($selectedThreadResult)) {
            $selectedThreadMessages[] = $chatRow;
        }
        mysqli_stmt_close($selectedThreadStmt);
    }
} elseif ($selectedThreadUserId > 0) {
    $selectedThreadStmt = mysqli_prepare($conn,
        'SELECT sender_role, sender_name, message, created_at
         FROM support_chat_messages
         WHERE user_id = ?
         ORDER BY id ASC LIMIT 400'
    );
    if ($selectedThreadStmt) {
        mysqli_stmt_bind_param($selectedThreadStmt, 'i', $selectedThreadUserId);
        mysqli_stmt_execute($selectedThreadStmt);
        $selectedThreadResult = mysqli_stmt_get_result($selectedThreadStmt);
        while ($chatRow = mysqli_fetch_assoc($selectedThreadResult)) {
            $selectedThreadMessages[] = $chatRow;
        }
        mysqli_stmt_close($selectedThreadStmt);
    }
} elseif ($selectedThreadEmail !== '') {
    $selectedThreadStmt = mysqli_prepare($conn,
        'SELECT sender_role, sender_name, message, created_at
         FROM support_chat_messages
         WHERE email = ?
         ORDER BY id ASC LIMIT 400'
    );
    if ($selectedThreadStmt) {
        mysqli_stmt_bind_param($selectedThreadStmt, 's', $selectedThreadEmail);
        mysqli_stmt_execute($selectedThreadStmt);
        $selectedThreadResult = mysqli_stmt_get_result($selectedThreadStmt);
        while ($chatRow = mysqli_fetch_assoc($selectedThreadResult)) {
            $selectedThreadMessages[] = $chatRow;
        }
        mysqli_stmt_close($selectedThreadStmt);
    }
}

$selectedThreadSignature = '0:0';
if (!empty($selectedThreadMessages)) {
    $lastMessage = $selectedThreadMessages[count($selectedThreadMessages) - 1];
    $selectedThreadSignature = count($selectedThreadMessages) . ':' . (int)($lastMessage['id'] ?? 0);
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Contacts | Admin | Quiz Competitors</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="page.css">
</head>

<body class="admin-theme">
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="adminpannel.php">ADMIN PANEL</a>
            <a href="admin_questions.php">QUESTIONS</a>
            <a href="admin_contacts.php" class="nav-active">CONTACTS</a>
            <a href="admin_support_replies.php">SUPPORT REPLIES</a>
            <a href="admin_active_attempts.php">ACTIVE ATTEMPTS</a>
            <a href="admin_payments.php">PAYMENTS</a>
            <a href="index2.html">HOME</a>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="nav-inline-form">
                <button type="submit" name="Logout" class="btnlogin-popup">LOG OUT</button>
            </form>
        </nav>
    </header>

    <main class="admin-panel-wrap">
        <div class="inner-card">
            <h2>📬 Contact Messages</h2>
            <p class="muted-line">Signed in as <strong><?php echo htmlspecialchars($_SESSION['AdminLoginId']); ?></strong></p>
            <p class="admin-contact-note">Messages submitted via the Contact page.</p>
            <?php if ($adminMessage !== '') { ?>
                <div class="admin-msg"><?php echo htmlspecialchars($adminMessage); ?></div>
            <?php } ?>

            <div class="sb-table-wrap">
                <table class="sb-table admin-question-table admin-table-compact">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Admin Reply</th>
                            <th>Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contactMessages) === 0) { ?>
                            <tr><td colspan="7">No messages yet.</td></tr>
                        <?php } else { ?>
                            <?php foreach ($contactMessages as $cm) { ?>
                                <tr>
                                    <td><?php echo (int)$cm['id']; ?></td>
                                    <td><?php echo htmlspecialchars(trim($cm['first_name'] . ' ' . $cm['last_name'])); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($cm['email']); ?>"><?php echo htmlspecialchars($cm['email']); ?></a></td>
                                    <td><?php echo htmlspecialchars($cm['phone']); ?></td>
                                    <td class="admin-cell-wrap"><?php echo htmlspecialchars($cm['message']); ?></td>
                                    <td class="admin-cell-wrap">
                                        <?php if (!empty($cm['admin_reply'])) { ?>
                                            <div><?php echo nl2br(htmlspecialchars($cm['admin_reply'])); ?></div>
                                            <small>
                                                by <?php echo htmlspecialchars((string)($cm['replied_by'] ?? 'Admin')); ?>
                                                <?php if (!empty($cm['replied_at'])) { ?>
                                                    on <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($cm['replied_at']))); ?>
                                                <?php } ?>
                                            </small>
                                            <hr>
                                        <?php } ?>
                                        <form method="POST" action="admin_contacts.php" class="form-reset-margin">
                                            <input type="hidden" name="contact_id" value="<?php echo (int)$cm['id']; ?>">
                                            <textarea name="admin_reply" rows="3" maxlength="2000" placeholder="Write reply to user" required><?php echo htmlspecialchars((string)($cm['admin_reply'] ?? '')); ?></textarea>
                                            <button type="submit" name="reply_contact" class="admin-mini-btn"><?php echo empty($cm['admin_reply']) ? 'Send Reply' : 'Update Reply'; ?></button>
                                        </form>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($cm['created_at']))); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="inner-card account-panel-gap">
            <h2>💬 Support Replies Chat</h2>
            <p class="admin-contact-note">Support chat has moved to its own dedicated page.</p>
            <a href="admin_support_replies.php" class="admin-mini-btn">Open Support Replies Page</a>
        </div>
    </main>
    <script src="india-time.js"></script>
</body>

</html>
