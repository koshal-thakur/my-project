<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();
require_user_login('LOGINpage.php');

header('Content-Type: application/json; charset=utf-8');

$username = $_SESSION['username'] ?? '';
$subjectLabel = $_SESSION['pending_quiz_subject_label'] ?? '';
$paymentMethod = $_SESSION['pending_quiz_payment_method'] ?? '';
$entryFee = (int)($_SESSION['pending_quiz_entry_fee'] ?? 0);
$expectedOrderId = $_SESSION['pending_payment_order_id'] ?? '';

$paymentId = trim((string)($_POST['payment_id'] ?? ''));
$orderId = trim((string)($_POST['order_id'] ?? ''));
$signature = trim((string)($_POST['signature'] ?? ''));

if ($paymentId === '' || $orderId === '' || $signature === '') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'message' => 'Missing payment verification fields.'));
    exit;
}

$existingPaymentStmt = mysqli_prepare(
    $conn,
    'SELECT id, amount, payment_method, gateway FROM quiz_payments WHERE transaction_ref = ? AND username = ? LIMIT 1'
);
mysqli_stmt_bind_param($existingPaymentStmt, 'ss', $paymentId, $username);
mysqli_stmt_execute($existingPaymentStmt);
$existingPaymentResult = mysqli_stmt_get_result($existingPaymentStmt);
$existingPaymentRow = mysqli_fetch_assoc($existingPaymentResult);
mysqli_stmt_close($existingPaymentStmt);

if (is_array($existingPaymentRow) && isset($existingPaymentRow['id'])) {
    $existingPaymentId = (int)$existingPaymentRow['id'];
    $callbackStatus = 'duplicate';
    $callbackMessage = 'Duplicate payment callback received. Existing payment is already successful.';

    $insertCallbackStmt = mysqli_prepare(
        $conn,
        'INSERT INTO quiz_payment_callbacks (username, order_id, transaction_ref, signature, payment_record_id, callback_status, callback_message) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param(
        $insertCallbackStmt,
        'ssssiss',
        $username,
        $orderId,
        $paymentId,
        $signature,
        $existingPaymentId,
        $callbackStatus,
        $callbackMessage
    );
    mysqli_stmt_execute($insertCallbackStmt);
    mysqli_stmt_close($insertCallbackStmt);

    $_SESSION['quiz_payment_status'] = 'awaiting_admin_approval';
    $_SESSION['quiz_payment_method'] = (string)($existingPaymentRow['payment_method'] ?? 'UPI');
    $_SESSION['quiz_payment_gateway'] = (string)($existingPaymentRow['gateway'] ?? 'FakePay');
    $_SESSION['quiz_transaction_ref'] = $paymentId;
    $_SESSION['quiz_entry_fee'] = (int)($existingPaymentRow['amount'] ?? 0);
    $_SESSION['quiz_paid_at'] = time();
    $_SESSION['quiz_payment_id'] = $existingPaymentId;

    echo json_encode(array('ok' => true, 'message' => $callbackMessage));
    exit;
}

if ($username === '' || $subjectLabel === '' || $paymentMethod === '' || $entryFee <= 0 || $expectedOrderId === '') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'message' => 'Payment session is invalid or expired.'));
    exit;
}

if ($orderId !== $expectedOrderId) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'message' => 'Order mismatch detected. Please try payment again.'));
    exit;
}

$insertPaymentStmt = mysqli_prepare(
    $conn,
    'INSERT INTO quiz_payments (username, subject, amount, payment_method, gateway, transaction_ref, status, admin_approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$gatewayName = 'FakePay';
$paymentStatus = 'paid';
$approvalStatus = 'pending';
$transactionRef = $paymentId;

mysqli_stmt_bind_param(
    $insertPaymentStmt,
    'ssisssss',
    $username,
    $subjectLabel,
    $entryFee,
    $paymentMethod,
    $gatewayName,
    $transactionRef,
    $paymentStatus,
    $approvalStatus
);

$insertOk = mysqli_stmt_execute($insertPaymentStmt);
$errorCode = mysqli_errno($conn);
$errorMessage = mysqli_error($conn);
$insertId = mysqli_insert_id($conn);
mysqli_stmt_close($insertPaymentStmt);

if (!$insertOk) {
    if ($errorCode === 1062) {
        $transactionRef = $paymentId . '_dup_' . time() . '_' . random_int(1000, 9999);

        $retryInsertStmt = mysqli_prepare(
            $conn,
            'INSERT INTO quiz_payments (username, subject, amount, payment_method, gateway, transaction_ref, status, admin_approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        mysqli_stmt_bind_param(
            $retryInsertStmt,
            'ssisssss',
            $username,
            $subjectLabel,
            $entryFee,
            $paymentMethod,
            $gatewayName,
            $transactionRef,
            $paymentStatus,
            $approvalStatus
        );

        $retryOk = mysqli_stmt_execute($retryInsertStmt);
        $insertId = mysqli_insert_id($conn);
        mysqli_stmt_close($retryInsertStmt);

        if (!$retryOk) {
            http_response_code(500);
            echo json_encode(array('ok' => false, 'message' => 'Payment verified but could not be recorded.'));
            exit;
        }
    } else {
        http_response_code(500);
        echo json_encode(array('ok' => false, 'message' => 'Payment verified but could not be recorded. ' . $errorMessage));
        exit;
    }
}

$paymentRecordId = (int)$insertId;

$insertCallbackStmt = mysqli_prepare(
    $conn,
    'INSERT INTO quiz_payment_callbacks (username, order_id, transaction_ref, signature, payment_record_id, callback_status, callback_message) VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$callbackStatus = 'success';
$callbackMessage = 'Payment successful. Awaiting admin approval.';
mysqli_stmt_bind_param(
    $insertCallbackStmt,
    'ssssiss',
    $username,
    $orderId,
    $transactionRef,
    $signature,
    $paymentRecordId,
    $callbackStatus,
    $callbackMessage
);
mysqli_stmt_execute($insertCallbackStmt);
mysqli_stmt_close($insertCallbackStmt);

$_SESSION['quiz_payment_status'] = 'awaiting_admin_approval';
$_SESSION['quiz_payment_method'] = $paymentMethod;
$_SESSION['quiz_payment_gateway'] = $gatewayName;
$_SESSION['quiz_transaction_ref'] = $transactionRef;
$_SESSION['quiz_entry_fee'] = $entryFee;
$_SESSION['quiz_paid_at'] = time();
$_SESSION['quiz_payment_id'] = $paymentRecordId;

unset($_SESSION['pending_payment_order_id']);
unset($_SESSION['pending_payment_receipt']);

echo json_encode(array('ok' => true, 'message' => $callbackMessage));
