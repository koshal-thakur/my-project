<?php
require_once 'config.php';
require_once 'redirect_helper.php';

header('Content-Type: application/json; charset=utf-8');

$context = trim((string)($_GET['context'] ?? 'user'));
if ($context !== 'admin') {
    $context = 'user';
}

function support_chat_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function support_chat_messages_query(mysqli $conn, int $userId, string $email): array
{
    $messages = [];

    if ($userId > 0 && $email !== '') {
        $stmt = mysqli_prepare($conn,
            "SELECT id, sender_role, sender_name, message, created_at, DATE_FORMAT(created_at, '%d %b %Y, %H:%i') AS created_at_label FROM support_chat_messages WHERE user_id = ? OR email = ? ORDER BY id ASC LIMIT 400"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $userId, $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        return $messages;
    }

    if ($userId > 0) {
        $stmt = mysqli_prepare($conn,
            "SELECT id, sender_role, sender_name, message, created_at, DATE_FORMAT(created_at, '%d %b %Y, %H:%i') AS created_at_label FROM support_chat_messages WHERE user_id = ? ORDER BY id ASC LIMIT 400"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        return $messages;
    }

    if ($email !== '') {
        $stmt = mysqli_prepare($conn,
            "SELECT id, sender_role, sender_name, message, created_at, DATE_FORMAT(created_at, '%d %b %Y, %H:%i') AS created_at_label FROM support_chat_messages WHERE email = ? ORDER BY id ASC LIMIT 400"
        );
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }

    return $messages;
}

function support_chat_is_typing(mysqli $conn, int $userId, string $email, string $senderRole): bool
{
    $sql = "
        SELECT is_typing
        FROM support_chat_typing_status
        WHERE sender_role = ?
          AND (user_id = ? OR email = ?)
          AND updated_at >= (NOW() - INTERVAL 10 SECOND)
        ORDER BY updated_at DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'sis', $senderRole, $userId, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (int)($row['is_typing'] ?? 0) === 1;
}

if ($context === 'admin') {
    if (!isset($_SESSION['AdminLoginId'])) {
        support_chat_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $threadUserId = (int)($_GET['thread_user_id'] ?? 0);
    $threadEmail = trim((string)($_GET['thread_email'] ?? ''));

    if ($threadUserId <= 0 && $threadEmail === '') {
        support_chat_response(['success' => true, 'signature' => 'empty', 'messages' => []]);
    }

    $messages = support_chat_messages_query($conn, $threadUserId, $threadEmail);
    $latestId = 0;
    foreach ($messages as $messageRow) {
        $latestId = max($latestId, (int)($messageRow['id'] ?? 0));
    }

    support_chat_response([
        'success' => true,
        'signature' => count($messages) . ':' . $latestId,
        'typing' => [
            'user' => support_chat_is_typing($conn, $threadUserId, $threadEmail, 'user'),
            'admin' => support_chat_is_typing($conn, $threadUserId, $threadEmail, 'admin'),
        ],
        'messages' => $messages,
    ]);
}

if (!isset($_SESSION['username'])) {
    support_chat_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserEmail = trim((string)($_SESSION['email'] ?? ''));

if ($currentUserId <= 0 && $currentUserEmail === '') {
    $username = trim((string)($_SESSION['username'] ?? ''));
    if ($username !== '') {
        $stmt = mysqli_prepare($conn, 'SELECT id, email FROM user WHERE username = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ($row) {
                $currentUserId = (int)($row['id'] ?? 0);
                $currentUserEmail = trim((string)($row['email'] ?? ''));
            }
        }
    }
}

$messages = support_chat_messages_query($conn, $currentUserId, $currentUserEmail);
$latestId = 0;
foreach ($messages as $messageRow) {
    $latestId = max($latestId, (int)($messageRow['id'] ?? 0));
}

support_chat_response([
    'success' => true,
    'signature' => count($messages) . ':' . $latestId,
    'typing' => [
        'user' => support_chat_is_typing($conn, $currentUserId, $currentUserEmail, 'user'),
        'admin' => support_chat_is_typing($conn, $currentUserId, $currentUserEmail, 'admin'),
    ],
    'messages' => $messages,
]);
