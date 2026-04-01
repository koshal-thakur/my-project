<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();
require_user_login('LOGINpage.php');

header('Content-Type: application/json; charset=utf-8');

$username = $_SESSION['username'] ?? '';
$attemptId = (int)($_SESSION['quiz_attempt_id'] ?? 0);

$quizControlResult = mysqli_query($conn, 'SELECT is_quiz_live FROM quiz_control WHERE id = 1 LIMIT 1');
$quizControlRow = $quizControlResult ? mysqli_fetch_assoc($quizControlResult) : null;
$isQuizLive = ((int)($quizControlRow['is_quiz_live'] ?? 0) === 1);

if (!$isQuizLive) {
    echo json_encode(array('ok' => true, 'allowed' => false, 'reason' => 'quiz_not_live', 'redirect' => 'alreadyended.php?reason=quiz_stopped'));
    exit;
}

if ($attemptId <= 0 || $username === '') {
    echo json_encode(array('ok' => true, 'allowed' => false, 'reason' => 'attempt_not_found', 'redirect' => 'alreadyended.php?reason=attempt_unavailable'));
    exit;
}

$attemptStmt = mysqli_prepare($conn, 'SELECT status FROM quiz_attempts WHERE id = ? AND username = ? LIMIT 1');
mysqli_stmt_bind_param($attemptStmt, 'is', $attemptId, $username);
mysqli_stmt_execute($attemptStmt);
$attemptResult = mysqli_stmt_get_result($attemptStmt);
$attemptRow = mysqli_fetch_assoc($attemptResult);
mysqli_stmt_close($attemptStmt);

$status = (string)($attemptRow['status'] ?? '');
if ($status !== 'active') {
    echo json_encode(array('ok' => true, 'allowed' => false, 'reason' => 'attempt_closed', 'redirect' => 'alreadyended.php?reason=attempt_cancelled'));
    exit;
}

echo json_encode(array('ok' => true, 'allowed' => true));
