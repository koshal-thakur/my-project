<?php
  require_once 'config.php';
    require_once 'redirect_helper.php';

    ensure_session_started();
    session_regenerate_id(true);
    require_user_login('LOGINpage.php');

  $subjectOptions = array(
    'ALL' => 'All Subjects',
    'DBMS' => 'DBMS',
    'Programming Languages' => 'Programming Languages',
    'Science' => 'Science',
    'Mathematics' => 'Mathematics',
    'General Knowledge' => 'General Knowledge',
    'Current Affairs' => 'Current Affairs'
  );

  $paymentMethods = array(
    'UPI' => 'UPI',
    'Card' => 'Debit/Credit Card',
    'Net Banking' => 'Net Banking',
    'Wallet' => 'Wallet'
  );

  $formError = '';

  if (isset($_POST['start_quiz'])) {
    $selectedSubject = trim($_POST['quiz_subject'] ?? 'ALL');
    $selectedPaymentMethod = trim($_POST['payment_method'] ?? '');

    if (!array_key_exists($selectedSubject, $subjectOptions)) {
      $selectedSubject = 'ALL';
    }

    if (!array_key_exists($selectedPaymentMethod, $paymentMethods)) {
      $formError = 'Please choose a payment method to continue.';
    }

    if ($formError === '') {
      $_SESSION['pending_quiz_payment_method'] = $selectedPaymentMethod;
      $_SESSION['pending_quiz_entry_fee'] = 10;
      $_SESSION['pending_quiz_subject'] = $selectedSubject;
      $_SESSION['pending_quiz_subject_label'] = $subjectOptions[$selectedSubject];
      unset($_SESSION['quiz_payment_status']);
      redirect_to('payment_confirm.php');
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0ea5a0">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Instructions — Quiz Competitors</title>
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="icons/icon-192.svg">
    <link rel="stylesheet" href="page.css">
</head>
<body>
  <header>
    <h2 class="QUIZ">QUIZ COMPETITORS</h2>
    <nav class="navigation">
      <a href="index2.html">Home</a>
      <a href="welcomequiz.php" class="nav-active">Start Quiz</a>
      <a href="Scoreboard.php">Rankings</a>
      <a href="account.php">My Account</a>
      <button onclick="document.getElementById('subjectForm').scrollIntoView({behavior:'smooth'});" class="btnlogin-popup">🚀 Start Quiz</button>
    </nav>
  </header>

  <div class="instructions-wrap">
    <span class="page-tag">📋 Before You Begin</span>
    <h1>Read the Instructions Carefully</h1>
    <section class="page-update-strip" aria-label="2026 platform update">
      <span class="page-update-badge">2026 UPDATE</span>
      <h2>Quiz Start Experience Improved</h2>
      <p>We optimized subject selection, payment handoff, and attempt flow so you can begin faster with better reliability.</p>
      <div class="page-update-mini-grid">
        <div class="page-update-mini">
          <strong>Faster Entry</strong>
          <span>Reduced friction before quiz launch.</span>
        </div>
        <div class="page-update-mini">
          <strong>Fair Attempts</strong>
          <span>Stronger control of valid quiz sessions.</span>
        </div>
        <div class="page-update-mini">
          <strong>Clear Progress</strong>
          <span>Cleaner journey from payment to start.</span>
        </div>
      </div>
    </section>

    <div class="instr-card">
      <h2>About the Quiz</h2>
      <p>Quiz Competitors hosts a competitive quiz for BCA and BSC students. Questions are based on DBMS, Programming Languages (Java, Python, etc.) and are tailored to your course curriculum.</p>
    </div>

    <div class="instr-card">
      <h2>Intellectual Property</h2>
      <p>Unless otherwise stated, we or our licensors own all intellectual property rights on this website and its content. All rights are reserved.</p>
    </div>

    <div class="instr-card">
      <h2>Terms &amp; Conditions</h2>
      <p>Entry is open only for BCA and BSC students.</p>
      <ol>
        <li>This is a timed quiz — 60 seconds per question, 7 questions per field.</li>
        <li>Multiple entries from the same user will not qualify.</li>
        <li>Provide accurate details when registering.</li>
        <li>Submitting contact details implies consent for quiz-related use only.</li>
        <li>Questions are randomly picked from the question bank via an automated process.</li>
        <li>Winners are selected by the highest correct answers in the least time.</li>
        <li>There is no negative marking.</li>
        <li>The quiz starts immediately when you click "Start Quiz".</li>
        <li>A submitted entry cannot be withdrawn.</li>
        <li>All participants must abide by the platform's rules and regulations.</li>
      </ol>
    </div>

    <div class="instr-card">
      <h2>Disclaimer</h2>
      <p>The information on this website is for general purposes only. We make no warranties about the completeness, accuracy, or suitability for any particular purpose.<br>
      By entering the quiz, you agree to these Terms &amp; Conditions.</p>
    </div>

    <div class="instr-btn-row">
      <form method="POST" id="subjectForm" class="instr-form-wrap">
        <?php if (!empty($formError)) { ?>
          <div class="form-error-box">
            <?php echo htmlspecialchars($formError); ?>
          </div>
        <?php } ?>
        <div class="subject-picker">
          <label for="quiz_subject">Choose Quiz Subject</label>
          <select name="quiz_subject" id="quiz_subject" required>
            <?php foreach ($subjectOptions as $subjectKey => $subjectLabel) { ?>
              <option value="<?php echo htmlspecialchars($subjectKey); ?>"><?php echo htmlspecialchars($subjectLabel); ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="subject-picker subject-picker-gap">
          <label for="payment_method">Select Payment Method (Entry Fee: ₹10)</label>
          <select name="payment_method" id="payment_method" required>
            <option value="" selected disabled>Choose payment method</option>
            <?php foreach ($paymentMethods as $paymentKey => $paymentLabel) { ?>
              <option value="<?php echo htmlspecialchars($paymentKey); ?>"><?php echo htmlspecialchars($paymentLabel); ?></option>
            <?php } ?>
          </select>
        </div>
        <button class="btn-start btn-start-top" type="submit" name="start_quiz">🚀 Start Quiz Now</button>
      </form>
    </div>
  </div>
  <script src="pwa-init.js"></script>
  <script src="india-time.js"></script>
</body>
</html>