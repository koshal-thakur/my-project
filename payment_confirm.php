<?php
require_once 'config.php';
require_once 'redirect_helper.php';

ensure_session_started();
session_regenerate_id(true);
require_user_login('LOGINpage.php');

$subjectLabel = $_SESSION['pending_quiz_subject_label'] ?? '';
$paymentMethod = $_SESSION['pending_quiz_payment_method'] ?? '';
$entryFee = (int)($_SESSION['pending_quiz_entry_fee'] ?? 0);
$username = $_SESSION['username'] ?? '';
$sessionPaymentId = (int)($_SESSION['quiz_payment_id'] ?? 0);

if ($subjectLabel === '' || $paymentMethod === '' || $entryFee <= 0) {
    redirect_to('welcomequiz.php');
}

$paymentDone = false;
$isApprovedByAdmin = false;
$approvalStatus = 'pending';
$isQuizLive = false;
$startBlockMessage = '';
$slipGeneratedAt = date('d M Y, h:i A');
$slipRefBase = (string)($_SESSION['quiz_transaction_ref'] ?? ('PAY-' . ($sessionPaymentId > 0 ? (string)$sessionPaymentId : date('YmdHis'))));
$paymentSlipId = 'SLIP-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $slipRefBase), 0, 16));

$quizControlQuery = mysqli_query($conn, 'SELECT is_quiz_live FROM quiz_control WHERE id = 1 LIMIT 1');
if ($quizControlQuery) {
  $quizControlRow = mysqli_fetch_assoc($quizControlQuery);
  $isQuizLive = ((int)($quizControlRow['is_quiz_live'] ?? 0) === 1);
}

if ($sessionPaymentId > 0 && $username !== '') {
  $paymentStatusStmt = mysqli_prepare($conn, 'SELECT status, admin_approval_status FROM quiz_payments WHERE id = ? AND username = ? LIMIT 1');
  mysqli_stmt_bind_param($paymentStatusStmt, 'is', $sessionPaymentId, $username);
  mysqli_stmt_execute($paymentStatusStmt);
  $paymentStatusResult = mysqli_stmt_get_result($paymentStatusStmt);
  $paymentStatusRow = mysqli_fetch_assoc($paymentStatusResult);
  mysqli_stmt_close($paymentStatusStmt);

  if ($paymentStatusRow) {
    $paymentDone = (($paymentStatusRow['status'] ?? '') === 'paid');
    $approvalStatus = (string)($paymentStatusRow['admin_approval_status'] ?? 'pending');
    $isApprovedByAdmin = ($approvalStatus === 'approved');
  }
}

