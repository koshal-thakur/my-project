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
        'SELECT id, sender_role, sender_name, message, created_at FROM support_chat_messages WHERE user_id = ? OR email = ? ORDER BY id ASC LIMIT 400'
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
        'SELECT id, sender_role, sender_name, message, created_at FROM support_chat_messages WHERE user_id = ? ORDER BY id ASC LIMIT 400'
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
        'SELECT id, sender_role, sender_name, message, created_at FROM support_chat_messages WHERE email = ? ORDER BY id ASC LIMIT 400'
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

$totalThreads = count($chatThreads);
$totalThreadMessages = count($selectedThreadMessages);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Support Replies | Admin | Quiz Competitors</title>
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
            <a href="admin_support_replies.php" class="nav-active">SUPPORT REPLIES</a>
            <a href="admin_active_attempts.php">ACTIVE ATTEMPTS</a>
            <a href="admin_payments.php">PAYMENTS</a>
            <a href="index2.html">HOME</a>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="nav-inline-form">
                <button type="submit" name="Logout" class="btnlogin-popup">LOG OUT</button>
            </form>
        </nav>
    </header>

    <main class="admin-panel-wrap support-page-wrap">
        <section class="support-page-hero support-page-hero-admin">
            <div>
                <span class="page-update-badge">Admin Console</span>
                <h1>Support Replies</h1>
                <p>Manage all user conversations from one professional support workspace.</p>
            </div>
            <div class="support-hero-stats">
                <div class="support-hero-stat">
                    <span>Active Threads</span>
                    <strong><?php echo (int)$totalThreads; ?></strong>
                </div>
                <div class="support-hero-stat">
                    <span>Messages In View</span>
                    <strong><?php echo (int)$totalThreadMessages; ?></strong>
                </div>
            </div>
        </section>

        <div class="inner-card account-panel-gap support-chat-card">
            <div class="support-chat-head">
                <h2>💬 Support Replies Chat</h2>
                <span class="support-chat-badge" id="adminSupportChatBadge" hidden>New message</span>
            </div>
            <p class="admin-contact-note support-subline">Select a thread on the left and reply instantly.</p>

            <?php if ($adminMessage !== '') { ?>
                <div class="admin-msg"><?php echo htmlspecialchars($adminMessage); ?></div>
            <?php } ?>

            <?php if (count($chatThreads) === 0) { ?>
                <p class="account-empty">No support chat messages yet.</p>
            <?php } else { ?>
                <div class="admin-support-chat-layout">
                    <div class="admin-support-thread-list">
                        <div class="admin-support-thread-list-head">
                            <strong>Conversations</strong>
                            <span><?php echo (int)$totalThreads; ?> total</span>
                        </div>
                        <?php foreach ($chatThreads as $thread) { ?>
                            <?php
                                $threadUserId = (int)($thread['user_id'] ?? 0);
                                $threadEmail = trim((string)($thread['email'] ?? ''));
                                $threadName = trim((string)($thread['username'] ?? ''));
                                if ($threadName === '') {
                                    $threadName = $threadEmail !== '' ? $threadEmail : ('User #' . $threadUserId);
                                }
                                $isActiveThread = ($threadUserId === $selectedThreadUserId && $threadEmail === $selectedThreadEmail);
                                $threadUrl = 'admin_support_replies.php?thread_user_id=' . $threadUserId . '&thread_email=' . urlencode($threadEmail);
                                $threadInitial = strtoupper(substr($threadName, 0, 1));
                            ?>
                            <a class="admin-support-thread-item<?php echo $isActiveThread ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($threadUrl); ?>">
                                <span class="admin-thread-avatar"><?php echo htmlspecialchars($threadInitial); ?></span>
                                <span class="admin-thread-text">
                                    <strong><?php echo htmlspecialchars($threadName); ?></strong>
                                    <span><?php echo htmlspecialchars($threadEmail !== '' ? $threadEmail : ('User ID: ' . $threadUserId)); ?></span>
                                    <small><?php echo (int)($thread['total_messages'] ?? 0); ?> messages</small>
                                </span>
                            </a>
                        <?php } ?>
                    </div>

                    <div class="support-chat-box" id="adminSupportChatRoot" data-thread-user-id="<?php echo (int)$selectedThreadUserId; ?>" data-thread-email="<?php echo htmlspecialchars($selectedThreadEmail); ?>">
                        <div class="support-chat-topbar">
                            <div class="support-chat-topbar-left">
                                <span class="support-online-dot"></span>
                                <strong><?php echo htmlspecialchars($selectedThreadUsername !== '' ? $selectedThreadUsername : 'User Conversation'); ?></strong>
                            </div>
                            <span class="support-chat-topbar-note">Live conversation</span>
                        </div>
                        <div class="support-chat-messages" id="adminSupportChatMessages" data-signature="<?php echo htmlspecialchars($selectedThreadSignature); ?>">
                            <?php if (count($selectedThreadMessages) === 0) { ?>
                                <p class="account-empty support-empty-state">💬 Select a user thread to view messages.</p>
                            <?php } else { ?>
                                <?php foreach ($selectedThreadMessages as $chatRow) { ?>
                                    <?php
                                        $isAdminMessage = (($chatRow['sender_role'] ?? '') === 'admin');
                                        $messageClass = $isAdminMessage ? 'support-msg support-msg-admin' : 'support-msg support-msg-user';
                                        $senderLabel = $isAdminMessage ? 'Admin' : 'User';
                                        $avatarLabel = $isAdminMessage ? 'A' : 'U';
                                        $avatarClass = $isAdminMessage ? 'support-msg-avatar support-msg-avatar-admin' : 'support-msg-avatar support-msg-avatar-user';
                                    ?>
                                    <div class="support-msg-row <?php echo $isAdminMessage ? 'support-msg-row-admin' : 'support-msg-row-user'; ?>">
                                        <div class="<?php echo $avatarClass; ?>"><?php echo htmlspecialchars($avatarLabel); ?></div>
                                        <div class="<?php echo $messageClass; ?> support-msg-body">
                                            <div class="support-msg-meta">
                                                <strong><?php echo htmlspecialchars($senderLabel); ?></strong>
                                                <span><?php echo htmlspecialchars(date('d M Y, H:i', strtotime((string)$chatRow['created_at']))); ?></span>
                                            </div>
                                            <p><?php echo nl2br(htmlspecialchars((string)$chatRow['message'])); ?></p>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <div class="support-typing-indicator" id="adminSupportTypingIndicator" hidden>User is typing…</div>

                        <form method="post" class="support-chat-form">
                            <input type="hidden" name="thread_user_id" value="<?php echo (int)$selectedThreadUserId; ?>">
                            <input type="hidden" name="thread_username" value="<?php echo htmlspecialchars($selectedThreadUsername); ?>">
                            <input type="hidden" name="thread_email" value="<?php echo htmlspecialchars($selectedThreadEmail); ?>">
                            <textarea name="admin_chat_message" rows="3" maxlength="2000" placeholder="Write a reply to this user..." required></textarea>
                            <button type="submit" name="send_support_chat_admin" class="admin-mini-btn support-send-btn">Send Reply</button>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    </main>

    <script>
    (function () {
        const chatRoot = document.getElementById('adminSupportChatRoot');
        const messagesBox = document.getElementById('adminSupportChatMessages');
        const chatBadge = document.getElementById('adminSupportChatBadge');
        const typingIndicator = document.getElementById('adminSupportTypingIndicator');
        if (!chatRoot || !messagesBox) return;

        const threadUserId = chatRoot.dataset.threadUserId || '0';
        const threadEmail = chatRoot.dataset.threadEmail || '';
        if (threadUserId === '0' && !threadEmail) return;

        let lastSignature = messagesBox.dataset.signature || '0:0';

        function latestIdFromSignature(signature) {
            const parts = String(signature || '0:0').split(':');
            if (parts.length !== 2) return 0;
            return Number(parts[1]) || 0;
        }

        function showBadge() {
            if (chatBadge) chatBadge.hidden = false;
        }

        function hideBadge() {
            if (chatBadge) chatBadge.hidden = true;
        }

        function playNotifySound() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.value = 760;
                gain.gain.value = 0.02;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.1);
            } catch (e) {}
        }

        let typingTimer = null;
        let localTyping = false;

        function setTypingState(isTyping) {
            const params = new URLSearchParams({
                context: 'admin',
                thread_user_id: threadUserId,
                thread_email: threadEmail,
                is_typing: isTyping ? '1' : '0'
            });

            fetch('support_chat_typing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: params.toString()
            }).catch(() => {});
        }

        function showRemoteTyping(show) {
            if (!typingIndicator) return;
            typingIndicator.hidden = !show;
        }

        function escHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatMessage(message) {
            const isAdmin = message.sender_role === 'admin';
            const wrapperClass = isAdmin ? 'support-msg support-msg-admin' : 'support-msg support-msg-user';
            const rowClass = isAdmin ? 'support-msg-row support-msg-row-admin' : 'support-msg-row support-msg-row-user';
            const avatarClass = isAdmin ? 'support-msg-avatar support-msg-avatar-admin' : 'support-msg-avatar support-msg-avatar-user';
            const avatar = isAdmin ? 'A' : 'U';
            const sender = isAdmin ? 'Admin' : 'User';
            const createdAt = escHtml(message.created_at_label || message.created_at || '');
            const msgText = escHtml(message.message || '').replace(/\n/g, '<br>');

            return `
                <div class="${rowClass}">
                    <div class="${avatarClass}">${avatar}</div>
                    <div class="${wrapperClass} support-msg-body">
                        <div class="support-msg-meta">
                            <strong>${sender}</strong>
                            <span>${createdAt}</span>
                        </div>
                        <p>${msgText}</p>
                    </div>
                </div>
            `;
        }

        function renderMessages(messages) {
            if (!Array.isArray(messages) || messages.length === 0) {
                messagesBox.innerHTML = '<p class="account-empty">No messages in this thread yet.</p>';
                return;
            }

            messagesBox.innerHTML = messages.map(formatMessage).join('');
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }

        function pollAdminSupportChat() {
            const params = new URLSearchParams({
                context: 'admin',
                thread_user_id: threadUserId,
                thread_email: threadEmail
            });

            fetch('support_chat_fetch.php?' + params.toString(), { cache: 'no-store' })
                .then((res) => res.json())
                .then((data) => {
                    if (!data || !data.success) return;
                    showRemoteTyping(Boolean(data.typing && data.typing.user));
                    const newSignature = String(data.signature || '0:0');
                    if (newSignature !== lastSignature) {
                        const prevLatestId = latestIdFromSignature(lastSignature);
                        const newLatestId = latestIdFromSignature(newSignature);
                        lastSignature = newSignature;
                        const messages = data.messages || [];
                        renderMessages(messages);

                        const lastMessage = messages.length ? messages[messages.length - 1] : null;
                        if (newLatestId > prevLatestId && lastMessage && lastMessage.sender_role === 'user') {
                            showBadge();
                            playNotifySound();
                        }
                    }
                })
                .catch(() => {});
        }

        const adminChatInput = document.querySelector('textarea[name="admin_chat_message"]');
        if (adminChatInput) {
            adminChatInput.addEventListener('focus', hideBadge);
            adminChatInput.addEventListener('input', () => {
                if (!localTyping) {
                    localTyping = true;
                    setTypingState(true);
                }

                if (typingTimer) {
                    clearTimeout(typingTimer);
                }
                typingTimer = setTimeout(() => {
                    localTyping = false;
                    setTypingState(false);
                }, 1600);
            });

            adminChatInput.addEventListener('blur', () => {
                localTyping = false;
                setTypingState(false);
            });
        }
        messagesBox.addEventListener('click', hideBadge);

        const chatFormEl = document.querySelector('.support-chat-form');
        if (chatFormEl) {
            chatFormEl.addEventListener('submit', () => {
                localTyping = false;
                setTypingState(false);
            });
        }

        window.addEventListener('beforeunload', () => {
            setTypingState(false);
        });

        setInterval(pollAdminSupportChat, 5000);
    })();
    </script>
    <script src="india-time.js"></script>
</body>
</html>
