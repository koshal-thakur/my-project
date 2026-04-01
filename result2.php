<?php
require_once '../config.php';
require_once '../redirect_helper.php';
ensure_session_started();
session_regenerate_id(true);
require_user_login('quiz2.php');

$score = 0;

if (isset($_POST['submit']) && !empty($_POST['check'])) {
    $i = 1;
    $select = $_POST['check'];
    $sql = "SELECT * FROM quiz_question";
    $data = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($data)) {
        $check = isset($select[$i]) && ((int)$row['a_id'] === (int)$select[$i]);
        if ($check) {
            $score += 10;
        }
        $i++;
    }
}

$end = time();
$start = isset($_SESSION['start_time']) ? (int)$_SESSION['start_time'] : $end;
$timeTaken = ($end - $start);

if (!isset($_SESSION['time_taken'])) {
    $_SESSION['time_taken'] = $timeTaken;
}
$timeTaken = (int)$_SESSION['time_taken'];

$username = $_SESSION['username'];
$sql = "INSERT INTO leaderboard(`username`, `score`, `time`) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        score = IF(VALUES(score) > score, VALUES(score), score),
        time = IF(VALUES(score) > score, VALUES(time), IF(VALUES(score) = score AND VALUES(time) < time, VALUES(time), time))";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sii', $username, $score, $timeTaken);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
?>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0ea5a0">
    <title>Your Result — Quiz Competitors</title>
    <link rel="stylesheet" href="../page.css">
    <link rel="stylesheet" href="../responsive.css">
</head>

<body>
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="../index2.html">Home</a>
            <button onclick="location.href='../Scoreboard.php';" class="btnlogin-popup">🏆 Scoreboard</button>
        </nav>
    </header>

    <div class="result-page-wrap">
        <div class="result-card">
            <span class="result-emoji">🎉</span>
            <h1>Quiz Complete</h1>
            <div class="result-score"><?php echo (int)$score; ?></div>
            <p class="result-time">⏱ Time taken: <strong><?php echo (int)$timeTaken; ?> seconds</strong></p>
            <div class="result-actions">
                <button class="btn-result btn-result-primary" onclick="location.href='../Scoreboard.php';">🏆 View Leaderboard</button>
                <button class="btn-result btn-result-ghost" onclick="location.href='../index2.html';">🏠 Home</button>
            </div>
        </div>
    </div>
    <script src="../india-time.js"></script>
</body>

</html>