if (isset($_POST['start_quiz_after_payment'])) {
  if (!$paymentDone) {
    $startBlockMessage = 'Please complete payment first.';
  } elseif (!$isApprovedByAdmin) {
    $startBlockMessage = 'Your payment is under admin review. Quiz access will unlock after approval.';
  } elseif (!$isQuizLive) {
    $startBlockMessage = 'Quiz is currently not live. Wait for admin to start the quiz.';
  } else {
    $_SESSION['quiz_payment_status'] = 'paid';

    $_SESSION['quiz_subject'] = $_SESSION['pending_quiz_subject'] ?? 'ALL';
    $_SESSION['quiz_subject_label'] = $_SESSION['pending_quiz_subject_label'] ?? 'All Subjects';

    unset($_SESSION['pending_quiz_subject']);
    unset($_SESSION['pending_quiz_subject_label']);
    unset($_SESSION['pending_quiz_payment_method']);
    unset($_SESSION['pending_quiz_entry_fee']);

    redirect_to('quiz2.php?n=1');
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
    <title>Payment Confirmation — Quiz Competitors</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="icons/icon-192.svg">
    <link rel="stylesheet" href="page.css">
</head>
<body class="payment-pdf-page">
  <header>
    <h2 class="QUIZ">QUIZ COMPETITORS</h2>
    <nav class="navigation">
      <a href="welcomequiz.php">Back</a>
      <a href="index2.html">Home</a>
      <a href="account.php">My Account</a>
    </nav>
  </header>

  <div class="instructions-wrap" id="paymentSlipPrintable">
    <div class="pdf-print-head">
      <h2>Quiz Competitors — Payment Slip</h2>
      <p><strong>Slip ID:</strong> <?php echo htmlspecialchars($paymentSlipId); ?> &nbsp;|&nbsp; <strong>Generated:</strong> <?php echo htmlspecialchars($slipGeneratedAt); ?></p>
    </div>
    <span class="page-tag">💳 Payment Required</span>
    <h1>Confirm Entry Fee Payment</h1>

    <div class="instr-card">
      <h2>Competition Details</h2>
      <p><strong>Quiz Subject:</strong> <?php echo htmlspecialchars($subjectLabel); ?></p>
      <p><strong>Entry Fee:</strong> ₹<?php echo (int)$entryFee; ?></p>
      <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($paymentMethod); ?></p>
    </div>

    <?php if (!$paymentDone): ?>
      <div class="instr-card">
        <h2>Step 1 of 2</h2>
        <p>Click below to complete a local test payment for this project.</p>
      </div>
      <div class="instr-btn-row">
        <div class="pay-box">
          <button class="btn-start" type="button" id="payBtn">Pay ₹<?php echo (int)$entryFee; ?> (Test Payment)</button>
          <div id="paymentMsg" class="payment-message"></div>
        </div>
      </div>
    <?php else: ?>
      <div class="instr-card">
        <h2>Step 2 of 2</h2>
        <p class="payment-success">✅ Payment Successful</p>
        <p><strong>Gateway:</strong> <?php echo htmlspecialchars($_SESSION['quiz_payment_gateway'] ?? ''); ?></p>
        <p><strong>Transaction Ref:</strong> <?php echo htmlspecialchars($_SESSION['quiz_transaction_ref'] ?? ''); ?></p>
        <p><strong>Admin Approval:</strong> <?php echo htmlspecialchars(ucfirst($approvalStatus)); ?></p>
        <p><strong>Quiz Live:</strong> <?php echo $isQuizLive ? 'Yes' : 'No'; ?></p>
        <p id="approvalStatusMsg">
          <?php if (!$isApprovedByAdmin): ?>
            Your payment is complete. Waiting for admin to approve your payment…
          <?php elseif (!$isQuizLive): ?>
            Payment approved! Waiting for admin to start the quiz…
          <?php else: ?>
            Payment approved and quiz is live! Starting countdown…
          <?php endif; ?>
        </p>
      </div>
      <?php if ($startBlockMessage !== ''): ?>
        <div class="admin-msg admin-msg-tight"><?php echo htmlspecialchars($startBlockMessage); ?></div>
      <?php endif; ?>
      <div class="instr-btn-row">
        <div class="countdown-wrap" id="countdownDisplay">
          <?php if ($isApprovedByAdmin && $isQuizLive): ?>
            Starting quiz in <span id="cdSec">30</span> seconds…
          <?php else: ?>
            Waiting for admin approval &amp; quiz to go live…
          <?php endif; ?>
        </div>
      </div>
      <div class="instr-btn-row">
        <button class="btn-start payment-slip-btn" type="button" onclick="window.print();">📄 Download Payment Slip (PDF)</button>
      </div>
    <?php endif; ?>
  </div>

  <script src="pwa-init.js"></script>
  <script src="india-time.js"></script>
  <?php if ($paymentDone): ?>
    <script>
      (function () {
        var isApproved = <?php echo $isApprovedByAdmin ? 'true' : 'false'; ?>;
        var isLive = <?php echo $isQuizLive ? 'true' : 'false'; ?>;
        var statusEl = document.getElementById('approvalStatusMsg');
        var cdEl = document.getElementById('countdownDisplay');
        var pollTimer = null;
        var countdownTimer = null;

        function redirectToQuiz() {
          var form = document.createElement('form');
          form.method = 'POST';
          form.action = 'payment_confirm.php';
          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'start_quiz_after_payment';
          input.value = '1';
          form.appendChild(input);
          document.body.appendChild(form);
          form.submit();
        }

        function startCountdown() {
          if (countdownTimer) return;
          if (statusEl) statusEl.textContent = 'Payment approved and quiz is live! Starting countdown…';
          if (cdEl) cdEl.innerHTML = 'Starting quiz in <span id="cdSec">30</span> seconds…';
          var seconds = 30;
          countdownTimer = setInterval(function () {
            seconds--;
            var secEl = document.getElementById('cdSec');
            if (secEl) secEl.textContent = seconds;
            if (seconds <= 0) {
              clearInterval(countdownTimer);
              redirectToQuiz();
            }
          }, 1000);
        }

        function checkStatus() {
          fetch('check_payment_status.php')
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data.approved && data.quiz_live) {
                clearInterval(pollTimer);
                startCountdown();
              } else if (data.approved && statusEl) {
                statusEl.textContent = 'Payment approved! Waiting for admin to start the quiz…';
              }
            })
            .catch(function () {});
        }

        if (isApproved && isLive) {
          startCountdown();
        } else {
          checkStatus();
          pollTimer = setInterval(checkStatus, 5000);
        }
      })();
    </script>
  <?php endif; ?>
  <?php if (!$paymentDone): ?>
    <script>
      (function () {
        var payBtn = document.getElementById('payBtn');
        var msgEl = document.getElementById('paymentMsg');

        function showMsg(text, ok) {
          if (!msgEl) return;
          msgEl.textContent = text;
          msgEl.style.color = ok ? '#15803d' : '#b91c1c';
        }

        function redirectToQuiz() {
          var form = document.createElement('form');
          form.method = 'POST';
          form.action = 'payment_confirm.php';

          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'start_quiz_after_payment';
          input.value = '1';

          form.appendChild(input);
          document.body.appendChild(form);
          form.submit();
        }

        async function verifyPayment(payload) {
          var formData = new FormData();
          formData.append('payment_id', payload.payment_id || '');
          formData.append('order_id', payload.order_id || '');
          formData.append('signature', payload.signature || '');

          var verifyRes = await fetch('verify_payment.php', {
            method: 'POST',
            body: formData
          });
          var verifyJson = await verifyRes.json();

          if (!verifyRes.ok || !verifyJson.ok) {
            throw new Error(verifyJson.message || 'Payment verification failed.');
          }

          return verifyJson.message || 'Payment verified successfully.';
        }

        async function startPayment() {
          if (!payBtn) return;
          payBtn.disabled = true;
          showMsg('Creating test payment order…', true);

          try {
            var orderRes = await fetch('create_payment_order.php', { method: 'POST' });
            var orderJson = await orderRes.json();

            if (!orderRes.ok || !orderJson.ok) {
              throw new Error(orderJson.message || 'Unable to create order.');
            }

            showMsg('Verifying test payment…', true);
            var verifyMessage = await verifyPayment({
              payment_id: 'pay_test_' + Date.now(),
              order_id: orderJson.order_id,
              signature: 'test_signature'
            });
            showMsg(verifyMessage + ' Waiting for admin approval…', true);
            setTimeout(function() { window.location.href = 'payment_confirm.php'; }, 1500);
          } catch (err) {
            showMsg(err.message || 'Unable to start payment.', false);
            payBtn.disabled = false;
          }
        }

        if (payBtn) {
          payBtn.addEventListener('click', startPayment);
        }
      })();
    </script>
  <?php endif; ?>
</body>
</html>
