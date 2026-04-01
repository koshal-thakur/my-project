<?php require_once '../config.php'; ?>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0ea5a0">
    <title>Leaderboard — Quiz Competitors</title>
    <link rel="stylesheet" href="../page.css">
    <link rel="stylesheet" href="../responsive.css">
</head>

<body>
    <header>
        <h2 class="QUIZ">QUIZ COMPETITORS</h2>
        <nav class="navigation">
            <a href="../index2.html">Home</a>
            <a href="../contact2.php">Contact</a>
            <button onclick="location.href='../logout.php';" id="myButton" class="btnlogin-popup">Log Out</button>
        </nav>
    </header>

    <div class="scoreboard-wrap">
        <h1>🏆 Leaderboard</h1>

        <div class="sb-table-wrap">
        <?php
        $sql = "SELECT * FROM leaderboard ORDER BY score DESC, time ASC, username ASC";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0):
        ?>
            <table class="sb-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student</th>
                        <th>Score</th>
                        <th>Time Taken</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $rank = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $rowClass = '';
                    if ($rank === 1) { $rowClass = 'sb-gold'; }
                    elseif ($rank === 2) { $rowClass = 'sb-silver'; }
                    elseif ($rank === 3) { $rowClass = 'sb-bronze'; }
                ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td><?php echo $rank; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="sb-score-val"><?php echo (int)$row['score']; ?></td>
                        <td><?php echo (int)$row['time']; ?> seconds</td>
                    </tr>
                <?php $rank++; endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="sb-no-data">
                <p class="sb-no-data-icon">📭</p>
                <p>No records found.</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
    <script src="../india-time.js"></script>
</body>
</html>