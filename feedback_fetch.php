<?php
require_once 'config.php';

header('Content-Type: application/json');

$result = mysqli_query($conn, "SELECT name, rating, message, created_at FROM feedback ORDER BY created_at DESC LIMIT 12");
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
echo json_encode($rows);
