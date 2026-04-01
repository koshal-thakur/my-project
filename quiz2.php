<?php
require_once '../redirect_helper.php';
require_once '../config.php';

$start_date = strtotime('2023-05-12 10:00:00');
$end_date = strtotime('+2 days', $start_date);
$current_time = time();

if ($current_time < $start_date) {
    header('Location:notstartedyet.php');
    exit;
}

if ($current_time > $end_date) {
    header('Location:alreadyended.php');
    exit;
}

ensure_session_started();
session_regenerate_id(true);
require_user_login('../LOGINpage.php');

$_SESSION['start_time'] = time();

$question_order = range(1, 9);
shuffle($question_order);
$_SESSION['question_order'] = $question_order;

$questionsData = array();

$questionStmt = mysqli_prepare($conn, 'SELECT question_number, question_text FROM quiz_question WHERE question_number = ? LIMIT 1');
$optionsStmt = mysqli_prepare($conn, 'SELECT a_id, choice FROM options WHERE question_number = ? ORDER BY a_id ASC');

foreach ($question_order as $questionNumber) {
    mysqli_stmt_bind_param($questionStmt, 'i', $questionNumber);
    mysqli_stmt_execute($questionStmt);
    $questionResult = mysqli_stmt_get_result($questionStmt);
    $questionRow = mysqli_fetch_assoc($questionResult);

    if (!$questionRow) {
        continue;
    }

    mysqli_stmt_bind_param($optionsStmt, 'i', $questionNumber);
    mysqli_stmt_execute($optionsStmt);
    $optionsResult = mysqli_stmt_get_result($optionsStmt);

    $options = array();
    while ($optionRow = mysqli_fetch_assoc($optionsResult)) {
        $options[] = $optionRow;
    }

    if (!empty($options)) {
        $questionsData[] = array(
            'question_number' => (int)$questionRow['question_number'],
            'question_text' => $questionRow['question_text'],
            'options' => $options
        );
    }
}

mysqli_stmt_close($questionStmt);
mysqli_stmt_close($optionsStmt);

$quizTotalTimeSeconds = get_quiz_total_time_seconds($conn);
$quizTotalTimeLabel = sprintf('%d:%02d', floor($quizTotalTimeSeconds / 60), $quizTotalTimeSeconds % 60);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0ea5a0">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Quiz — Quiz Competitors</title>
  <link rel="manifest" href="../manifest.webmanifest">
  <link rel="apple-touch-icon" href="../icons/icon-192.svg">
    <link rel="stylesheet" href="../page.css">
