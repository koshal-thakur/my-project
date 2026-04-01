<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name    = trim($_POST['name']    ?? '');
$rating  = (int)($_POST['rating'] ?? 5);
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Name and message are required.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $rating = 5;
}

// Limit lengths to prevent abuse
$name    = mb_substr($name,    0, 100);
$message = mb_substr($message, 0, 1000);

$stmt = mysqli_prepare($conn, "INSERT INTO feedback (name, rating, message) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sis', $name, $rating, $message);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save feedback. Please try again.']);
}
mysqli_stmt_close($stmt);
