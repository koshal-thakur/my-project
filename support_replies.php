<?php
require_once 'config.php';
require_once 'redirect_helper.php';

require_user_login('LOGINpage.php');
session_regenerate_id(true);

if (isset($_POST['Logout'])) {
    session_destroy();
    redirect_to('LOGINpage.php');
}

$accountUsername = trim((string)($_SESSION['username'] ?? ''));
$profileUserId = (int)($_SESSION['user_id'] ?? 0);
$profileEmail = trim((string)($_SESSION['email'] ?? ''));

if ($profileUserId <= 0 || $profileEmail === '') {
    $profileStmt = mysqli_prepare($conn, 'SELECT id, email FROM user WHERE username = ? LIMIT 1');
    if ($profileStmt) {
        mysqli_stmt_bind_param($profileStmt, 's', $accountUsername);
        mysqli_stmt_execute($profileStmt);
        $profileResult = mysqli_stmt_get_result($profileStmt);
        $profileRow = $profileResult ? mysqli_fetch_assoc($profileResult) : null;
        if ($profileRow) {
            $profileUserId = (int)($profileRow['id'] ?? 0);
            $profileEmail = trim((string)($profileRow['email'] ?? ''));
            $_SESSION['user_id'] = $profileUserId;
            $_SESSION['email'] = $profileEmail;
        }
        mysqli_stmt_close($profileStmt);
    }
}