</head>
<body>
  <header>
    <h2 class="QUIZ">QUIZ COMPETITORS</h2>
    <nav class="navigation">
      <a href="../index2.html">Home</a>
      <div class="quiz-timer-badge" id="timerBadge">⏱ <span id="time"><?php echo $quizTotalTimeLabel; ?></span></div>
    </nav>
  </header>

  <script>
    var totalQuizTime = <?php echo (int)$quizTotalTimeSeconds; ?>;
    var timeLeft = totalQuizTime;
    var timerHandle = null;

    function timeout(onExpire) {
      var minute = Math.floor(timeLeft / 60);
      var second = timeLeft % 60;
      var badge  = document.getElementById('timerBadge');

      var timeDisplay = document.getElementById('time');
      if (timeDisplay) {
        timeDisplay.textContent = minute + ':' + (second < 10 ? '0' + second : second);
      }

      if (timeLeft <= 0) {
        if (typeof onExpire === 'function') {
          onExpire();
        }
        return;
      } else {
        if (timeLeft <= 10 && badge) {
          badge.classList.add('warn');
        }
      }

      timeLeft--;
      timerHandle = setTimeout(function () {
        timeout(onExpire);
      }, 1000);
    }

    function startQuizTimer(onExpire) {
      var badge = document.getElementById('timerBadge');
      if (timerHandle) {
        clearTimeout(timerHandle);
      }
      timeLeft = totalQuizTime;
      if (badge) {
        badge.classList.remove('warn');
      }
      timeout(onExpire);
    }
  </script>

  <div class="quiz-page-wrap">
    <?php if (count($questionsData) === 0): ?>
      <div class="inner-card quiz-empty-card">
        <div class="quiz-empty-icon">📭</div>
        <h2 class="quiz-empty-title">No Questions Available</h2>
        <p class="quiz-empty-note quiz-empty-note-lg">No questions found right now.</p>
        <a class="btn-start" href="../welcomequiz.php">Back</a>
      </div>
    <?php else: ?>
      <form method="POST" id="quiz" action="result2.php">
        <div class="quiz-layout">
          <div class="quiz-main-panel">
            <?php foreach ($questionsData as $i => $question): ?>
              <div class="question-card quiz-question-item<?php echo $i === 0 ? ' is-active' : ''; ?>" data-question-index="<?php echo $i; ?>">
                <div class="question-num">Question <?php echo $i + 1; ?> of <?php echo count($questionsData); ?></div>
                <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
                <div class="options-grid">
                  <?php foreach ($question['options'] as $option): ?>
                    <label class="option-label">
                      <input class="option-radio" type="radio" name="check[<?php echo (int)$question['question_number']; ?>]" value="<?php echo (int)$option['a_id']; ?>">
                      <span class="option-dot"></span>
                      <?php echo htmlspecialchars($option['choice']); ?>
                    </label>
                  <?php endforeach; ?>
                </div>

                <?php if ($i + 1 === count($questionsData)): ?>
                  <div class="quiz-question-actions">
                    <button id="sub12" class="btn-quiz-submit" type="submit" name="submit">Submit Quiz ✓</button>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <aside class="quiz-side-panel">
            <h3>Quiz Summary</h3>
            <div class="quiz-side-stat">
              <span>Total Questions</span>
              <strong><?php echo count($questionsData); ?></strong>
            </div>
            <div class="quiz-side-stat">
              <span>Current Question</span>
              <strong id="currentQuestionCount">1</strong>
            </div>
            <div class="quiz-progress-bar">
              <div class="quiz-progress-fill" id="quizProgressFill"></div>
            </div>
            <div class="question-serial-list" id="questionSerialList">
              <?php foreach ($questionsData as $i => $question): ?>
                <button type="button" class="question-serial-btn<?php echo $i === 0 ? ' is-active' : ''; ?>" data-target-index="<?php echo $i; ?>">
                  <?php echo $i + 1; ?>
                </button>
              <?php endforeach; ?>
            </div>
          </aside>
        </div>
      </form>

      <script>
        (function () {
          var questionCards = Array.prototype.slice.call(document.querySelectorAll('.quiz-question-item'));
          var currentQuestionIndex = 0;
          var totalQuestions = questionCards.length;
          var currentQuestionCount = document.getElementById('currentQuestionCount');
          var quizProgressFill = document.getElementById('quizProgressFill');
          var serialButtons = Array.prototype.slice.call(document.querySelectorAll('.question-serial-btn'));

          function submitQuiz() {
            var submitButton = document.getElementById('sub12');
            if (submitButton) {
              submitButton.click();
              return;
            }

            var quizForm = document.getElementById('quiz');
            if (quizForm) {
              quizForm.submit();
            }
          }

          function setQuestion(index) {
            currentQuestionIndex = index;

            questionCards.forEach(function (card, cardIndex) {
              card.classList.toggle('is-active', cardIndex === currentQuestionIndex);
            });

            if (currentQuestionCount) {
              currentQuestionCount.textContent = String(currentQuestionIndex + 1);
            }

            if (quizProgressFill && totalQuestions > 0) {
              quizProgressFill.style.width = (((currentQuestionIndex + 1) / totalQuestions) * 100) + '%';
            }

            serialButtons.forEach(function (button, buttonIndex) {
              button.classList.toggle('is-active', buttonIndex === currentQuestionIndex);

              var card = questionCards[buttonIndex];
              var isAnswered = !!card.querySelector('.option-radio:checked');
              button.classList.toggle('is-answered', isAnswered);
            });
          }

          function moveToNextQuestion() {
            if (currentQuestionIndex < totalQuestions - 1) {
              setQuestion(currentQuestionIndex + 1);
            }
          }

          questionCards.forEach(function (card, cardIndex) {
            var optionInputs = card.querySelectorAll('.option-radio');
            var isLastQuestion = cardIndex === totalQuestions - 1;

            optionInputs.forEach(function (optionInput) {
              optionInput.addEventListener('change', function () {
                if (!isLastQuestion) {
                  setTimeout(moveToNextQuestion, 120);
                }
                setQuestion(currentQuestionIndex);
              });
            });
          });

          serialButtons.forEach(function (button) {
            button.addEventListener('click', function () {
              var targetIndex = Number(button.getAttribute('data-target-index'));
              if (!Number.isNaN(targetIndex) && targetIndex >= 0 && targetIndex < totalQuestions) {
                setQuestion(targetIndex);
              }
            });
          });

          if (totalQuestions > 0) {
            setQuestion(0);
            startQuizTimer(function () {
              submitQuiz();
            });
          }
        })();
      </script>
    <?php endif; ?>
  </div>
  <script src="../pwa-init.js"></script>
  <script src="../india-time.js"></script>
</body>
</html>
