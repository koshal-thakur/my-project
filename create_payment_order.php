<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();
require_user_login('LOGINpage.php');

header('Content-Type: application/json; charset=utf-8');

$subjectLabel = $_SESSION['pending_quiz_subject_label'] ?? '';
$paymentMethod = $_SESSION['pending_quiz_payment_method'] ?? '';
$entryFee = (int)($_SESSION['pending_quiz_entry_fee'] ?? 0);
$username = $_SESSION['username'] ?? '';

if ($subjectLabel === '' || $paymentMethod === '' || $entryFee <= 0 || $username === '') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'message' => 'Invalid session for payment. Please restart quiz entry.'));
    exit;
}

$amountInPaise = $entryFee * 100;
$receipt = 'quiz_' . time() . '_' . bin2hex(random_bytes(4));
$orderId = 'order_test_' . bin2hex(random_bytes(6));

$_SESSION['pending_payment_order_id'] = $orderId;
$_SESSION['pending_payment_receipt'] = $receipt;

echo json_encode(array(
    'ok' => true,
    'mock' => true,
    'amount' => $amountInPaise,
    'currency' => 'INR',
    'order_id' => $orderId,
    'name' => 'Quiz Competitors',
    'description' => 'Quiz Competition Entry Fee (Test Payment)',
    'prefill' => array(
        'name' => $username
    ),
    'notes' => array(
        'subject' => $subjectLabel,
        'payment_method' => $paymentMethod
    )
));