$supportChatNotice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_support_chat'])) {
    $chatMessage = trim((string)($_POST['support_chat_message'] ?? ''));
    if ($chatMessage === '') {
        $supportChatNotice = 'Please enter a message before sending.';
    } elseif ($profileUserId <= 0 && $profileEmail === '') {
        $supportChatNotice = 'Unable to identify your chat thread. Please login again.';
    } else {
        $chatMessage = mb_substr($chatMessage, 0, 2000);
        $senderName = $accountUsername !== '' ? $accountUsername : 'User';
        $chatRole = 'user';
        $chatUserId = $profileUserId > 0 ? $profileUserId : 0;

        $insertChatStmt = mysqli_prepare($conn,
            'INSERT INTO support_chat_messages (user_id, username, email, sender_role, sender_name, message) VALUES (?, ?, ?, ?, ?, ?)'
        );
        if ($insertChatStmt) {
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

$supportChatMessages = [];
if ($profileUserId > 0 && $profileEmail !== '') {
    $supportStmt = mysqli_prepare($conn,
        'SELECT id, sender_role, sender_name, message, created_at FROM support_chat_messages WHERE user_id = ? OR email = ? ORDER BY id ASC LIMIT 400'
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
        'SELECT id, sender_role, sender_name, message, created_at FROM support_chat_messages WHERE user_id = ? ORDER BY id ASC LIMIT 400'
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
        'SELECT id, sender_role, sender_name, message, created_at FROM support_chat_messages WHERE email = ? ORDER BY id ASC LIMIT 400'
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

$totalChatMessages = count($supportChatMessages);
$adminReplyCount = 0;
foreach ($supportChatMessages as $chatRow) {
    if (($chatRow['sender_role'] ?? '') === 'admin') {
        $adminReplyCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Replies — Quiz Competitors</title>
    <link rel="stylesheet" href="page.css">
</head>
<body class="account-page">
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="index.php">Home</a>
            <a href="welcomequiz.php">Start Quiz</a>
            <a href="Scoreboard.php">Rankings</a>
            <a href="account.php">My Account</a>
            <a href="support_replies.php" class="nav-active">Support Replies</a>
        </nav>
    </header>

    <div class="account-wrap support-page-wrap">
        <section class="support-page-hero">
            <div>
                <span class="page-update-badge">Support Center</span>
                <h1>Support Replies</h1>
                <p>Chat directly with admin in one dedicated conversation thread.</p>
            </div>
            <div class="support-hero-stats">
                <div class="support-hero-stat">
                    <span>Total Messages</span>
                    <strong><?php echo (int)$totalChatMessages; ?></strong>
                </div>
                <div class="support-hero-stat">
                    <span>Admin Replies</span>
                    <strong><?php echo (int)$adminReplyCount; ?></strong>
                </div>
            </div>
        </section>

        <div class="account-panel account-panel-gap support-chat-card" id="support-messages">
            <div class="support-chat-head">
                <h2 class="account-panel-title">💬 Conversation</h2>
                <span class="support-chat-badge" id="supportChatBadge" hidden>New reply</span>
            </div>
            <p class="account-empty support-subline">Send your query and get replies here without switching pages.</p>

            <?php if ($supportChatNotice !== ''): ?>
                <div class="admin-msg admin-msg-tight"><?php echo htmlspecialchars($supportChatNotice); ?></div>
            <?php endif; ?>

            <div class="support-chat-box">
                <div class="support-chat-topbar">
                    <div class="support-chat-topbar-left">
                        <span class="support-online-dot"></span>
                        <strong>Admin Support</strong>
                    </div>
                    <span class="support-chat-topbar-note">Typically replies within a few minutes</span>
                </div>
                <div class="support-chat-messages" id="supportChatMessages" data-signature="<?php echo htmlspecialchars($supportChatSignature); ?>">
                    <?php if (empty($supportChatMessages)): ?>
                        <p class="account-empty support-empty-state">💬 No messages yet. Start the conversation below.</p>
                    <?php else: ?>
                        <?php foreach ($supportChatMessages as $chatRow): ?>
                            <?php
                                $isAdminMessage = (($chatRow['sender_role'] ?? '') === 'admin');
                                $messageClass = $isAdminMessage ? 'support-msg support-msg-admin' : 'support-msg support-msg-user';
                                $senderLabel = $isAdminMessage ? 'Admin' : 'You';
                                $avatarLabel = $isAdminMessage ? 'A' : 'Y';
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="support-typing-indicator" id="supportTypingIndicator" hidden>Admin is typing…</div>

                <form method="post" class="support-chat-form">
                    <textarea name="support_chat_message" rows="3" maxlength="2000" placeholder="Write your message to admin..." required></textarea>
                    <button type="submit" name="send_support_chat" class="admin-mini-btn support-send-btn">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const messagesBox = document.getElementById('supportChatMessages');
        const chatBadge = document.getElementById('supportChatBadge');
        const typingIndicator = document.getElementById('supportTypingIndicator');
        if (!messagesBox) return;

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
                osc.type = 'sine';
                osc.frequency.value = 920;
                gain.gain.value = 0.02;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.11);
            } catch (e) {}
        }

        let typingTimer = null;
        let localTyping = false;

        function setTypingState(isTyping) {
            fetch('support_chat_typing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: `context=user&is_typing=${isTyping ? 1 : 0}`
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
            const avatar = isAdmin ? 'A' : 'Y';
            const sender = isAdmin ? 'Admin' : 'You';
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
                messagesBox.innerHTML = '<p class="account-empty">No messages yet. Start the conversation below.</p>';
                return;
            }

            messagesBox.innerHTML = messages.map(formatMessage).join('');
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }

        function pollSupportChat() {
            fetch('support_chat_fetch.php?context=user', { cache: 'no-store' })
                .then((res) => res.json())
                .then((data) => {
                    if (!data || !data.success) return;
                    showRemoteTyping(Boolean(data.typing && data.typing.admin));
                    const newSignature = String(data.signature || '0:0');
                    if (newSignature !== lastSignature) {
                        const prevLatestId = latestIdFromSignature(lastSignature);
                        const newLatestId = latestIdFromSignature(newSignature);
                        lastSignature = newSignature;
                        const messages = data.messages || [];
                        renderMessages(messages);

                        const lastMessage = messages.length ? messages[messages.length - 1] : null;
                        if (newLatestId > prevLatestId && lastMessage && lastMessage.sender_role === 'admin') {
                            showBadge();
                            playNotifySound();
                        }
                    }
                })
                .catch(() => {});
        }

        const chatInput = document.querySelector('.support-chat-form textarea');
        if (chatInput) {
            chatInput.addEventListener('focus', hideBadge);
            chatInput.addEventListener('input', () => {
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

            chatInput.addEventListener('blur', () => {
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

        setInterval(pollSupportChat, 5000);
    })();
    </script>
    <script src="india-time.js"></script>
</body>
</html>
