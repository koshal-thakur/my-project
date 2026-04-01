<?php
require_once 'config.php';
require_once 'redirect_helper.php';

header('Content-Type: application/json; charset=utf-8');

function typing_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

$context = trim((string)($_POST['context'] ?? $_GET['context'] ?? 'user'));
if ($context !== 'admin') {
    $context = 'user';
}

$isTyping = isset($_POST['is_typing']) ? (int)$_POST['is_typing'] : -1;
if ($isTyping !== 0 && $isTyping !== 1) {
    typing_response(['success' => false, 'message' => 'Invalid typing value'], 400);
}

if ($context === 'admin') {
    if (!isset($_SESSION['AdminLoginId'])) {
        typing_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $threadUserId = (int)($_POST['thread_user_id'] ?? 0);
    $threadEmail = trim((string)($_POST['thread_email'] ?? ''));
    if ($threadUserId <= 0 && $threadEmail === '') {
        typing_response(['success' => false, 'message' => 'Invalid thread'], 400);
    }

    $senderRole = 'admin';
    $upsertSql = "
        INSERT INTO support_chat_typing_status (user_id, email, sender_role, is_typing)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE is_typing = VALUES(is_typing), updated_at = CURRENT_TIMESTAMP
    ";
    $stmt = mysqli_prepare($conn, $upsertSql);
    if (!$stmt) {
        typing_response(['success' => false, 'message' => 'Failed to save typing status'], 500);
    }

    $safeUserId = $threadUserId > 0 ? $threadUserId : 0;
    mysqli_stmt_bind_param($stmt, 'issi', $safeUserId, $threadEmail, $senderRole, $isTyping);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        typing_response(['success' => false, 'message' => 'Failed to save typing status'], 500);
    }

    typing_response(['success' => true]);
}

if (!isset($_SESSION['username'])) {
    typing_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentEmail = trim((string)($_SESSION['email'] ?? ''));
$currentUsername = trim((string)($_SESSION['username'] ?? ''));

if ($currentUserId <= 0 || $currentEmail === '') {
    $stmt = mysqli_prepare($conn, 'SELECT id, email FROM user WHERE username = ? LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $currentUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if ($row) {
            $currentUserId = (int)($row['id'] ?? 0);
            $currentEmail = trim((string)($row['email'] ?? ''));
        }
    }
}

if ($currentUserId <= 0 && $currentEmail === '') {
    typing_response(['success' => false, 'message' => 'Invalid user thread'], 400);
}

$senderRole = 'user';
$upsertSql = "
    INSERT INTO support_chat_typing_status (user_id, email, sender_role, is_typing)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE is_typing = VALUES(is_typing), updated_at = CURRENT_TIMESTAMP
";
$stmt = mysqli_prepare($conn, $upsertSql);
if (!$stmt) {
    typing_response(['success' => false, 'message' => 'Failed to save typing status'], 500);
}

$safeUserId = $currentUserId > 0 ? $currentUserId : 0;
mysqli_stmt_bind_param($stmt, 'issi', $safeUserId, $currentEmail, $senderRole, $isTyping);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    typing_response(['success' => false, 'message' => 'Failed to save typing status'], 500);
}

typing_response(['success' => true]);
