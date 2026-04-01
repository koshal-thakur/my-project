<?php
require_once 'config.php';
require_once 'redirect_helper.php';
ensure_session_started();
session_regenerate_id(true);
require_user_login('LOGINpage.php');

$start = time();
$_SESSION['start_time'] = $start;

$question_order = range(1, 9);
shuffle($question_order);
$_SESSION['question_order'] = $question_order;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0ea5a0">
    <title>Quiz — Quiz Competitors</title>
    <link rel="stylesheet" href="page.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
      .legacy-quiz-wrap {
        max-width: 980px;
        margin: 0 auto;
        padding: calc(var(--header-h) + 24px) 20px 50px;
      }
      .legacy-quiz-panel {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 20px;
        box-shadow: var(--shadow-md);
        padding: 26px;
      }
      .legacy-quiz-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }
      .legacy-quiz-top h1 {
        margin: 0;
        font-size: 1.55rem;
        font-weight: 800;
        color: var(--text-dark);
      }
      #time {
        background: linear-gradient(135deg, rgba(13,148,136,.1), rgba(249,115,22,.1));
        border: 1px solid rgba(13,148,136,.2);
        color: var(--primary-dark);
        border-radius: 99px;
        padding: 8px 14px;
        font-weight: 800;
      }
      .legacy-question {
        margin-top: 18px;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 18px;
        background: #fff;
      }
      .legacy-question h2 {
        margin: 0 0 12px;
        font-size: 1.08rem;
        color: var(--text-dark);
      }
      .legacy-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        margin-bottom: 8px;
        background: #f8fafc;
      }
      #sub12 {
        margin-top: 18px;
      }
    </style>
</head>
<body onload="timeout()">
  <header>
    <h2 class="QUIZ">QUIZ COMPETITORS</h2>
    <nav class="navigation">
      <a href="index2.html">Home</a>
      <div class="quiz-timer-badge" id="timerBadge">⏱ <span id="time">5:00</span></div>
    </nav>
  </header>

  <div class="legacy-quiz-wrap">
    <form method="POST" id="quiz" action="result2.php" class="legacy-quiz-panel">
      <div class="legacy-quiz-top">
        <h1>Quiz Questions</h1>
      </div>

      <?php
      foreach ($_SESSION['question_order'] as $i) {
          $sql = "SELECT * FROM quiz_question WHERE question_number=$i";
          $data = mysqli_query($conn, $sql);
          while ($row = mysqli_fetch_assoc($data)) {
      ?>
          <div class="legacy-question">
            <h2><?php echo htmlspecialchars($row['question_text']); ?></h2>
            <?php
            $sql = "SELECT * FROM options WHERE question_number=$i";
            $data = mysqli_query($conn, $sql);
            while ($opt = mysqli_fetch_assoc($data)) {
            ?>
              <label class="legacy-option">
                <input type="radio" name="check[<?php echo (int)$opt['question_number']; ?>]" value="<?php echo (int)$opt['a_id']; ?>">
                <?php echo htmlspecialchars($opt['choice']); ?>
              </label>
            <?php } ?>
          </div>
      <?php
          }
      }
      ?>

      <button id="sub12" class="btn-quiz-submit" type="submit" name="submit">Submit Quiz ✓</button>
    </form>
  </div>

  <script>
    var timeLeft = 300;
    function timeout() {
      var minute = Math.floor(timeLeft / 60);
      var second = timeLeft % 60;
      if (timeLeft <= 0) {
        document.getElementById('sub12').click();
      } else {
        document.getElementById('time').textContent = minute + ':' + (second < 10 ? '0' + second : second);
      }
      timeLeft--;
      setTimeout(timeout, 1000);
    }
  </script>
</body>
</html>