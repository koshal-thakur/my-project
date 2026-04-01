<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();
require_user_login('LOGINpage.php');

header('Content-Type: application/json; charset=utf-8');

$username = $_SESSION['username'] ?? '';
$paymentId = (int)($_SESSION['quiz_payment_id'] ?? 0);

$approved = false;
$quizLive = false;

if ($paymentId > 0 && $username !== '') {
    $stmt = mysqli_prepare($conn, 'SELECT admin_approval_status FROM quiz_payments WHERE id = ? AND username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'is', $paymentId, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $approved = (($row['admin_approval_status'] ?? '') === 'approved');
}

$ctrlQuery = mysqli_query($conn, 'SELECT is_quiz_live FROM quiz_control WHERE id = 1 LIMIT 1');
if ($ctrlQuery) {
    $ctrlRow = mysqli_fetch_assoc($ctrlQuery);
    $quizLive = ((int)($ctrlRow['is_quiz_live'] ?? 0) === 1);
}

echo json_encode(array('approved' => $approved, 'quiz_live' => $quizLive));
